<?php
/**
 * @package     JUMultiThumb
 * @subpackage  plg_editors-xtd_jumultithumb_editorbutton
 * @copyright   (C) 2007-2026 Denys Nosov, WEBUS Studio
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $dispatcher = $container->get(DispatcherInterface::class);
                $config     = (array) PluginHelper::getPlugin('editors-xtd', 'jumultithumb_editorbutton');

                require_once dirname(__DIR__) . '/jumultithumb_editorbutton.php';

                $plugin = new \plgButtonJUmultithumb_EditorButton($dispatcher, $config);
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};