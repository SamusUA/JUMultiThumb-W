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

class Pkg_JUMultiThumbInstallerScript
{
    protected $dbSupport = ['mysql', 'mysqli', 'postgresql', 'sqlsrv', 'sqlazure'];
    protected $message;
    protected $status;

    public function preflight()
    {
        if (version_compare(JVERSION, '4.0', 'lt'))
        {
            Factory::getApplication()->enqueueMessage('Update for Joomla! 4.0+ required.', 'error');

            return false;
        }

        $lang = Factory::getLanguage();
        $lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

        if (!in_array(Factory::getDbo()->name, $this->dbSupport, true))
        {
            Factory::getApplication()->enqueueMessage(Text::_('PLG_JUMULTITHUMB_ERROR_DB_SUPPORT'), 'error');

            return false;
        }

        $this->makeDir(JPATH_SITE . '/img');

        if (!is_dir(JPATH_SITE . '/img/'))
        {
            Factory::getApplication()->enqueueMessage("Error creating folder 'img'. Please manually create the folder 'img' in the root of the site where you installed Joomla!", 'error');
        }

        $cache = Factory::getCache('plg_jumultithumb');
        $cache->clean();

        return true;
    }

    private function makeDir($dir)
    {
        if (@mkdir($dir, 0777, true) || is_dir($dir))
        {
            return true;
        }

        if (!$this->makeDir(dirname($dir)))
        {
            return false;
        }

        return mkdir($dir, 0777, true);
    }

    public function postflight()
    {
        $db = Factory::getDbo();

        $query = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -100 WHERE `element` = ' . $db->quote('jumultithumb') . ' AND `type` = ' . $db->quote('plugin') . ' AND `client_id` = 0';
        $db->setQuery($query);
        $db->execute();

        $query = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -99 WHERE `element` = ' . $db->quote('jumultithumb_gallery') . ' AND `type` = ' . $db->quote('plugin') . ' AND `client_id` = 0';
        $db->setQuery($query);
        $db->execute();

        $query = 'UPDATE `#__extensions` SET `enabled` = 1 WHERE `element` = ' . $db->quote('jumultithumb_editorbutton') . ' AND `type` = ' . $db->quote('plugin') . ' AND `client_id` = 0';
        $db->setQuery($query);
        $db->execute();

        $query = 'UPDATE `#__extensions` SET `enabled` = 1 WHERE `element` = ' . $db->quote('jumultithumb_contentform') . ' AND `type` = ' . $db->quote('plugin') . ' AND `client_id` = 0';
        $db->setQuery($query);
        $db->execute();

        return true;
    }

    public function unlinkRecursive($dir, $deleteRootToo = false)
    {
        if (!$dh = opendir($dir))
        {
            return true;
        }

        while (($obj = readdir($dh)) !== false)
        {
            if ($obj === '.' || $obj === '..')
            {
                continue;
            }

            if (!unlink($dir . '/' . $obj))
            {
                $this->unlinkRecursive($dir . '/' . $obj, true);
            }
        }

        closedir($dh);

        if ($deleteRootToo)
        {
            @rmdir($dir);
        }

        return true;
    }
}
