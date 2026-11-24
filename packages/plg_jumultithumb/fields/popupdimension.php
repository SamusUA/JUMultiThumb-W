<?php

use Joomla\CMS\Form\Field\TextField;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

class JFormFieldPopupdimension extends TextField
{
	protected $type = 'Popupdimension';

	public function filter($value, $group = null, ?Registry $input = null)
	{
		$data = $input?->toArray() ?? [];
		$groups = $this->element->xpath('ancestor::fields[@name]/@name') ?: [];
		foreach($groups as $parent)
		{
			$data = is_array($data) ? ($data[(string) $parent] ?? []) : [];
		}

		$name = (string) $this->element['name'];
		$watermark = (string) $this->element['watermark_field'];
		if(is_array($data) && (int) ($data[$watermark] ?? 0) === 1 && array_key_exists($name, $data))
		{
			return $data[$name];
		}

		return parent::filter($value, $group, $input);
	}
}
