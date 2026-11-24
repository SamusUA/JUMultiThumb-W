<?php

if($argc !== 2 || !is_file($argv[1] . '/includes/framework.php'))
{
	fwrite(STDERR, "Usage: php tests/joomla-popup-form.php <isolated-joomla-root>\n");
	exit(1);
}

define('_JEXEC', 1);
define('JPATH_BASE', realpath($argv[1]));
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['DOCUMENT_ROOT'] = JPATH_BASE;
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = \Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.cli')
	->alias('JSession', 'session.cli')
	->alias(\Joomla\CMS\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\Session::class, 'session.cli')
	->alias(\Joomla\Session\SessionInterface::class, 'session.cli');
\Joomla\CMS\Factory::$application = $container->get(\Joomla\Console\Application::class);

$manifest = simplexml_load_file(JPATH_PLUGINS . '/content/jumultithumb/jumultithumb.xml');
if($manifest === false)
{
	throw new \RuntimeException('Installed plugin manifest could not be loaded');
}

foreach(['article', 'categories', 'category', 'featured', 'module'] as $context)
{
	$fields = $manifest->xpath('//config/fields/fieldset/field[@name="' . $context . '_popup_width" or @name="' . $context . '_popup_height" or @name="' . $context . '_watermark_popup"]');
	if(count($fields) !== 3)
	{
		throw new \RuntimeException('Installed fields missing for ' . $context);
	}

	$cacheFields = $manifest->xpath('//config/fields/fieldset/field[@name="' . $context . '_cache_clear"]');
	if(count($cacheFields) !== 1)
	{
		throw new \RuntimeException('Installed cache-clear field missing for ' . $context);
	}
	$fields[] = $cacheFields[0];

	$source = '<form><fields name="params" addfieldpath="/plugins/content/jumultithumb/fields/" addrulepath="/plugins/content/jumultithumb/rules/">';
	foreach($fields as $field)
	{
		$source .= $field->asXML();
	}
	$source .= '</fields></form>';
	$form = new \Joomla\CMS\Form\Form('popup-' . $context);
	$form->load($source);
	$cacheField = $form->getField($context . '_cache_clear', 'params');
	if($cacheField === false || !str_contains($cacheField->input, 'data-context="' . $context . '"'))
	{
		throw new \RuntimeException('Joomla cache-clear field could not be rendered for ' . $context);
	}

	foreach([
		['watermark' => 1, 'width' => '', 'height' => '500', 'valid' => false],
		['watermark' => 1, 'width' => '1', 'height' => '500', 'valid' => false],
		['watermark' => 1, 'width' => '12.5', 'height' => '500', 'valid' => false],
		['watermark' => 1, 'width' => 'abc800', 'height' => '500', 'valid' => false],
		['watermark' => 1, 'width' => '-20', 'height' => '500', 'valid' => false],
		['watermark' => 1, 'width' => '800', 'height' => '0', 'valid' => false],
		['watermark' => 1, 'width' => '800', 'height' => '500', 'valid' => true],
		['watermark' => 0, 'width' => '', 'height' => '1', 'valid' => true],
	] as $case)
	{
		$data = ['params' => [
			$context . '_watermark_popup' => $case['watermark'],
			$context . '_popup_width' => $case['width'],
			$context . '_popup_height' => $case['height'],
		]];
		$filtered = $form->filter($data);
		if($form->validate($filtered) !== $case['valid'])
		{
			throw new \RuntimeException('Joomla form validation failed for ' . $context . ': ' . json_encode($case) . ' filtered=' . json_encode($filtered));
		}
	}
}

echo "Joomla popup form validation passed.\n";
