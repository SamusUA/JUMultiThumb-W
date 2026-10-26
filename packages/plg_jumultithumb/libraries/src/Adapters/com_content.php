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

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use JUMultiThumb\Helpers\AutoLinks;
use JUMultiThumb\Helpers\Image;
use JUMultiThumb\Helpers\Utils;

defined('_JEXEC') or die;

require_once dirname(__DIR__) . '/jumultithumb/libraries/vendor/autoload.php';

if (!class_exists('JUImage'))
{
    \JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');
}

class plgContentJUMULTITHUMB_Gallery extends CMSPlugin
{
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage();

        $this->juimg = new JUImage();
        $this->app = Factory::getApplication();
        $this->doc = Factory::getDocument();
        $this->option = $this->app->input->get('option');
        $this->itemid = $this->app->input->getInt('Itemid');
        $this->modeHelper = '\\JUMultiThumb\\Adapters\\' . ($this->option ?: 'com_content');
    }

    public function onContentBeforeDisplay($context, $article): void
    {
        if (!is_object($article) || !class_exists($this->modeHelper))
        {
            return;
        }

        if ($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
        {
            return;
        }

        if ($this->modeHelper::view('Article'))
        {
            return;
        }

        $link = $this->modeHelper::link($article);
        $article->text = AutoLinks::handleImgLinks($article->text ?? '', $article->title ?? '', $link, 1);
    }

    public function onContentPrepare($context, $article): void
    {
        if (!is_object($article) || !class_exists($this->modeHelper))
        {
            return;
        }

        if ($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
        {
            return;
        }

        if (!($this->modeHelper::view('Article')) && ($this->params->get('useimgagegallery') == '0'))
        {
            $regex = "/<p>\s*{gallery\s+(.*?)}\s*<\/p>/i";
            preg_match_all($regex, $article->text ?? '', $matches, PREG_SET_ORDER);

            foreach ($matches as $val)
            {
                if ($val)
                {
                    $article->text = preg_replace($regex, '', $article->text ?? '', 1);

                    return;
                }
            }
        }

        if (isset($article->text))
        {
            $article->text = @$this->GalleryReplace($article->text, $article);
        }

        if (isset($article->fulltext))
        {
            $article->fulltext = @$this->GalleryReplace($article->fulltext, $article);
        }
    }

    public function GalleryReplace($text, $article)
    {
        $regex = "/{gallery\s+(.*?)}/i";
        preg_match_all($regex, $text ?? '', $matches, PREG_SET_ORDER);

        if ($matches)
        {
            $plugin = PluginHelper::getPlugin('content', 'jumultithumb');
            $json = $plugin && !empty($plugin->params) ? json_decode($plugin->params) : new \stdClass();

            foreach ($matches as $match)
            {
                $matcheslist = explode('|', $match[1] ?? '');

                $galltitle = null;
                $gallstyle = null;

                if (!array_key_exists(1, $matcheslist))
                {
                    $matcheslist[1] = null;
                }

                if (!array_key_exists(2, $matcheslist))
                {
                    $matcheslist[2] = $galltitle;
                }

                if (!array_key_exists(3, $matcheslist))
                {
                    $matcheslist[3] = $gallstyle;
                }

                $gallwidth = $this->params->get('gallwidth');
                $gallheight = $this->params->get('gallheight');
                $gallcropzoom = $this->params->get('gallcropzoom');

                $galltitle = str_replace('title=', '', trim($matcheslist[1] ?? ''));
                if (!$galltitle)
                {
                    $galltitle = $this->params->get('gallery_title');
                }

                $gallstyle = str_replace('class=', '', trim($matcheslist[2] ?? ''));
                if (!$gallstyle)
                {
                    $gallstyle = $this->params->get('cssclass');
                }

                $imgTitle = preg_replace('/"/', "'", $article->title ?? '');
                $lightbox = $this->params->get('selectlightbox');
                $folder = trim($matcheslist[0] ?? '');
                $imgpath = 'images/' . $folder;
                $root = JPATH_ROOT . '/';
                $imgFolder = $root . $imgpath;

                $html = '';
                if (is_dir($imgFolder))
                {
                    $images = glob($imgFolder . '/{*.[jJ][pP][gG],*.[jJ][pP][eE][gG],*.[gG][iI][fF],*.[pP][nN][gG],*.[bB][mM][pP],*.[tT][iI][fF],*.[tT][iI][fF][fF]}', GLOB_BRACE);
                    $images = str_replace($root, '', $images);

                    $_gallery = [];
                    foreach ($images as $file)
                    {
                        $thumbFilterColor = $json->thumb_filtercolor ?? 0;

                        switch ($thumbFilterColor)
                        {
                            case '1':
                                $impFilterColor = ['fltr_1' => 'gray'];
                                break;
                            case '2':
                                $impFilterColor = ['fltr_1' => 'sep'];
                                break;
                            case '3':
                                $impFilterColor = ['fltr_1' => 'th|' . ($json->thumb_th_seting ?? 10)];
                                break;
                            case '4':
                                $impFilterColor = ['fltr_1' => 'clr|' . ($json->colorized ?? 25) . '|' . str_replace('#', '', (string) ($json->colorpicker ?? '#0000ff'))];
                                break;
                            default:
                                $impFilterColor = [];
                                break;
                        }

                        $usmFilterColor = [];
                        if (($json->thumb_unsharp ?? 0) == 1)
                        {
                            $usmFilterColor = ['fltr_2' => 'usm|' . ($json->thumb_unsharp_amount ?? 80) . '|' . ($json->thumb_unsharp_radius ?? 1) . '|' . ($json->thumb_unsharp_threshold ?? 3)];
                        }

                        $blurFilterColor = [];
                        if (($json->thumb_blur ?? 0) == 1)
                        {
                            $blurFilterColor = ['fltr_3' => 'blur|' . ($json->thumb_blur_seting ?? 1)];
                        }

                        $britFilterColor = [];
                        if (($json->thumb_brit ?? 0) == 1)
                        {
                            $britFilterColor = ['fltr_4' => 'brit|' . ($json->thumb_brit_seting ?? 50)];
                        }

                        $contFilterColor = [];
                        if (($json->thumb_cont ?? 0) == 1)
                        {
                            $contFilterColor = ['fltr_5' => 'cont|' . ($json->thumb_cont_seting ?? 50)];
                        }

                        if (!$this->modeHelper::view('Article') && ($this->params->get('useimgagegallery') == '1'))
                        {
                            $_title = mb_strtoupper(mb_substr($imgTitle, 0, 1)) . mb_substr($imgTitle, 1);

                            $thumbImg = Image::thumb([
                                'image' => $file,
                                'zc' => $this->params->get('cropzoom'),
                                'w' => $this->params->get('width'),
                                'h' => $this->params->get('height'),
                                'q' => $json->quality ?? 90,
                            ]);

                            return Utils::image('jumultithumb_gallery', 'image', [
                                'image' => $thumbImg,
                                'orig_img' => $file,
                                'link_img' => $file,
                                'w' => $this->params->get('width'),
                                'h' => $this->params->get('height'),
                                'title' => $_title,
                                'caption' => $_title,
                            ]);
                        }

                        $imgParams = [
                            'image' => $file,
                            'zc' => $gallcropzoom,
                            'w' => $gallwidth,
                            'h' => $gallheight,
                            'q' => $json->quality ?? 90,
                        ];

                        $options = array_merge($impFilterColor, $usmFilterColor, $blurFilterColor, $britFilterColor, $contFilterColor, $imgParams);

                        $thumbImg = Image::thumb($options);

                        $_title = ($galltitle == '' ? $imgTitle : $galltitle . '. ' . $imgTitle);
                        $_title = mb_strtoupper(mb_substr($_title, 0, 1)) . mb_substr($_title, 1);

                        $_gallery[] = Utils::image('jumultithumb_gallery', 'image', [
                            'image' => $thumbImg,
                            'orig_img' => $file,
                            'link_img' => $file,
                            'w' => $gallwidth,
                            'h' => $gallheight,
                            'title' => $_title,
                            'caption' => $_title,
                            'lightbox' => $lightbox,
                        ]);
                    }

                    $gallery = implode($_gallery);

                    $html = Utils::tmpl('jumultithumb_gallery', 'gallery', [
                        'gallery' => $gallery,
                        'gallstyle' => $gallstyle,
                        'galltitle' => $galltitle,
                    ]);
                }

                $text = preg_replace($regex, $html, $text, 1);
            }
        }

        return $text;
    }
}
