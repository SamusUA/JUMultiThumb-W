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
	$field->element = new \SimpleXMLElement('<field context="article" />');
	$html = $field->renderInput();
	$expectedUrl = 'data-url="https://example.invalid/administrator/index.php?option=com_ajax&amp;plugin=jumultithumb&amp;group=content&amp;format=json"';

	if(!str_contains($html, $expectedUrl) || !str_contains($html, 'data-context="article"') || !str_contains($html, 'data-token="test-token"'))
	{
		throw new \RuntimeException('The cache button must use the authenticated administrator endpoint and context token.');
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
