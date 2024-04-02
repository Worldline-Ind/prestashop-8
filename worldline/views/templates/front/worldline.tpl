    <!doctype html>
    <html lang="{$language.iso_code}">

    <head>
      {block name='head'}
        {include file='_partials/head.tpl'}
      {/block}
    
    </head>

    <body id="{$page.page_name}" class="{$page.body_classes|classnames}">

      {hook h='displayAfterBodyOpeningTag'}

      <main>

        <header id="header">
          {block name='header'}
           {include file='_partials/helpers.tpl'}
            {include file='_partials/header.tpl'}
          {/block}
        </header>
        
        <div id="worldlinepayment"></div>


        <section id="wrapper">
          <div class="container">

            {block name='breadcrumb'}
              {include file='_partials/breadcrumb.tpl'}
            {/block}

            {block name="content_wrapper"}
              <div id="content-wrapper">
                {block name="content"}
                  <p></p>
                {/block}
              </div>
            {/block}

          </div>
        </section>

        <footer id="footer">
          {block name="footer"}
            {include file="_partials/footer.tpl"}
          {/block}
        </footer>

      </main>

    </body>

    </html>
    <!--
<form action='{$data.returnUrl2}' id="response-form" method="POST">
    <input type="hidden" name="msg" value="" id="response-string">
</form>
-->
<script src="https://www.paynimo.com/paynimocheckout/client/lib/jquery.min.js" type="text/javascript"></script>

<script type="text/javascript" src="https://www.paynimo.com/Paynimocheckout/server/lib/checkout.js"></script>
<script type="text/javascript"><!--
        $(document).ready(function() {
            var configJson = {
                    'tarCall': false,
                    'features':{
                        'showLoader': true,
                        'showPGResponseMsg': true,
                        'enableNewWindowFlow':{$data.enableNewWindowFlow}, //for hybrid applications please disable this by passing false
                        'enableExpressPay': {$data.enableExpressPay},
                        'enableAbortResponse': false,
                        'enableMerTxnDetails': true,
                        'hideSavedInstruments': {$data.hideSavedInstruments},
                        'enableInstrumentDeRegistration':{$data.enableInstrumentDeRegistration},
                        'separateCardMode': {$data.separateCardMode},
                        
                    },
                    'consumerData': {
                        'deviceId': 'WEBSH2',
                        'token': '{$data.token}',
                        'responseHandler': handleResponse,
                        'returnUrl': '{$data.returnUrl}',
                        'merchantLogoUrl':'{$data.merchant_logo_url}',  //provided merchant logo will be displayed
                        'merchantId': '{$data.mrctCode}',
                        'currency': '{$data.currency}',
                        'txnType': '{$data.txnType}',
                        'txnSubType': 'DEBIT',
                        'paymentMode': '{$data.paymentMode}',
                        'checkoutElement': '',
                        'saveInstrument': '{$data.saveInstrument}',
                        'disclaimerMsg': '{$data.disclaimerMsg}',
                        'merchantMsg': '{$data.merchantMsg}',
                        'consumerId': '{$data.CustomerId}',
                        'paymentModeOrder': [
                            '{$data.paymentModeOrder_1}',
                            '{$data.paymentModeOrder_2}',
                            '{$data.paymentModeOrder_3}',
                            '{$data.paymentModeOrder_4}',
                            '{$data.paymentModeOrder_5}',
                            '{$data.paymentModeOrder_6}',
                            '{$data.paymentModeOrder_7}',
                            '{$data.paymentModeOrder_8}',
                            '{$data.paymentModeOrder_9}',
                            '{$data.paymentModeOrder_10}'
                        ],
                        'consumerMobileNo': '{$data.customerMobNumber}',
                        'consumerEmailId': '{$data.email}',
                        'txnId': '{$data.merchantTxnRefNumber}',   //Unique merchant transaction ID
                        'items': [{
                            'itemId': '{$data.scheme}',
                            'amount': '{$data.Amount}',
                            'comAmt': '0'
                        }],
                        'cartDescription': '{$data.cart_desc}',
                        'merRefDetails': [
                            {
                                "name": "Txn. Ref. ID", 
                                "value": '{$data.merchantTxnRefNumber}'
                            }
                        ],
                        'customStyle': {
                            'PRIMARY_COLOR_CODE': '{$data.primary_color_code}',   //merchant primary color code
                            'SECONDARY_COLOR_CODE':'{$data.secondary_color_code}',   //provide merchant's suitable color code
                            'BUTTON_COLOR_CODE_1': '{$data.button_color_code_1}',   //merchant's button background color code
                            'BUTTON_COLOR_CODE_2':'{$data.button_color_code_2}'   //provide merchant's suitable color code for button text
                        }

                    }
                };

                $.pnCheckout(configJson);
                if(configJson.features.enableNewWindowFlow){
                    pnCheckoutShared.openNewWindow();
                }
                $(".checkout-detail-box-inner .popup-close,.confirmBox .errBtnCancel").on("click", function () {
                        window.location = '{$data.checkout_url}';     
                });
                function handleResponse(res) {
                        if (typeof res != 'undefined' && typeof res.paymentMethod != 'undefined' && typeof res.paymentMethod.paymentTransaction != 'undefined' && typeof res.paymentMethod.paymentTransaction.statusCode != 'undefined' && res.paymentMethod.paymentTransaction.statusCode == '0300') {
                            let stringResponse = res.stringResponse;
                            $("#response-string").val(stringResponse);
                            $("#response-form").submit();
                        } else {

                        }
                };
            
        });
</script>