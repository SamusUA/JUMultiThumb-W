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
expectSame('jumultithumb-inline', ContextSettings::lightboxGroup('article'), 'Article images must use the inline PhotoSwipe group');
expectSame('jumultithumb-module', ContextSettings::lightboxGroup('module'), 'Module images must use their separate PhotoSwipe group');
expect(ContextSettings::isModuleArticleEvent('com_content.article', (object) ['event' => new stdClass()]), 'Article modules must be identified from their event payload');
expect(ContextSettings::isModuleArticleEvent('com_content.article', (object) ['imageSrc' => 'images/example.jpg']), 'News modules must be identified from their image source');
foreach(['mod_articles.content', 'mod_articles_news.content', 'mod_articles_category.content', 'mod_custom.content'] as $moduleEvent)
{
	expect(ContextSettings::isModuleArticleEvent($moduleEvent, (object) ['text' => '']), 'Joomla module content event must use the module context');
}
expect(!ContextSettings::isModuleArticleEvent('com_custom.content', (object) ['text' => '']), 'Non-module content must not be classified as module content');
expect(!ContextSettings::isModuleArticleEvent('com_content.article', (object) ['title' => 'Component article']), 'Component articles must not be classified as module events');
expect(!ContextSettings::isModuleArticleEvent('com_content.category', (object) ['event' => new stdClass()]), 'Non-article events must not be classified as article modules');
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
$contextNames = ['article' => 'ARTICLE', 'categories' => 'CATEGORIES', 'category' => 'CATEGORY', 'featured' => 'FEATURED', 'module' => 'MODULE'];
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
	foreach(['popup_width', 'popup_height'] as $suffix)
	{
		$field = $fields[$prefix . '_' . $suffix];
		expectSame($prefix . '_popup_maxsize:1[OR]' . $prefix . '_watermark_popup:1', (string) $field['showon'], 'Popup dimensions must be visible with either size processing or watermarking');
		expectSame('popupdimension', (string) $field['validate'], 'Popup dimensions must use conditional validation');
		expectSame($prefix . '_watermark_popup', (string) $field['watermark_field'], 'Validation must use the corresponding context watermark setting');
	}
	expectSame('0', (string) $fields[$prefix . '_popup_maxsize']['default'], 'Popup crop must preserve the Joomla 3 default');
	expectSame('0', (string) $fields[$prefix . '_watermark_popup']['default'], 'Popup watermark must default to No');
	expectSame('0', (string) $fields[$prefix . '_watermark_thumbnail']['default'], 'Thumbnail watermark must default to No');
	expectSame($prefix, (string) $fields[$prefix . '_cache_clear']['context'], 'Cache clear must be scoped to its context');
}

$photoswipe = file_get_contents(__DIR__ . '/../packages/plg_jumultithumb/assets/photoswipe/photoswipe-init.js');
expect(is_string($photoswipe), 'PhotoSwipe initialization must be readable');
expect(str_contains($photoswipe, 'a.jumultithumb-pswp[data-pswp-width][data-pswp-height]'), 'PhotoSwipe must select only plugin-generated links');
expect(str_contains($photoswipe, 'jumultithumb-module'), 'Module images must have an independent PhotoSwipe group');
expect(!str_contains($photoswipe, 'article-full-image img'), 'Native Joomla images must not be added to plugin PhotoSwipe galleries');

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
