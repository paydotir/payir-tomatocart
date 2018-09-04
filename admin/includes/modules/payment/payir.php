<?php

class osC_Payment_Payir extends osC_Payment_Admin
{
	var $_title;
	var $_code = 'payir';
	var $_author_name = 'Pay.ir';
	var $_author_www = 'https://pay.ir/';
	var $_status = false;

	function osC_Payment_Payir()
	{
		global $osC_Language;

		$this->_title = $osC_Language->get('payment_payir_title');
		$this->_description = $osC_Language->get('payment_payir_description');
		$this->_method_title = $osC_Language->get('payment_payir_method_title');
		$this->_status = (defined('MODULE_PAYMENT_PAYIR_STATUS') && (MODULE_PAYMENT_PAYIR_STATUS == '1') ? true : false);
		$this->_sort_order = (defined('MODULE_PAYMENT_PAYIR_SORT_ORDER') ? MODULE_PAYMENT_PAYIR_SORT_ORDER : null);
	}

	function isInstalled() {

		return (bool)defined('MODULE_PAYMENT_PAYIR_STATUS');
	}

    function install()
	{
		global $osC_Database, $osC_Language;

		parent::install();

		$osC_Database->simpleQuery("CREATE TABLE IF NOT EXISTS `" . DB_TABLE_PREFIX . "online_transactions` (`id` int(10) unsigned NOT NULL auto_increment, `orders_id` int(10) default NULL, `receipt_id` varchar(60) default NULL, `transaction_method` varchar(60) default NULL, `transaction_date` datetime default NULL, `transaction_amount` varchar(20) default NULL, `transaction_id` varchar(60) default NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('" . $osC_Language->get('payment_payir_status_title') . "', 'MODULE_PAYMENT_PAYIR_STATUS', '-1', '" . $osC_Language->get('payment_payir_status_description') . "', '6', '0', 'osc_cfg_use_get_boolean_value', 'osc_cfg_set_boolean_value(array(1, -1))', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_payir_api_title') . "', 'MODULE_PAYMENT_PAYIR_API', '', '" .  $osC_Language->get('payment_payir_api_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_payir_sendurl_title') . "', 'MODULE_PAYMENT_PAYIR_SENDURL', 'https://pay.ir/payment/send', '" .  $osC_Language->get('payment_payir_sendurl_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_payir_verifyurl_title') . "', 'MODULE_PAYMENT_PAYIR_VERIFYURL', 'https://pay.ir/payment/verify', '" .  $osC_Language->get('payment_payir_verifyurl_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_payir_gatewayurl_title') . "', 'MODULE_PAYMENT_PAYIR_GATEWAYURL', 'https://pay.ir/payment/gateway/', '" .  $osC_Language->get('payment_payir_gatewayurl_description') . "', '6', '0', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . $osC_Language->get('payment_payir_currency_title') . "', 'MODULE_PAYMENT_PAYIR_CURRENCY', 'IRR', '" . $osC_Language->get('payment_payir_currency_description') . "', '6', '0', 'osc_cfg_set_boolean_value(array(\'Selected Currency\',\'IRR\'))', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) VALUES ('" . $osC_Language->get('payment_payir_zone_title') . "', 'MODULE_PAYMENT_PAYIR_ZONE', '0', '" . $osC_Language->get('payment_payir_zone_description') . "', '6', '0', 'osc_cfg_use_get_zone_class_title', 'osc_cfg_set_zone_classes_pull_down_menu', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) VALUES ('" . $osC_Language->get('payment_payir_order_title') . "', 'MODULE_PAYMENT_PAYIR_ORDER_STATUS_ID', '0', '" . $osC_Language->get('payment_payir_order_description') . "', '6', '0', 'osc_cfg_set_order_statuses_pull_down_menu', 'osc_cfg_use_get_order_status_title', now())");

		$osC_Database->simpleQuery("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) VALUES ('" . $osC_Language->get('payment_payir_sort_title') . "', 'MODULE_PAYMENT_PAYIR_SORT_ORDER', '0', '" . $osC_Language->get('payment_payir_sort_description') . "', '6', '0', now())");
	}

	function getKeys()
	{
		if (!isset($this->_keys)) {

        $this->_keys = array (
			'MODULE_PAYMENT_PAYIR_STATUS',
			'MODULE_PAYMENT_PAYIR_API',
			'MODULE_PAYMENT_PAYIR_SENDURL',
			'MODULE_PAYMENT_PAYIR_VERIFYURL',
			'MODULE_PAYMENT_PAYIR_GATEWAYURL',
			'MODULE_PAYMENT_PAYIR_CURRENCY',
			'MODULE_PAYMENT_PAYIR_ZONE',
			'MODULE_PAYMENT_PAYIR_ORDER_STATUS_ID',
			'MODULE_PAYMENT_PAYIR_SORT_ORDER');
		}

		return $this->_keys;
	}	
}