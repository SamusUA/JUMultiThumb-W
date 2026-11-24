<?php

require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/ContextSettings.php';

use JUMultiThumb\Helpers\ContextSettings;

function expectSame(mixed $expected, mixed $actual, string $message): void
{
	if($expected !== $actual)
	{
		throw new RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
	}
}

function expect(bool $condition, string $message): void
{
	if(!$condition)
	{
		throw new RuntimeException($message);
	}
}

foreach(ContextSettings::CONTEXTS as $context)
{
	expectSame($context, ContextSettings::forView($context), 'View context mapping failed');
	expectSame('img/' . $context, ContextSettings::cachePath($context), 'Cache path mapping failed');
}

expectSame('article', ContextSettings::forView('unknown'), 'Unknown views must use the article defaults');
expectSame(0, ContextSettings::dimension(1), 'Dimension 1 must disable the dimension');
expectSame(0, ContextSettings::dimension(0), 'Zero must disable the dimension');
expectSame(320, ContextSettings::dimension('320'), 'Positive dimensions must be retained');
expect(ContextSettings::sourceBelowTarget(100, 300, 160, 160), 'A too-small source width must prevent thumbnail processing');
expect(ContextSettings::sourceBelowTarget(300, 100, 160, 160), 'A too-small source height must prevent thumbnail processing');
expect(!ContextSettings::sourceBelowTarget(160, 160, 160, 160), 'A source equal to the target must be processable');
expect(!ContextSettings::sourceBelowTarget(100, 100, 0, 0), 'Disabled dimensions must not trigger the minimum-size guard');

$xml = simplexml_load_file(__DIR__ . '/../packages/plg_jumultithumb/jumultithumb.xml');
expect($xml !== false, 'Plugin XML must be valid');
$fieldsets = [];
foreach($xml->config->fields->fieldset as $fieldset)
{
	$fieldsets[(string) $fieldset['name']] = $fieldset;
}

$suffixes = [
	'nothumb', 'nopopup', 'width', 'height', 'allow_upscale', 'crop',
	'popup_width', 'popup_height', 'popup_maxsize', 'watermark_popup',
	'watermark_thumbnail', 'cache_clear',
];
$contextNames = ['article' => 'ARTICLE', 'categories' => 'CATEGORIES', 'category' => 'CATEGORY', 'featured' => 'FEATURED'];
$helpKeys = [
	'PLG_JUMULTITHUMB_HELP_NOTHUMB',
	'PLG_JUMULTITHUMB_HELP_NOPOPUP',
	'PLG_JUMULTITHUMB_HELP_WIDTH',
	'PLG_JUMULTITHUMB_HELP_HEIGHT',
	'PLG_JUMULTITHUMB_HELP_UPSCALE',
	'PLG_JUMULTITHUMB_HELP_CROP',
	'PLG_JUMULTITHUMB_HELP_POPUP_WIDTH',
	'PLG_JUMULTITHUMB_HELP_POPUP_HEIGHT',
	'PLG_JUMULTITHUMB_HELP_POPUP_MAXSIZE',
	'PLG_JUMULTITHUMB_HELP_WATERMARK_POPUP',
	'PLG_JUMULTITHUMB_HELP_WATERMARK_THUMBNAIL',
	'PLG_JUMULTITHUMB_HELP_CACHE_CLEAR',
];

foreach($contextNames as $prefix => $fieldsetName)
{
	expect(isset($fieldsets[$fieldsetName]), 'Missing context fieldset ' . $fieldsetName);
	$fields = [];
	foreach($fieldsets[$fieldsetName]->field as $field)
	{
		$fields[(string) $field['name']] = $field;
	}

	expectSame(12, count($fields), 'Each context must expose exactly twelve requested controls');
	expectSame(array_map(static fn(string $suffix): string => $prefix . '_' . $suffix, $suffixes), array_keys($fields), 'Context field order or names differ');
	expectSame('0', (string) $fields[$prefix . '_nothumb']['default'], 'Thumbnail opt-out must default to No');
	expectSame('0', (string) $fields[$prefix . '_nopopup']['default'], 'Popup opt-out must default to No');
	expectSame('160', (string) $fields[$prefix . '_width']['default'], 'Thumbnail width default differs');
	expectSame('160', (string) $fields[$prefix . '_height']['default'], 'Thumbnail height default differs');
	expectSame('0', (string) $fields[$prefix . '_allow_upscale']['default'], 'Upscaling must default to No');
	expectSame('1', (string) $fields[$prefix . '_crop']['default'], 'Thumbnail crop must default to Yes');
	expectSame('800', (string) $fields[$prefix . '_popup_width']['default'], 'Popup width default differs');
	expectSame('500', (string) $fields[$prefix . '_popup_height']['default'], 'Popup height default differs');
	expectSame('0', (string) $fields[$prefix . '_popup_maxsize']['default'], 'Popup crop must preserve the Joomla 3 default');
	expectSame('0', (string) $fields[$prefix . '_watermark_popup']['default'], 'Popup watermark must default to No');
	expectSame('0', (string) $fields[$prefix . '_watermark_thumbnail']['default'], 'Thumbnail watermark must default to No');
	expectSame($prefix, (string) $fields[$prefix . '_cache_clear']['context'], 'Cache clear must be scoped to its context');
}

foreach(['uk-UA', 'en-GB'] as $locale)
{
	$path = __DIR__ . '/../packages/plg_jumultithumb/language/' . $locale . '/' . $locale . '.plg_content_jumultithumb.ini';
	$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$translations = [];
	foreach($lines as $line)
	{
		if(preg_match('/^(PLG_JUMULTITHUMB_HELP_[A-Z_]+)=\"(.*)\"$/u', $line, $matches))
		{
			$translations[$matches[1]] = $matches[2];
		}
	}

	foreach($helpKeys as $key)
	{
		expect(isset($translations[$key]), 'Missing ' . $key . ' for ' . $locale);
		$length = preg_match_all('/./us', $translations[$key], $unused);
		expect($length >= 500, $key . ' must contain at least 500 characters for ' . $locale);
	}
}

echo "Context settings tests passed.\n";
