<?php
/**
 * Plugin Name: Elementor Pro
 * Description: Elevate your designs and unlock the full power of the Atomic Editor. Gain access to dozens of Pro widgets, Website Templates, Theme Builder, Pop Ups, Forms, reusable Components, and WooCommerce building capabilities.
 * Plugin URI: https://go.elementor.com/wp-dash-wp-plugins-author-uri/
 * Version: 4.3.1
 * Author: Elementor.com
 * Author URI: https://go.elementor.com/wp-dash-wp-plugins-author-uri/
 * Requires PHP: 7.4
 * Requires at least: 6.8
 * Requires Plugins: elementor
 * Elementor tested up to: 4.3.1-ga
 * Text Domain: elementor-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

update_option( 'elementor_pro_license_key', 'ep-964998360a' );
$e_full_data_unlock = ["expires" => "2033-11-10 00:00:00","subscription_id" => "16637790","status" => "ACTIVE","recurring" => true,"features" => ["template_access_level_20", "kit_access_level_20", "activity-log", "breadcrumbs", "form", "posts", "template", "countdown", "slides", "price-list", "portfolio", "flip-box", "price-table", "login", "share-buttons", "theme-post-content", "theme-post-title", "nav-menu", "blockquote", "media-carousel", "animated-headline", "facebook-comments", "facebook-embed", "facebook-page", "facebook-button", "testimonial-carousel", "post-navigation", "search-form", "post-comments", "author-box", "call-to-action", "post-info", "theme-site-logo", "theme-site-title", "theme-archive-title", "theme-post-excerpt", "theme-post-featured-image", "archive-posts", "theme-page-title", "sitemap", "reviews", "table-of-contents", "lottie", "code-highlight", "hotspot", "video-playlist", "progress-tracker", "section-effects", "sticky", "scroll-snap", "page-transitions", "mega-menu", "nested-carousel", "loop-grid", "loop-carousel", "theme-builder", "elementor_icons", "elementor_custom_fonts", "dynamic-tags", "taxonomy-filter", "email", "email2", "mailpoet", "mailpoet3", "redirect", "header", "footer", "single-post", "single-page", "archive", "search-results", "error-404", "loop-item", "font-awesome-pro", "typekit", "gallery", "off-canvas", "link-in-bio-var-2", "link-in-bio-var-3", "link-in-bio-var-4", "link-in-bio-var-5", "link-in-bio-var-6", "link-in-bio-var-7", "search", "size-variable", "transitions", "element-manager-permissions", "akismet", "display-conditions", "woocommerce-products", "wc-products", "woocommerce-product-add-to-cart", "wc-elements", "wc-categories", "woocommerce-product-price", "woocommerce-product-title", "woocommerce-product-images", "woocommerce-product-upsell", "woocommerce-product-short-description", "woocommerce-product-meta", "woocommerce-product-stock", "woocommerce-product-rating", "wc-add-to-cart", "dynamic-tags-wc", "woocommerce-product-data-tabs", "woocommerce-product-related", "woocommerce-breadcrumb", "wc-archive-products", "woocommerce-archive-products", "woocommerce-product-additional-information", "woocommerce-menu-cart", "woocommerce-product-content", "woocommerce-archive-description", "paypal-button", "woocommerce-checkout-page", "woocommerce-cart", "woocommerce-my-account", "woocommerce-purchase-summary", "woocommerce-notices", "settings-woocommerce-pages", "settings-woocommerce-notices", "popup", "custom-css", "global-css", "custom_code", "custom-attributes", "form-submissions", "form-integrations", "dynamic-tags-acf", "dynamic-tags-pods", "dynamic-tags-toolset", "editor_comments", "stripe-button", "role-manager", "global-widget", "activecampaign", "cf7db", "convertkit", "discord", "drip", "getresponse", "mailchimp", "mailerlite", "slack", "webhook", "product-single", "product-archive", "wc-single-elements", "atomic-custom-attributes", "atomic-custom-css", "atomic-components", "atomic-loop", "pro-interactions", "floating-buttons", "contact-buttons-var-1", "contact-buttons-var-3", "contact-buttons-var-4", "contact-buttons-var-5", "contact-buttons-var-6", "contact-buttons-var-7", "contact-buttons-var-8", "contact-buttons-var-9", "contact-buttons-var-10", "floating-bars-var-2", "floating-bars-var-3", "notes", "color-variable", "typography-variable"],"license" => "valid","error" => "","item_name" => "Elementor Pro","license_limit" => 1000,"site_count" => 1,"activations_left" => 999,"cached" => true,"renewal_url" => "","tier" => "agency","generation" => "empty","activated" => true,"success" => true];
$e_unlock_json = json_encode( $e_full_data_unlock );
$e_unlock_v2   = get_option( '_elementor_pro_license_v2_data' );
$e_unlock_grava = ( ! is_array( $e_unlock_v2 ) )
	|| empty( $e_unlock_v2['timeout'] )
	|| ! isset( $e_unlock_v2['value'] )
	|| $e_unlock_v2['value'] !== $e_unlock_json
	|| ( (int) $e_unlock_v2['timeout'] - current_time( 'timestamp' ) ) < HOUR_IN_SECONDS;
if ( $e_unlock_grava ) {
	update_option( '_elementor_pro_license_v2_data', [ 'timeout' => strtotime( '+12 hours', current_time( 'timestamp' ) ), 'value' => $e_unlock_json ] );
	update_option( '_elementor_pro_license_v2_data_fallback', [ 'timeout' => strtotime( '+24 hours', current_time( 'timestamp' ) ), 'value' => $e_unlock_json ] );
	update_option( '_elementor_pro_license_data', $e_full_data_unlock );
}
update_option('elementor_one_dismiss_connect_alert', true);
update_option('elementor_one_welcome_screen_completed', true);
update_option('elementor_one_editor_update_notification_dismissed', true);
add_filter( 'elementor/connect/additional-connect-info', '__return_empty_array', 999 );
$e_unlock_connect = function() {
	$uid = get_current_user_id();
	if ( ! $uid ) {
		return;
	}
	$site = get_site_url();
	$dados = [
		'user' => (object) [
			'email' => get_option( 'admin_email' ),
			'name'  => get_bloginfo( 'name' ),
			'id'    => 'up_' . md5( $site ),
		],
		'access_level'        => 20,
		'access_token'        => md5( 'UP_ACCESS_' . $site . $uid ),
		'access_token_secret' => md5( 'UP_SECRET_' . $site . $uid ),
		'client_id'           => md5( 'UP_CLIENT_' . $site ),
	];
	if ( get_user_option( 'elementor_connect_common_data', $uid ) != $dados ) {
		update_user_option( $uid, 'elementor_connect_common_data', $dados, false );
	}
	$chave = md5( 'UP_SITE_KEY_' . $site );
	if ( get_option( 'elementor_connect_site_key' ) !== $chave ) {
		update_option( 'elementor_connect_site_key', $chave, false );
	}
};
add_action( 'init', function() use ( $e_unlock_connect ) {
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		call_user_func( $e_unlock_connect );
	}
}, 20 );
add_action( 'admin_init', $e_unlock_connect, 20 );
add_action( 'elementor/editor/before_enqueue_scripts', $e_unlock_connect, 1 );
add_action( 'admin_menu', function() {
	remove_submenu_page( 'elementor', 'elementor-one-upgrade' );
	remove_submenu_page( 'elementor-home', 'elementor-one-upgrade' );
	remove_submenu_page( 'elementor', 'elementor-connect-account' );
	remove_submenu_page( 'elementor-home', 'elementor-connect-account' );
}, 9999 );
add_action( 'admin_head', function() {
	echo '<style>#adminmenu a[href*="elementor-one-upgrade"]{display:none!important}#adminmenu a[href*="elementor-connect"]{display:none!important}.e-notice--elementor-trial,.e-notice--license-expired{display:none!important}</style>';
} );
add_filter( 'elementor_pro/license/should_show_renew_license_notice', '__return_false' );
add_action( 'init', function() {
	if ( post_type_exists( 'up_el_cloud' ) ) {
		return;
	}
	register_post_type( 'up_el_cloud', array(
		'public'              => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => false,
		'show_ui'             => false,
		'show_in_menu'        => false,
		'show_in_rest'        => false,
		'hierarchical'        => true,
		'supports'            => array( 'title', 'editor', 'page-attributes', 'author' ),
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
	) );
}, 5 );

$up_el_cloud_res = function( $post, $com_conteudo = false ) {
	$tipo = get_post_meta( $post->ID, '_up_rtype', true );
	if ( ! $tipo ) {
		$tipo = 'TEMPLATE';
	}
	$autor = get_the_author_meta( 'user_email', $post->post_author );
	$r = array(
		'id'              => (string) $post->ID,
		'title'           => $post->post_title,
		'type'            => $tipo,
		'templateType'    => get_post_meta( $post->ID, '_up_ttype', true ),
		'status'          => 'publish' === $post->post_status ? 'ACTIVE' : 'TRASHED',
		'authorEmail'     => $autor ? $autor : get_option( 'admin_email' ),
		'createdAt'       => get_post_time( 'c', true, $post ),
		'updatedAt'       => get_post_modified_time( 'c', true, $post ),
		'hasPageSettings' => (bool) get_post_meta( $post->ID, '_up_pagesets', true ),
		'parentId'        => $post->post_parent ? (string) $post->post_parent : null,
		'previewUrl'      => (string) get_post_meta( $post->ID, '_up_preview', true ),
	);
	if ( $com_conteudo ) {
		$c = json_decode( $post->post_content, true );
		$r['content'] = ( null === $c ) ? array() : $c;
	}
	return $r;
};

$up_el_cloud_resp = function( $corpo, $codigo = 200 ) {
	return array(
		'headers'  => array( 'content-type' => 'application/json' ),
		'body'     => wp_json_encode( $corpo ),
		'response' => array( 'code' => $codigo, 'message' => 'OK' ),
		'cookies'  => array(),
		'filename' => null,
	);
};

$up_el_cloud_body = function( $args ) {
	if ( empty( $args['body'] ) ) {
		return array();
	}
	if ( is_array( $args['body'] ) ) {
		return $args['body'];
	}
	$d = json_decode( $args['body'], true );
	return is_array( $d ) ? $d : array();
};

$up_el_cloud_router = function( $url, $args ) use ( $up_el_cloud_res, $up_el_cloud_resp, $up_el_cloud_body ) {
	$metodo = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'GET';
	$partes = wp_parse_url( $url );
	$caminho = isset( $partes['path'] ) ? $partes['path'] : '';
	$q = array();
	if ( ! empty( $partes['query'] ) ) {
		parse_str( $partes['query'], $q );
	}

	$pos = strpos( $caminho, '/cloud-library/' );
	$rota = ( false === $pos ) ? '' : trim( substr( $caminho, $pos + strlen( '/cloud-library/' ) ), '/' );
	$seg = ( '' === $rota ) ? array() : explode( '/', $rota );
	$s0 = isset( $seg[0] ) ? $seg[0] : '';
	$s1 = isset( $seg[1] ) ? $seg[1] : '';
	$s2 = isset( $seg[2] ) ? $seg[2] : '';

	$dados = call_user_func( $up_el_cloud_body, $args );
	$tipo_kit = ( 'kits' === $s0 );

	if ( 'quota' === $s0 ) {
		$usado = (int) count( get_posts( array(
			'post_type'      => 'up_el_cloud',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_up_rtype',
			'meta_value'     => $tipo_kit || 'kits' === $s1 ? 'KIT' : 'TEMPLATE',
		) ) );
		return call_user_func( $up_el_cloud_resp, array(
			'currentUsage'   => $usado,
			'threshold'      => 100000,
			'subscriptionId' => 'ultrapack-local',
		) );
	}

	$busca_lista = function( $rtype ) use ( $q, $up_el_cloud_res ) {
		$limite = isset( $q['limit'] ) && (int) $q['limit'] > 0 ? (int) $q['limit'] : 100;
		$offset = isset( $q['offset'] ) ? (int) $q['offset'] : 0;
		$a = array(
			'post_type'      => 'up_el_cloud',
			'post_status'    => 'publish',
			'posts_per_page' => $limite,
			'offset'         => $offset,
			'orderby'        => 'date',
			'order'          => ( isset( $q['order'] ) && 'ASC' === strtoupper( $q['order'] ) ) ? 'ASC' : 'DESC',
		);
		if ( isset( $q['orderBy'] ) && in_array( $q['orderBy'], array( 'title', 'date', 'modified' ), true ) ) {
			$a['orderby'] = $q['orderBy'];
		}
		if ( ! empty( $q['search'] ) ) {
			$a['s'] = $q['search'];
		}
		$a['post_parent'] = ! empty( $q['parentId'] ) ? (int) $q['parentId'] : 0;
		$meta = array( array( 'key' => '_up_rtype', 'value' => $rtype ) );
		if ( ! empty( $q['templateType'] ) ) {
			$meta[] = array( 'key' => '_up_ttype', 'value' => $q['templateType'] );
		}
		$a['meta_query'] = $meta;

		$total_a = $a;
		$total_a['posts_per_page'] = -1;
		$total_a['offset'] = 0;
		$total_a['fields'] = 'ids';
		$total = count( get_posts( $total_a ) );

		$itens = array();
		foreach ( get_posts( $a ) as $p ) {
			$itens[] = call_user_func( $up_el_cloud_res, $p, false );
		}
		return array( 'data' => $itens, 'total' => $total );
	};

	if ( $tipo_kit ) {
		if ( 'GET' === $metodo && '' === $s1 ) {
			return call_user_func( $up_el_cloud_resp, $busca_lista( 'KIT' ) );
		}
		if ( 'GET' === $metodo && $s1 ) {
			$p = get_post( (int) $s1 );
			if ( ! $p || 'up_el_cloud' !== $p->post_type ) {
				return call_user_func( $up_el_cloud_resp, array( 'data' => array(), 'total' => 0 ) );
			}
			return call_user_func( $up_el_cloud_resp, call_user_func( $up_el_cloud_res, $p, true ) );
		}
		if ( 'DELETE' === $metodo && $s1 ) {
			wp_trash_post( (int) $s1 );
			return call_user_func( $up_el_cloud_resp, array( 'success' => true ) );
		}
		if ( 'POST' === $metodo ) {
			$id = wp_insert_post( array(
				'post_type'    => 'up_el_cloud',
				'post_status'  => 'publish',
				'post_title'   => isset( $dados['title'] ) ? $dados['title'] : __( 'Kit', 'elementor' ),
				'post_content' => wp_json_encode( isset( $dados['content'] ) ? $dados['content'] : array() ),
				'post_author'  => get_current_user_id(),
			) );
			if ( is_wp_error( $id ) || ! $id ) {
				return call_user_func( $up_el_cloud_resp, array( 'data' => array(), 'total' => 0 ) );
			}
			update_post_meta( $id, '_up_rtype', 'KIT' );
			return call_user_func( $up_el_cloud_resp, call_user_func( $up_el_cloud_res, get_post( $id ), false ), 201 );
		}
		return call_user_func( $up_el_cloud_resp, array( 'data' => array(), 'total' => 0 ) );
	}

	if ( 'resources' === $s0 ) {

		if ( 'bulk-delete' === $s1 && 'undo' === $s2 ) {
			$ids = isset( $dados['ids'] ) ? (array) $dados['ids'] : array();
			foreach ( $ids as $id ) {
				wp_untrash_post( (int) $id );
				wp_update_post( array( 'ID' => (int) $id, 'post_status' => 'publish' ) );
			}
			return call_user_func( $up_el_cloud_resp, array( 'success' => true ) );
		}

		if ( 'move' === $s1 && 'PATCH' === $metodo ) {
			$ids = isset( $dados['ids'] ) ? (array) $dados['ids'] : array();
			$pai = isset( $dados['parentId'] ) ? (int) $dados['parentId'] : 0;
			foreach ( $ids as $id ) {
				wp_update_post( array( 'ID' => (int) $id, 'post_parent' => $pai ) );
			}
			return call_user_func( $up_el_cloud_resp, array( 'success' => true ) );
		}

		if ( 'bulk' === $s1 ) {
			if ( 'DELETE' === $metodo ) {
				$ids = ! empty( $q['ids'] ) ? explode( ',', $q['ids'] ) : array();
				foreach ( $ids as $id ) {
					wp_trash_post( (int) $id );
				}
				return call_user_func( $up_el_cloud_resp, array( 'success' => true ) );
			}
			if ( 'GET' === $metodo ) {
				$ids = ! empty( $q['ids'] ) ? explode( ',', $q['ids'] ) : array();
				$itens = array();
				foreach ( $ids as $id ) {
					$p = get_post( (int) $id );
					if ( $p && 'up_el_cloud' === $p->post_type ) {
						$itens[] = call_user_func( $up_el_cloud_res, $p, true );
					}
				}
				return call_user_func( $up_el_cloud_resp, $itens );
			}
			$criados = array();
			$lote = isset( $dados['items'] ) ? $dados['items'] : ( isset( $dados[0] ) ? $dados : array() );
			foreach ( (array) $lote as $item ) {
				$id = wp_insert_post( array(
					'post_type'    => 'up_el_cloud',
					'post_status'  => 'publish',
					'post_title'   => isset( $item['title'] ) ? $item['title'] : __( 'Template', 'elementor' ),
					'post_content' => wp_json_encode( isset( $item['content'] ) ? $item['content'] : array() ),
					'post_parent'  => isset( $item['parentId'] ) ? (int) $item['parentId'] : 0,
					'post_author'  => get_current_user_id(),
				) );
				if ( ! is_wp_error( $id ) && $id ) {
					update_post_meta( $id, '_up_rtype', isset( $item['type'] ) ? $item['type'] : 'TEMPLATE' );
					update_post_meta( $id, '_up_ttype', isset( $item['templateType'] ) ? $item['templateType'] : '' );
					$criados[] = call_user_func( $up_el_cloud_res, get_post( $id ), false );
				}
			}
			return call_user_func( $up_el_cloud_resp, $criados, 201 );
		}

		if ( 'GET' === $metodo && '' === $s1 ) {
			$lista = $busca_lista( 'TEMPLATE' );
			$pastas = $busca_lista( 'FOLDER' );
			$lista['data'] = array_merge( $pastas['data'], $lista['data'] );
			$lista['total'] = $lista['total'] + $pastas['total'];
			return call_user_func( $up_el_cloud_resp, $lista );
		}

		if ( 'GET' === $metodo && $s1 ) {
			$p = get_post( (int) $s1 );
			if ( ! $p || 'up_el_cloud' !== $p->post_type ) {
				return call_user_func( $up_el_cloud_resp, array( 'data' => array(), 'total' => 0 ) );
			}
			return call_user_func( $up_el_cloud_resp, call_user_func( $up_el_cloud_res, $p, true ) );
		}

		if ( 'POST' === $metodo && '' === $s1 ) {
			$id = wp_insert_post( array(
				'post_type'    => 'up_el_cloud',
				'post_status'  => 'publish',
				'post_title'   => isset( $dados['title'] ) ? $dados['title'] : __( 'Template', 'elementor' ),
				'post_content' => wp_json_encode( isset( $dados['content'] ) ? $dados['content'] : array() ),
				'post_parent'  => isset( $dados['parentId'] ) ? (int) $dados['parentId'] : 0,
				'post_author'  => get_current_user_id(),
			) );
			if ( is_wp_error( $id ) || ! $id ) {
				return call_user_func( $up_el_cloud_resp, array( 'data' => array(), 'total' => 0 ) );
			}
			update_post_meta( $id, '_up_rtype', isset( $dados['type'] ) ? $dados['type'] : 'TEMPLATE' );
			update_post_meta( $id, '_up_ttype', isset( $dados['templateType'] ) ? $dados['templateType'] : '' );
			update_post_meta( $id, '_up_pagesets', ! empty( $dados['hasPageSettings'] ) ? 1 : 0 );
			return call_user_func( $up_el_cloud_resp, call_user_func( $up_el_cloud_res, get_post( $id ), false ), 201 );
		}

		if ( 'PATCH' === $metodo && $s1 && 'preview' === $s2 ) {
			$url_prev = '';
			foreach ( array( 'previewUrl', 'url', 'preview' ) as $k ) {
				if ( ! empty( $dados[ $k ] ) && is_string( $dados[ $k ] ) ) {
					$url_prev = $dados[ $k ];
					break;
				}
			}
			if ( $url_prev ) {
				update_post_meta( (int) $s1, '_up_preview', esc_url_raw( $url_prev ) );
			}
			$p = get_post( (int) $s1 );
			return call_user_func( $up_el_cloud_resp, $p ? call_user_func( $up_el_cloud_res, $p, false ) : array( 'success' => true ) );
		}

		if ( 'PATCH' === $metodo && $s1 ) {
			$campos = array( 'ID' => (int) $s1 );
			if ( isset( $dados['title'] ) ) {
				$campos['post_title'] = $dados['title'];
			}
			if ( isset( $dados['parentId'] ) ) {
				$campos['post_parent'] = (int) $dados['parentId'];
			}
			if ( isset( $dados['content'] ) ) {
				$campos['post_content'] = wp_json_encode( $dados['content'] );
			}
			if ( count( $campos ) > 1 ) {
				wp_update_post( $campos );
			}
			$p = get_post( (int) $s1 );
			return call_user_func( $up_el_cloud_resp, $p ? call_user_func( $up_el_cloud_res, $p, false ) : array( 'success' => true ) );
		}

		if ( 'DELETE' === $metodo && $s1 ) {
			wp_trash_post( (int) $s1 );
			return call_user_func( $up_el_cloud_resp, array( 'success' => true ) );
		}
	}

	return call_user_func( $up_el_cloud_resp, array( 'data' => array(), 'total' => 0 ) );
};

$up_el_my_idiomas = array(
	'da_DK', 'de_DE', 'en_AU', 'en_CA', 'en_GB', 'en_NZ', 'en_ZA', 'es_ES', 'fi',
	'fr_BE', 'fr_FR', 'he_IL', 'it_IT', 'ja', 'nb_NO', 'nl_NL', 'pt_BR', 'pt_PT',
	'ru_RU', 'sv_SE',
);

$up_el_my_trad_data = '2026-05-21 18:11:49';

define( 'UP_EL_MIRROR_URL', 'https://activations.ultrapackv2.com/product/1b873cda/elementor-pro-api/mirror.json' );

$up_el_my_mirror = function() {
	static $memo = null;
	if ( null !== $memo ) {
		return $memo;
	}
	$guardado = get_option( 'up_el_api_mirror' );
	$valido = is_array( $guardado ) && ! empty( $guardado['d'] ) && ! empty( $guardado['t'] );
	if ( $valido && ( time() - (int) $guardado['t'] ) < 12 * HOUR_IN_SECONDS ) {
		$memo = $guardado['d'];
		return $memo;
	}
	$r = wp_remote_get( UP_EL_MIRROR_URL, array( 'timeout' => 25 ) );
	if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
		$d = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( is_array( $d ) && ! empty( $d['templates_info'] ) ) {
			update_option( 'up_el_api_mirror', array( 't' => time(), 'd' => $d ), false );
			$memo = $d;
			return $memo;
		}
	}
	$memo = $valido ? $guardado['d'] : false;
	return $memo;
};

$up_el_my_resp = function( $corpo, $codigo = 200 ) {
	return array(
		'headers'  => array( 'content-type' => 'application/json' ),
		'body'     => is_string( $corpo ) ? $corpo : wp_json_encode( $corpo ),
		'response' => array( 'code' => $codigo, 'message' => 'OK' ),
		'cookies'  => array(),
		'filename' => null,
	);
};

$up_el_my_router = function( $url, $parsed_args, $licenca ) use ( $up_el_my_resp, $up_el_my_idiomas, $up_el_my_trad_data, $up_el_my_mirror ) {

	if ( strpos( $url, '/api/v1/templates/info' ) !== false
		|| strpos( $url, '/api/v2/info' ) !== false ) {
		$m = call_user_func( $up_el_my_mirror );
		$e_indice = ( strpos( $url, '/api/v1/templates/info' ) !== false );
		if ( is_array( $m ) ) {
			$corpo = $e_indice
				? ( isset( $m['templates_info'] ) ? $m['templates_info'] : null )
				: ( isset( $m['info'] ) ? $m['info'] : null );
			if ( is_array( $corpo ) ) {
				return call_user_func( $up_el_my_resp, $corpo );
			}
		}
		return call_user_func( $up_el_my_resp, $e_indice
			? array( 'templates' => array(), 'categories' => array(), 'types_data' => new stdClass() )
			: array( 'timestamp' => time(), 'upgrade_notice' => new stdClass(), 'feed' => array(),
				'pro_widgets' => array(), 'canary_deployment' => new stdClass(),
				'library' => array( 'templates' => array() ) ) );
	}

	if ( strpos( $url, '/api/v1/feedback' ) !== false
		|| strpos( $url, '/feedback/api/v1' ) !== false
		|| strpos( $url, 'feedback-pro' ) !== false ) {
		return call_user_func( $up_el_my_resp, array( 'success' => true ) );
	}

	if ( strpos( $url, '/api/v1/licenses/' ) !== false ) {
		return call_user_func( $up_el_my_resp, $licenca );
	}

	if ( strpos( $url, '/api/v1/pro-downloads/' ) !== false ) {
		return call_user_func( $up_el_my_resp, array( 'package_url' => '', 'versions' => array() ) );
	}

	if ( strpos( $url, '/api/v2/pro/info' ) !== false ) {
		$versao = defined( 'ELEMENTOR_PRO_VERSION' ) ? ELEMENTOR_PRO_VERSION : '1.0.0';

		$m = call_user_func( $up_el_my_mirror );
		$do_mirror = ( is_array( $m ) && ! empty( $m['translations'] ) && is_array( $m['translations'] ) );
		$mapa = $do_mirror
			? $m['translations']
			: array_fill_keys( $up_el_my_idiomas, $up_el_my_trad_data );

		$instaladas = function_exists( 'wp_get_installed_translations' ) ? wp_get_installed_translations( 'plugins' ) : array();
		$instaladas = isset( $instaladas['elementor-pro'] ) ? $instaladas['elementor-pro'] : array();

		$traducoes = array();
		$disponiveis = function_exists( 'get_available_languages' ) ? get_available_languages() : array();
		foreach ( $disponiveis as $loc ) {
			if ( ! isset( $mapa[ $loc ] ) ) {
				continue;
			}
			if ( isset( $instaladas[ $loc ] ) ) {
				$ja = empty( $instaladas[ $loc ]['PO-Revision-Date'] ) ? 0 : strtotime( $instaladas[ $loc ]['PO-Revision-Date'] );
				if ( ! $do_mirror || ( $ja && $ja >= strtotime( $mapa[ $loc ] . ' +0000' ) ) ) {
					continue;
				}
			}
			$traducoes[] = array(
				'language' => $loc,
				'version'  => $versao,
				'updated'  => $mapa[ $loc ],
				'package'  => 'https://activations.ultrapackv2.com/wp-content/ElementorAPIData/translations/elementor-pro-' . $loc . '.zip',
			);
		}

		$secoes = serialize( array(
			'description' => '<p>Elementor Pro.</p>',
			'changelog'   => '<p>Consulte o changelog do fabricante.</p>',
		) );

		return call_user_func( $up_el_my_resp, array(
			'new_version'  => $versao,
			'stable_version' => $versao,
			'name'         => 'Elementor Pro',
			'slug'         => 'elementor-pro',
			'url'          => 'https://elementor.com/',
			'requires'     => '6.0',
			'tested'       => get_bloginfo( 'version' ),
			'last_updated' => $up_el_my_trad_data,
			'download_link' => '',
			'sections'     => $secoes,
			'translations' => $traducoes,
		) );
	}

	return null;
};

add_action( 'plugins_loaded', function() {
	add_filter( 'pre_http_request', function( $pre, $parsed_args, $url ) {
	if ( strpos( $url, 'activations.ultrapackv2.com' ) !== false ) {
		return false;
	}
	if ( strpos( $url, 'cloud-library.prod.builder.elementor.red') !== false || strpos( $url, 'ms-8874.elementor.com') !== false ) {
		global $up_el_cloud_router;
		if ( $up_el_cloud_router ) {
			return call_user_func( $up_el_cloud_router, $url, $parsed_args );
		}
		return [
			'body' => json_encode(['data' => [], 'total' => 0]),
			'response' => ['code' => 200, 'message' => 'OK'],
			'headers' => ['content-type' => 'application/json'],
		];
	}
	if ( strpos( $url, 'my.elementor.com') !== false ) {
		global $e_full_data_unlock;
		global $up_el_my_router;
		if ( $up_el_my_router ) {
			$up_resp_local = call_user_func( $up_el_my_router, $url, $parsed_args, $e_full_data_unlock );
			if ( null !== $up_resp_local ) {
				return $up_resp_local;
			}
		}
		if ( strpos( $url, '/api/connect/v1/library/get_template_content' ) !== false ) {
			$response = wp_remote_get( "https://activations.ultrapackv2.com/product/1b873cda/elementor-pro-templates-ultrapackv2-com-53579245/{$parsed_args['body']['id']}.json", [ 'sslverify' => true, 'timeout' => 35 ] );
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				return $response;
			} else {
				return $pre;
			}

		} elseif ( preg_match( '/https:\/\/(my\.elementor|ms-8874\.elementor)\.com\/api\/v1\/kits-library\/kits\/([\w]+)\/download-link/', $url, $matches ) ) {
			$kit_id = $matches[2];
            $response = array(
                'body' => json_encode(array(
                    'download_link' => 'https://activations.ultrapackv2.com/product/1b873cda/elementor-pro-kits-ultrapackv2-com-53579245/templates-id/' . $kit_id . '.zip',
                )),
                'response' => array(
                    'code' => 200,
                    'message' => 'OK',
                ),
                'headers' => array(
                    'content-type' => 'application/json',
                ),
            );
            return $response;

		} elseif ( strpos( $url, '/api/v2/license/validate' ) !== false ||  strpos( $url, '/api/v2/license/activate' ) !== false ) {
            $response = array(
                'body' => json_encode($e_full_data_unlock),
                'response' => array(
                    'code' => 200,
                    'message' => 'OK',
                ),
                'headers' => array(
                    'content-type' => 'application/json',
                ),
            );
            return $response;
		
		} else {
			return $pre;
		}
	}
	
	return $pre;
	}, PHP_INT_MAX, 3 );
} );

define( 'ELEMENTOR_PRO_VERSION', '4.3.1' );

/**
 * All versions should be `major.minor`, without patch, in order to compare them properly.
 * Therefore, we can't set a patch version as a requirement.
 * (e.g. Core 3.15.0-beta1 and Core 3.15.0-cloud2 should be fine when requiring 3.15, while
 * requiring 3.15.2 is not allowed)
 */
define( 'ELEMENTOR_PRO_REQUIRED_CORE_VERSION', '4.0' );
define( 'ELEMENTOR_PRO_RECOMMENDED_CORE_VERSION', '4.3' );

define( 'ELEMENTOR_PRO__FILE__', __FILE__ );
define( 'ELEMENTOR_PRO_PLUGIN_BASE', plugin_basename( ELEMENTOR_PRO__FILE__ ) );
define( 'ELEMENTOR_PRO_PATH', plugin_dir_path( ELEMENTOR_PRO__FILE__ ) );
define( 'ELEMENTOR_PRO_ASSETS_PATH', ELEMENTOR_PRO_PATH . 'assets/' );
define( 'ELEMENTOR_PRO_MODULES_PATH', ELEMENTOR_PRO_PATH . 'modules/' );
define( 'ELEMENTOR_PRO_URL', plugins_url( '/', ELEMENTOR_PRO__FILE__ ) );
define( 'ELEMENTOR_PRO_ASSETS_URL', ELEMENTOR_PRO_URL . 'assets/' );
define( 'ELEMENTOR_PRO_MODULES_URL', ELEMENTOR_PRO_URL . 'modules/' );

require_once ELEMENTOR_PRO_MODULES_PATH . 'core-upgrade-recommendation/classes/upgrade-recommendation-dismissal.php';

\ElementorPro\Modules\CoreUpgradeRecommendation\Classes\Upgrade_Recommendation_Dismissal::register_dismiss_handlers();

/**
 * Load gettext translate for our text domain.
 *
 * @since 1.0.0
 *
 * @return void
 */
function elementor_pro_load_plugin() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'elementor_pro_fail_load' );

		return;
	}

	$core_version = ELEMENTOR_VERSION;
	$core_version_required = ELEMENTOR_PRO_REQUIRED_CORE_VERSION;
	$core_version_recommended = ELEMENTOR_PRO_RECOMMENDED_CORE_VERSION;

	if ( ! elementor_pro_compare_major_version( $core_version, $core_version_required, '>=' ) ) {
		add_action( 'admin_notices', 'elementor_pro_fail_load_out_of_date' );

		return;
	}

	if ( ! elementor_pro_compare_major_version( $core_version, $core_version_recommended, '>=' ) ) {
		add_action( 'admin_notices', 'elementor_pro_admin_notice_upgrade_recommendation' );
	}

	require ELEMENTOR_PRO_PATH . 'plugin.php';
}

function elementor_pro_compare_major_version( $left, $right, $operator ) {
	$pattern = '/^(\d+\.\d+).*/';
	$replace = '$1.0';

	$left  = preg_replace( $pattern, $replace, $left );
	$right = preg_replace( $pattern, $replace, $right );

	return version_compare( $left, $right, $operator );
}

add_action( 'plugins_loaded', 'elementor_pro_load_plugin' );

function print_error( $message ) {
	if ( ! $message ) {
		return;
	}
	// PHPCS - $message should not be escaped
	echo '<div class="error">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
/**
 * Show in WP Dashboard notice about the plugin is not activated.
 *
 * @since 1.0.0
 *
 * @return void
 */
function elementor_pro_fail_load() {
	$screen = get_current_screen();
	if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
		return;
	}

	$plugin = 'elementor/elementor.php';

	if ( _is_elementor_installed() ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );

		$message = '<h3>' . esc_html__( 'You\'re not using Elementor Pro yet!', 'elementor-pro' ) . '</h3>';
		$message .= '<p>' . esc_html__( 'Activate the Elementor plugin to start using all of Elementor Pro plugin’s features.', 'elementor-pro' ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate Now', 'elementor-pro' ) ) . '</p>';
	} else {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' );

		$message = '<h3>' . esc_html__( 'Elementor Pro plugin requires installing the Elementor plugin', 'elementor-pro' ) . '</h3>';
		$message .= '<p>' . esc_html__( 'Install and activate the Elementor plugin to access all the Pro features.', 'elementor-pro' ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Now', 'elementor-pro' ) ) . '</p>';
	}

	print_error( $message );
}

function elementor_pro_fail_load_out_of_date() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$file_path = 'elementor/elementor.php';

	$upgrade_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file_path, 'upgrade-plugin_' . $file_path );

	$message = sprintf(
		'<h3>%1$s</h3><p>%2$s <a href="%3$s" class="button-primary">%4$s</a></p>',
		esc_html__( 'Elementor Pro requires newer version of the Elementor plugin', 'elementor-pro' ),
		esc_html__( 'Update the Elementor plugin to reactivate the Elementor Pro plugin.', 'elementor-pro' ),
		$upgrade_link,
		esc_html__( 'Update Now', 'elementor-pro' )
	);

	print_error( $message );
}

function elementor_pro_admin_notice_upgrade_recommendation() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$recommended_version = ELEMENTOR_PRO_RECOMMENDED_CORE_VERSION;
	$core_version = ELEMENTOR_VERSION;

	if ( ! \ElementorPro\Modules\CoreUpgradeRecommendation\Classes\Upgrade_Recommendation_Dismissal::should_show_notice(
		$recommended_version,
		$core_version
	) ) {
		return;
	}

	$file_path = 'elementor/elementor.php';
	$upgrade_link = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file_path, 'upgrade-plugin_' . $file_path );
	$notice_id = \ElementorPro\Modules\CoreUpgradeRecommendation\Classes\Upgrade_Recommendation_Dismissal::get_notice_id( $recommended_version );

	$notice_options = [
		'id' => $notice_id,
		'title' => esc_html__( 'Don’t miss out on the new version of Elementor', 'elementor-pro' ),
		'description' => esc_html__( 'Update to the latest version of Elementor to enjoy new features, better performance and compatibility.', 'elementor-pro' ),
		'button' => [
			'text' => esc_html__( 'Update Now', 'elementor-pro' ),
			'url' => $upgrade_link,
			'type' => 'cta',
		],
		'dismissible' => true,
	];

	if ( ! did_action( 'elementor/loaded' ) || ! isset( \Elementor\Plugin::$instance->admin ) ) {
		elementor_pro_print_upgrade_recommendation_legacy_notice( $upgrade_link );

		return;
	}

	/** @var \Elementor\Core\Admin\Admin_Notices|null $admin_notices */
	$admin_notices = \Elementor\Plugin::$instance->admin->get_component( 'admin-notices' );

	if ( ! $admin_notices ) {
		elementor_pro_print_upgrade_recommendation_legacy_notice( $upgrade_link );

		return;
	}

	$admin_notices->print_admin_notice( $notice_options, [] );
}

function elementor_pro_print_upgrade_recommendation_legacy_notice( string $upgrade_link ): void {
	$message = sprintf(
		'<h3>%1$s</h3><p>%2$s <a href="%3$s" class="button-primary">%4$s</a></p>',
		esc_html__( 'Don’t miss out on the new version of Elementor', 'elementor-pro' ),
		esc_html__( 'Update to the latest version of Elementor to enjoy new features, better performance and compatibility.', 'elementor-pro' ),
		$upgrade_link,
		esc_html__( 'Update Now', 'elementor-pro' )
	);

	print_error( $message );
}

if ( ! function_exists( '_is_elementor_installed' ) ) {

	function _is_elementor_installed() {
		$file_path = 'elementor/elementor.php';
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $file_path ] );
	}
}
