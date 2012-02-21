<?php

function hybrid_classes_autoload ($class) {

	$classPath = explode('\\',$class);

	if ($classPath[0] == 'Hybrid') {
		$file = dirname(__FILE__) . "/" . strtolower(implode('/',$classPath)) .".php";

		if (file_exists($file)) {
			require_once($file);
		}
	}
};

spl_autoload_register('hybrid_classes_autoload');
