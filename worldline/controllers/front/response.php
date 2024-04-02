<?php

class worldlineresponseModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        if ($_POST) {
            $response = $_POST;
            $merchantDetails = $this->module->getConfigFormValues();
            if (is_array($response)) {
                $str = $response['msg'];
                $filename = 'worldline_' . date("Ymd") . '.log';
                $logger = new FileLogger(0);
                $logger->setFilename(_PS_ROOT_DIR_ . "/var/logs/" . $filename);
                $logger->log("worldline Response: " . $str);
            }

            $responseData = explode('|', $str);
            $responseData_1 = explode('|', $str);
            $response_message2 = $responseData[2];
            if (!$response_message2) {
                $response_message2 = "Transaction Failed";
            }

            $status = $responseData[0];
            $error_status_msg = $this->getErrorStatusMessage($status);

            $cart = Context::getContext()->cart;
            $customer = new Customer((int)$cart->id_customer);

            $total = $this->context->cart->getOrderTotal(true, Cart::BOTH);
            $extra_vars = array();
            $id_es = array();
            $transaction_id = $responseData[5];
            $identifier = $merchantDetails['PAYNIMO_MERCHANT_CODE'];
            $currency_array = Currency::getCurrencyInstance((int)$this->context->cookie->id_currency);
            $currency_code = $currency_array->iso_code;
            $transaction_id = $responseData[5];
            $merchant_transaction_id = $responseData[3];
            $amount = $responseData[6];
            $extra_vars['transaction_id'] = $responseData[5];

            $verificationHash = array_pop($responseData_1);
            $hashableString = join('|', $responseData_1) . "|" . $merchantDetails["SALT"];
            $hashedString = hash('sha512',  $hashableString);

            if (Tools::usingSecureMode()) {
                $domain = Tools::getShopDomainSsl(true);
            } else {
                $domain = Tools::getShopDomain(true);
            }
            if ($status == '300' && $hashedString == $verificationHash && $this->S_call($identifier, $currency_code, $transaction_id) == '300') {
                $this->addOrderHistory($_GET['currentOrderId'], (int)Configuration::get('PS_OS_PAYMENT'));
                $orderId = Order::getOrderByCartId((int)$_GET['currentOrderId']);

                $order = new Order((int)$orderId);
                $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));

                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'custom_merchantdetail` (`id_order`,`transaction_id`,`merchant_transaction_id`) VALUES (' . (int)$orderId . ',' . (int)$transaction_id . ',' . (int)$merchant_transaction_id . ')');

                $orderReference = $order->reference;
                if (!is_null($cart)) {
                    $cart->delete();
                }
                if ($_GET['isGuest'] == '1') {
                    $url = $domain . '/guest-tracking?order_reference=' . $orderReference . '?email=' . $_GET['guestEmail'];
                } else {
                    $url = $domain . '/order-confirmation?id_order=' . (int)$this->module->currentOrder;
                }

                $this->success[] = $this->l("Your order Has been Successfully Placed");
                $this->redirectWithNotifications($url);
            } else {
                $this->addOrderHistory($_GET['currentOrderId'], (int)Configuration::get('PS_OS_ERROR'));

                $orderId = Order::getOrderByCartId((int)$_GET['currentOrderId']);
                $order = new Order((int)$orderId);
                $order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'custom_merchantdetail` (`id_order`,`transaction_id`,`merchant_transaction_id`) VALUES (' . (int)$orderId . ',' . (int)$transaction_id . ',' . (int)$merchant_transaction_id . ')');
                $orderReference = $order->reference;

                if ($_GET['isGuest'] == 1) {
                    $url = $domain . '/guest-tracking?order_reference=' . $orderReference . '?email=' . $_GET['guestEmail'];
                } else {
                    $url = $domain . '/order-confirmation?id_order=' . (int)$this->module->currentOrder;
                }
                $this->errors[] = $this->l('Transaction Status: ' . $error_status_msg);
                $this->errors[] = $this->l('Transaction Error Message from Payment Gateway: ' . $response_message2);
                $this->redirectWithNotifications($url);
            }

            Tools::redirectLink($url);
        }
    }

    public function getErrorStatusMessage($code)
    {
        $messages = [
            "0300" => "Successful Transaction",
            "0392" => "Transaction cancelled by user either in Bank Page or in PG Card /PG Bank selection",
            "0396" => "Transaction response not received from Bank, Status Check on same Day",
            "0397" => "Transaction Response not received from Bank. Status Check on next Day",
            "0399" => "Failed response received from bank",
            "0400" => "Refund Initiated Successfully",
            "0401" => "Refund in Progress (Currently not in used)",
            "0402" => "Instant Refund Initiated Successfully(Currently not in used)",
            "0499" => "Refund initiation failed",
            "9999" => "Transaction not found :Transaction not found in PG"
        ];
        if (in_array($code, array_keys($messages))) {
            return $messages[$code];
        }
        return null;
    }

    public function S_call($identifier, $currency, $transaction_id)
    {
        $request_array = array(
            "merchant" => array("identifier" => $identifier),
            "transaction" => array(
                "deviceIdentifier" => "S",
                "currency" => $currency,
                "dateTime" => date("Y-m-d"),
                "token" => $transaction_id,
                "requestType" => "S"
            )
        );
        $Scall_data = json_encode($request_array);
        $Scall_url = "https://www.paynimo.com/api/paynimoV2.req";
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode($request_array),
                'header' =>  "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n"
            )
        );
        $context  = stream_context_create($options);
        $response_array = json_decode(file_get_contents($Scall_url, false, $context));
        $status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
        if ($status_code) {
            return $status_code;
        } else {
            return 'Failed';
        }
    }

    /**
     * Adds order history to order_history table
     *
     * @param $currentOrderId
     * @param $orderStateId
     */
    public function addOrderHistory($orderId, $orderStateId)
    {
        $sql = "UPDATE `" . _DB_PREFIX_ . "order_history`
                SET `id_order_state` = '" . $orderStateId . "'
                WHERE id_order = " . $orderId;

        Db::getInstance()->execute($sql);
    }
}
