<?php
/**
 * @package     JUMultiThumb\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace JUMultiThumb\Helpers;

use JUImage;

if (!class_exists('JUImage'))
{
    \JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');
}

class Image
{
    public static function thumb(array $options = []): string
    {
        $juImg = new JUImage();

        $aspect = '0';
        $cropAspect = $options['cropaspect'] ?? 0;

        if (($options['zc'] ?? 0) == 1 && $cropAspect !== 0)
        {
            $aspect = self::aspect($options['image'], (float) $cropAspect);
        }

        if ($aspect >= 1 && ($options['zc'] ?? 0) == 4)
        {
            $options['zc'] = 'T';
        }

        if ($aspect >= 1 && ($options['zc'] ?? 0) == 2)
        {
            $newImgParams = [
                'far' => '1',
                'bg'  => str_replace('#', '', (string) ($options['zoom_crop_bg'] ?? '')),
            ];
        }
        else
        {
            $newImgParams = [
                'zc' => $options['zc'] ?? 0,
            ];
        }

        if (($options['zc'] ?? 0) == 3)
        {
            $newImgParams = [
                'far' => $options['far_crop'] ?? '',
                'bg'  => str_replace('#', '', (string) ($options['zoom_crop_bg'] ?? '')),
            ];
        }

        $imgParams = [
            'w'     => $options['w'] ?? 0,
            'h'     => $options['h'] ?? 0,
            'q'     => $options['q'] ?? 90,
            'cache' => 'img',
        ];

        $blurBg = [];
        if ($aspect >= 0.98 && ($options['zc'] ?? 0) == 5)
        {
            $_imgsource = $juImg->render($options['image'], [
                'h'     => $options['h'] ?? 0,
                'cache' => 'img',
            ]);
            $_imgsource = JPATH_BASE . '/' . $_imgsource;

            $blurBg = [
                'blur_bg'  => 100,
                'fltr_100' => 'wmi|' . $_imgsource . '|C|100',
            ];
        }

        $_imgParams = array_merge($imgParams, $newImgParams, $blurBg);

        return $juImg->render($options['image'], $_imgParams);
    }

    private static function aspect(string $html, float $_cropaspect)
    {
        $juImg = new JUImage();

        $size = $juImg->size(rawurldecode(JPATH_SITE . '/' . $html));
        $width = $size->width;
        $height = ($size->height * ($_cropaspect !== '' ? $_cropaspect : '0'));

        return $height / $width;
    }
}
