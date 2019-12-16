<?php

spl_autoload_register(function($class) {
	// handle namspaced classes
	$class = str_replace('\\', '/', $class);
	// include file
	include_once 'src/' . $class . '.php';
});
