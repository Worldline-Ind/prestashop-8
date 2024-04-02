<?php


class AdminVerificationController extends ModuleAdminController
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
		$data =  array();
		$currency = Currency::getCurrencyInstance((int)(Configuration::get('PS_CURRENCY_DEFAULT')));

		$this->data['currency'] = $currency->iso_code;
		if (!$this->data['currency']) {
			$this->data['currency'] = "INR";
		}
		$this->data["mrctCode"] = $merchantDetails['PAYNIMO_MERCHANT_CODE'];
		if (!$this->data["mrctCode"]) {
			$this->data["mrctCode"] = null;
		}
	}

	public function initContent()
	{
		parent::initContent();
		$this->context->smarty->assign('data', $this->data);
		$this->setTemplate('verification.tpl');
	}
}
