<?php


class AdminReconcilationController extends ModuleAdminController
{
	private $data = array();
	public function __construct()
	{
		parent::__construct();
		$this->bootstrap =  true;
		$this->id_lang = $this->context->language->id;
		$this->default_form_language = $this->context->language->id;
	}

	public function postProcess()
	{
		$merchantDetails = $this->module->getConfigFormValues();
		$mrctCode =  $merchantDetails['PAYNIMO_MERCHANT_CODE'];
		if (!$mrctCode) {
			$mrctCode = null;
		}
		if ($_POST) {
			$fromdate = Tools::getValue('fromdate');
			$todate = Tools::getValue('todate');
			$sql = 'SELECT h.id_order, o.transaction_id, h.date_add as mydate, o.id_currency FROM ' . _DB_PREFIX_ . 'order_payment o
				JOIN ' . _DB_PREFIX_ . 'order_invoice_payment b 
				ON o.id_order_payment = b.id_order_payment
				JOIN ' . _DB_PREFIX_ . 'orders h
				ON h.id_order = b.id_order
				WHERE h.current_state=3 AND o.payment_method= "Pay Using worldline" AND o.transaction_id IS NOT NULL AND h.date_add BETWEEN "' . $fromdate . ' 00:00:00" AND "' . $todate . ' 23:59:59" ORDER BY h.id_order DESC;';
			$query = Db::getInstance()->executeS($sql);
			$successFullOrdersIds = [];
			if ($query) {
				foreach ($query as $row) {
					$orderid = $row["id_order"];
					$this->data["merchantTxnRefNumber"] = $row["transaction_id"];
					$date_raw = explode(" ", $row["mydate"]);
					$date_input = $date_raw[0];
					$currency_raw = Currency::getCurrencyInstance((int)(Configuration::get('PS_CURRENCY_DEFAULT')));
					$currency = $currency_raw->iso_code;

					$request_array = array(
						"merchant" => array("identifier" => $mrctCode),
						"transaction" => array(
							"deviceIdentifier" => "S",
							"currency" => $currency,
							"identifier" => $this->data["merchantTxnRefNumber"],
							"dateTime" => $date_input,
							"requestType" => "O"
						)
					);
					$refund_data = json_encode($request_array);
					$url = "https://www.paynimo.com/api/paynimoV2.req";
					$options = array(
						'http' => array(
							'method'  => 'POST',
							'content' => json_encode($request_array),
							'header' =>  "Content-Type: application/json\r\n" .
								"Accept: application/json\r\n"
						)
					);
					$context     = stream_context_create($options);
					$response_array = json_decode(file_get_contents($url, false, $context));
					$status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
					$status_message = $response_array->paymentMethod->paymentTransaction->statusMessage;
					$txn_id = $response_array->paymentMethod->paymentTransaction->identifier;

					if ($status_code == '0300') {
						$success_ids = $orderid;
						$order = new Order((int)$success_ids);
						$order->setCurrentState((int)Configuration::get('PS_OS_PAYMENT'));
						array_push($successFullOrdersIds, $success_ids);
					} else if ($status_code == "0397" || $status_code == "0399" || $status_code == "0396" || $status_code == "0392") {
						$success_ids = $orderid;
						$order = new Order((int)$success_ids);
						$order->setCurrentState((int)Configuration::get('PS_OS_ERROR'));
						array_push($successFullOrdersIds, $success_ids);
					} else {
						null;
					}
				}

				if ($successFullOrdersIds) {
					$this->data['order_ids'] = $successFullOrdersIds;
				} else {
					$this->data['message'] = "Updated Order Status for Order ID: None";
				}
			} else {
				$this->data['message'] = "Updated Order Status for Order ID: None";
			}
		} else {
			$this->data['message'] = null;
		}
	}

	public function initContent()
	{
		parent::initContent();
		if (isset($this->data['order_ids']) && is_array($this->data['order_ids']) && count($this->data['order_ids']) > 0) {
			$order_ids = $this->data['order_ids'];
			$order_details = Db::getInstance()->executeS("SELECT o.id_order, o.delivery_date, o.total_paid, o.payment,osl.name AS order_state_name 
			FROM " . _DB_PREFIX_ . "orders o  
			JOIN  " . _DB_PREFIX_ . "order_state os ON o.current_state = os.id_order_state
			JOIN  " . _DB_PREFIX_ . "order_state_lang osl ON os.id_order_state = osl.id_order_state	
			WHERE o.id_order IN (" . implode(',', $order_ids) . ") group by id_order");
			$this->context->smarty->assign('order_details', $order_details);
		} else {
			$this->context->smarty->assign('order_details', []);
		}
		$this->setTemplate('reconcilation.tpl');
	}
}
