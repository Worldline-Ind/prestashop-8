<?php


class worldlines2sverificationModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        //After confirming order
        $merchantDetails = $this->module->getConfigFormValues();
        $str = $_GET['msg'];
        if ($str) {
            $responseData = explode('|', $str);
            $responseData_1 = explode('|', $str);

            $verificationHash = array_pop($responseData_1);
            $hashableString = join('|', $responseData_1) . "|" . $merchantDetails["SALT"];;
            $hashedString = hash('sha512',  $hashableString);
            $oid = explode('orderid:', $responseData[7]);
            $oid_1 = $oid[1];
            $oid2 = explode('}', $oid_1);
            $oidreceived = $oid2[0];
            $orderId = $oidreceived;
            $orderId_form_cart_id = Order::getOrderByCartId((int)$orderId);
            $order = new Order((int)$orderId_form_cart_id);
            $status = $responseData[0];
            if ($status == '300' && $hashedString == $verificationHash) {
                $order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));
                echo $responseData[3] . "|" . $responseData[5] . "|1";
                die;
            } else {
                $order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
                echo $responseData[3] . "|" . $responseData[5] . "|0";
                die;
            }
        } else {
            echo json_encode("INVALID DATA");
            die;
        }
    }

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {

        parent::initContent();
    }
}
