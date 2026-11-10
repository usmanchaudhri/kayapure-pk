<?php
/**
 * Storefront speed: external Elementor CSS, WebP images, anonymous CDN caching.
 */

/**
 * Elementor otherwise prints post CSS inside the HTML document.
 */
function kaya_force_external_elementor_css() {
	return 'external';
}
add_filter( 'pre_option_elementor_css_print_method', 'kaya_force_external_elementor_css' );

add_action(
	'init',
	static function () {
		if ( get_option( 'kaya_elementor_external_css' ) || ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		global $wpdb;
		$stored = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
				'elementor_css_print_method'
			)
		);
		if ( 'external' !== $stored ) {
			remove_all_filters( 'pre_option_elementor_css_print_method' );
			update_option( 'elementor_css_print_method', 'external' );
			add_filter( 'pre_option_elementor_css_print_method', 'kaya_force_external_elementor_css' );
		}

		update_option( 'kaya_elementor_external_css', 1, false );
	},
	0
);

/**
 * Path written once the WebP files in the manifest exist.
 */
function kaya_webp_ready_flag() {
	return WP_CONTENT_DIR . '/uploads/.kaya-webp-ready';
}

/**
 * Relative upload paths that should have a WebP sibling.
 *
 * @return string[]
 */
function kaya_webp_sources() {
	$file = get_stylesheet_directory() . '/inc/webp-sources.php';
	if ( ! is_readable( $file ) ) {
		return array();
	}

	$sources = require $file;
	return is_array( $sources ) ? $sources : array();
}

/**
 * Write a WebP next to a PNG or JPEG, capped at the size the layout can show.
 */
function kaya_write_webp( $source, $destination ) {
	if ( ! is_file( $source ) ) {
		return false;
	}
	if ( is_file( $destination ) && filesize( $destination ) > 0 ) {
		return true;
	}
	if ( ! function_exists( 'imagewebp' ) || filesize( $source ) > 8 * 1024 * 1024 ) {
		return false;
	}

	$info = @getimagesize( $source );
	if ( ! is_array( $info ) ) {
		return false;
	}

	$width  = (int) $info[0];
	$height = (int) $info[1];
	$type   = (int) $info[2];
	if ( $width < 1 || $height < 1 || $width > 7000 || $height > 7000 ) {
		return false;
	}

	$target_width  = $width;
	$target_height = $height;
	$is_resized    = (bool) preg_match( '/-\d+x\d+\.(?:png|jpe?g)$/i', $source );
	$max_width     = 1600;
	if ( ! $is_resized && $width > $max_width ) {
		$target_width  = $max_width;
		$target_height = max( 1, (int) round( $height * ( $max_width / $width ) ) );
	}

	if ( IMAGETYPE_JPEG === $type ) {
		$image = @imagecreatefromjpeg( $source );
	} elseif ( IMAGETYPE_PNG === $type ) {
		$image = @imagecreatefrompng( $source );
	} else {
		return false;
	}
	if ( ! $image ) {
		return false;
	}

	if ( function_exists( 'imagepalettetotruecolor' ) ) {
		imagepalettetotruecolor( $image );
	}
	imagealphablending( $image, false );
	imagesavealpha( $image, true );

	if ( $target_width !== $width || $target_height !== $height ) {
		$scaled = imagecreatetruecolor( $target_width, $target_height );
		imagealphablending( $scaled, false );
		imagesavealpha( $scaled, true );
		$transparent = imagecolorallocatealpha( $scaled, 0, 0, 0, 127 );
		imagefilledrectangle( $scaled, 0, 0, $target_width, $target_height, $transparent );
		imagecopyresampled( $scaled, $image, 0, 0, 0, 0, $target_width, $target_height, $width, $height );
		imagedestroy( $image );
		$image = $scaled;
	}

	$written = imagewebp( $image, $destination, 78 );
	imagedestroy( $image );
	if ( $written ) {
		@chmod( $destination, 0644 );
	}

	return (bool) $written && is_file( $destination );
}

/**
 * Convert the next batch of manifest images. Returns how many are still missing.
 */
function kaya_convert_webp_batch( $limit ) {
	$uploads  = WP_CONTENT_DIR . '/uploads/';
	$remaining = 0;
	$converted = 0;

	foreach ( kaya_webp_sources() as $relative ) {
		$relative = ltrim( str_replace( '\\', '/', (string) $relative ), '/' );
		if ( '' === $relative || false !== strpos( $relative, '..' ) ) {
			continue;
		}
		if ( ! preg_match( '/\.(?:png|jpe?g)$/i', $relative ) ) {
			continue;
		}

		$source = $uploads . $relative;
		$webp   = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $source );
		$skip   = $webp . '.skip';
		if ( ! is_file( $source ) || is_file( $webp ) || is_file( $skip ) ) {
			continue;
		}

		$remaining++;
		if ( $converted >= $limit ) {
			continue;
		}
		if ( kaya_write_webp( $source, $webp ) ) {
			$converted++;
			$remaining--;
		} else {
			file_put_contents( $skip, '' );
			$remaining--;
		}
	}

	return $remaining;
}

/**
 * Create missing WebP files for one markup string, within a short time budget.
 */
function kaya_rewrite_upload_images_to_webp( $markup ) {
	if ( ! is_string( $markup ) || '' === $markup || false === stripos( $markup, '/wp-content/uploads/' ) ) {
		return $markup;
	}

	$deadline = microtime( true ) + 1.5;
	$cache    = array();

	$replace = static function ( $path ) use ( &$cache, $deadline ) {
		$path = rawurldecode( $path );
		if ( isset( $cache[ $path ] ) ) {
			return $cache[ $path ];
		}

		$relative = preg_replace( '#^.*/wp-content/uploads/#', '', $path );
		$relative = strtok( (string) $relative, '?' );
		$source   = WP_CONTENT_DIR . '/uploads/' . $relative;
		$webp     = preg_replace( '/\.(png|jpe?g)$/i', '.webp', $source );
		$ready    = is_file( $webp );
		if ( ! $ready && microtime( true ) < $deadline ) {
			$ready = kaya_write_webp( $source, $webp );
		}

		$cache[ $path ] = $ready;
		return $ready;
	};

	$markup = preg_replace_callback(
		'#((?:https?:)?//[^"\'\s)]+/wp-content/uploads/[^"\'\s)]+\.(?:png|jpe?g))#i',
		static function ( $match ) use ( $replace ) {
			$url  = $match[1];
			$path = (string) parse_url( $url, PHP_URL_PATH );
			if ( ! $replace( $path ) ) {
				return $url;
			}
			return preg_replace( '/\.(png|jpe?g)(?=$|\?)/i', '.webp', $url );
		},
		$markup
	);

	return preg_replace_callback(
		'#((?:https?:)?\\\\?/\\\\?/[^"\'\s]+?\\\\?/wp-content\\\\?/uploads\\\\?/[^"\'\s]+?\.(?:png|jpe?g))#i',
		static function ( $match ) use ( $replace ) {
			$url      = $match[1];
			$plain    = str_replace( '\\/', '/', $url );
			$path     = (string) parse_url( $plain, PHP_URL_PATH );
			if ( ! $replace( $path ) ) {
				return $url;
			}
			return preg_replace( '/\.(png|jpe?g)$/i', '.webp', $url );
		},
		$markup
	);
}

add_action(
	'init',
	static function () {
		if ( is_file( kaya_webp_ready_flag() ) || wp_doing_ajax() ) {
			return;
		}
		if ( ! is_readable( get_stylesheet_directory() . '/inc/webp-sources.php' ) ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( 0 === kaya_convert_webp_batch( 20 ) ) {
			file_put_contents( kaya_webp_ready_flag(), gmdate( 'c' ) );
			if ( function_exists( 'rocket_clean_domain' ) ) {
				rocket_clean_domain();
			}
		}
	},
	1
);

add_action(
	'template_redirect',
	static function () {
		if ( is_admin() || wp_doing_ajax() || is_feed() ) {
			return;
		}
		ob_start( 'kaya_rewrite_upload_images_to_webp' );
	},
	0
);

add_action(
	'shutdown',
	static function () {
		if ( ! function_exists( 'kaya_request_is_cdn_cacheable' ) || ! kaya_request_is_cdn_cacheable() || headers_sent() ) {
			return;
		}

		$kept = array();
		$removed = false;
		foreach ( headers_list() as $header ) {
			if ( preg_match( '/^Set-Cookie:\s*(_fbp|_fbc)=/i', $header ) ) {
				$removed = true;
				continue;
			}
			if ( 0 === stripos( $header, 'Set-Cookie:' ) ) {
				$kept[] = $header;
			}
		}
		if ( ! $removed ) {
			return;
		}

		header_remove( 'Set-Cookie' );
		foreach ( $kept as $header ) {
			header( $header, false );
		}
	},
	0
);

add_action(
	'shutdown',
	static function () {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}
		$dir = WP_CONTENT_DIR . '/uploads/elementor/css';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( glob( $dir . '/*.css' ) ?: array() as $file ) {
			$css = file_get_contents( $file );
			if ( ! is_string( $css ) || ! preg_match( '/\.(?:png|jpe?g)/i', $css ) ) {
				continue;
			}
			$updated = kaya_rewrite_upload_images_to_webp( $css );
			if ( is_string( $updated ) && $updated !== $css ) {
				file_put_contents( $file, $updated );
			}
		}
	},
	0
);
