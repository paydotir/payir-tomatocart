<?php

class osC_Payment_Payir extends osC_Payment
{
	var $_title, $_code = 'payir', $_status = false, $_sort_order, $_order_id, $_hash_id;

	function osC_Payment_Payir()
	{
		global $osC_Database, $osC_Language, $osC_ShoppingCart;

		$this->_title = $osC_Language->get('payment_payir_title');
		$this->_method_title = $osC_Language->get('payment_payir_method_title');
		$this->_status = (MODULE_PAYMENT_PAYIR_STATUS == '1') ? true : false;
		$this->_sort_order = MODULE_PAYMENT_PAYIR_SORT_ORDER;

		$this->form_action_url = osC_href_link(FILENAME_CHECKOUT, 'process&cmd=send', 'SSL', null, null, true);

		if ($this->_status === true) {

			if ((int) MODULE_PAYMENT_PAYIR_ORDER_STATUS_ID > 0) {

				$this->order_status = MODULE_PAYMENT_PAYIR_ORDER_STATUS_ID;
			}

			if ((int) MODULE_PAYMENT_PAYIR_ZONE > 0) {

				$check_flag = false;

				$Qcheck = $osC_Database->query('SELECT zone_id from :table_zones_to_geo_zones WHERE geo_zone_id = :geo_zone_id AND zone_country_id = :zone_country_id ORDER BY zone_id');

				$Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
				$Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_PAYIR_ZONE);
				$Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
				$Qcheck->execute();

				while ($Qcheck->next()) {

					if ($Qcheck->valueInt('zone_id') < 1) {

						$check_flag = true;
						break;

					} elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {

						$check_flag = true;
						break;
					}
				}

				if ($check_flag === false) {

					$this->_status = false;
				}
			}
		}
	}

	function selection()
	{
		return array (
			'id' => $this->_code,
			'module' => $this->_method_title
		);
	}

	function pre_confirmation_check()
	{
		return false;
	}

	function confirmation()
	{
		global $osC_Language;

		$this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);

		$confirmation = array (
			'title' => $this->_method_title,
			'fields' => array (array ('title' => $osC_Language->get('payment_payir_description')))
		);

		return $confirmation;
	}

	function process_button()
	{
		$process_button_string = osC_draw_hidden_field('order', $this->_order_id);

		return $process_button_string;
	}

	function get_error()
	{
		return false;
	}

	function process()
	{
		global $osC_Currencies, $osC_Database, $osC_ShoppingCart, $osC_Language, $messageStack;

		$this->_order_id = osC_Order::insert(ORDERS_STATUS_PREPARING);
		$this->_hash_id = '4d8c7ee7d12903b4436cb116861d6043';

		if (MODULE_PAYMENT_PAYIR_CURRENCY == 'Selected Currency') {

			$currency = $osC_Currencies->getCode();

		} else {

			$currency = MODULE_PAYMENT_PAYIR_CURRENCY;
		}

		if (isset($_GET['cmd']) && $_GET['cmd'] == 'send') {

			$order_id = isset($_POST['order']) ? $_POST['order'] : null;

			if (isset($this->_order_id) && $this->_order_id == $order_id) {

				if (extension_loaded('curl')) {

					$parameters = array (
						'api' => MODULE_PAYMENT_PAYIR_API,
						'amount' => round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2),
						'redirect' => urlencode(osC_href_link(FILENAME_CHECKOUT, 'process&cmd=verify', 'SSL', null, null, true)),
						'factorNumber' => $this->_order_id
					);

					$result = $this->common(MODULE_PAYMENT_PAYIR_SENDURL, $parameters);
					$result = json_decode($result);

					if (isset($result->status) && $result->status == 1) {

						$Qtransaction = $osC_Database->query('INSERT INTO :table_online_transactions (orders_id, receipt_id, transaction_method, transaction_date, transaction_amount) VALUES (:orders_id, :receipt_id, :transaction_method, now(), :transaction_amount)');

						$Qtransaction->bindTable(':table_online_transactions', TABLE_ONLINE_TRANSACTIONS);
						$Qtransaction->bindInt(':orders_id', $this->_order_id);
						$Qtransaction->bindValue(':receipt_id', $result->transId);
						$Qtransaction->bindValue(':transaction_method', $this->_code);
						$Qtransaction->bindValue(':transaction_amount', round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2));
						$Qtransaction->execute();

						osC_redirect(osC_href_link(MODULE_PAYMENT_PAYIR_GATEWAYURL . $result->transId, null, null, null, true));

					} else {

						$errorCode = isset($result->errorCode) ? $result->errorCode : 'Undefined';
						$errorMessage = isset($result->errorMessage) ? $result->errorMessage : $osC_Language->get('payment_payir_undefined');

						$messageStack->add_session('checkout', $osC_Language->get('payment_payir_request_error') . '<br/><br/>' . $osC_Language->get('payment_payir_error_code') . $errorCode . '<br/>' . $osC_Language->get('payment_payir_error_message') . $errorMessage, 'error');

						osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));
					}

				} else {

					$messageStack->add_session('checkout', $osC_Language->get('payment_payir_curl'), 'error');

					osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));
				}

			} else {

				$messageStack->add_session('checkout', $osC_Language->get('payment_payir_something_wrong'), 'error');

				osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
			}

		} elseif (isset($_GET['cmd']) && $_GET['cmd'] == 'verify') {

			if (isset($_POST['status']) && isset($_POST['transId']) && isset($_POST['factorNumber'])) {

				$status = $_POST['status'];
				$transId = $_POST['transId'];
				$factorNumber = $_POST['factorNumber'];
				$message = $_POST['message'];

				if (isset($status) && $status == 1) {

					$parameters = array (
						'api' => MODULE_PAYMENT_PAYIR_API,
						'transId' => $transId
					);

					$result = $this->common(MODULE_PAYMENT_PAYIR_VERIFYURL, $parameters);
					$result = json_decode($result);

					if (isset($result->status) && $result->status == 1) {

						$amount = round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2);

						if ($amount == $result->amount) {

							$Qupdate = $osC_Database->query('UPDATE :table_online_transactions SET transaction_id = :transaction_id, transaction_date = now() WHERE orders_id = :orders_id AND receipt_id = :receipt_id');

							$Qupdate->bindTable(':table_online_transactions', TABLE_ONLINE_TRANSACTIONS);
							$Qupdate->bindValue(':transaction_id', $transId);
							$Qupdate->bindInt(':orders_id', $this->_order_id);
							$Qupdate->bindValue(':receipt_id', $transId);
							$Qupdate->execute();

							$Qtransaction = $osC_Database->query('INSERT INTO :table_orders_transactions_history (orders_id, transaction_code, transaction_return_value, transaction_return_status, date_added) VALUES (:orders_id, :transaction_code, :transaction_return_value, :transaction_return_status, now())');

							$Qtransaction->bindTable(':table_orders_transactions_history', TABLE_ORDERS_TRANSACTIONS_HISTORY);
							$Qtransaction->bindInt(':orders_id', $this->_order_id);
							$Qtransaction->bindInt(':transaction_code', 1);
							$Qtransaction->bindValue(':transaction_return_value', $transId);
							$Qtransaction->bindInt(':transaction_return_status', 1);
							$Qtransaction->execute();

							$comments = $osC_Language->get('payment_payir_method_authority') . '[' . $transId . ']';

							osC_Order::process($this->_order_id, $this->order_status, $comments);

						} else {

							$messageStack->add_session('checkout', $osC_Language->get('payment_payir_invalid_amount'), 'error');

							osC_Order::remove($this->_order_id);
							osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
						}

					} else {

						$errorCode = isset($result->errorCode) ? $result->errorCode : 'Undefined';
						$errorMessage = isset($result->errorMessage) ? $result->errorMessage : $osC_Language->get('payment_payir_undefined');

						$messageStack->add_session('checkout', $osC_Language->get('payment_payir_verify_error') . '<br/><br/>' . $osC_Language->get('payment_payir_error_code') . $errorCode . '<br/>' . $osC_Language->get('payment_payir_error_message') . $errorMessage, 'error');

						osC_Order::remove($this->_order_id);
						osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
					}

				} else {

					$messageStack->add_session('checkout', $osC_Language->get('payment_payir_invalid_payment'), 'error');

					osC_Order::remove($this->_order_id);
					osC_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
				}

			} else {

				$messageStack->add_session('checkout', $osC_Language->get('payment_payir_invalid_data'), 'error');

				osC_Order::remove($this->_order_id);
				osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
			}

		} else {

			osC_redirect(osC_href_link(FILENAME_CHECKOUT, 'checkout', 'SSL', null, null, true));
		}
	}

	function callback()
	{
		global $osC_Database;
	}

	function common($url, $parameters)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));

		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}
}