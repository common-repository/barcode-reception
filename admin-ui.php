<?php

class BCRAdminUi {
	var $file_path;

	public function __construct( $path ){
		$this->file_path = $path;
		$this->setUi();
	}

	public function setUi(){
		register_setting(BCR::OPTIONS, BCR::OPTIONS, array( &$this, 'validate' ));
		add_settings_section('main_section', '表示設定', array(&$this,'section_text_fn'), $this->file_path);
		add_settings_field('bcr_offset', 'バーコードのオフセット', array(&$this,'setting_offset'), $this->file_path, 'main_section');
	}

	public function show_admin_page() {
		$file = $this->file_path;
		$option_name = BCR::OPTIONS;
		include_once('admin-view.php');
	}

	function validate($input) {
		if ( !is_numeric( $input['bcr_offset']) || $input['bcr_offset'] > 999999){
			$input['bcr_offset'] = 0;
		}
		$input['bcr_offset'] = abs($input['bcr_offset']);
		return $input;
	}

	function  section_text_fn() {
	}

	function setting_offset() {
		$options = BCR::get_option();
		$value = $options["bcr_offset"];
		echo "<input id='bcr_offset' name='barcode_options[bcr_offset]' size='6' type='text' value='{$value}' />";
	}
}
