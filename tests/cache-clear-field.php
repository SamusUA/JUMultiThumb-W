<?php

namespace Joomla\CMS\Form {
	class FormField
	{
		public string $id = 'jform_params_article_cache_clear';
		public \SimpleXMLElement $element;
	}
}

namespace Joomla\CMS\Language {
	class Text
	{
		public static function _(string $key): string
		{
			return $key;
		}
	}
}

namespace Joomla\CMS\Session {
	class Session
	{
		public static function getFormToken(): string
		{
			return 'test-token';
		}
	}
}

namespace Joomla\CMS\Uri {
	class Uri
	{
		public static function base(): string
		{
			return 'https://example.invalid/administrator/';
		}

		public static function root(): string
		{
			return 'https://example.invalid/';
		}
	}
}

namespace {
	define('JPATH_BASE', __DIR__);
	require_once __DIR__ . '/../packages/plg_jumultithumb/fields/cacheclear.php';

	class TestCacheclearField extends \JFormFieldCacheclear
	{
		public function renderInput(): string
		{
			return $this->getInput();
		}
	}

	$field = new TestCacheclearField();
	$manifest = simplexml_load_file(__DIR__ . '/../packages/plg_jumultithumb/jumultithumb.xml');
	$fields = $manifest->xpath('//field[@type="Cacheclear"]');
	$expectedUrl = 'data-url="https://example.invalid/administrator/index.php?option=com_ajax&amp;plugin=jumultithumb&amp;group=content&amp;format=json"';

	if(count($fields) !== 5)
	{
		throw new \RuntimeException('Expected cache-clear fields for all five contexts.');
	}

	foreach($fields as $element)
	{
		$field->element = $element;
		$context = (string) $element['context'];
		$html = $field->renderInput();
		if(!str_contains($html, $expectedUrl) || !str_contains($html, 'data-context="' . $context . '"') || !str_contains($html, 'data-token="test-token"'))
		{
			throw new \RuntimeException('The cache button must use the authenticated administrator endpoint and context token for ' . $context);
		}
	}

	$field->element = new \SimpleXMLElement('<field context="invalid" />');

	try
	{
		$field->renderInput();
		throw new \RuntimeException('The cache button accepted an invalid context.');
	}
	catch(\UnexpectedValueException $exception)
	{
	}

	echo "Cache-clear field tests passed.\n";
}
