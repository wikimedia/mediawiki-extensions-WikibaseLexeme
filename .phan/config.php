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
// @todo Remove the next line once HHVM is gone. Many instances are on ArrayObjects, and on HHVM the
// coalesce operator doesn't behave well on them.
$cfg['suppress_issue_types'][] = 'PhanPluginDuplicateConditionalNullCoalescing';

return $cfg;
