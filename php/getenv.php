<?php
	$PROJECT_ROOT = dirname(__FILE__);
	
	while (!file_exists("$PROJECT_ROOT/.phpenv")) {
		if ($PROJECT_ROOT == "/" || $PROJECT_ROOT == "") 
			throw new RuntimeException("Could not find .phpenv file.");
		$PROJECT_ROOT = dirname($PROJECT_ROOT);
	}
	
	require_once "$PROJECT_ROOT/.phpenv";
	
	// set_include_path(get_include_path() . PATH_SEPARATOR . $PROJECT_ROOT);
?>