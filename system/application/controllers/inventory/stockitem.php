<?php

class StockItem extends Controller {

	function StockItem()
	{
		parent::Controller();
		$this->load->model('Stock_Unit_model');
		$this->load->model('Stock_Group_model');
		$this->load->model('Stock_Item_model');
		return;
	}

	function index()
	{
		redirect('inventory/stockitem/add');
		return;
	}

	function add()
	{
		$this->template->set('page_title', 'New Stock Item');

		/* Check access */
		if ( ! check_access('create stock item'))
		{
			$this->messages->add('Permission denied.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Check for account lock */
		if ($this->config->item('account_locked') == 1)
		{
			$this->messages->add('Account is locked.', 'error');
			redirect('account');
			return;
		}

		/* Form fields */
		$data['stock_item_name'] = array(
			'name' => 'stock_item_name',
			'id' => 'stock_item_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);
		$data['stock_item_op_quantity'] = array(
			'name' => 'stock_item_op_quantity',
			'id' => 'stock_item_op_quantity',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);
		$data['stock_item_op_rate_per_unit'] = array(
			'name' => 'stock_item_op_rate_per_unit',
			'id' => 'stock_item_op_rate_per_unit',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);
		$data['stock_item_op_total'] = array(
			'name' => 'stock_item_op_total',
			'id' => 'stock_item_op_total',
			'maxlength' => '100',
			'size' => '40',
			'value' => '',
		);
		$data['stock_item_costing_methods'] = array(
			'1' => 'Last In First Out (LIFO)',
			'2' => 'First In First Out (FIFO)',
		);
		$data['stock_item_costing_method_active'] = 1;
		$data['stock_item_units'] = $this->Stock_Unit_model->get_all_units();
		$data['stock_item_unit_active'] = 0;
		$data['stock_item_groups'] = $this->Stock_Group_model->get_stock_item_groups();
		$data['stock_item_group_active'] = 0;

		/* Form validations */
		$this->form_validation->set_rules('stock_item_name', 'Stock item name', 'trim|required|min_length[2]|max_length[100]|unique[stock_items.name]');
		$this->form_validation->set_rules('stock_item_group', 'Stock group', 'trim|required|is_natural');
		$this->form_validation->set_rules('stock_item_unit', 'Stock unit', 'trim|required|is_natural');
		$this->form_validation->set_rules('stock_item_costing_method', 'Costing method', 'trim|required|is_natural');
		$this->form_validation->set_rules('stock_item_op_quantity', 'Opening Balance Quantity', 'trim|quantity');
		$this->form_validation->set_rules('stock_item_op_rate_per_unit', 'Opening Balance Rate per unit', 'trim|currency');
		$this->form_validation->set_rules('stock_item_op_total', 'Opening Balance Total value', 'trim|currency');

		/* Re-populating form */
		if ($_POST)
		{
			$data['stock_item_name']['value'] = $this->input->post('stock_item_name', TRUE);
			$data['stock_item_group_active'] = $this->input->post('stock_item_group', TRUE);
			$data['stock_item_unit_active'] = $this->input->post('stock_item_unit', TRUE);
			$data['stock_item_costing_method_active'] = $this->input->post('stock_item_costing_method', TRUE);
			$data['stock_item_op_quantity']['value'] = $this->input->post('stock_item_op_quantity', TRUE);
			$data['stock_item_op_rate_per_unit']['value'] = $this->input->post('stock_item_op_rate_per_unit', TRUE);
			$data['stock_item_op_total']['value'] = $this->input->post('stock_item_op_total', TRUE);
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'inventory/stockitem/add', $data);
			return;
		}
		else
		{
			$data_stock_item_name = $this->input->post('stock_item_name', TRUE);
			$data_stock_item_group_id = $this->input->post('stock_item_group', TRUE);
			$data_stock_item_unit_id = $this->input->post('stock_item_unit', TRUE);
			$data_stock_item_costing_method = $this->input->post('stock_item_costing_method', TRUE);
			$data_stock_item_op_quantity = $this->input->post('stock_item_op_quantity', TRUE);
			$data_stock_item_op_rate_per_unit = $this->input->post('stock_item_op_rate_per_unit', TRUE);
			$data_stock_item_op_total = $this->input->post('stock_item_op_total', TRUE);

			/* Check if stock group present */
			$this->db->select('id')->from('stock_groups')->where('id', $data_stock_item_group_id);
			if ($this->db->get()->num_rows() < 1)
			{
				$this->messages->add('Invalid stock group.', 'error');
				$this->template->load('template', 'inventory/stockitem/add', $data);
				return;
			}

			/* Check if stock unit present */
			$this->db->select('id')->from('stock_units')->where('id', $data_stock_item_unit_id);
			if ($this->db->get()->num_rows() < 1)
			{
				$this->messages->add('Invalid stock unit.', 'error');
				$this->template->load('template', 'inventory/stockitem/add', $data);
				return;
			}

			if (($data_stock_item_costing_method < 1) or ($data_stock_item_costing_method > 2))
				$data_stock_item_costing_method = 1;

			$this->db->trans_start();
			$insert_data = array(
				'name' => $data_stock_item_name,
				'stock_group_id' => $data_stock_item_group_id,
				'stock_unit_id' => $data_stock_item_unit_id,
				'costing_method' => $data_stock_item_costing_method,
				'op_balance_quantity' => $data_stock_item_op_quantity,
				'op_balance_rate_per_unit' => $data_stock_item_op_rate_per_unit,
				'op_balance_total_value' => $data_stock_item_op_total,
			);
			if ( ! $this->db->insert('stock_items', $insert_data))
			{
				$this->db->trans_rollback();
				$this->messages->add('Error addding Stock Item - ' . $data_stock_item_name . '.', 'error');
				$this->logger->write_message("error", "Error adding Stock Item named " . $data_stock_item_name);
				$this->template->load('template', 'inventory/stockitem/add', $data);
				return;
			} else {
				$this->db->trans_complete();
				$this->messages->add('Added Stock Item - ' . $data_stock_item_name . '.', 'success');
				$this->logger->write_message("success", "Added Stock Item named " . $data_stock_item_name);
				redirect('inventory/account');
				return;
			}
		}
		return;
	}

	function edit($id)
	{
		$this->template->set('page_title', 'Edit Stock Item');

		/* Check access */
		if ( ! check_access('edit stock item'))
		{
			$this->messages->add('Permission denied.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Check for account lock */
		if ($this->config->item('account_locked') == 1)
		{
			$this->messages->add('Account is locked.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1) {
			$this->messages->add('Invalid Stock Item.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Loading current stock item */
		$this->db->from('stock_items')->where('id', $id);
		$stock_item_data_q = $this->db->get();
		if ($stock_item_data_q->num_rows() < 1)
		{
			$this->messages->add('Invalid Stock Item.', 'error');
			redirect('inventory/account');
			return;
		}
		$stock_item_data = $stock_item_data_q->row();

		/* Form fields */
		$data['stock_item_name'] = array(
			'name' => 'stock_item_name',
			'id' => 'stock_item_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => $stock_item_data->name,
		);
		$data['stock_item_op_quantity'] = array(
			'name' => 'stock_item_op_quantity',
			'id' => 'stock_item_op_quantity',
			'maxlength' => '100',
			'size' => '40',
			'value' => $stock_item_data->op_balance_quantity,
		);
		$data['stock_item_op_rate_per_unit'] = array(
			'name' => 'stock_item_op_rate_per_unit',
			'id' => 'stock_item_op_rate_per_unit',
			'maxlength' => '100',
			'size' => '40',
			'value' => $stock_item_data->op_balance_rate_per_unit,
		);
		$data['stock_item_op_total'] = array(
			'name' => 'stock_item_op_total',
			'id' => 'stock_item_op_total',
			'maxlength' => '100',
			'size' => '40',
			'value' => $stock_item_data->op_balance_total_value,
		);
		$data['stock_item_costing_methods'] = array(
			'1' => 'Last In First Out (LIFO)',
			'2' => 'First In First Out (FIFO)',
		);
		$data['stock_item_costing_method_active'] = $stock_item_data->costing_method;
		$data['stock_item_units'] = $this->Stock_Unit_model->get_all_units();
		$data['stock_item_unit_active'] = $stock_item_data->stock_unit_id;
		$data['stock_item_groups'] = $this->Stock_Group_model->get_stock_item_groups();
		$data['stock_item_group_active'] = $stock_item_data->stock_group_id;
		$data['stock_item_id'] = $id;

		/* Form validations */
		$this->form_validation->set_rules('stock_item_name', 'Stock item name', 'trim|required|min_length[2]|max_length[100]|uniquewithid[stock_items.name.' . $id . ']');
		$this->form_validation->set_rules('stock_item_group', 'Stock group', 'trim|required|is_natural');
		$this->form_validation->set_rules('stock_item_unit', 'Stock unit', 'trim|required|is_natural');
		$this->form_validation->set_rules('stock_item_costing_method', 'Costing method', 'trim|required|is_natural');
		$this->form_validation->set_rules('stock_item_op_quantity', 'Opening Balance Quantity', 'trim|quantity');
		$this->form_validation->set_rules('stock_item_op_rate_per_unit', 'Opening Balance Rate per unit', 'trim|currency');
		$this->form_validation->set_rules('stock_item_op_total', 'Opening Balance Total value', 'trim|currency');

		/* Re-populating form */
		if ($_POST)
		{
			$data['stock_item_name']['value'] = $this->input->post('stock_item_name', TRUE);
			$data['stock_item_group_active'] = $this->input->post('stock_item_group', TRUE);
			$data['stock_item_unit_active'] = $this->input->post('stock_item_unit', TRUE);
			$data['stock_item_costing_method_active'] = $this->input->post('stock_item_costing_method', TRUE);
			$data['stock_item_op_quantity']['value'] = $this->input->post('stock_item_op_quantity', TRUE);
			$data['stock_item_op_rate_per_unit']['value'] = $this->input->post('stock_item_op_rate_per_unit', TRUE);
			$data['stock_item_op_total']['value'] = $this->input->post('stock_item_op_total', TRUE);
		}

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'inventory/stockitem/edit', $data);
			return;
		}
		else
		{
			$data_stock_item_name = $this->input->post('stock_item_name', TRUE);
			$data_stock_item_group_id = $this->input->post('stock_item_group', TRUE);
			$data_stock_item_unit_id = $this->input->post('stock_item_unit', TRUE);
			$data_stock_item_costing_method = $this->input->post('stock_item_costing_method', TRUE);
			$data_stock_item_op_quantity = $this->input->post('stock_item_op_quantity', TRUE);
			$data_stock_item_op_rate_per_unit = $this->input->post('stock_item_op_rate_per_unit', TRUE);
			$data_stock_item_op_total = $this->input->post('stock_item_op_total', TRUE);
			$data_id = $id;

			/* Check if stock group present */
			$this->db->select('id')->from('stock_groups')->where('id', $data_stock_item_group_id);
			if ($this->db->get()->num_rows() < 1)
			{
				$this->messages->add('Invalid stock group.', 'error');
				$this->template->load('template', 'inventory/stockitem/add', $data);
				return;
			}

			/* Check if stock unit present */
			$this->db->select('id')->from('stock_units')->where('id', $data_stock_item_unit_id);
			if ($this->db->get()->num_rows() < 1)
			{
				$this->messages->add('Invalid stock unit.', 'error');
				$this->template->load('template', 'inventory/stockitem/add', $data);
				return;
			}

			if (($data_stock_item_costing_method < 1) or ($data_stock_item_costing_method > 2))
				$data_stock_item_costing_method = 1;

			$this->db->trans_start();
			$update_data = array(
				'name' => $data_stock_item_name,
				'stock_group_id' => $data_stock_item_group_id,
				'stock_unit_id' => $data_stock_item_unit_id,
				'costing_method' => $data_stock_item_costing_method,
				'op_balance_quantity' => $data_stock_item_op_quantity,
				'op_balance_rate_per_unit' => $data_stock_item_op_rate_per_unit,
				'op_balance_total_value' => $data_stock_item_op_total,
			);
			if ( ! $this->db->where('id', $data_id)->update('stock_items', $update_data))
			{
				$this->db->trans_rollback();
				$this->messages->add('Error updating Stock Item - ' . $data_stock_item_name . '.', 'error');
				$this->logger->write_message("error", "Error updating Stock Item named " . $data_stock_item_name . " [id:" . $data_id . "]");
				$this->template->load('template', 'inventory/stockgroup/edit', $data);
				return;
			} else {
				$this->db->trans_complete();
				$this->messages->add('Updated Stock Item - ' . $data_stock_item_name . '.', 'success');
				$this->logger->write_message("success", "Updated Stock Item named " . $data_stock_item_name . " [id:" . $data_id . "]");
				redirect('inventory/account');
				return;
			}
		}
		return;
	}

	function delete($id)
	{
		/* Check access */
		if ( ! check_access('delete stock item'))
		{
			$this->messages->add('Permission denied.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Check for account lock */
		if ($this->config->item('account_locked') == 1)
		{
			$this->messages->add('Account is locked.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1) {
			$this->messages->add('Invalid Stock Item.', 'error');
			redirect('inventory/account');
			return;
		}

		/* Get the stock item details */
		$this->db->from('stock_items')->where('id', $id);
		$stock_item_q = $this->db->get();
		if ($stock_item_q->num_rows() < 1)
		{
			$this->messages->add('Invalid Stock Item.', 'error');
			redirect('inventory/account');
			return;
		} else {
			$stock_item_data = $stock_item_q->row();
		}

		/* Deleting item */
		$this->db->trans_start();
		if ( ! $this->db->delete('stock_items', array('id' => $id)))
		{
			$this->db->trans_rollback();
			$this->messages->add('Error deleting Stock Item - ' . $stock_item_data->name . '.', 'error');
			$this->logger->write_message("error", "Error deleting Stock Item named " . $stock_item_data->name . " [id:" . $id . "]");
			redirect('inventory/account');
			return;
		} else {
			$this->db->trans_complete();
			$this->messages->add('Deleted Stock Item - ' . $stock_item_data->name . '.', 'success');
			$this->logger->write_message("success", "Deleted Stock Item named " . $stock_item_data->name . " [id:" . $id . "]");
			redirect('inventory/account');
			return;
		}
		return;
	}
}

/* End of file stockitem.php */
/* Location: ./system/application/controllers/inventory/stockitem.php */
