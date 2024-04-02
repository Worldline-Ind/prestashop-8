<?php

function is_not_17()
{
    return version_compare(_PS_VERSION_, '1.7', '<');
}

class worldlinerequestModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    private $template_data = array();
    private $data = array();
    public $display_column_left = false;

    public function postProcess()
    {
        //After confirming order
        if (Tools::getValue('confirm')) {

            if (Tools::usingSecureMode()) {
                $domain = Tools::getShopDomainSsl(true);
            } else {
                $domain = Tools::getShopDomain(true);
            }

            /* Getting merchant details */
            $merchantDetails = $this->module->getConfigFormValues();

            /* Setting payment details */
            $merchantTxnId = rand(1, 1000000);
            $curDate = date("d-m-Y");

            /* Getting cart details */
            $cart = Context::getContext()->cart;
            $amount = number_format((float)$cart->getOrderTotal(true, 3), 2, '.', '');
            $address = new Address((int)($cart->id_address_delivery));

            $customerId = (int)$this->context->cookie->id_customer;
            $customer = new Customer((int)$customerId);

            $currency = Currency::getCurrencyInstance((int)$this->context->cookie->id_currency);

            $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);

            $result = Db::getInstance()->getRow('
                        SELECT `id_guest`
                        FROM `' . _DB_PREFIX_ . 'guest`
                        WHERE `id_customer` = ' . $customerId);

            $isGuest = '0';
            if (!$this->context->customer->id) {
                $isGuest = '1';
            }

            if ($merchantDetails['PAYNIMO_LIVE_MODE'] == 'Live') {
                $amount_final = $amount;
            } else {
                $amount_final = '1.00';
            }
            if ($customer) {
                $customerName = Tools::safeOutput($customer->firstname) . ' ' . Tools::safeOutput($customer->lastname);
            }

            $orderId = $this->context->cart->id;
            $returnUrl = $domain . __PS_BASE_URI__ .
                "index.php?fc=module&module=worldline&controller=response&currentOrderId=" . $orderId . "&isGuest=" . $isGuest .
                "&guestEmail=" . $customer->email .  "&cartId=" . (int)$this->context->cart->id;

            $returnUrl2 = $domain . __PS_BASE_URI__ .
                "index.php?fc=module&module=worldline&controller=response&currentOrderId=" . $orderId . "&isGuest=" . $isGuest .
                "&guestEmail=" . $customer->email .  "&cartId=" . (int)$this->context->cart->id;

            $this->data['returnUrl2'] = $returnUrl2;


            if ($merchantDetails['handle_response_on_popup'] == "yes" && $merchantDetails['enableNewWindowFlow'] == 1) {
                $this->data['returnUrl'] = "";
            } else {
                $this->data['returnUrl'] = $returnUrl;
            }

            if ($merchantDetails['primary_color_code']) {
                $this->data["primary_color_code"] = $merchantDetails['primary_color_code'];
            } else {
                $this->data["primary_color_code"] = '#3977b7';
            }

            if ($merchantDetails['secondary_color_code']) {
                $this->data["secondary_color_code"] = $merchantDetails['secondary_color_code'];
            } else {
                $this->data["secondary_color_code"] = '#FFFFFF';
            }

            if ($merchantDetails['button_color_code_1']) {
                $this->data["button_color_code_1"] = $merchantDetails['button_color_code_1'];
            } else {
                $this->data["button_color_code_1"] = '#1969bb';
            }

            if ($merchantDetails['button_color_code_2']) {
                $this->data["button_color_code_2"] = $merchantDetails['button_color_code_2'];
            } else {
                $this->data["button_color_code_2"] = '#FFFFFF';
            }

            $logo_url = $merchantDetails['merchant_logo_url'];
            if (!empty($logo_url) && @getimagesize($logo_url)) {
                $this->data['merchant_logo_url'] = $logo_url;
            } else {
                $this->data['merchant_logo_url'] = 'https://www.paynimo.com/CompanyDocs/company-logo-md.png';
            }
            if ($merchantDetails['checkoutElement'] == 'yes') {
                $this->data['checkoutElement'] = '#worldlinepayment';
            } else {
                $this->data['checkoutElement'] = '';
            }

            $customerMobNumber = $address->phone;
            if (strpos($customerMobNumber, '+') !== false) {
                $customerMobNumber = str_replace("+", "", $customerMobNumber);
            }

            $this->data['enableExpressPay'] = $merchantDetails['enableExpressPay'];
            $this->data['separateCardMode'] = $merchantDetails['separateCardMode'];
            $this->data['enableNewWindowFlow'] = $merchantDetails['enableNewWindowFlow'];
            $this->data['enableInstrumentDeRegistration'] = $merchantDetails['enableInstrumentDeRegistration'];
            $this->data['hideSavedInstruments'] = $merchantDetails['hideSavedInstruments'];
            $this->data['saveInstrument'] = $merchantDetails['saveInstrument'];


            $this->data['merchantMsg'] = $merchantDetails['merchantMsg'];
            $this->data['disclaimerMsg'] = $merchantDetails['disclaimerMsg'];
            $this->data['paymentMode'] = $merchantDetails['paymentMode'];
            $this->data['txnType'] = $merchantDetails['txnType'];

            if ($merchantDetails['paymentModeOrder']) {
                $paymentModeOrder = $merchantDetails['paymentModeOrder'];
                $paymentorderarray = explode(',', $paymentModeOrder);
                $this->data['paymentModeOrder_1'] = isset($paymentorderarray[0]) ? $paymentorderarray[0] : null;
                $this->data['paymentModeOrder_2'] = isset($paymentorderarray[1]) ? $paymentorderarray[1] : null;
                $this->data['paymentModeOrder_3'] = isset($paymentorderarray[2]) ? $paymentorderarray[2] : null;
                $this->data['paymentModeOrder_4'] = isset($paymentorderarray[3]) ? $paymentorderarray[3] : null;
                $this->data['paymentModeOrder_5'] = isset($paymentorderarray[4]) ? $paymentorderarray[4] : null;
                $this->data['paymentModeOrder_6'] = isset($paymentorderarray[5]) ? $paymentorderarray[5] : null;
                $this->data['paymentModeOrder_7'] = isset($paymentorderarray[6]) ? $paymentorderarray[6] : null;
                $this->data['paymentModeOrder_8'] = isset($paymentorderarray[7]) ? $paymentorderarray[7] : null;
                $this->data['paymentModeOrder_9'] = isset($paymentorderarray[8]) ? $paymentorderarray[8] : null;
                $this->data['paymentModeOrder_10'] = isset($paymentorderarray[9]) ? $paymentorderarray[9] : null;
            } else {
                $this->data['paymentModeOrder_1'] = "cards";
                $this->data['paymentModeOrder_2'] = "netBanking";
                $this->data['paymentModeOrder_3'] = "imps";
                $this->data['paymentModeOrder_4'] = "wallets";
                $this->data['paymentModeOrder_5'] = "cashCards";
                $this->data['paymentModeOrder_6'] =  "UPI";
                $this->data['paymentModeOrder_7'] =  "MVISA";
                $this->data['paymentModeOrder_8'] = "debitPin";
                $this->data['paymentModeOrder_9'] = "emiBanks";
                $this->data['paymentModeOrder_10'] = "NEFTRTGS";
            }

            $cust_id = "cons" . strval(rand(1, 1000000));
            $this->data["orderid"] = $orderId;
            $this->data["merchantTxnRefNumber"] = rand(1, 1000000);
            $this->data["Amount"] = $amount_final;
            $this->data["CustomerId"] = $cust_id;


            $this->data["customerMobNumber"] = $customerMobNumber;
            $this->data["email"] = $customer->email;
            $this->data["mrctCode"] = $merchantDetails['PAYNIMO_MERCHANT_CODE'];
            $this->data['SALT'] = $merchantDetails['SALT'];
            $this->data['scheme'] = $merchantDetails['PAYNIMO_SCODE'];

            $this->data['currency'] = $currency->iso_code;
            $this->data['CustomerName'] = $customerName;

            $this->data['cart_desc'] =  "}{custname:" . $customerName . "}{orderid:" . $orderId;
            $this->data['bankCode'] = 470;

            $datastring = $this->data['mrctCode'] . "|" . $this->data['merchantTxnRefNumber'] . "|" . $this->data['Amount'] . "|" . "|" . $this->data['CustomerId'] . "|" . $this->data['customerMobNumber'] . "|" . $this->data['email'] . "||||||||||" . $this->data['SALT'];

            $hashed = hash('sha512', $datastring);
            $this->data['token'] = $hashed;

            $extra_vars = array();
            $extra_vars['transaction_id'] = $this->data["merchantTxnRefNumber"];
            $filename = 'worldline_' . date("Ymd") . '.log';
            $logger = new FileLogger(0);
            $logger->setFilename(_PS_ROOT_DIR_ . "/var/logs/" . $filename);
            $logger->log("worldline Request: " . $datastring);


            $link_obj  = new Link();
            $link = $link_obj->getPageLink("order.php");
            $this->data["checkout_url"] = $link;

            $this->module->validateOrder($this->context->cart->id, _PS_OS_PREPARATION_, $total, Configuration::get('worldline_checkout_label'), NULL, $extra_vars, NULL, false, $customer->secure_key, NULL);

            $oldCart = new Cart($this->context->cart->id);
            $duplication = $oldCart->duplicate();
            $this->context->cookie->id_cart = $duplication['cart']->id;
            $context = $this->context;
            $context->cart = $duplication['cart'];
            CartRule::autoAddToCart($context);
            $this->context->cookie->write();
        }
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        $this->data['enableExpressPay'] = !empty($this->data['enableExpressPay']) ? 'true' : 'false';
        $this->data['separateCardMode'] = !empty($this->data['separateCardMode']) ? 'true' : 'false';
        $this->data['enableNewWindowFlow'] = !empty($this->data['enableNewWindowFlow']) ? 'true' : 'false';
        $this->data['hideSavedInstruments'] = !empty($this->data['hideSavedInstruments']) ? 'true' : 'false';
        $this->data['enableInstrumentDeRegistration'] = !empty($this->data['enableInstrumentDeRegistration']) ? 'true' : 'false';

        $this->context->smarty->assign('data', $this->data);
        $this->setTemplate('module:worldline/views/templates/front/worldline.tpl');
        parent::initContent();
    }
}
