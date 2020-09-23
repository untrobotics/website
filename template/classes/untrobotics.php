<?php
class untrobotics {
	private $is_sandbox = false;
	
	public function __construct() {
		// nowt
	}
	
	public function set_sandbox($is_sandbox) {
		$this->is_sandbox = $is_sandbox;
	}
	public function get_sandbox() {
		return $this->is_sandbox;
	}
}