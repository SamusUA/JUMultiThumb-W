<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2026 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class Pkg_JUMultiThumbInstallerScript
{
	protected $dbSupport = ['mysql', 'mysqli', 'postgresql', 'sqlsrv', 'sqlazure'];
	protected $message;
	protected $status;

	public function preflight()
	{
		if(version_compare(JVERSION, '5.0', 'lt'))
		{
			Factory::getApplication()->enqueueMessage('Update for Joomla! 5.0+', 'error');
			return false;
		}

		$lang = Factory::getLanguage();
		$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

		if(!in_array(Factory::getDbo()->name, $this->dbSupport, true))
		{
			Factory::getApplication()->enqueueMessage(Text::_('PLG_JUMULTITHUMB_ERROR_DB_SUPPORT'), 'error');
			return false;
		}

		$this->makeDir(JPATH_SITE . '/img');

		if(!is_dir(JPATH_SITE . '/img/'))
		{
			Factory::getApplication()->enqueueMessage("Error creating folder 'img'. Please manually create the folder 'img' in the root of the site where you installed Joomla!", 'error');
		}

		$cache = Factory::getCache('plg_jumultithumb');
		$cache->clean();

		return true;
	}

	private function makeDir($dir)
	{
		if(@mkdir($dir, 0777, true) || is_dir($dir))
		{
			return true;
		}

		if(!$this->makeDir(dirname($dir)))
		{
			return false;
		}

		return mkdir($dir, 0777, true);
	}

	public function postflight()
	{
		$db = Factory::getDbo();

		$qv = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -100 WHERE `element` = ' . $db->Quote('jumultithumb') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->execute();

		$qv = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -99 WHERE `element` = ' . $db->Quote('jumultithumb_gallery') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->execute();

		$qv = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -98 WHERE `element` = ' . $db->Quote('jumultithumb_contentform') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->execute();

		$qv = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -97 WHERE `element` = ' . $db->Quote('jumultithumb_editorbutton') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->execute();

		return true;
	}
}