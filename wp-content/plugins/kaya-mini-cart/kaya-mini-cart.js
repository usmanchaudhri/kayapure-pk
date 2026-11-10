jQuery(function ($) {
  var S = KayaMiniCart.selectors;
  var $drawer = $(S.drawer);
  var $overlay = $(S.overlay);
  var busyCls = 'is-busy';
  var openCls = 'is-open';

  function openCart() {
    $drawer.addClass(openCls).attr('aria-hidden', 'false');
    $overlay.addClass(openCls).attr('aria-hidden', 'false');
    $('body').addClass('kaya-minicart-open');
    // focus close for accessibility
    setTimeout(function () { $drawer.find('[data-kaya-cart-close]').trigger('focus'); }, 50);
  }
  function closeCart() {
    $drawer.removeClass(openCls).attr('aria-hidden', 'true');
    $overlay.removeClass(openCls).attr('aria-hidden', 'true');
    $('body').removeClass('kaya-minicart-open');
  }

  // Toggle bindings
  $(document).on('click', S.open + ',' + S.toggle, function (e) { e.preventDefault(); openCart(); });
  $(document).on('click', S.close + ',' + S.overlay, function (e) { e.preventDefault(); closeCart(); });
  $(document).on('keyup', function (e) { if (e.key === 'Escape') closeCart(); });

  // Open on add-to-cart success
  // $(document.body).on('added_to_cart', openCart); //original

  // REPLACE WITH THIS:
$(document.body).on('added_to_cart', function(e, fragments, cart_hash) {
    openCart();
    sendCartRequest({
        action: 'kaya_get_refreshed_fragments',
        nonce: KayaMiniCart.nonce
    });
});

//test
//   $(document.body).on('added_to_cart', function(e, fragments, cart_hash) {
//     openCart();
//     if (fragments) {
//         sendCartRequest({
//             action: 'kaya_get_refreshed_fragments',
//             nonce: KayaMiniCart.nonce
//         });
//     }
//     $('#kaya-minicart-footer').show();
// });

  // Utility: show/hide busy state
  function setBusy(isBusy) {
    $drawer.toggleClass(busyCls, !!isBusy)
      .find('.kaya-minicart__loader').attr('aria-hidden', isBusy ? 'false' : 'true');
  }

  // Utility: pull a cart key no matter which element received the click
  function getCartKey($el) {
    return $el.data('cart_item_key')
      || $el.attr('data-cart_item_key')
      || $el.closest('[data-cart_item_key]').attr('data-cart_item_key')
      || $el.data('cartItemKey')
      || $el.attr('data-cart-item-key')
      || $el.closest('[data-cart-item-key]').attr('data-cart-item-key')
      || '';
  }

  // Utility: send AJAX, update our fragments safely (don’t nuke wrappers)
  function sendCartRequest(data) {
    setBusy(true);
    return $.ajax({
      type: 'POST',
      url: KayaMiniCart.ajax_url,
      data: data,
      dataType: 'json'
    }).always(function () {
      setBusy(false);
    }).done(function (res) {
      if (!res || !res.fragments) return;

      // Only touch our known targets; leave anything else to Woo/other scripts.
      var keys = [
        '#kaya-minicart-icon-count',
        '#kaya-minicart-count',
        '#kaya-minicart-contents',
        '#kaya-minicart-footer',
        '#kaya-minicart-savings',
        '#kaya-minicart-badges'
      ];

      keys.forEach(function (selector) {
        if (!res.fragments.hasOwnProperty(selector)) return;
        var html = res.fragments[selector];
        var $target = $(selector);
        if (!$target.length) return;

        // Counts -> text; Wrappers -> inner html
        if (selector === '#kaya-minicart-icon-count' || selector === '#kaya-minicart-count') {
          $target.text($.trim($('<div>').html(html).text())); // ensure plain number/text
        } else if (selector === '#kaya-minicart-savings') {
          $target.html(html);
          // toggle hidden class based on content presence
          if ($.trim($target.text()) === '') $target.addClass('is-hidden');
          else $target.removeClass('is-hidden');
        } else {
          $target.html(html);
        }
      });

      // Handle totals hide/show
      // if (res.fragments['#kaya-minicart-totals'] !== undefined) {
      //   var html = res.fragments['#kaya-minicart-totals'];
      //   if ($.trim(html) === '') {
      //     $('#kaya-minicart-footer').hide();
      //   } else {
      //     $('#kaya-minicart-footer').show();
      //     $('#kaya-minicart-totals').html(html); // replace subtotal + total together
      //   }
      // }

      //testing
      if (res.fragments['#kaya-minicart-totals'] !== undefined) {
    var html = res.fragments['#kaya-minicart-totals'];
    if ($.trim(html) === '') {
        $('#kaya-minicart-footer').hide().empty();
    } else {
        var $footer = $('#kaya-minicart-footer');
        if (!$footer.find('#kaya-minicart-totals').length) {
            $footer.html(
                '<div id="kaya-minicart-totals"></div>' +
                '<a class="kaya-minicart__checkout" href="/checkout/">' +
                'Secure Checkout <span aria-hidden="true"> →</span>' +
                '</a>' +
                '<p class="kaya-minicart__ssl">🔒 Secured by 256-bit SSL encryption</p>'
            );
        }
        $footer.find('#kaya-minicart-totals').html(html);
        $footer.show();
    }
}

      // Handle badges hide/show
      if (res.fragments['#kaya-minicart-badges'] !== undefined) {
        var html = res.fragments['#kaya-minicart-badges'];
        if ($.trim(html) === '') {
          $('#kaya-minicart-badges').hide();
        } else {
          $('#kaya-minicart-badges').show().html(html);
        }
      }

      // After DOM updates, announce
      $(document.body).trigger('wc_fragments_refreshed');

    }).fail(function (xhr) {
      if (window.console) console.error('MiniCart AJAX failed:', xhr && xhr.responseText);
      window.alert(KayaMiniCart.i18n.error);
    });
  }

  window.sendCartRequest = sendCartRequest;

  // Qty increment/decrement buttons (AJAX)
  $(document).on('click', '.kaya-minicart-qtybtn', function () {
    var $wrap = $(this).closest(S.qtyWrap);
    var $input = $wrap.find(S.qty);
    var key = getCartKey($input);
    var step = parseInt($(this).data('step'), 10) || 0;
    var next = Math.max(0, (parseInt($input.val(), 10) || 0) + step);
    $input.val(next);

    sendCartRequest({
      action: 'kaya_update_cart_item',
      cart_item_key: key,
      qty: next,
      nonce: KayaMiniCart.nonce
    });
  });

  // Manual qty input change (AJAX) — debounce light to avoid spam
  var qtyTimer = null;
  $(document).on('input change', S.qty, function () {
    var $input = $(this);
    var key = getCartKey($input);
    var qty = Math.max(0, parseInt($input.val(), 10) || 0);

    clearTimeout(qtyTimer);
    qtyTimer = setTimeout(function () {
      sendCartRequest({
        action: 'kaya_update_cart_item',
        cart_item_key: key,
        qty: qty,
        nonce: KayaMiniCart.nonce
      });
    }, 120);
  });

  $(document).on('click', S.remove, function (e) {
    e.preventDefault();
    var key = getCartKey($(this));
    if (!key) {
      if (window.console) console.warn('No cart_item_key found on remove click.');
      return;
    }
    sendCartRequest({
      action: 'kaya_remove_cart_item',
      cart_item_key: key,
      nonce: KayaMiniCart.nonce
    });
  });
});
