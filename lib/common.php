<?php
if (!file_exists('conf/config.php')) {
	die('Please read the installing instructions in the README file.');
}

require_once('conf/config.php');
require_once('vendor/autoload.php');
foreach (glob("lib/*.php") as $file) {
	require_once($file);
}