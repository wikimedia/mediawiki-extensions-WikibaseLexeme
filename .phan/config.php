<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	[
		'WikibaseLexeme.datatypes.client.php',
		'WikibaseLexeme.datatypes.php',
		'WikibaseLexeme.entitytypes.php',
		'WikibaseLexeme.entitytypes.repo.php',
		'WikibaseLexeme.i18n.alias.php',
		'WikibaseLexeme.mediawiki-services.php',
		'WikibaseLexeme.resources.php',
	]
);

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/Wikibase/client',
		'../../extensions/Wikibase/data-access',
		'../../extensions/Wikibase/lib',
		'../../extensions/Wikibase/repo',
		'../../extensions/Wikibase/view',
		'../../extensions/Scribunto/',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/Wikibase/client',
		'../../extensions/Wikibase/data-access',
		'../../extensions/Wikibase/lib',
		'../../extensions/Wikibase/repo',
		'../../extensions/Wikibase/view',
		'../../extensions/Scribunto/',
	]
);

$cfg['suppress_issue_types'][] = 'PhanParamSignatureMismatch';

return $cfg;
