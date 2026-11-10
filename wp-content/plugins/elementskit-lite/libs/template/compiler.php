<?php
namespace ElementsKit_Lite\Libs\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Compiles a widget-builder HTML template into PHP render markup.
 *
 * The template is walked with a small HTML tokenizer so every {{ placeholder }}
 * is escaped for the context it actually sits in (text node, attribute value,
 * URL attribute, tag name, <script>, <style>, ...). Placeholders in contexts
 * that can't be made safe (event-handler attributes, attribute names, JS
 * comments, ...) are dropped and reported through get_warnings().
 */
class Compiler {

	/**
	 * Bump whenever compiled output changes in a security-relevant way; stored
	 * widget.php files older than this are recompiled on the next load.
	 */
	const VERSION = 3;

	const MARK = "\0";

	const DATA          = 'data';
	const TAG_NAME      = 'tag_name';
	const BEFORE_ATTR   = 'before_attr';
	const ATTR_NAME     = 'attr_name';
	const AFTER_ATTR    = 'after_attr';
	const BEFORE_VALUE  = 'before_value';
	const ATTR_VALUE    = 'attr_value';
	const COMMENT       = 'comment';
	const BOGUS_COMMENT = 'bogus_comment';
	const RAWTEXT       = 'rawtext';

	// Elements whose content the browser does not parse as markup.
	private static $rawtext_tags = array( 'script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes', 'noscript', 'plaintext' );

	private static $url_attrs = array( 'href', 'src', 'action', 'formaction', 'poster', 'cite', 'background', 'data', 'codebase', 'longdesc', 'usemap', 'manifest', 'ping', 'icon', 'profile', 'classid', 'archive', 'lowsrc', 'dynsrc', 'xlink:href' );

	private $transformer;
	private $prefix;
	private $placeholders;
	private $blocks;
	private $warnings;

	private $src;
	private $len;
	private $i;
	private $out;
	private $state;

	private $tag;
	private $tag_dynamic;
	private $closing;
	private $attr;
	private $quote;
	private $value_literal;
	private $raw_tag;

	private $js_mode;
	private $js_quote;
	private $js_escape;
	private $js_str_empty;
	private $js_depth;
	private $js_tpl_stack;
	private $js_skip;

	public function __construct() {
		$this->transformer = new Transformer();
	}

	public function compile( $markup, $prefix ) {
		$this->prefix       = $prefix;
		$this->placeholders = array();
		$this->blocks       = array();
		$this->warnings     = array();

		$markup = str_replace( self::MARK, '', (string) $markup );

		// PHP written into the template itself is never allowed.
		$markup = preg_replace( '/<\?[\s\S]*?\?>/', '', $markup );

		$markup = preg_replace_callback(
			'/\{\{([^{}]+)\}\}/',
			function ( $m ) {
				$this->placeholders[] = $m[1];
				return self::MARK . ( count( $this->placeholders ) - 1 ) . self::MARK;
			},
			$markup
		);

		$this->tokenize( $markup );

		// Anything left that PHP could read as an open tag is template text.
		$out = str_replace( '<?', '&lt;?', $this->out );

		return preg_replace_callback(
			'/\x00(\d+)\x00/',
			function ( $m ) {
				return $this->blocks[ (int) $m[1] ];
			},
			$out
		);
	}

	public function get_warnings() {
		return $this->warnings;
	}

	private function tokenize( $src ) {
		$this->src   = $src;
		$this->len   = strlen( $src );
		$this->i     = 0;
		$this->out   = '';
		$this->state = self::DATA;

		while ( $this->i < $this->len ) {
			$c = $src[ $this->i ];

			if ( self::MARK === $c ) {
				$end      = strpos( $src, self::MARK, $this->i + 1 );
				$index    = (int) substr( $src, $this->i + 1, $end - $this->i - 1 );
				$this->i  = $end + 1;
				$this->placeholder( $this->placeholders[ $index ] );
				continue;
			}

			$this->{ 'state_' . $this->state }( $c );
		}

		if ( self::ATTR_VALUE === $this->state && '' === $this->quote ) {
			$this->out .= '"';
		}
	}

	private function peek( $offset = 1 ) {
		$pos = $this->i + $offset;
		return $pos < $this->len ? $this->src[ $pos ] : '';
	}

	private static function is_space( $c ) {
		return '' !== $c && false !== strpos( " \t\n\r\f", $c );
	}

	private static function is_alpha( $c ) {
		return '' !== $c && ctype_alpha( $c );
	}

	private function emit( $c, $advance = 1 ) {
		$this->out .= $c;
		$this->i   += $advance;
	}

	/* ---------------------------------------------------------------------
	 * HTML states
	 * ------------------------------------------------------------------- */

	private function state_data( $c ) {
		if ( '<' === $c ) {
			$next = $this->peek();

			if ( '<!--' === substr( $this->src, $this->i, 4 ) ) {
				$this->emit( '<!--', 4 );
				$this->state = self::COMMENT;
				return;
			}

			if ( '!' === $next || '?' === $next ) {
				$this->emit( $c );
				$this->state = self::BOGUS_COMMENT;
				return;
			}

			if ( '/' === $next && ( self::is_alpha( $this->peek( 2 ) ) || self::MARK === $this->peek( 2 ) ) ) {
				$this->emit( '</', 2 );
				$this->start_tag( true );
				return;
			}

			if ( self::is_alpha( $next ) || self::MARK === $next ) {
				$this->emit( $c );
				$this->start_tag( false );
				return;
			}
		}

		$this->emit( $c );
	}

	private function start_tag( $closing ) {
		$this->tag         = '';
		$this->tag_dynamic = false;
		$this->closing     = $closing;
		$this->state       = self::TAG_NAME;
	}

	private function finish_tag() {
		$this->emit( '>' );

		if ( ! $this->closing && ! $this->tag_dynamic && in_array( $this->tag, self::$rawtext_tags, true ) ) {
			$this->state   = self::RAWTEXT;
			$this->raw_tag = $this->tag;

			if ( 'script' === $this->tag ) {
				$this->js_mode      = 'code';
				$this->js_escape    = false;
				$this->js_depth     = 0;
				$this->js_tpl_stack = array();
				$this->js_skip      = 0;
			}
			return;
		}

		$this->state = self::DATA;
	}

	private function state_tag_name( $c ) {
		if ( self::is_space( $c ) || '/' === $c ) {
			$this->emit( $c );
			$this->state = self::BEFORE_ATTR;
		} elseif ( '>' === $c ) {
			$this->finish_tag();
		} else {
			$this->emit( $c );
			$this->tag .= strtolower( $c );
		}
	}

	private function state_before_attr( $c ) {
		if ( self::is_space( $c ) || '/' === $c ) {
			$this->emit( $c );
		} elseif ( '>' === $c ) {
			$this->finish_tag();
		} else {
			$this->attr  = strtolower( $c );
			$this->state = self::ATTR_NAME;
			$this->emit( $c );
		}
	}

	private function state_attr_name( $c ) {
		if ( self::is_space( $c ) ) {
			$this->emit( $c );
			$this->state = self::AFTER_ATTR;
		} elseif ( '/' === $c ) {
			$this->emit( $c );
			$this->state = self::BEFORE_ATTR;
		} elseif ( '=' === $c ) {
			$this->emit( $c );
			$this->state = self::BEFORE_VALUE;
		} elseif ( '>' === $c ) {
			$this->finish_tag();
		} else {
			$this->attr .= strtolower( $c );
			$this->emit( $c );
		}
	}

	private function state_after_attr( $c ) {
		if ( self::is_space( $c ) ) {
			$this->emit( $c );
		} elseif ( '/' === $c ) {
			$this->emit( $c );
			$this->state = self::BEFORE_ATTR;
		} elseif ( '=' === $c ) {
			$this->emit( $c );
			$this->state = self::BEFORE_VALUE;
		} elseif ( '>' === $c ) {
			$this->finish_tag();
		} else {
			$this->attr  = strtolower( $c );
			$this->state = self::ATTR_NAME;
			$this->emit( $c );
		}
	}

	private function state_before_value( $c ) {
		if ( self::is_space( $c ) ) {
			$this->emit( $c );
		} elseif ( '"' === $c || "'" === $c ) {
			$this->emit( $c );
			$this->start_value( $c );
		} elseif ( '>' === $c ) {
			$this->finish_tag();
		} else {
			// Unquoted value: always write it out double-quoted.
			$this->out .= '"';
			$this->start_value( '' );
		}
	}

	private function start_value( $quote ) {
		$this->quote         = $quote;
		$this->value_literal = '';
		$this->state         = self::ATTR_VALUE;
	}

	private function state_attr_value( $c ) {
		if ( '' !== $this->quote ) {
			if ( $c === $this->quote ) {
				$this->emit( $c );
				$this->state = self::BEFORE_ATTR;
				return;
			}
		} elseif ( self::is_space( $c ) ) {
			$this->emit( '"' . $c );
			$this->state = self::BEFORE_ATTR;
			return;
		} elseif ( '>' === $c ) {
			$this->out .= '"';
			$this->finish_tag();
			return;
		} elseif ( '"' === $c ) {
			$this->value_literal .= $c;
			$this->out           .= '&quot;';
			$this->i++;
			return;
		}

		$this->value_literal .= $c;
		$this->emit( $c );
	}

	private function state_comment( $c ) {
		if ( '-->' === substr( $this->src, $this->i, 3 ) ) {
			$this->emit( '-->', 3 );
			$this->state = self::DATA;
			return;
		}

		$this->emit( $c );
	}

	private function state_bogus_comment( $c ) {
		if ( '>' === $c ) {
			$this->state = self::DATA;
		}

		$this->emit( $c );
	}

	private function state_rawtext( $c ) {
		$tag_len = strlen( $this->raw_tag );

		if (
			'<' === $c && '/' === $this->peek()
			&& strtolower( substr( $this->src, $this->i + 2, $tag_len ) ) === $this->raw_tag
			&& ( self::is_space( $this->peek( $tag_len + 2 ) ) || in_array( $this->peek( $tag_len + 2 ), array( '/', '>', '' ), true ) )
		) {
			$this->emit( '</', 2 );
			$this->start_tag( true );
			return;
		}

		if ( 'script' === $this->raw_tag ) {
			$this->js_step( $c );
		}

		$this->emit( $c );
	}

	/* ---------------------------------------------------------------------
	 * Minimal JS lexer: only tracks strings, template literals and comments,
	 * which is all that's needed to pick the placeholder escaping.
	 * ------------------------------------------------------------------- */

	private function js_step( $c ) {
		if ( $this->js_skip > 0 ) {
			$this->js_skip--;
			return;
		}

		$next = $this->peek();

		switch ( $this->js_mode ) {
			case 'code':
				if ( '"' === $c || "'" === $c || '`' === $c ) {
					$this->js_mode      = 'string';
					$this->js_quote     = $c;
					$this->js_str_empty = true;
				} elseif ( '/' === $c && '/' === $next ) {
					$this->js_mode = 'line_comment';
					$this->js_skip = 1;
				} elseif ( '/' === $c && '*' === $next ) {
					$this->js_mode = 'block_comment';
					$this->js_skip = 1;
				} elseif ( '{' === $c ) {
					$this->js_depth++;
				} elseif ( '}' === $c ) {
					if ( ! empty( $this->js_tpl_stack ) && end( $this->js_tpl_stack ) === $this->js_depth ) {
						array_pop( $this->js_tpl_stack );
						$this->js_mode      = 'string';
						$this->js_quote     = '`';
						$this->js_str_empty = false;
					} else {
						$this->js_depth--;
					}
				}
				break;

			case 'string':
				if ( $this->js_escape ) {
					$this->js_escape = false;
				} elseif ( '\\' === $c ) {
					$this->js_escape = true;
				} elseif ( $c === $this->js_quote ) {
					$this->js_mode = 'code';
				} elseif ( '`' === $this->js_quote && '$' === $c && '{' === $next ) {
					$this->js_tpl_stack[] = $this->js_depth;
					$this->js_mode        = 'code';
					$this->js_skip        = 1;
				}
				$this->js_str_empty = false;
				break;

			case 'line_comment':
				if ( "\n" === $c || "\r" === $c ) {
					$this->js_mode = 'code';
				}
				break;

			case 'block_comment':
				if ( '*' === $c && '/' === $next ) {
					$this->js_mode = 'code';
					$this->js_skip = 1;
				}
				break;
		}
	}

	private function js_context() {
		if ( 'code' === $this->js_mode ) {
			return Transformer::CONTEXT_JS;
		}

		if ( 'string' !== $this->js_mode ) {
			return null; // Inside a JS comment: a newline or */ would escape it.
		}

		$quote = $this->js_quote;

		// "{{ x }}" as a whole string: replace the quotes with a JSON value.
		if (
			$this->js_str_empty && $quote === $this->peek( 0 )
			&& substr( $this->out, -1 ) === $quote
		) {
			$this->out     = substr( $this->out, 0, -1 );
			$this->js_mode = 'code';
			$this->i++;
			return Transformer::CONTEXT_JS_QUOTED;
		}

		$this->js_str_empty = false;

		return Transformer::CONTEXT_JS_STRING;
	}

	/* ---------------------------------------------------------------------
	 * Placeholders
	 * ------------------------------------------------------------------- */

	private function placeholder( $expr ) {
		$context = null;
		$reason  = '';

		switch ( $this->state ) {
			case self::DATA:
				$context = Transformer::CONTEXT_TEXT;
				break;

			case self::TAG_NAME:
				// Only a whole tag name, e.g. <{{ tag }} class="...">.
				$next = $this->peek( 0 );
				if ( '' === $this->tag && ! $this->tag_dynamic && ( self::is_space( $next ) || in_array( $next, array( '/', '>', '' ), true ) ) ) {
					$context           = Transformer::CONTEXT_TAG;
					$this->tag_dynamic = true;
				} else {
					$reason = 'part of a tag name';
				}
				break;

			case self::BEFORE_ATTR:
			case self::ATTR_NAME:
			case self::AFTER_ATTR:
				$reason = 'an attribute name';
				break;

			case self::BEFORE_VALUE:
				$this->out .= '"';
				$this->start_value( '' );
				// Fall through: now inside an (unquoted) attribute value.

			case self::ATTR_VALUE:
				list( $context, $reason ) = $this->attr_value_context();
				break;

			case self::COMMENT:
			case self::BOGUS_COMMENT:
				$context = Transformer::CONTEXT_HTML;
				break;

			case self::RAWTEXT:
				if ( 'script' === $this->raw_tag ) {
					$context = $this->js_context();
					$reason  = 'a JavaScript comment';
				} elseif ( 'style' === $this->raw_tag ) {
					$context = Transformer::CONTEXT_CSS;
				} else {
					$context = Transformer::CONTEXT_HTML;
				}
				break;
		}

		$php = null === $context ? '' : $this->transformer->render( $expr, $this->prefix, $context );

		if ( '' === $php ) {
			$this->warnings[] = sprintf(
				/* translators: 1: template placeholder, 2: where it was used */
				esc_html__( 'Placeholder {{%1$s}} was removed: it is not allowed in %2$s.', 'elementskit-lite' ),
				esc_html( trim( $expr ) ),
				esc_html( $reason ? $reason : 'this position' )
			);
			return;
		}

		$this->blocks[] = $php;
		$this->out     .= self::MARK . ( count( $this->blocks ) - 1 ) . self::MARK;
	}

	private function attr_value_context() {
		$attr = $this->attr;

		if ( 0 === strpos( $attr, 'on' ) || 'srcdoc' === $attr ) {
			return array( null, 'an event-handler or srcdoc attribute' );
		}

		// SVG animation can rewrite href to a javascript: URL.
		if ( in_array( $this->tag, array( 'animate', 'set' ), true ) && in_array( $attr, array( 'attributename', 'values', 'to', 'from', 'by' ), true ) ) {
			return array( null, 'an SVG animation attribute' );
		}

		if ( in_array( $attr, self::$url_attrs, true ) ) {
			$literal = ltrim( html_entity_decode( $this->value_literal, ENT_QUOTES | ENT_HTML5, 'UTF-8' ), " \t\n\r\0\x0B\f" );

			// Until the literal text fixes the scheme/path, the value could become javascript:.
			if ( false === strpbrk( $literal, ':/?#' ) ) {
				return array( Transformer::CONTEXT_URL, '' );
			}
		}

		return array( Transformer::CONTEXT_ATTR, '' );
	}
}
