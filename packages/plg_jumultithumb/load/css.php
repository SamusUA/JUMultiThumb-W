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

namespace JUMultiThumb\Adapters;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;

defined('_JEXEC') or die;

class com_content
{
    public static function view($type): string
    {
        $app = Factory::getApplication();
        $option = $app->input->get('option');
        $view = $app->input->get('view');
        $layout = $app->input->get('layout');
        $print = $app->input->get('print');

        switch ($type)
        {
            case 'Component':
                return ($option === 'com_content') ? '1' : '0';

            case 'CatBlog':
                return (($view === 'category' && ($layout === 'blog')) || ($view === 'category' && ($layout === 'card'))) ? '1' : '0';

            case 'Blog':
                return ($layout === 'blog') ? '1' : '0';

            case 'Category':
                return ($view === 'categories' && !$layout) ? '1' : '0';

            case 'Categories':
                return ($view === 'categories') ? '1' : '0';

            case 'Featured':
                return ($view === 'featured') ? '1' : '0';

            case 'Print':
                return ($print == '1') ? '1' : '0';

            case 'Article':
                return ($view === 'article') ? '1' : '0';
        }

        return '';
    }

    public static function link($article): string
    {
        $app = Factory::getApplication();

        if (!empty($article->params) && $article->params->get('access-view'))
        {
            return Route::_(RouteHelper::getArticleRoute($article->slug, $article->catid));
        }

        $active = $app->getMenu()->getActive();
        $itemId = $active ? $active->id : 0;
        $link1 = Route::_('index.php?option=com_users&view=login&Itemid=' . (int) $itemId);
        $returnURL = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catid));
        $link = new Uri($link1);
        $link->setVar('return', base64_encode($returnURL));

        return (string) $link;
    }
}
