<?php
if (!file_exists('conf/config.php')) {
	die('Configuration is missing.');
}

require_once('conf/config.php');
require_once('vendor/autoload.php');
foreach (glob("lib/*.php") as $file) {
	require_once($file);
}