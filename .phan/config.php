<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config-library.php';

$cfg['autoload_internal_extension_signatures'] = [
	'excimer' => '.phan/internal_stubs/excimer.php',
];

$cfg['directory_list'] = [
	'src',
	'vendor',
	'tests'
];
$cfg['exclude_analysis_directory_list'][] = 'vendor';
$cfg['suppress_issue_types'] = [
	// It's a library, methods don't have to be called
	'PhanUnreferencedPublicMethod',
	// It means internal to the library
	'PhanAccessMethodInternal',
];
return $cfg;
