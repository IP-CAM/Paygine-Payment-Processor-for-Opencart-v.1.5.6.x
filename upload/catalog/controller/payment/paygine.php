<?php
class ControllerPaymentPaygine extends Controller {
	public function index() {
		$this->language->load('payment/paygine');
    	$this->data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		if (!$this->config->get('paygine_test')){
			$this->data['action'] = 'https://pay.paygine.com/webapi/';
		} else {
			$this->data['action'] = 'https://test.paygine.com/webapi/';
		}

		$currency = array('RUB' => 643, 'USD' => 840, 'EUR' => 978);

		$products = $this->cart->getProducts();
		$product_names = array();
		foreach ($products as $product) {
			$product_names[] = $product['name'] . ' (' . $product['quantity'] . ')';
		}
		$description = $product_names ? implode(', ', $product_names) : '';

		$currency_code = $this->config->get('paygine_currency');
		$total = $this->currency->convert($order_info['total'], $order_info['currency_code'], $currency_code);

		$commission = ($this->config->get('paygine_commission_pay') == 1 && $this->config->get('paygine_commission') && floatval($this->config->get('paygine_commission'))) ? floatval($this->config->get('paygine_commission')) : 0;
		$commission_amount = $total/100*$commission;

        $fiscalPositions='';
        //$KKT = $this->config->get('paygine_kkt');
        $KKT = true;

        $fiscalAmount=0;
        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

        if ($KKT==1){
            $TAX = (strlen($this->config->get('paygine_tax')) > 0 && $this->config->get('paygine_tax') != 0 && $this->config->get('paygine_tax') < 7) ?
                intval($this->config->get('paygine_tax')) : 6;
            if ($TAX > 0 && $TAX < 7){
                $products = $this->cart->getProducts();
                foreach ($products as $product) {
                    $fiscalPositions.=$product['quantity'].';';
                    $elementPrice = $product['price'];
                    $elementPrice = $elementPrice * 100;
                    $fiscalPositions.=$elementPrice.';';
                    $fiscalPositions.=$TAX.';';
                    $fiscalPositions.=$product['name'].'|';

                    $fiscalAmount = $fiscalAmount + (intval($product['quantity'])*intval($elementPrice));
                }
                if ($this->session->data['shipping_method']){
                    $shippingCost = $this->session->data['shipping_method']['cost'];
                    if ($shippingCost){
                        $shippingCost = $shippingCost * 100;
                        $fiscalPositions.='1'.';';
                        $fiscalPositions.= $shippingCost.';';
                        $fiscalPositions.=$TAX.';';
                        $fiscalPositions.='Доставка'.'|';
                        $fiscalAmount = $fiscalAmount + $shippingCost;
                    }
                }

                $amountDiff=abs($fiscalAmount - intval($amount * 100));
                if ($amountDiff!=0){
                    $fiscalPositions.='1'.';';
                    $fiscalPositions.=$amountDiff.';';
                    $fiscalPositions.=$TAX.';';
                    $fiscalPositions.='coupon'.';';
                    $fiscalPositions.='14'.'|';
                    $fiscalAmount = intval($amount * 100);
                }
                $fiscalPositions = substr($fiscalPositions, 0, -1);
            }
        }

        $register_data['amount'] = $this->currency->format($total + $commission_amount, $currency_code, $order_info['currency_value'], false)*100;
        $register_data['sector'] = $this->config->get('paygine_sector');
        $register_data['currency'] = isset($currency[$currency_code]) ? $currency[$currency_code] : 0;


        $signature = base64_encode(md5($register_data['sector'] . $register_data['amount'] . $register_data['currency'] . $this->config->get('paygine_password')));

        $query = http_build_query(array(
            'sector' => $this->config->get('paygine_sector'),
            'reference' => $order_info['order_id'],
            'fiscal_positions' => $fiscalPositions,
            'amount' => $this->currency->format($total + $commission_amount, $currency_code, $order_info['currency_value'], false)*100,
            'description' =>  substr($description, 0, 1000),
            'email' => $order_info['email'],
            'phone' => $order_info['telephone'],
            'currency' => isset($currency[$currency_code]) ? $currency[$currency_code] : 0,
            'mode' => 1,
            'url' => HTTP_SERVER . 'index.php?route=payment/paygine/request',
            'failurl' => HTTP_SERVER . 'index.php?route=checkout/checkout',
            'signature' => $signature
        ));

        $context = stream_context_create(array(
            'http' => array(
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                    . "Content-Length: " . strlen($query) . "\r\n",
                'method'  => 'POST',
                'content' => $query
            )
            //        'ssl' => array(
            //  'verify_peer' => false,
            //     'verify_peer_name' => false
            //  )
        ));

        $old_lvl = error_reporting(0);

        $url = $this->data['action'] . 'Register';


        $paygine_order_id = file_get_contents($url, false, $context);
		$order_id = 0;

		if (is_numeric($paygine_order_id)) {
    		$order_id = (int) $paygine_order_id;
    	} else {
    		$this->data['error'] = $this->language->get('text_error');
    	}

		if ($order_id) {
			$this->data['action'] .= 'Purchase';
			$this->data['sector'] = $this->config->get('paygine_sector');
			$this->data['id'] = $order_id;
			$this->data['signature'] = base64_encode(md5($this->config->get('paygine_sector') . $order_id . $this->config->get('paygine_password')));

			$this->data['commission_text'] = $commission ? sprintf($this->language->get('text_commission'), $commission, '%') : '';

			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paygine.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/paygine.tpl';
			} else {
				$this->template = 'default/template/payment/paygine.tpl';
			}
		} else {
			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paygine_error.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/paygine_error.tpl';
			} else {
				$this->template = 'default/template/payment/paygine_error.tpl';
			}
		}

		$this->render();	
	}

	public function callback() {
		$result = new SimpleXMLElement(http_get_request_body());

		if (isset($result->reason_code) && $result->reason_code == 1) {
    		$this->load->model('checkout/order');

			$this->model_checkout_order->confirm($result->reference, $this->config->get('paygine_order_status_id'));

    		$message = 'Paygine callback.';

			$this->model_checkout_order->update($result->reference, $this->config->get('paygine_order_status_id'), $message, false);
    	}
	}
	
	public function request() {
		$this->language->load('payment/paygine');
	
		$this->data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

		if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
			$this->data['base'] = $this->config->get('config_url');
		} else {
			$this->data['base'] = $this->config->get('config_ssl');
		}
	
		$this->data['language'] = $this->language->get('code');
		$this->data['direction'] = $this->language->get('direction');
	
		$this->data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
		
		$this->data['text_success'] = $this->language->get('text_success');
		$this->data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
		$this->data['text_failure'] = $this->language->get('text_failure');
		$this->data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart', '', 'SSL'));

		$error = false;
		
		if (isset($this->request->get['operation']) && $this->request->get['operation'] && isset($this->request->get['id']) && $this->request->get['id']) {
			//OPERATION
			if (!$this->config->get('paygine_test')){
				$action = 'https://pay.paygine.com/webapi/';
			} else {
				$action = 'https://test.paygine.com/webapi/';
			}

			$operation_data['sector'] = $this->config->get('paygine_sector');
			$operation_data['id'] = $this->request->get['id'];
			$operation_data['operation'] = $this->request->get['operation'];
			$operation_data['signature'] = base64_encode(md5($operation_data['sector'] . $operation_data['id'] . $operation_data['operation'] . $this->config->get('paygine_password')));

			$operation = file_get_contents($action . 'Operation'.'?sector='.$operation_data['sector'].'&id='.$operation_data['id'].'&operation='.$operation_data['operation'].'&signature='.$operation_data['signature']);

			if ($operation) {
				$result = new SimpleXMLElement($operation);

		    	if (isset($result->reason_code) && $result->reason_code == 1) {

		    		$this->load->model('checkout/order');
					$this->model_checkout_order->confirm($this->request->get['reference'], $this->config->get('paygine_order_status_id'));
		    		$message = '';

					if (isset($result->order_id)) {
						$message .= 'order_id: ' . $result->order_id . "\n";
					}
					if (isset($result->order_state)) {
						$message .= 'order_state: ' . $result->order_state . "\n";
					}
					if (isset($result->reference)) {
						$message .= 'reference: ' . $result->reference . "\n";
					}
					if (isset($result->id)) {
						$message .= 'id: ' . $result->id . "\n";
					}	
					if (isset($result->date)) {
						$message .= 'date: ' . $result->date . "\n";
					}	
					if (isset($result->type)) {
						$message .= 'type: ' . $result->type . "\n";
					}	
					if (isset($result->state)) {
						$message .= 'state: ' . $result->state . "\n";
					}	
					if (isset($result->reason_code)) {
						$message .= 'reason_code: ' . $result->reason_code . "\n";
					}
					if (isset($result->message)) {
						$message .= 'message: ' . $result->message . "\n";
					}
					if (isset($result->name)) {
						$message .= 'name: ' . $result->name . "\n";
					}	
					if (isset($result->pan)) {
						$message .= 'pan: ' . $result->pan . "\n";
					}		
					if (isset($result->email)) {
						$message .= 'email: ' . $result->email . "\n";
					}
					if (isset($result->amount)) {
						$message .= 'amount: ' . $result->amount . "\n";
					}	
					if (isset($result->approval_code)) {
						$message .= 'approval_code: ' . $result->approval_code . "\n";
					}	

					$this->model_checkout_order->update($this->request->get['reference'], $this->config->get('paygine_order_status_id'), $message, false);
			
					$this->data['continue'] = $this->url->link('checkout/success');

					header('Location: ' . $this->data['continue']);
					
					if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paygine_success.tpl')) {
						$this->template = $this->config->get('config_template') . '/template/payment/paygine_success.tpl';
					} else {
						$this->template = 'default/template/payment/paygine_success.tpl';
					}	
			
					$this->response->setOutput($this->render());				

		    	} elseif (isset($result->reason_code)) {
		    		$error = true;
		    		if (isset($result->reason_code)) {
		    			$this->data['text_failure'] .= '<br>' . $this->language->get('text_reason_' . (int)$result->reason_code);
		    		}
		    	} else {
		    		$error = true;
		    		$this->data['text_failure'] .= '<br>' . sprintf($this->language->get('text_error_code'), isset($result->code) ? $result->code : '');
		    	}
			} else {
				$error = true;
			}

		} else {
			$error = true;
		}

		if ($error) {
			$this->data['continue'] = $this->url->link('checkout/cart');

			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/paygine_failure.tpl')) {
				$this->template = $this->config->get('config_template') . '/template/payment/paygine_failure.tpl';
			} else {
				$this->template = 'default/template/payment/paygine_failure.tpl';
			}
			
			$this->response->setOutput($this->render());
		}

	}
}
?>