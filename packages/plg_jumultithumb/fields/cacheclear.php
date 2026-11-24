<?php

use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;

defined('JPATH_BASE') or die;

class JFormFieldCacheclear extends FormField
{
	protected $type = 'Cacheclear';

	protected function getInput(): string
	{
		$context = (string) $this->element['context'];
		$validContexts = ['article', 'categories', 'category', 'featured'];

		if(!in_array($context, $validContexts, true))
		{
			throw new \UnexpectedValueException('Unsupported image cache context.');
		}

		$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
		$buttonId = $escape($this->id);
		$statusId = $escape($this->id . '-status');
		$url = $escape(Uri::base() . 'index.php?option=com_ajax&plugin=jumultithumb&group=content&format=json');
		$contextAttribute = $escape($context);
		$token = $escape(Session::getFormToken());
		$buttonText = $escape(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_BUTTON'));
		$workingText = $escape(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_WORKING'));
		$errorText = $escape(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_ERROR'));

		return '<button type="button" class="btn btn-secondary" id="' . $buttonId
			. '" data-url="' . $url
			. '" data-context="' . $contextAttribute
			. '" data-token="' . $token
			. '" data-working="' . $workingText
			. '" data-error="' . $errorText
			. '">' . $buttonText . '</button>'
			. ' <span id="' . $statusId . '" role="status" aria-live="polite"></span>'
			. '<script>(function(){const button=document.getElementById(' . json_encode($this->id) . ');'
			. 'const status=document.getElementById(' . json_encode($this->id . '-status') . ');'
			. 'button.addEventListener("click",async function(){button.disabled=true;'
			. 'status.textContent=button.dataset.working;'
			. 'const query=new URLSearchParams({context:button.dataset.context});'
			. 'query.set(button.dataset.token,"1");'
			. 'try{const response=await fetch(button.dataset.url+"&"+query.toString(),{credentials:"same-origin",headers:{Accept:"application/json"}});'
			. 'const payload=await response.json();const result=Array.isArray(payload.data)?payload.data[0]:payload.data;'
			. 'if(!response.ok||payload.success===false||result?.success===false){throw new Error(button.dataset.error);}'
			. 'status.textContent=result?.message||button.dataset.error;'
			. '}catch(error){status.textContent=button.dataset.error;}finally{button.disabled=false;}});})();</script>';
	}
}
