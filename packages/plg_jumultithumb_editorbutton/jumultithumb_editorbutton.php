<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2023 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\Attribute\AllowUnauthorizedAdministratorAccess;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;

defined('_JEXEC') or die;

class plgButtonJUmultithumb_EditorButton extends CMSPlugin
{
	protected $autoloadLanguage = true;

	#[AllowUnauthorizedAdministratorAccess]
	public function onAjaxJumultithumb_editorbutton(): string
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();
		$articleId = (int) $app->getUserState('com_content.edit.article.id');
		$allowed = $user->authorise('core.create', 'com_content')
			|| $user->authorise('core.edit', 'com_content')
			|| count($user->getAuthorisedCategories('com_content', 'core.create')) > 0
			|| ($articleId > 0 && $user->authorise('core.edit', 'com_content.article.' . $articleId));

		if(!$allowed)
		{
			$app->setHeader('status', 403, true);

			return '';
		}

		$task = $app->input->getCmd('task', 'form');

		if($task === 'form')
		{
			ob_start();
			require __DIR__ . '/form.php';

			return (string) ob_get_clean();
		}

		if($task !== 'folders' || $app->input->getMethod() !== 'POST' || !Session::checkToken('post'))
		{
			$app->setHeader('status', 403, true);

			return '';
		}

		return $this->renderFolderList($app->input->post->getString('dir', ''));
	}

	private function renderFolderList(string $relativePath): string
	{
		$relativePath = trim(str_replace('\\', '/', $relativePath), '/');
		$segments = explode('/', $relativePath);

		if(strtolower($segments[0] ?? '') !== 'images'
			|| in_array('', $segments, true)
			|| in_array('..', $segments, true)
			|| in_array('.', $segments, true)
			|| str_contains($relativePath, ':')
			|| str_contains($relativePath, "\0"))
		{
			Factory::getApplication()->setHeader('status', 400, true);

			return '';
		}

		$siteRoot = realpath(JPATH_SITE);
		$imagesPath = JPATH_SITE . DIRECTORY_SEPARATOR . 'images';
		$imagesRoot = is_link($imagesPath) ? false : realpath($imagesPath);

		if($siteRoot === false || $imagesRoot === false || !is_dir($imagesRoot))
		{
			Factory::getApplication()->setHeader('status', 404, true);

			return '';
		}

		if(!$this->isWithinPath($imagesRoot, $siteRoot))
		{
			Factory::getApplication()->setHeader('status', 400, true);

			return '';
		}

		$directory = $imagesRoot;

		foreach(array_slice($segments, 1) as $segment)
		{
			$candidate = $directory . DIRECTORY_SEPARATOR . $segment;

			if(is_link($candidate))
			{
				Factory::getApplication()->setHeader('status', 400, true);

				return '';
			}

			$resolved = realpath($candidate);

			if($resolved === false || !is_dir($resolved) || !$this->isWithinPath($resolved, $imagesRoot))
			{
				Factory::getApplication()->setHeader('status', 404, true);

				return '';
			}

			$directory = $resolved;
		}

		$folders = scandir($directory);

		if($folders === false)
		{
			Factory::getApplication()->setHeader('status', 500, true);

			return '';
		}

		$subfolders = [];

		foreach($folders as $folder)
		{
			$path = $directory . DIRECTORY_SEPARATOR . $folder;
			$resolved = realpath($path);

			if($folder !== '.' && $folder !== '..' && !is_link($path) && $resolved !== false
				&& is_dir($resolved) && $this->isWithinPath($resolved, $imagesRoot))
			{
				$subfolders[] = $folder;
			}
		}

		natcasesort($subfolders);
		$html = '<ul class="jqueryFileTree">';

		foreach($subfolders as $folder)
		{
			$childPath = $relativePath . '/' . $folder . '/';
			$html .= '<li class="directory collapsed"><a href="#" rel="'
				. htmlspecialchars($childPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
				. htmlspecialchars($folder, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
				. '</a></li>';
		}

		return $html . '</ul>';
	}

	private function isWithinPath(string $path, string $root): bool
	{
		$normalizedPath = rtrim(str_replace('\\', '/', $path), '/');
		$normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
		$rootPrefix = $normalizedRoot . '/';
		$compare = DIRECTORY_SEPARATOR === '\\' ? 'strncasecmp' : 'strncmp';

		return $compare($normalizedPath, $normalizedRoot, strlen($normalizedRoot)) === 0
			&& ($normalizedPath === $normalizedRoot || $compare($normalizedPath, $rootPrefix, strlen($rootPrefix)) === 0);
	}

	/**
	 * @param $name
	 * @param $asset
	 * @param $author
	 *
	 * @return \Joomla\CMS\Object\CMSObject|void
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onDisplay($name, $asset, $author)
	{
		$user             = Factory::getUser();
		$canCreateRecords = $user->authorise('core.create', 'com_content') || count($user->getAuthorisedCategories('com_content', 'core.create')) > 0;
		$articleId        = (int) Factory::getApplication()->getUserState('com_content.edit.article.id');
		$isEditingRecords = $articleId > 0 && $user->authorise('core.edit', 'com_content.article.' . $articleId);
		$hasAccess        = $canCreateRecords || $isEditingRecords;

		if(!$hasAccess)
		{
			return;
		}

		$link            = 'index.php?option=com_ajax&plugin=jumultithumb_editorbutton&group=editors-xtd&format=raw&task=form&editor=' . rawurlencode((string) $name);
		$button          = new CMSObject();
		$button->modal   = true;
		$button->link    = $link;
		$button->text    = Text::_('PLG_JUMULTITHUMB') . ' - ' . Text::_('COM_PLUGINS_GALLERY_FIELDSET_LABEL');
		$button->name    = 'image';
		$button->icon    = 'gallery';
		$button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M22 16V4c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2zm-11-4l2.03 2.71L16 11l4 5H8l3-4zM2 6v14c0 1.1.9 2 2 2h14v-2H4V6H2z"/></svg>';
		$button->options = [
			'height'     => '300px',
			'width'      => '800px',
			'bodyHeight' => '70',
			'modalWidth' => '80',
		];

		return $button;
	}
}