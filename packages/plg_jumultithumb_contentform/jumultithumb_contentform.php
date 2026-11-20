<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2023 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @copyright        2026 (C) WEBUS Studio, https://webus-studio.com. All rights reserved.
 * @contributor      AI-assisted adaptation and maintenance by WEBUS Studio for Joomla 5/6 compatibility.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Form\Form;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class plgContentJUMultithumb_ContentForm extends CMSPlugin
{
	/**
	 * plgContentJUMultithumb_ContentForm constructor.
	 *
	 * @param $subject
	 * @param $config
	 *
	 * @since 7.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
	}

	/**
	 * @param $form
	 * @param $data
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function onContentPrepareForm($form, $data): void
	{
		if (!($form instanceof Form))
		{
			return;
		}

		if ($form->getName() !== 'com_content.article')
		{
			return;
		}

		Form::addFormPath(__DIR__ . '/forms');

		$form->loadFile('article', false);
	}
}