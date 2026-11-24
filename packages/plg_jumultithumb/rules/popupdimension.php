<?php

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class JFormRulePopupdimension extends FormRule
{
	public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
	{
		$key = ($group ? $group . '.' : '') . (string) $element['watermark_field'];
		if((int) ($input?->get($key, 0) ?? 0) !== 1)
		{
			return true;
		}

		return is_scalar($value) && preg_match('/^[0-9]+$/D', (string) $value) === 1 && (int) $value > 1;
	}
}
