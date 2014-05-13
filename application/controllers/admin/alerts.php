<?php
class Alerts extends CI_Controller {

	public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('user');
		$this->load->library('pagination');
		$this->load->model('Alerts_model');
		$this->load->model('Messages_model');
		$this->load->model('Staffs_model');
	}

	public function index() {
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
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
		
		if ($this->user->getStaffId()) {
			$filter['staff_id'] = (int)$this->user->getStaffId();
		}

		$data['heading'] 			= 'Alerts';
		$data['button_delete'] 		= 'Delete';
		$data['text_empty'] 		= 'There are no alerts available.';

		//load ratings data into array
		$data['alerts'] = array();
		$results = $this->Alerts_model->getList($filter);
		foreach ($results as $result) {					
			$data['alerts'][] = array(
				'message_id'	=> $result['message_id'],
				'date'			=> mdate('%d %M %y - %H:%i', strtotime($result['date'])),
				'sender'		=> $result['staff_name'],
				'subject' 		=> $result['subject'],
				'body' 			=> substr(strip_tags(html_entity_decode($result['body'], ENT_QUOTES, 'UTF-8')), 0, 100) . '..',
				'view'			=> site_url('admin/alerts/view?id=' . $result['message_id'])
			);
		}
		
		$config['base_url'] 		= site_url('admin/alerts').$url;
		$config['total_rows'] 		= $this->Alerts_model->record_count($filter);
		$config['per_page'] 		= $filter['limit'];
		
		$this->pagination->initialize($config);

		$data['pagination'] = array(
			'info'		=> $this->pagination->create_infos(),
			'links'		=> $this->pagination->create_links()
		);

		if ($this->input->post('delete') && $this->_deleteMessage() === TRUE) {
			redirect('admin/alerts');
		}	

		$regions = array('header', 'footer');
		if (file_exists(APPPATH .'views/themes/admin/'.$this->config->item('admin_theme').'alerts.php')) {
			$this->template->render('themes/admin/'.$this->config->item('admin_theme'), 'alerts', $regions, $data);
		} else {
			$this->template->render('themes/admin/default/', 'alerts', $regions, $data);
		}
	}

	public function view() {
		
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		//check if customer_id is set in uri string
		if (is_numeric($this->input->get('id'))) {
			$message_id = (int)$this->input->get('id');
		} else {
		    redirect('admin/alerts');
		}

		$message_info = $this->Messages_model->viewAdminMessage($message_id);
		
		if ($message_info) {
			$data['heading'] 		= 'Alerts';
			$data['sub_menu_back'] 	= site_url('admin/alerts');

			if (!empty($message_info['receiver'])) {
				$receiver = $this->Staffs_model->getStaff($message_info['receiver']);
				$receiver = $receiver['staff_name'];
			} else {
				$receiver = 'Customers';
			}
			
			$data['message_id'] 	= $message_info['message_id'];
			$data['date'] 			= mdate('%d %M %y - %H:%i', strtotime($message_info['date']));
			$data['sender'] 		= $message_info['staff_name'];
			$data['receiver'] 		= $receiver;
			$data['subject'] 		= $message_info['subject'];
			$data['body'] 			= $message_info['body'];
		}		

		$regions = array('header', 'footer');
		if (file_exists(APPPATH .'views/themes/admin/'.$this->config->item('admin_theme').'alerts_view.php')) {
			$this->template->render('themes/admin/'.$this->config->item('admin_theme'), 'alerts_view', $regions, $data);
		} else {
			$this->template->render('themes/admin/default/', 'alerts_view', $regions, $data);
		}
	}

	public function _deleteMessage($menu_id = FALSE) {
    	if (!$this->user->hasPermissions('modify', 'admin/messages')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to delete!</p>');
    	} else { 
			if (is_array($this->input->post('delete'))) {
				foreach ($this->input->post('delete') as $key => $value) {
					$message_id = $value;
					$this->Messages_model->deleteMessage($message_id);
				}			
			
				$this->session->set_flashdata('alert', '<p class="success">Message(s) Deleted Sucessfully!</p>');
			}
		}
				
		return TRUE;
	}
}

/* End of file alerts.php */
/* Location: ./application/controllers/admin/alerts.php */