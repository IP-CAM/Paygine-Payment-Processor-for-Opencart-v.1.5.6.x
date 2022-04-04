<?php 
class ControllerPaymentPaygine extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('payment/paygine');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('paygine', $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');
		$this->data['text_on'] = $this->language->get('text_on');
		$this->data['text_off'] = $this->language->get('text_off');
		$this->data['text_seller'] = $this->language->get('text_seller');
		$this->data['text_buyer'] = $this->language->get('text_buyer');
		
		$this->data['entry_sector'] = $this->language->get('entry_sector');
		$this->data['entry_tax'] = $this->language->get('entry_tax');
		$this->data['entry_kkt'] = $this->language->get('entry_kkt');
		$this->data['entry_password'] = $this->language->get('entry_password');
		$this->data['entry_callback'] = $this->language->get('entry_callback');
		$this->data['entry_currency'] = $this->language->get('entry_currency');
		$this->data['entry_commission'] = $this->language->get('entry_commission');
		$this->data['entry_commission_pay'] = $this->language->get('entry_commission_pay');
		$this->data['entry_test'] = $this->language->get('entry_test');
		$this->data['entry_total'] = $this->language->get('entry_total');	
		$this->data['entry_order_status'] = $this->language->get('entry_order_status');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

 		if (isset($this->error['sector'])) {
            $this->data['error_sector'] = $this->error['sector'];
        } else {
            $this->data['error_sector'] = '';
        }

        if (isset($this->error['tax'])) {
            $this->data['error_tax'] = $this->error['tax'];
        } else {
            $this->data['error_tax'] = '';
        }

        if (isset($this->error['kkt'])) {
            $this->data['error_kkt'] = $this->error['kkt'];
        } else {
            $this->data['error_kkt'] = '';
        }

 		if (isset($this->error['password'])) {
			$this->data['error_password'] = $this->error['password'];
		} else {
			$this->data['error_password'] = '';
		}

  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/paygine', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
				
		$this->data['action'] = $this->url->link('payment/paygine', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
		
		if (isset($this->request->post['paygine_sector'])) {
			$this->data['paygine_sector'] = $this->request->post['paygine_sector'];
		} else {
			$this->data['paygine_sector'] = $this->config->get('paygine_sector');
		}

        if (isset($this->request->post['paygine_tax'])) {
            $this->data['paygine_tax'] = $this->request->post['paygine_tax'];
        } else {
            $this->data['paygine_tax'] = $this->config->get('paygine_tax');
        }

        if (isset($this->request->post['paygine_kkt'])) {
            $this->data['paygine_kkt'] = $this->request->post['paygine_kkt'];
        } else {
            $this->data['paygine_kkt'] = $this->config->get('paygine_kkt');
        }
		
		if (isset($this->request->post['paygine_password'])) {
			$this->data['paygine_password'] = $this->request->post['paygine_password'];
		} else {
			$this->data['paygine_password'] = $this->config->get('paygine_password');
		}
		
		$this->data['callback'] = HTTP_CATALOG . 'index.php?route=payment/paygine/callback';

		$this->load->model('localisation/currency');
		
		$this->data['currencies'] = $this->model_localisation_currency->getCurrencies();

		if (isset($this->request->post['paygine_currency'])) {
			$this->data['paygine_currency'] = $this->request->post['paygine_currency'];
		} else {
			$this->data['paygine_currency'] = $this->config->get('paygine_currency');
		}

		if (isset($this->request->post['paygine_commission'])) {
			$this->data['paygine_commission'] = $this->request->post['paygine_commission'];
		} else {
			$this->data['paygine_commission'] = $this->config->get('paygine_commission');
		}

		if (isset($this->request->post['paygine_commission_pay'])) {
			$this->data['paygine_commission_pay'] = $this->request->post['paygine_commission_pay'];
		} else {
			$this->data['paygine_commission_pay'] = $this->config->get('paygine_commission_pay');
		}

		if (isset($this->request->post['paygine_test'])) {
			$this->data['paygine_test'] = $this->request->post['paygine_test'];
		} else {
			$this->data['paygine_test'] = $this->config->get('paygine_test');
		}
		
		if (isset($this->request->post['paygine_total'])) {
			$this->data['paygine_total'] = $this->request->post['paygine_total'];
		} else {
			$this->data['paygine_total'] = $this->config->get('paygine_total');
		} 
				
		if (isset($this->request->post['paygine_order_status_id'])) {
			$this->data['paygine_order_status_id'] = $this->request->post['paygine_order_status_id'];
		} else {
			$this->data['paygine_order_status_id'] = $this->config->get('paygine_order_status_id');
		} 
		
		$this->load->model('localisation/order_status');
		
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['paygine_geo_zone_id'])) {
			$this->data['paygine_geo_zone_id'] = $this->request->post['paygine_geo_zone_id'];
		} else {
			$this->data['paygine_geo_zone_id'] = $this->config->get('paygine_geo_zone_id');
		} 

		$this->load->model('localisation/geo_zone');
										
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['paygine_status'])) {
			$this->data['paygine_status'] = $this->request->post['paygine_status'];
		} else {
			$this->data['paygine_status'] = $this->config->get('paygine_status');
		}
		
		if (isset($this->request->post['paygine_sort_order'])) {
			$this->data['paygine_sort_order'] = $this->request->post['paygine_sort_order'];
		} else {
			$this->data['paygine_sort_order'] = $this->config->get('paygine_sort_order');
		}

		$this->template = 'payment/paygine.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);
				
		$this->response->setOutput($this->render());
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'payment/paygine')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['paygine_sector']) {
            $this->error['sector'] = $this->language->get('error_sector');
        }

        if (!$this->request->post['paygine_tax']) {
            $this->error['tax'] = $this->language->get('error_tax');
        }

        if (!$this->request->post['paygine_kkt']) {
            $this->error['kkt'] = $this->language->get('error_kkt');
        }
		
		if (!$this->request->post['paygine_password']) {
			$this->error['password'] = $this->language->get('error_password');
		}
		
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	
	}
}
?>