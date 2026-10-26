<?php
/**
 * @package     JUMultiThumb\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace JUMultiThumb\Helpers;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;

class Utils
{
    public static function img_source(string $src): string
    {
        return ltrim(str_replace(Uri::base(), '', $src), '/');
    }

    public static function img_alt(string $alt): string
    {
        $alt = (string) $alt;

        return mb_strtoupper(mb_substr($alt, 0, 1)) . mb_substr($alt, 1);
    }

    public static function img_class(array $options = []): string
    {
        $imgClass = '';
        $view = $options['view'] ?? '';

        if (!empty($options['imgalign']))
        {
            $imgClass = 'ju' . trim((string) $options['imgalign']) . ' ';
        }
        elseif (!empty($options['imgstyle']))
        {
            $imgClass = 'ju' . trim((string) $options['imgstyle']) . ' ';
        }

        return 'juimage ' . ($options['imgclass'] ?? '') . ' ' . $imgClass . 'juimg-' . $view;
    }

    public static function image(string $plugin, string $name, array $options = []): string
    {
        $link = $options['link_img'] ?? '';
        $origImg = $options['orig_img'] ?? '';
        $linkImg = $options['link_img'] ?? '';
        $lightbox = '';
        $lightboxData = '';

        switch ($options['lightbox'] ?? 'jmodal')
        {
            case 'lightgallery':
                $link = '#';
                $lightbox = ' ';
                $lightboxData = ' ' . ($linkImg ? 'data-src="' . Uri::base() . $linkImg . '"' : '') . ' ' . ($origImg ? 'data-download-url="' . Uri::base() . $origImg . '"' : '');
                break;

            case 'colorbox':
                $link = $linkImg;
                $lightbox = ' class="lightbox" rel="lightbox[gall]"';
                break;

            default:
            case 'jmodal':
                $link = $linkImg;
                $lightbox = ' rel="{handler: \'image\', marginImage: {x: 50, y: 50}}"';
                break;
        }

        return Utils::tmpl($plugin, $name, [
            'img'            => $options['image'] ?? '',
            'w'              => $options['w'] ?? '',
            'h'              => $options['h'] ?? '',
            'class'          => $options['class'] ?? '',
            'alt'            => $options['alt'] ?? '',
            'caption'        => $options['caption'] ?? '',
            'title'          => $options['title'] ?? '',
            'link_img'       => $linkImg,
            'orig_img'       => $origImg,
            'link'           => $link,
            'lightbox'       => $lightbox,
            'lightbox_data ' => $lightboxData,
        ]);
    }

    public static function tmpl(string $plugin, string $name, array $variables = []): string
    {
        $template = Factory::getApplication()->getTemplate();
        $override = JPATH_SITE . '/templates/' . $template . '/html/' . $plugin . '/';
        $tmpl = JPATH_SITE . '/plugins/content/' . $plugin . '/tmpl/';
        $filename = $override . $name . '.php';

        if (is_file($filename))
        {
            return (new FileLayout($name, $override))->render($variables);
        }

        return (new FileLayout($name, $tmpl))->render($variables);
    }
}
