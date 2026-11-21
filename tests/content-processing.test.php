<?php

require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/Utils.php';

use JUMultiThumb\Helpers\Utils;

$checks = [
	'accept com_content article context' => Utils::isContentArticleContext('com_content.article'),
	'reject custom module context' => !Utils::isContentArticleContext('mod_custom.content'),
	'reject other component context' => !Utils::isContentArticleContext('com_content.category'),
	'exclude nothumbnail class' => Utils::hasImageExclusionClass('juimage nothumbnail'),
	'exclude noimage class' => Utils::hasImageExclusionClass('noimage juimage'),
	'exclude configured class' => Utils::hasImageExclusionClass('juimage noim', 'noim'),
	'exclude configured class lists' => Utils::hasImageExclusionClass('image noim', 'skip, noim'),
	'preserve ordinary juimage opt-in' => !Utils::hasImageExclusionClass('image juimage'),
	'do not match class name prefixes' => !Utils::hasImageExclusionClass('noimage-large'),
	'process unmarked image in all-images mode' => Utils::shouldProcessImage('', true),
	'do not process unmarked image in opt-in mode' => !Utils::shouldProcessImage('', false),
	'process juimage in opt-in mode' => Utils::shouldProcessImage('image juimage', false),
	'exclusion overrides juimage in opt-in mode' => !Utils::shouldProcessImage('juimage noimage', false),
	'configured exclusion overrides all-images mode' => !Utils::shouldProcessImage('custom-skip', true, 'custom-skip'),
];

foreach ($checks as $name => $passed)
{
	if (!$passed)
	{
		fwrite(STDERR, "Failed: {$name}\n");
		exit(1);
	}
}

echo 'Passed ' . count($checks) . " content-processing checks.\n";
