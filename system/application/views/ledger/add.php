<?php
	echo form_open('ledger/add');
	echo "<p>";
	echo form_label('Ledger name', 'ledger_name');
	echo "<br />";
	echo form_input($ledger_name);
	echo "</p>";
	echo "<p>";
	echo form_label('Parent group', 'ledger_group_id');
	echo "<br />";
	echo form_dropdown('ledger_group_id', $ledger_group_id);
	echo "</p>";
	echo "<p>";
	echo form_label('Opening balance', 'op_balance');
	echo "<br />";
	echo form_dropdown_dc('op_balance_dc');
	echo " ";
	echo form_input($op_balance);
	echo "</p>";
	echo form_submit('submit', 'Create');
	echo " ";
	echo anchor('account', 'Back', 'Back to Chart of Accounts');
	echo form_close();
?>