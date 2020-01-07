<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// These are too spammy for now. TODO enable
$cfg['null_casts_as_any_type'] = true;
$cfg['scalar_implicit_cast'] = true;

$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	[
		'WikibaseLexeme.datatypes.client.php',
		'WikibaseLexeme.datatypes.php',
		'WikibaseLexeme.entitytypes.php',
		'WikibaseLexeme.entitytypes.repo.php',
		'WikibaseLexeme.i18n.alias.php',
		'WikibaseLexeme.mediawiki-services.php',
		'WikibaseLexeme.php',
		'WikibaseLexeme.resources.php',
	]
);

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/Wikibase',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/Wikibase',
	]
);

$cfg['suppress_issue_types'][] = 'PhanParamSignatureMismatch';

return $cfg;
