<?php

class Gensetting extends Controller {

	function Gensetting()
	{
		parent::Controller();
		return;
	}
	
	function index()
	{
		$this->template->set('page_title', 'General Settings');

		/* Default settings */
		$data['row_count'] = 20;

		/* Loading settings from ini file */
		$ini_file = "system/application/config/general.ini";

		/* Check if database ini file exists */
		if (get_file_info($ini_file))
		{
			/* Parsing database ini file */
			$cur_setting = parse_ini_file($ini_file);
			if ($cur_setting)
			{
				$data['row_count'] = isset($cur_setting['row_count']) ? $cur_setting['row_count'] : "20";
			}
		}

		/* Form fields */
		$data['row_count_options'] = array(
			'10' => 10,
			'20' => 20,
			'50' => 50,
			'100' => 100,
			'200' => 200,
		);

		/* Form validations */
		$this->form_validation->set_rules('row_count', 'Row Count', 'trim|required|is_natural_no_zero');

		/* Repopulating form */
		if ($_POST)
		{
			$data['row_count'] = $this->input->post('row_count', TRUE);
		}

		/* Validating form */
		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('admin_template', 'admin/gensetting', $data);
			return;
		}
		else
		{
			$data_row_count = $this->input->post('row_count', TRUE);

			if ($data_row_count < 0 || $data_row_count > 200)
			{
				$this->messages->add('Invalid value for Row Count', 'error');
				$this->template->load('admin_template', 'admin/gensetting');
				return;
			}

			$new_setting = "[general]" . "\r\n" . "row_count = \"" . $data_row_count . "\"" . "\r\n";

			$new_setting_html = '[general]<br />row_count = "' . $data_row_count . '"<br />';

			/* Writing the connection string to end of file - writing in 'a' append mode */
			if ( ! write_file($ini_file, $new_setting))
			{
				$this->messages->add("Failed to update settings file. Please check if \"" . $ini_file . "\" file is writable", 'error');
				$this->messages->add("You can manually create a text file \"" . $ini_file . "\" with the following content :<br /><br />" . $new_setting_html, 'error');
				$this->template->load('admin_template', 'admin/gensetting', $data);
				return;
			} else {
				$this->messages->add('General settings updated successfully', 'success');
				redirect('admin/gensetting');
				return;
			}
		}
		return;
	}
}

/* End of file gensetting.php */
/* Location: ./system/application/controllers/admin/gensetting.php */
