<?php

if (!defined('_PS_VERSION_')) {
	exit;
}

class Worldline extends PaymentModule
{
	public function __construct()
	{
		$this->name = 'worldline';
		$this->tab = 'payments_gateways';
		$this->version = '1.0';
		$this->author = 'worldline ePayments';
		$this->need_instance = 0;
		$this->bootstrap = true;
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';
		$this->module_key = 'your_module_key';
		parent::__construct();
		$this->displayName = $this->l('Worldline');
		$this->description = $this->l("worldline ePayments is India's leading digital payment solutions company. We are present in India for over 20 years and are powering over 550,000 businesses with our tailored payment solution.");
		$this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
		$this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
	}

	public function hookDisplayHeader()
	{
		$this->context->controller->addCSS($this->_path . 'views/css/checkout.css', 'all');
	}

	public function install()
	{

		$sqldrop = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'custom_merchantdetail`';
		if (!Db::getInstance()->execute($sqldrop)) {
			return false;
		}

		// Installation code goes here
		parent::install() && $this->installModuleTab() && $this->installModuleReconcilation();
		$sqlcreate = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'custom_merchantdetail` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`id_order` INT NOT NULL,
					`transaction_id` varchar(255) NOT NULL,
					`merchant_transaction_id` varchar(255) NOT NULL,
					`date_add` datetime DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=utf8';

		if (!Db::getInstance()->execute($sqlcreate)) {
			return false;
		}

		$this->registerHook('payment');
		$this->registerHook('displayPaymentEU');
		//$this->registerHook('paymentReturn');
		$this->registerHook('actionProductCancel');
		Configuration::updateValue('worldline_checkout_label', 'Pay Using worldline');
		return true;
		// return parent::install() && $this->registerHooks();
	}

	public function uninstall()
	{
		// Uninstallation code goes here
		$this->uninstallModuleTab() && $this->uninstallModuleReconcilation() && parent::uninstall();
		Configuration::deleteByName('worldline_client_id');
		Configuration::deleteByName('worldline_client_secret');
		Configuration::deleteByName('instmaojo_testmode');
		Configuration::deleteByName('worldline_checkout_label');
		$sqldrop = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'custom_merchantdetail`';
		if (!Db::getInstance()->execute($sqldrop)) {
			return false;
		}
		return true;
		// return parent::uninstall();
	}

	public function installModuleTab()
	{
		$tab = new Tab();
		$tab->name = array(
			1 => 'Offline Verification',
		);
		$tab->class_name = 'AdminVerification';
		$tab->module = $this->name;
		$tab->id_parent = (int)Tab::getIdFromClassName('AdminParentOrders');
		return $tab->add();
	}

	public function uninstallModuleTab()
	{
		$id_tab = Tab::getIdFromClassName('AdminVerification');
		if ($id_tab) {
			$tab = new Tab($id_tab);
			return $tab->delete();
		}
		return true;
	}

	public function installModuleReconcilation()
	{
		$tab = new Tab;
		$langs = language::getLanguages();
		foreach ($langs as $lang) {
			$tab->name[$lang['id_lang']] = 'Reconcilation';
		}
		$tab->module = $this->name;
		$tab->id_parent = (int) Tab::getIdFromClassName('AdminParentOrders');
		$tab->class_name = "AdminReconcilation";
		return $tab->save();
	}

	public function uninstallModuleReconcilation()
	{
		$id_tab = Tab::getIdFromClassName('AdminReconcilation');
		if ($id_tab) {
			$tab = new Tab($id_tab);
			return $tab->delete();
		}
		return true;
	}

	public function registerHooks()
	{
		// Register hooks code goes here
		if (!$this->active)
			return;

		$this->smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_worldline' => $this->_path,
			'checkout_label' => $this->l((Configuration::get('worldline_checkout_label')) ? Configuration::get('worldline_checkout_label') : "Pay using worldline"),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
		));

		return $this->display(__FILE__, 'payment.tpl');
		// return true;
	}

	public function hookPayment($params)
	{
		// Your payment display code here
		if (!$this->active)
			return;
	}
	public function hookDisplayPaymentEU($params)
	{
		// Your displayPaymentEU code here
		if (!$this->active)
			return;

		$merchantDetails = $this->getConfigFormValues();
		$logo_icon_name = $merchantDetails["logo_icon_name"];
		if (!$logo_icon_name) {
			$logo_text = "Cards / UPI / Netbanking / Wallets";
			$logo_img =  _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/worldline/views/img/logo-sm.png';
		} elseif (!empty($logo_icon_name) && @getimagesize($logo_icon_name)) {
			$logo_img = $logo_icon_name;
			$logo_text = "";
		} elseif (!empty($logo_icon_name) && !@getimagesize($logo_icon_name)) {
			$logo_text = "Cards / UPI / Netbanking / Wallets";
			$logo_img = "";
		} else {
			$logo_text = $logo_icon_name;
			$logo_img = "";
		}

		return array(
			'cta_text' => $logo_text,
			'logo' => $logo_img,
			'action' => $this->context->link->getModuleLink($this->name, 'request', array('confirm' => true), true)
		);
	}
	public function hookActionProductCancel($params)
	{
		try {

			$merchantDetails = $this->getConfigFormValues();
			$merchant_code = $merchantDetails["PAYNIMO_MERCHANT_CODE"];
			$service_loc = $merchantDetails["PAYNIMO_LIVE_MODE"];
			if ($params) {
				$currency_id = $params["order"]->id_currency;
				$currency_arr = Currency::getCurrencyInstance((int)$currency_id);
				$currency = $currency_arr->iso_code;
				$datewithtime = explode(" ", $params["order"]->date_add);
				$order_date = trim($datewithtime[0]);
				if ($service_loc == "Test") {
					$amount = "1.00";
				} elseif ($params['action'] == 2) {
					$amount = $params['cancel_amount'];
				} elseif ($params['action'] == 3) {
					$amount = $params["order"]->total_paid;
					//$amount = "1";
				}
				$orderId = Order::getOrderByCartId((int)$params["order"]->id_cart);
				$order_payment_collection = $params['order']->getOrderPaymentCollection();
				$order_payment = $order_payment_collection->getFirst();
				$merchantDetails = Db::getInstance()->executeS(
					'SELECT * FROM `' . _DB_PREFIX_ . 'custom_merchantdetail`
       						 WHERE `id_order` = ' . (int) $orderId
				);

				$transactionId = '';
				if (!empty($merchantDetails)) {
					foreach ($merchantDetails as $orderPayment) {
						$transactionId = $orderPayment['transaction_id'];
						// Your code to handle the transaction id
					}
				}
				$txn_id = $order_payment->transaction_id;
			} else {
				$this->errors[] = Tools::displayError('Refund Failed');
				die;
			}
			$request_array = array(
				"merchant" => array("identifier" => $merchant_code),
				"cart" => (object) null,
				"transaction" => array(
					"deviceIdentifier" => "S",
					"amount" => $amount,
					"currency" => $currency,
					"dateTime" => $order_date,
					"token" => $transactionId,
					"requestType" => "R"
				)
			);

			$refund_data = json_encode($request_array);
			$refund_url = "https://www.paynimo.com/api/paynimoV2.req";
			$options = array(
				'http' => array(
					'method'  => 'POST',
					'content' => $refund_data,
					'header' =>  "Content-Type: application/json\r\n" .
						"Accept: application/json\r\n"
				)
			);

			$context = stream_context_create($options);
			$response_array = json_decode(file_get_contents($refund_url, false, $context));
			$status_code = $response_array->paymentMethod->paymentTransaction->statusCode;
			$status_message = $response_array->paymentMethod->paymentTransaction->statusMessage;
			$error_message = $response_array->paymentMethod->paymentTransaction->errorMessage;
			$errorMessage = 'Refund Not Applicable';
			if ($status_code == '0400') {
				$order = new Order((int)$orderId);
				$order->setCurrentState(7);
				return true;
			} else if ($status_code == '0399') {
				throw new PrestaShopException($errorMessage);
			} else {

				throw new PrestaShopException($errorMessage);
			}
		} catch (Exception $e) {
			throw new PrestaShopException("Failed");
		}
	}

	# Show Configuration form in admin panel.
	public function getContent()
	{
		if (((bool)Tools::isSubmit('submitworldlineModule')) == true) {
			$this->postProcess();
		}

		$this->context->smarty->assign('module_dir', $this->_path);

		return $this->renderForm();
	}

	protected function postProcess()
	{
		$form_values = $this->getConfigFormValues();
		foreach (array_keys($form_values) as $key) {
			Configuration::updateValue($key, Tools::getValue($key));
		}
	}

	protected function renderForm()
	{
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitworldlineModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			. '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}


	protected function getConfigForm()
	{
		return array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'select',
						'label' => $this->l('Type of Payment'),
						'desc' => "For TEST mode amount will be charge 1",
						'required' => true,
						'name' => 'PAYNIMO_LIVE_MODE',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => 'Live',
									'name' => 'Live',
								),
								array(
									'id_option' => 'Test',
									'name' => 'Test',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'PAYNIMO_MERCHANT_CODE',
						'required' => true,
						'label' => $this->l('Merchant Code'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'SALT',
						'required' => true,
						'label' => $this->l('SALT'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'PAYNIMO_SCODE',
						'required' => true,
						'label' => $this->l('Merchant Scheme Code'),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'primary_color_code',
						'label' => $this->l('Primary Color'),
						'desc' => "Color value can be hex, rgb or actual color name",
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'secondary_color_code',
						'label' => $this->l('Secondary Color'),
						'desc' => "Color value can be hex, rgb or actual color name",
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'button_color_code_1',
						'label' => $this->l('Button Color 1'),
						'desc' => "Color value can be hex, rgb or actual color name",
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'button_color_code_2',
						'label' => $this->l('Button Color 2'),
						'desc' => "Color value can be hex, rgb or actual color name",
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'merchant_logo_url',
						'value' => 'https://www.paynimo.com/CompanyDocs/company-logo-md.png',
						'label' => $this->l('Logo Url'),
						'desc' => "An absolute URL pointing to a logo image of merchant which will show on checkout popup",
					),
					array(
						'type' => 'select',
						'label' => $this->l('Enable Express Pay'),
						'desc' => "To enable saved payments enable this feature",
						'name' => 'enableExpressPay',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => false,
									'name' => 'Disable',
								),
								array(
									'id_option' => true,
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Separate Card Mode'),
						'desc' => "If this feature is enabled checkout shows two separate payment mode(Credit Card and Debit Card)",
						'name' => 'separateCardMode',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => false,
									'name' => 'Disable',
								),
								array(
									'id_option' => true,
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Enable New Window Flow'),
						'desc' => "If this feature is enabled, then bank page will open in new window",
						'name' => 'enableNewWindowFlow',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => true,
									'name' => 'Enable',
								),
								array(
									'id_option' => false,
									'name' => 'Disable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Enable Instrument De Registration'),
						'desc' => "If this feature is enabled, you will have an option to delete saved cards",
						'name' => 'enableInstrumentDeRegistration',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => false,
									'name' => 'Disable',
								),
								array(
									'id_option' => true,
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Hide Saved Instrument'),
						'desc' => "If enabled checkout hides saved payment options even in case of enableExpressPay is enabled.",
						'name' => 'hideSavedInstruments',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => false,
									'name' => 'Disable',
								),
								array(
									'id_option' => true,
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Save Instrument'),
						'desc' => "Enable this feature to vault instrument",
						'name' => 'saveInstrument',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => false,
									'name' => 'Disable',
								),
								array(
									'id_option' => true,
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'merchantMsg',
						'label' => $this->l('Merchant Message'),
						'desc' => "Customize message from merchant which will be shown to customer in checkout page",
					),
					array(
						'col' => 3,
						'type' => 'text',
						'name' => 'disclaimerMsg',
						'label' => $this->l('Disclaimer Message'),
						'desc' => "Customize disclaimer message from merchant which will be shown to customer in checkout page",
					),
					array(
						'type' => 'select',
						'label' => $this->l('Transaction Type'),
						'name' => 'txnType',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => "SALE",
									'name' => 'SALE',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Response Message on Pup Up'),
						'name' => 'handle_response_on_popup',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => "no",
									'name' => 'Disable',
								),
								array(
									'id_option' => "yes",
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'type' => 'select',
						'label' => $this->l('Payment Mode'),
						'desc' => "If Bank selection is at worldline ePayments India Pvt. Ltd. end then select all, if bank selection at Merchant end then pass appropriate mode respective to selected option",
						'name' => 'paymentMode',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => "all",
									'name' => 'all',
								),
								array(
									'id_option' => "cards",
									'name' => 'cards',
								),
								array(
									'id_option' => "netBanking",
									'name' => 'netBanking',
								),
								array(
									'id_option' => "UPI",
									'name' => 'UPI',
								),
								array(
									'id_option' => "imps",
									'name' => 'imps',
								),
								array(
									'id_option' => "wallets",
									'name' => 'wallets',
								),
								array(
									'id_option' => "cashCards",
									'name' => 'cashCards',
								),
								array(
									'id_option' => "NEFTRTGS",
									'name' => 'NEFTRTGS',
								), array(
									'id_option' => "emiBanks",
									'name' => 'emiBanks',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'col' => 3,
						'type' => 'textarea',
						'name' => 'paymentModeOrder',
						'label' => $this->l('Payment Mode Order'),
						'desc' => "Please pass order in this format: cards,netBanking,imps,wallets,cashCards,UPI,MVISA,debitPin,NEFTRTGS,emiBanks. Merchant can define their payment mode order",
					),
					array(
						'type' => 'select',
						'label' => $this->l('Embed Payment Gateway On Page'),
						'name' => 'checkoutElement',
						'options' => array(
							'query' => $options = array(
								array(
									'id_option' => "no",
									'name' => 'Disable',
								),
								array(
									'id_option' => "yes",
									'name' => 'Enable',
								),
							),
							'id' => 'id_option',
							'name' => 'name',
						),
					),
					array(
						'col' => 3,
						'type' => 'textarea',
						'name' => 'logo_icon_name',
						'placeholder' => "worldline",
						'label' => $this->l('Payment Icon/Text'),
						'desc' => "Payment Icon/Text",
					),

				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	/**
	 * Set values for the inputs.
	 */
	public function getConfigFormValues()
	{
		return array(
			'PAYNIMO_LIVE_MODE' => Configuration::get('PAYNIMO_LIVE_MODE', null),
			'REQUEST_TYPE' => Configuration::get('REQUEST_TYPE', null),
			'PAYNIMO_MERCHANT_CODE' => Configuration::get('PAYNIMO_MERCHANT_CODE', null),
			'SALT' => Configuration::get('SALT', null),
			'PAYNIMO_IV' => Configuration::get('PAYNIMO_IV', null),
			'PAYNIMO_SCODE' => Configuration::get('PAYNIMO_SCODE', null),
			'primary_color_code' => Configuration::get('primary_color_code', null),
			'secondary_color_code' => Configuration::get('secondary_color_code', null),
			'button_color_code_1' => Configuration::get('button_color_code_1', null),
			'button_color_code_2' => Configuration::get('button_color_code_2', null),
			'merchant_logo_url' => Configuration::get('merchant_logo_url', null),
			'enableExpressPay' => Configuration::get('enableExpressPay', null),
			'separateCardMode' => Configuration::get('separateCardMode', null),
			'enableNewWindowFlow' => Configuration::get('enableNewWindowFlow', null),
			'enableInstrumentDeRegistration' => Configuration::get('enableInstrumentDeRegistration', null),
			'hideSavedInstruments' => Configuration::get('hideSavedInstruments', null),
			'saveInstrument' => Configuration::get('saveInstrument', null),
			'merchantMsg' => Configuration::get('merchantMsg', null),
			'disclaimerMsg' => Configuration::get('disclaimerMsg', null),
			'txnType' => Configuration::get('txnType', null),
			'handle_response_on_popup' => Configuration::get('handle_response_on_popup', null),
			'paymentMode' => Configuration::get('paymentMode', null),
			'paymentModeOrder' => Configuration::get('paymentModeOrder', null),
			'checkoutElement' => Configuration::get('checkoutElement', null),
			'logo_icon_name' => Configuration::get('logo_icon_name', null),
		);
	}
}
