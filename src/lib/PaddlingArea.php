<?php
class PaddlingArea
{
	// everything is public so we can convert it to JSON
	public $paName = null;
	public $selected = FALSE;

	function __construct($paName) {
		$this->paName = $paName;
	}
}
?>
