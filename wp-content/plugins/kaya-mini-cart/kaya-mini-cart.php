<?php

/**
 * Plugin Name: Kaya Mini Cart (Slide-Out)
 * Description: Right-side slide-out mini cart with live fragments, qty/remove, and header icon — styled to product spec.
 * Version: 1.2.0
 * Author: You
 * Text Domain: kaya-minicart
 */

if (!defined('ABSPATH')) exit;

final class Kaya_Mini_Cart
{
    const VER = '1.2.0';
    const NONCE_ACTION = 'kaya_cart_nonce';
    const OPTION_FS_THRESHOLD = 50;

    private static $drawer_printed = false;

    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_footer', [$this, 'render_drawer']);
        add_action('init', [$this, 'register_ajax']);
        add_filter('woocommerce_add_to_cart_fragments', [$this, 'cart_fragments']);

        // Provide a header icon tag (optional)
        add_action('after_setup_theme', function () {
            if (!function_exists('kaya_cart_icon')) {
                function kaya_cart_icon()
                {
                    Kaya_Mini_Cart::output_cart_icon();
                }
            }
        });
    }

    /** ============ Assets ============ */
    public function enqueue_assets()
    {
        if (!class_exists('WooCommerce')) return;

        $base = plugin_dir_url(__FILE__);
        $path = plugin_dir_path(__FILE__);

        // Robust versioning (avoid warnings if files missing)
        $css_file = $path . 'kaya-mini-cart.css';
        $js_file  = $path . 'kaya-mini-cart.js';
        $css_version = file_exists($css_file) ? filemtime($css_file) : self::VER;
        $js_version  = file_exists($js_file)  ? filemtime($js_file)  : self::VER;

        wp_enqueue_style(
            'kaya-minicart',
            $base . 'kaya-mini-cart.css',
            [],
            $css_version
        );

        wp_enqueue_script(
            'kaya-minicart',
            $base . 'kaya-mini-cart.js',
            ['jquery'],
            $js_version,
            true
        );

        $threshold = (float) apply_filters('kaya_minicart_free_shipping_threshold', self::OPTION_FS_THRESHOLD);

        wp_localize_script('kaya-minicart', 'KayaMiniCart', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce(self::NONCE_ACTION),
            'threshold' => $threshold,
            'selectors' => [
                'drawer'  => '#kaya-minicart',
                'overlay' => '#kaya-minicart-overlay',
                'toggle'  => '[data-kaya-cart-toggle]',
                'open'    => '[data-kaya-cart-open]',
                'close'   => '[data-kaya-cart-close]',
                'qty'     => '.kaya-minicart-qty',
                'qtyWrap' => '.kaya-minicart-qtywrap',
                'remove'  => '.kaya-minicart-remove a', // ← target the link directly (fix)
            ],
            'i18n' => [
                'updating' => esc_html__('Updating…', 'kaya-minicart'),
                'error'    => esc_html__('Something went wrong. Please try again.', 'kaya-minicart'),
            ],
        ]);
    }

    /** ============ Drawer Markup ============ */
    public function render_drawer()
    {
        if (!class_exists('WooCommerce') || self::$drawer_printed) return;
        self::$drawer_printed = true;

        $cart_count   = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        $subtotal     = WC()->cart ? (float) WC()->cart->get_displayed_subtotal() : 0.0;
        $discount_raw = WC()->cart ? (float) WC()->cart->get_discount_total() : 0.0;
        $threshold    = (float) apply_filters('kaya_minicart_free_shipping_threshold', self::OPTION_FS_THRESHOLD);
        $remaining    = max(0, $threshold - $subtotal);
?>
        <!-- Overlay -->
        <div id="kaya-minicart-overlay" aria-hidden="true"></div>

        <!-- Drawer -->
        <aside id="kaya-minicart" class="kaya-minicart" aria-hidden="true" aria-label="<?php esc_attr_e('Mini cart', 'kaya-minicart'); ?>">
            <header class="kaya-minicart__header">
                <h3 class="kaya-minicart__title">
                    <?php esc_html_e('Your Cart', 'kaya-minicart'); ?>
                    <span class="kaya-minicart__count">(
                        <span id="kaya-minicart-count"><?php echo (int) $cart_count; ?></span>
                        )</span>
                </h3>
                <button type="button" class="kaya-minicart__close" data-kaya-cart-close aria-label="<?php esc_attr_e('Close cart', 'kaya-minicart'); ?>">×</button>
            </header>

            <div class="kaya-minicart__banner<?php echo $discount_raw > 0 ? '' : ' is-hidden'; ?>" id="kaya-minicart-savings">
                <?php
                if ($discount_raw > 0) {
                    printf('🎁 ' . esc_html__('You’re saving %s!', 'kaya-minicart'), wc_price($discount_raw));
                }
                ?>
            </div>

            <div class="kaya-minicart__content" id="kaya-minicart-contents" aria-live="polite">
                <?php $this->render_items_list(); ?>
            </div>

            <!-- Badges -->

            <div class="kaya-minicart__badges" id="kaya-minicart-badges">
                <div class="kaya-minicart__badges-flex">

                    <span class="kaya-minicart__badge">
                        <?php
                            if ($remaining > 0) {
                                printf(esc_html__('Complete your order and start your wellness journey today.', 'kaya-minicart'), wc_price($remaining));
                            } else {
                                esc_html_e('Complete your order and start your wellness journey today.', 'kaya-minicart');
                            }
                            ?>
                    </span>
                    <span class="kaya-minicart__badge">🛡 <?php esc_html_e('30-day guarantee', 'kaya-minicart'); ?></span>
                </div>
            </div>


            <footer class="kaya-minicart__footer" id="kaya-minicart-footer">
                <div id="kaya-minicart-totals">
                    <?php $this->render_totals_block(); ?>
                </div>

                <a class="kaya-minicart__checkout" href="<?php echo esc_url(wc_get_checkout_url()); ?>">
                    <?php esc_html_e('Secure Checkout', 'kaya-minicart'); ?>
                    <span aria-hidden="true"> →</span>
                </a>

                <p class="kaya-minicart__ssl">🔒 <?php esc_html_e('Secured by 256-bit SSL encryption', 'kaya-minicart'); ?></p>
            </footer>

            <div class="kaya-minicart__loader" role="status" aria-live="polite" aria-hidden="true">
                <span class="kaya-minicart__spinner"></span>
                <span class="kaya-minicart__sr"><?php esc_html_e('Updating…', 'kaya-minicart'); ?></span>
            </div>
        </aside>
    <?php
    }

    private function render_items_list()
    {
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            echo '<div class="kaya-minicart__empty">';
            echo '  <div class="kaya-minicart__empty-icon">🛒</div>';
            echo '  <h3 class="kaya-minicart__empty-title">' . esc_html__('Your cart is empty', 'kaya-minicart') . '</h3>';
            echo '  <p class="kaya-minicart__empty-text">' . esc_html__('Add some energy supplements to get started!', 'kaya-minicart') . '</p>';
            echo '  <a href="' . esc_url(home_url('/')) . '" class="kaya-minicart__empty-btn">' . esc_html__('Continue Shopping', 'kaya-minicart') . '</a>';
            echo '</div>';
            return;
        }

        echo '<ul class="kaya-minicart__list" aria-label="' . esc_attr__('Cart items', 'kaya-minicart') . '">';
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
            if (!$product || !$product->exists()) continue;

            $name       = $product->get_name();
            $thumbnail  = $product->get_image('woocommerce_thumbnail', ['class' => 'kaya-minicart__thumb']);
            $permalink  = $product->is_visible() ? $product->get_permalink($cart_item) : '';
            $qty        = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0;
            $is_on_sale = $product->is_on_sale();
            $regular    = wc_get_price_to_display($product, ['price' => $product->get_regular_price()]);
            $sale       = wc_get_price_to_display($product, ['price' => $product->get_price()]);
            $meta       = wc_get_formatted_cart_item_data($cart_item);
            $remove_url = wc_get_cart_remove_url($cart_item_key);

            $data_attrs = ' data-cart_item_key="' . esc_attr($cart_item_key) . '" data-cart-item-key="' . esc_attr($cart_item_key) . '" ';

            echo '<li class="kaya-minicart__item"' . $data_attrs . '>';

            // Image Column
            echo '<div class="kaya-minicart__media">';
            echo $thumbnail ?: wc_placeholder_img('woocommerce_thumbnail');
            echo '</div>';

            // Content Column
            echo '<div class="kaya-minicart__body">';

            // Name + meta + remove in a single row
            echo '<div class="kaya-minicart__meta-remove-wrapper">';

            echo '<div class="kaya-minicart__meta">';
            echo '<h4 class="kaya-minicart__name">';
            if ($permalink) echo '<a href="' . esc_url($permalink) . '" class="kaya-minicart__link">';
            echo esc_html($name);
            if ($permalink) echo '</a>';
            echo '</h4>';

            if ($meta) {
                echo '<div class="kaya-minicart__meta-content">' . $meta . '</div>';
            }
            echo '</div>'; // .kaya-minicart__meta

            echo '<div class="kaya-minicart-remove"' . $data_attrs . '>';
            echo '<a class="kaya-minicart-remove__link" href="' . esc_url($remove_url) . '"' . $data_attrs . ' aria-label="' . esc_attr__('Remove item', 'kaya-minicart') . '" title="' . esc_attr__('Remove', 'kaya-minicart') . '">×</a>';
            echo '</div>';

            echo '</div>'; // .kaya-minicart__meta-remove-wrapper

            // Price + qty actions row
            echo '<div class="kaya-minicart__price-actions-wrapper">';

            echo '<div class="kaya-minicart__price">';
            echo '<span class="kaya-minicart__price-current">' . wc_price($sale) . '</span>';
            if ($is_on_sale && $regular > $sale) {
                echo ' <span class="kaya-minicart__price-old">' . wc_price($regular) . '</span>';
            }
            echo '</div>';

            echo '<div class="kaya-minicart__actions">';
            $cart_url = wc_get_cart_url();
            echo '<form class="kaya-minicart__qtyform" action="' . esc_url($cart_url) . '" method="post" aria-label="' . esc_attr__('Update quantity', 'kaya-minicart') . '">';
            echo '<div class="kaya-minicart-qtywrap">';
            echo '<button class="kaya-minicart-qtybtn kaya-minicart-qtydec" type="button" aria-label="' . esc_attr__('Decrease quantity', 'kaya-minicart') . '" data-step="-1">−</button>';
            echo '<input class="kaya-minicart-qty" type="number" min="0" step="1" name="cart[' . esc_attr($cart_item_key) . '][qty]" value="' . esc_attr($qty) . '" data-cart_item_key="' . esc_attr($cart_item_key) . '" data-cart-item-key="' . esc_attr($cart_item_key) . '" inputmode="numeric" pattern="[0-9]*" />';
            echo '<button class="kaya-minicart-qtybtn kaya-minicart-qtyinc" type="button" aria-label="' . esc_attr__('Increase quantity', 'kaya-minicart') . '" data-step="1">+</button>';
            echo '</div>';
            echo '<noscript><button type="submit" name="update_cart" value="1" class="kaya-minicart__noscript-submit">' . esc_html__('Update Cart', 'kaya-minicart') . '</button></noscript>';
            echo '</form>';
            echo '</div>'; // actions

            echo '</div>'; // price-actions-wrapper

            echo '</div>'; // body
            echo '</li>';
        }
        echo '</ul>';
    }

    /** Totals block (matches design) */
    private function render_totals_block()
    {
        $cart = WC()->cart;
        if (!$cart || $cart->is_empty()) {
            echo '';
            return;
        }

        echo '<div class="kaya-minicart__totals">';

        // Subtotal
        echo '<div class="kaya-minicart__line"><span>' . esc_html__('Subtotal', 'kaya-minicart') . '</span><strong class="kaya-minicart-subtotal">';
        wc_cart_totals_subtotal_html();
        echo '</strong></div>';

        // Savings
        $discount = (float) $cart->get_discount_total();
        echo '<div class="kaya-minicart__line kaya-minicart__line--save" id="kaya-minicart-save-line"' . ($discount > 0 ? '' : ' style="display:none"') . '>';
        echo '<span>' . esc_html__('You Save', 'kaya-minicart') . '</span><strong class="kaya-minicart-save">' . ($discount > 0 ? '-' . wc_price($discount) : '') . '</strong></div>';

        // Shipping
        $subtotal  = (float) $cart->get_displayed_subtotal();
        $threshold = (float) apply_filters('kaya_minicart_free_shipping_threshold', self::OPTION_FS_THRESHOLD);
        if ($subtotal >= $threshold) {
            $shipping_html = esc_html__('Free Shipping', 'kaya-minicart');
        } elseif ($cart->show_shipping()) {
            $shipping_html = WC()->cart->get_cart_shipping_total();
        } else {
            $shipping_html = esc_html__('Calculated at checkout', 'kaya-minicart');
        }
        echo '<div class="kaya-minicart__line"><span>' . esc_html__('Shipping', 'kaya-minicart') . '</span><strong class="kaya-minicart-shipping">' . $shipping_html . '</strong></div>';

        echo '</div>'; // .totals

        // Grand total
        echo '<div class="kaya-minicart__grand">';
        echo '<span>' . esc_html__('Total', 'kaya-minicart') . '</span>';
        echo '<strong class="kaya-minicart-total">';
        wc_cart_totals_order_total_html();
        echo '</strong></div>';
    }


    /** Header cart icon (optional shortcode: [kaya_cart_icon] and [mini_cart]) */
    public static function output_cart_icon()
    {
        if (!class_exists('WooCommerce')) return;
        $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0; ?>
        <button type="button" class="kaya-minicart-icon" data-kaya-cart-open aria-label="<?php esc_attr_e('Open cart', 'kaya-minicart'); ?>">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
                <path d="M7 4h-2l-1 2h2l3.6 7.59-1.35 2.44A1.992 1.992 0 008 19h10v-2H8.42a.25.25 0 01-.22-.37L9.1 14h6.45a2 2 0 001.79-1.11l3.58-6.49A1 1 0 0019 5H7z" />
            </svg>
            <span class="kaya-minicart-icon__count" id="kaya-minicart-icon-count"><?php echo (int) $count; ?></span>
        </button>
        <?php
    }

    /** ============ Fragments (AJAX refresh) ============ */
    public function cart_fragments($fragments)
    {
        $cart = WC()->cart;

        // Header counts
        ob_start();
        echo (int) ($cart ? $cart->get_cart_contents_count() : 0);
        $count_html = ob_get_clean();
        $fragments['#kaya-minicart-icon-count'] = $count_html;
        $fragments['#kaya-minicart-count']      = $count_html;

        // List contents
        ob_start();
        $this->render_items_list();
        $fragments['#kaya-minicart-contents'] = ob_get_clean();

        // Totals/footer
        // if ($cart && !$cart->is_empty()) {
        //     ob_start();
        //     $this->render_totals_block(); // full block now
        //     $fragments['#kaya-minicart-totals'] = ob_get_clean();
        // } else {
        //     $fragments['#kaya-minicart-footer'] = '';
        // }
        
        // Totals/footer testing
            if ($cart && !$cart->is_empty()) {
                ob_start();
                $this->render_totals_block();
                $fragments['#kaya-minicart-totals'] = ob_get_clean();
                $fragments['#kaya-minicart-checkout-visible'] = 'yes';
            } else {
                $fragments['#kaya-minicart-totals'] = '';
                $fragments['#kaya-minicart-footer'] = '';
            }

        // Savings banner
        ob_start();
        $discount = $cart ? (float) $cart->get_discount_total() : 0.0;
        if ($discount > 0) {
            printf('🎁 ' . esc_html__('You’re saving %s!', 'kaya-minicart'), wc_price($discount));
        }
        $fragments['#kaya-minicart-savings'] = ob_get_clean();

        // Badges
        if ($cart && !$cart->is_empty()) {
            ob_start();
            $subtotal  = (float) $cart->get_displayed_subtotal();
            $threshold = (float) apply_filters('kaya_minicart_free_shipping_threshold', self::OPTION_FS_THRESHOLD);
            $remaining = max(0, $threshold - $subtotal);
        ?>
            <span class="kaya-minicart__badge">
                <?php
                    if ($remaining > 0) {
                        printf(esc_html__('Complete your order and start your wellness journey today.', 'kaya-minicart'), wc_price($remaining));
                    } else {
                        esc_html_e('Complete your order and start your wellness journey today.', 'kaya-minicart');
                    }
                    ?>
            </span>
            <span class="kaya-minicart__badge">🛡 <?php esc_html_e('30-day guarantee', 'kaya-minicart'); ?></span>
<?php
            $fragments['#kaya-minicart-badges'] = ob_get_clean();
        } else {
            $fragments['#kaya-minicart-badges'] = '';
        }

        return $fragments;
    }

    /** ============ AJAX ============ */
    public function register_ajax()
    {
        add_action('wp_ajax_kaya_update_cart_item', [$this, 'ajax_update_cart_item']);
        add_action('wp_ajax_nopriv_kaya_update_cart_item', [$this, 'ajax_update_cart_item']);
        add_action('wp_ajax_kaya_remove_cart_item', [$this, 'ajax_remove_cart_item']);
        add_action('wp_ajax_nopriv_kaya_remove_cart_item', [$this, 'ajax_remove_cart_item']);
    }

    public function ajax_update_cart_item()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';
        $qty = isset($_POST['qty']) ? max(0, (int) $_POST['qty']) : -1;

        if (!$key || $qty < 0) {
            wp_send_json_error(['message' => __('Invalid request', 'kaya-minicart')], 400);
        }

        $result = WC()->cart->set_quantity($key, $qty, true); // 0 removes item
        if ($result !== false) {
            // Returns JSON with fragments+hash. We echo & die here.
            WC_AJAX::get_refreshed_fragments();
        } else {
            wp_send_json_error(['message' => __('Could not update quantity', 'kaya-minicart')], 500);
        }
        wp_die();
    }

    public function ajax_remove_cart_item()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $key = isset($_POST['cart_item_key']) ? wc_clean(wp_unslash($_POST['cart_item_key'])) : '';
        if (!$key) {
            wp_send_json_error(['message' => __('Invalid request', 'kaya-minicart')], 400);
        }

        if (WC()->cart->remove_cart_item($key)) {
            WC_AJAX::get_refreshed_fragments();
        } else {
            wp_send_json_error(['message' => __('Could not remove item', 'kaya-minicart')], 500);
        }
        wp_die();
    }
}

new Kaya_Mini_Cart();

/** Shortcodes */
add_shortcode('kaya_cart_icon', function () {
    ob_start();
    if (function_exists('kaya_cart_icon')) kaya_cart_icon();
    return ob_get_clean();
});

add_shortcode('mini_cart', function () {
    ob_start();
    Kaya_Mini_Cart::output_cart_icon();
    return ob_get_clean();
});



add_filter('woocommerce_enable_cart_fragmentation', '__return_false');
add_filter('woocommerce_cart_fragment_name', '__return_empty_string');

add_action('woocommerce_before_cart_item_quantity_zero', function () {
    if (!headers_sent()) {
        header("Cache-Control: no-cache, no-store, must-revalidate");
        header("Pragma: no-cache");
        header("Expires: 0");
    }
});

