(function ($) {
  if (!WTN.isAndroidApp && !WTN.isIosApp) {
    return;
  }

  const addCartButton = $('.single_add_to_cart_button, .add_to_cart_button');

  function processPayment() {
    const button = $(this);
    const productId = button.data('product_id') || button.val();

    if (!productId) {
      return;
    }

    const resturl = webtonative_payment_settings.rest_url;
    const nonce = webtonative_payment_settings.nonce;

    function createOrder(data, dataToProcess) {
      let dataToSend = {
        productId: productId,
        platform: WTN.isAndroidApp ? 'ANDROID' : 'IOS',
        nativeProductId: dataToProcess.productId,
      };
      if (WTN.isIosApp) {
        dataToSend.receiptData = data;
      }
      if (WTN.isAndroidApp) {
        dataToSend = {
          ...dataToSend,
          ...data,
        };
      }
      $.ajax({
        url: resturl + '/order',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(dataToSend),
        headers: {
          'X-WP-Nonce': nonce,
        },
        success: function (response) {
          if (response.status === 'success') {
            button.text('Payment created');
            return;
          }
        },
        error: function (error) {
          button.text('Payment failed');
        },
      });
    }

    button.text('Processing...');
    button.prop('disabled', true);

    $.ajax({
      url: resturl + '/product',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        productId: productId,
        platform: WTN.isAndroidApp ? 'ANDROID' : 'IOS',
      }),
      headers: {
        'X-WP-Nonce': nonce,
      },
      success: function (response) {
        button.prop('disabled', false);
        const dataToProcess = {
          productId: response.productId,
          productType: response.productType === 'SUBS' ? 'SUBS' : 'INAPP',
          isConsumable: response.isConsumable === 'yes',
        };
        if (!dataToProcess.productId || !dataToProcess.productType) {
          console.log('Invalid product');
          button.off('click', processPayment);
          button.click();
          return;
        }
        button.prop('disabled', true);
        WTN.inAppPurchase({
          ...dataToProcess,
          callback: (data) => paymentCallback(data, dataToProcess),
        });
      },
      error: function (error) {
        button.text('Payment failed');
        button.prop('disabled', false);
        alert('Not able to process payment');
      },
    });

    function paymentCallback(data, dataToProcess) {
      button.prop('disabled', false);
      if (!data.isSuccess) {
        alert('Payment failed');
        button.text('Buy Now');
        return;
      }
      const receiptData = data.receiptData;
      createOrder(receiptData, dataToProcess);
    }

    return false;
  }

  addCartButton.removeAttr('data-wc-on--click');
  addCartButton.on('click', processPayment);
  addCartButton.text('Buy Now');
})(jQuery);
