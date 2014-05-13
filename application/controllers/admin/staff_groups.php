<?php
class Staff_groups extends CI_Controller {

	public function __construct() {
		parent::__construct(); //  calls the constructor
		$this->load->library('user');
		$this->load->model('Staff_groups_model');
	}

	public function index() {
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/staff_groups')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		$data['heading'] 			= 'Staff Groups';
		$data['button_add'] 		= 'New';
		$data['button_delete'] 		= 'Delete';
		$data['text_empty'] 		= 'There is no staff group available.';

		//load ratings data into array
		$data['staff_groups'] = array();
		$results = $this->Staff_groups_model->getStaffGroups();
		foreach ($results as $result) {					
			$data['staff_groups'][] = array(
				'staff_group_id'		=>	$result['staff_group_id'],
				'staff_group_name'		=>	$result['staff_group_name'],
				'edit'					=> site_url('admin/staff_groups/edit?id=' . $result['staff_group_id'])
			);
		}
		
		$ignore_path = array(
			'admin/login',
			'admin/logout',
			'admin/dashboard',
			'common/forgotten'
		);

		$files = glob(APPPATH .'/controllers/admin/*.php');
		$extension_files = glob(APPPATH .'extensions/admin/controllers/*.php');
	
		$paths = array();
		foreach (array_merge($files, $extension_files) as $file) {
			$file_name = 'admin/'. basename($file, '.php');

			if (!in_array($file_name, $ignore_path)) {
				$paths[] = $file_name;
			}
		}
		
		$data['paths'] = $paths;

		if ($this->input->post('delete') && $this->_deleteStaffGroup() === TRUE) {
			
		    redirect('admin/staff_groups');
		}	

		$regions = array('header', 'footer');
		if (file_exists(APPPATH .'views/themes/admin/'.$this->config->item('admin_theme').'staff_groups.php')) {
			$this->template->render('themes/admin/'.$this->config->item('admin_theme'), 'staff_groups', $regions, $data);
		} else {
			$this->template->render('themes/admin/default/', 'staff_groups', $regions, $data);
		}
	}

	public function edit() {
			
		if (!$this->user->islogged()) {  
  			redirect('admin/login');
		}

    	if (!$this->user->hasPermissions('access', 'admin/staff_groups')) {
  			redirect('admin/permission');
		}
		
		if ($this->session->flashdata('alert')) {
			$data['alert'] = $this->session->flashdata('alert');  // retrieve session flashdata variable if available
		} else {
			$data['alert'] = '';
		}

		if (is_numeric($this->input->get('id'))) {
			$staff_group_id = (int)$this->input->get('id');
			$data['action']	= site_url('admin/staff_groups/edit?id='. $staff_group_id);
		} else {
		    $staff_group_id = 0;
			$data['action']	= site_url('admin/staff_groups/edit');
		}

		$result = $this->Staff_groups_model->getStaffGroup($staff_group_id);
		
		$data['heading'] 			= 'Staff Group - '. $result['staff_group_name'];
		$data['button_save'] 		= 'Save';
		$data['button_save_close'] 	= 'Save & Close';
		$data['sub_menu_back'] 		= site_url('admin/staff_groups');

		if (isset($this->input->post['staff_group_name'])) {
			$data['staff_group_name'] = $this->input->post['staff_group_name'];
		} elseif (isset($result['staff_group_name'])) {
			$data['staff_group_name'] = $result['staff_group_name'];
		} else { 
			$data['staff_group_name'] = '';
		}

		if ($this->input->post('permission')) {
			$permission = $this->input->post('permission');
		} else if (isset($result['permission'])) {
			$permission = unserialize($result['permission']);
		}
		
		if (isset($permission['access'])) {
			$data['access'] = $permission['access'];
		} else { 
			$data['access'] = array();
		}

		if (isset($permission['modify'])) {
			$data['modify'] = $permission['modify'];
		} else { 
			$data['modify'] = array();
		}
	
		$ignore_path = array(
			'admin/login',
			'admin/logout',
			'admin/permission',
			'admin/dashboard',
			'admin/alerts'
		);

		$files = glob(APPPATH .'/controllers/admin/*.php');
		$extension_files = glob(APPPATH .'extensions/admin/controllers/*.php');

		$paths = array();
		foreach (array_merge($files, $extension_files) as $file) {
			$file_name = 'admin/'. basename($file, '.php');

			if (!in_array($file_name, $ignore_path)) {
				$paths[] = $file_name;
			}
		}
		
		sort($paths);
		$data['paths'] = $paths;

		if ($this->input->post() && $this->_addStaffGroup() === TRUE) {
		    redirect('admin/staff_groups');
		}

		if ($this->input->post() && $this->_updateStaffGroup() === TRUE) {
			if ($this->input->post('save_close') === '1') {
				redirect('admin/staff_groups');
			}
			
			redirect('admin/staff_groups/edit?id='. $staff_group_id);
		}
		
		$regions = array('header', 'footer');
		if (file_exists(APPPATH .'views/themes/admin/'.$this->config->item('admin_theme').'staff_groups_edit.php')) {
			$this->template->render('themes/admin/'.$this->config->item('admin_theme'), 'staff_groups_edit', $regions, $data);
		} else {
			$this->template->render('themes/admin/default/', 'staff_groups_edit', $regions, $data);
		}
	}

	public function _addStaffGroup() {
    	if (!$this->user->hasPermissions('modify', 'admin/staff_groups')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to add!</p>');
  			return TRUE;
    	} else if ( ! $this->input->get('id') AND $this->validateForm() === TRUE) { 
			$add = array();

			$add['staff_group_name']	= $this->input->post('staff_group_name');
			
			if ($this->input->post('permission')) {
				$add['permission'] = serialize($this->input->post('permission'));
			} else {
				$add['permission'] = serialize(array('EMPTY'));
			}

			if ($this->Staff_groups_model->addStaffGroup($add)) { // calls model to save data to SQL
				$this->session->set_flashdata('alert', '<p class="success">Staff Groups Added Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');
			}
		
			return TRUE;
		}
	}

	public function _updateStaffGroup() {
    	if (!$this->user->hasPermissions('modify', 'admin/staff_groups')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to update!</p>');
  			return TRUE;
    	} else if ($this->input->get('id') AND $this->validateForm() === TRUE) { 
			$update = array();

			$update['staff_group_id']		= $this->input->get('id');
			$update['staff_group_name']		= $this->input->post('staff_group_name');
		
			if ($this->input->post('permission')) {
				$update['permission'] = serialize($this->input->post('permission'));
			} else {
				$update['permission'] = serialize(array());
			}

			if ($this->Staff_groups_model->updateStaffGroup($update)) { // calls model to save data to SQL
				$this->session->set_flashdata('alert', '<p class="success">Staff Group Updated Sucessfully!</p>');
			} else {
				$this->session->set_flashdata('alert', '<p class="warning">Nothing Updated!</p>');
			}
		
			return TRUE;
		}
	}	

	public function _deleteStaffGroup() {
    	if (!$this->user->hasPermissions('modify', 'admin/staff_groups')) {
			$this->session->set_flashdata('alert', '<p class="warning">Warning: You do not have permission to delete!</p>');
    	} else { 
			if (is_array($this->input->post('delete'))) {
				foreach ($this->input->post('delete') as $key => $value) {
					$staff_group_id = $value;
					$this->Staff_groups_model->deleteStaffGroup($staff_group_id);
				}			
			
				$this->session->set_flashdata('alert', '<p class="success">Staff Group(s) Deleted Sucessfully!</p>');
			}
		}
				
		return TRUE;
	}
	
	public function validateForm() {
		$this->form_validation->set_rules('staff_group_name', 'Staff Group', 'xss_clean|trim|required|min_length[2]|max_length[32]');
		$this->form_validation->set_rules('permission[access][]', 'Access Permission', 'xss_clean|trim');
		$this->form_validation->set_rules('permission[modify][]', 'Modify Permission', 'xss_clean|trim');

		if ($this->form_validation->run() === TRUE) {
			return TRUE;
		} else {
			return FALSE;
		}		
	}
}

/* End of file staff_groups.php */
/* Location: ./application/controllers/admin/staff_groups.php */