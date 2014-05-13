<?php
class Customers extends CI_Controller {

	public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('user');
		$this->load->library('pagination');
		$this->load->model('Customers_model');
		$this->load->model('Countries_model');
		$this->load->model('Security_questions_model');
		$this->load->model('Locations_model');
		$this->load->model('Activity_model');
	}

	public function index() {
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/customers')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		$url = '?';
		$filter = array();
		if ($this->input->get('page')) {
			$filter['page'] = (int) $this->input->get('page');
		} else {
			$filter['page'] = '';
		}
		
		if ($this->config->item('page_limit')) {
			$filter['limit'] = $this->config->item('page_limit');
		}
				
		if ($this->input->get('filter_search')) {
			$filter['filter_search'] = $this->input->get('filter_search');
			$data['filter_search'] = $filter['filter_search'];
			$url .= 'filter_search='.$filter['filter_search'].'&';
		} else {
			$data['filter_search'] = '';
		}
		
		if ($this->input->get('filter_date')) {
			$filter['filter_date'] = $this->input->get('filter_date');
			$data['filter_date'] = $filter['filter_date'];
			$url .= 'filter_date='.$filter['filter_date'].'&';
		} else {
			$filter['filter_date'] = '';
			$data['filter_date'] = '';
		}
		
		if (is_numeric($this->input->get('filter_status'))) {
			$filter['filter_status'] = $this->input->get('filter_status');
			$data['filter_status'] = $filter['filter_status'];
			$url .= 'filter_status='.$filter['filter_status'].'&';
		} else {
			$filter['filter_status'] = '';
			$data['filter_status'] = '';
		}
		
		if ($this->input->get('sort_by')) {
			$filter['sort_by'] = $this->input->get('sort_by');
			$data['sort_by'] = $filter['sort_by'];
		} else {
			$filter['sort_by'] = '';
			$data['sort_by'] = '';
		}
		
		if ($this->input->get('order_by')) {
			$filter['order_by'] = $this->input->get('order_by');
			$data['order_by_active'] = strtolower($this->input->get('order_by')) .' active';
			$data['order_by'] = strtolower($this->input->get('order_by'));
		} else {
			$filter['order_by'] = '';
			$data['order_by_active'] = '';
			$data['order_by'] = 'desc';
		}
		
		$data['heading'] 			= 'Customers';
		$data['button_add'] 		= 'New';
		$data['button_delete'] 		= 'Delete';
		$data['text_empty'] 		= 'There are no customers available.';

		$order_by = (isset($filter['order_by']) AND $filter['order_by'] == 'DESC') ? 'ASC' : 'DESC';
		$data['sort_first'] 		= site_url('admin/customers'.$url.'sort_by=first_name&order_by='.$order_by);
		$data['sort_last'] 			= site_url('admin/customers'.$url.'sort_by=last_name&order_by='.$order_by);
		$data['sort_email'] 		= site_url('admin/customers'.$url.'sort_by=email&order_by='.$order_by);
		$data['sort_date'] 			= site_url('admin/customers'.$url.'sort_by=date_added&order_by='.$order_by);
		$data['sort_id'] 			= site_url('admin/customers'.$url.'sort_by=customer_id&order_by='.$order_by);

		$data['customers'] = array();
		$results = $this->Customers_model->getList($filter);
		foreach ($results as $result) {
			
			$data['customers'][] = array(
				'customer_id' 		=> $result['customer_id'],
				'first_name' 		=> $result['first_name'],
				'last_name'			=> $result['last_name'],
				'email' 			=> $result['email'],
				'telephone' 		=> $result['telephone'],
				'date_added' 		=> mdate('%d %M %y', strtotime($result['date_added'])),
				'status' 			=> ($result['status'] === '1') ? 'Enabled' : 'Disabled',
				'edit' 				=> site_url('admin/customers/edit?id=' . $result['customer_id'])
			);
		}

		$data['questions'] = array();
		$results = $this->Security_questions_model->getQuestions();
		foreach ($results as $result) {
			$data['questions'][] = array(
				'id'	=> $result['question_id'],
				'text'	=> $result['text']
			);
		}

		$data['country_id'] = $this->config->item('country_id');
		$data['countries'] = array();
		$results = $this->Countries_model->getCountries(); 										// retrieve countries array from getCountries method in locations model
		foreach ($results as $result) {															// loop through crountries array
			$data['countries'][] = array( 														// create array of countries data to pass to view
				'country_id'	=>	$result['country_id'],
				'name'			=>	$result['country_name'],
			);
		}

		$data['customer_dates'] = array();
		$customer_dates = $this->Customers_model->getCustomerDates();
		foreach ($customer_dates as $customer_date) {
			$month_year = '';
			$month_year = $customer_date['year'].'-'.$customer_date['month'];
			$data['customer_dates'][$month_year] = mdate('%F %Y', strtotime($customer_date['date_added']));
		}

		if (!empty($filter['sort_by']) AND !empty($filter['order_by'])) {
			$url .= 'sort_by='.$filter['sort_by'].'&';
			$url .= 'order_by='.$filter['order_by'].'&';
		}
		
		$config['base_url'] 		= site_url('admin/customers').$url;
		$config['total_rows'] 		= $this->Customers_model->record_count($filter);
		$config['per_page'] 		= $filter['limit'];
		
		$this->pagination->initialize($config);
		
		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		if ($this->input->post('delete') && $this->_deleteCustomer() === TRUE) {
			
			redirect('admin/customers');  			
		}	

		$regions = array('header', 'footer');
		if (file_exists(APPPATH .'views/themes/admin/'.$this->config->item('admin_theme').'customers.php')) {
			$this->template->render('themes/admin/'.$this->config->item('admin_theme'), 'customers', $regions, $data);
		} else {
			$this->template->render('themes/admin/default/', 'customers', $regions, $data);
		}
	}
	
	public function edit() {
		$this->load->model('Orders_model');
		
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/customers')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		$orders_filter = array();
		if ($this->input->get('page')) {
			$orders_filter['page'] = (int) $this->input->get('page');
		} else {
			$orders_filter['page'] = 1;
		}
		
		if ($this->config->item('page_limit')) {
			$orders_filter['limit'] = $this->config->item('page_limit');
		} else {
			$orders_filter['limit'] = '';
		}
				
		if (is_numeric($this->input->get('id'))) {
			$customer_id = $this->input->get('id');
			$data['action']	= site_url('admin/customers/edit?id='. $customer_id);
			$orders_filter['customer_id'] = $this->input->get('id');
		} else {
		    $customer_id = 0;
			$data['action']	= site_url('admin/customers/edit');
			$orders_filter['customer_id'] = 0;
		}
		
		$customer_info = $this->Customers_model->getCustomer($customer_id);
		$data['heading'] 			= 'Customers - '. $customer_info['first_name'] .' '. $customer_info['last_name'];
		$data['button_save'] 		= 'Save';
		$data['button_save_close'] 	= 'Save & Close';
		$data['sub_menu_back'] 		= site_url('admin/customers');
		$data['text_empty'] 		= 'There are no order available for this customer.';
		$data['text_empty_activity'] = 'This customer has no recent activity.';
	
		$data['first_name'] 		= $customer_info['first_name'];
		$data['last_name'] 			= $customer_info['last_name'];
		$data['email'] 				= $customer_info['email'];
		$data['telephone'] 			= $customer_info['telephone'];
		$data['security_question'] 	= $customer_info['security_question_id'];
		$data['security_answer'] 	= $customer_info['security_answer'];
		$data['status'] 			= $customer_info['status'];
		
		if ($this->input->post('address')) {
			$data['addresses'] 			= $this->input->post('address');
		} else {
			$data['addresses'] 			= $this->Customers_model->getCustomerAddresses($customer_id);
		}
		
		$data['questions'] = array();
		$results = $this->Security_questions_model->getQuestions();
		foreach ($results as $result) {
			$data['questions'][] = array(
				'id'	=> $result['question_id'],
				'text'	=> $result['text']
			);
		}

		$data['country_id'] = $this->config->item('country_id');
		$data['countries'] = array();
		$results = $this->Countries_model->getCountries(); 										// retrieve countries array from getCountries method in locations model
		foreach ($results as $result) {															// loop through crountries array
			$data['countries'][] = array( 														// create array of countries data to pass to view
				'country_id'	=>	$result['country_id'],
				'name'			=>	$result['country_name'],
			);
		}

		$results = $this->Orders_model->getCustomerOrders($orders_filter);
		$data['orders'] = array();
		foreach ($results as $result) {					
			$current_date = mdate('%d-%m-%Y', time());
			$date_added = mdate('%d-%m-%Y', strtotime($result['date_added']));
			
			if ($current_date === $date_added) {
				$date_added = 'Today';
			} else {
				$date_added = mdate('%d %M %y', strtotime($date_added));
			}
			
			$data['orders'][] = array(
				'order_id'			=> $result['order_id'],
				'location_name'		=> $result['location_name'],
				'first_name'		=> $result['first_name'],
				'last_name'			=> $result['last_name'],
				'order_type' 		=> ($result['order_type'] === '1') ? 'Delivery' : 'Collection',
				'order_time'		=> mdate('%H:%i', strtotime($result['order_time'])),
				'order_status'		=> $result['status_name'],
				'date_added'		=> $date_added,
				'edit' 				=> site_url('admin/orders/edit?id=' . $result['order_id'])
			);
		}
			
		$config['base_url'] 		= site_url('admin/customers/edit'.'?id='. $customer_id .'&');
		$config['total_rows'] 		= $this->Orders_model->customer_record_count($orders_filter);
		$config['per_page'] 		= $orders_filter['limit'];
		
		$this->pagination->initialize($config);
				
		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		$activities = $this->Activity_model->getCustomerActivities($customer_id);
		$data['activities'] = array();
		foreach ($activities as $activity) {
			$data['activities'][] = array(
				'activity_id'		=> $activity['activity_id'],
				'ip_address' 		=> $activity['ip_address'],
				'customer_name'		=> ($activity['customer_id']) ? $activity['first_name'] .' '. $activity['last_name'] : 'Guest',
				'access_type'		=> ucwords($activity['access_type']),
				'browser'			=> $activity['browser'],
				'request_uri'		=> (!empty($activity['request_uri'])) ? $activity['request_uri'] : '--',
				'referrer_uri'		=> (!empty($activity['referrer_uri'])) ? $activity['referrer_uri'] : '--',
				'date_added'		=> mdate('%d %M %y - %H:%i', strtotime($activity['date_added'])),
				'blacklist' 		=> site_url('admin/customers_activity/blacklist?ip=' . $activity['ip_address'])
			);
		}
			
		if ($this->input->post() && $this->_addCustomer() === TRUE) {
			redirect('admin/customers');
		}

		if ($this->input->post() && $this->_updateCustomer($data['email']) === TRUE) {
			if ($this->input->post('save_close') === '1') {
				redirect('admin/customers');
			}
			
			redirect('admin/customers/edit?id='. $customer_id);
		}
		
		$regions = array('header', 'footer');
		if (file_exists(APPPATH .'views/themes/admin/'.$this->config->item('admin_theme').'customers_edit.php')) {
			$this->template->render('themes/admin/'.$this->config->item('admin_theme'), 'customers_edit', $regions, $data);
		} else {
			$this->template->render('themes/admin/default/', 'customers_edit', $regions, $data);
		}
	}

	public function autocomplete() {
		$json = array();
		
		if ($this->input->get('customer_name')) {
			$filter_data = array();
			$filter_data = array(
				'customer_name' => urldecode($this->input->get('customer_name'))
			);
		}
		
		$results = $this->Customers_model->getAutoComplete($filter_data);

		if ($results) {
			foreach ($results as $result) {
				$json[] = array(
					'customer_id' 		=> $result['customer_id'],
					'customer_name' 	=> $result['first_name'] .' '. $result['last_name']
				);
			}
		}
		
		$this->output->set_output(json_encode($json));
	}
	
	public function _addCustomer() {
						
    	if ( ! $this->user->hasPermissions('modify', 'admin/customers')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to add!</p>');
  			return TRUE;
    	} else if ( ! $this->input->get('id') AND $this->validateForm() === TRUE) {
			$add = array();
			
			//store post values
			$add['first_name'] 				= $this->input->post('first_name');
			$add['last_name'] 				= $this->input->post('last_name');
			$add['email'] 					= $this->input->post('email');
			$add['password'] 				= $this->input->post('password');
			$add['telephone'] 				= $this->input->post('telephone');
			$add['security_question_id'] 	= $this->input->post('security_question');
			$add['security_answer'] 		= $this->input->post('security_answer');
			$add['date_added'] 				= mdate('%Y-%m-%d', time());
			$add['status']					= $this->input->post('status');
			$add['address']					= $this->input->post('address');

			//add into database
			if ($this->Customers_model->addCustomer($add)) {	
				$this->session->set_flashdata('alert', '<p class="success">Customer Added Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Added!</p>');
			}
			
			return TRUE;		
		}
	}

	public function _updateCustomer($customer_email) {
						
    	if ( ! $this->user->hasPermissions('modify', 'admin/customers')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to update!</p>');
  			return TRUE;
    	} else if ($this->input->get('id') AND $this->validateForm($customer_email) === TRUE) {
			$update = array();
			
			$update['customer_id'] 			= $this->input->get('id');
			$update['first_name'] 			= $this->input->post('first_name');
			$update['last_name'] 			= $this->input->post('last_name');
			$update['telephone'] 			= $this->input->post('telephone');
			$update['security_question_id'] = $this->input->post('security_question');
			$update['security_answer']		= $this->input->post('security_answer');
			$update['status']				= $this->input->post('status');
			$update['address']				= $this->input->post('address');
			
			if ($customer_email !== $this->input->post('email')) {
				$update['email']	= $this->input->post('email');
			}

			if ($this->input->post('password')) {
				$update['password'] = $this->input->post('password');
			}

			if ($this->Customers_model->updateCustomer($update)) {
				$this->session->set_flashdata('alert', '<p class="success">Customer Updated Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');	
			}
			
			return TRUE;
		}
	}

	public function _deleteCustomer($customer_id = FALSE) {
    	if (!$this->user->hasPermissions('modify', 'admin/menus')) {
		
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to delete!</p>');
    	
    	} else { 
			if (is_array($this->input->post('delete'))) {
				foreach ($this->input->post('delete') as $key => $value) {
					$customer_id = $value;
					$this->Customers_model->deleteCustomer($customer_id);
				}			
			
				$this->session->set_flashdata('alert', '<p class="success">Customer(s) Deleted Sucessfully!</p>');
			}
		}
				
		return TRUE;
	}
	
	public function validateForm($customer_email = FALSE) {
		$this->form_validation->set_rules('first_name', 'First Name', 'xss_clean|trim|required|min_length[2]|max_length[12]');
		$this->form_validation->set_rules('last_name', 'First Name', 'xss_clean|trim|required|min_length[2]|max_length[12]');

		if ($customer_email !== $this->input->post('email')) {
			$this->form_validation->set_rules('email', 'Email', 'xss_clean|trim|required|valid_email|max_length[96]|is_unique[customers.email]');
		}

		if ($this->input->post('password')) {
			$this->form_validation->set_rules('password', 'Password', 'xss_clean|trim|required|min_length[6]|max_length[32]|matches[confirm_password]');
			$this->form_validation->set_rules('confirm_password', 'Confirm Password', 'xss_clean|trim|required|md5');
		}
		
		$this->form_validation->set_rules('telephone', 'Telephone', 'xss_clean|trim|required|integer');
		$this->form_validation->set_rules('security_question', 'Security Question', 'xss_clean|trim|required|integer');
		$this->form_validation->set_rules('security_answer', 'Security Answer', 'xss_clean|trim|required|min_length[2]');
		$this->form_validation->set_rules('status', 'Status', 'xss_clean|trim|required|integer');

		if ($this->input->post('address')) {
			foreach ($this->input->post('address') as $key => $value) {
				$this->form_validation->set_rules('address['.$key.'][address_1]', 'Address 1 ['.$key.']', 'xss_clean|trim|required|min_length[3]|max_length[128]');
				$this->form_validation->set_rules('address['.$key.'][city]', 'City ['.$key.']', 'xss_clean|trim|required|min_length[2]|max_length[128]');
				$this->form_validation->set_rules('address['.$key.'][postcode]', 'Postcode ['.$key.']', 'xss_clean|trim|required|min_length[2]|max_length[10]');
				$this->form_validation->set_rules('address['.$key.'][country_id]', 'Country ['.$key.']', 'xss_clean|trim|required|integer');
			}
		}

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}		
	}
}

/* End of file customers.php */
/* Location: ./application/controllers/admin/customers.php */