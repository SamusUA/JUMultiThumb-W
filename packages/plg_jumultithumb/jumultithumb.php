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

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use JUMultiThumb\Helpers\AutoLinks;
use JUMultiThumb\Helpers\Image;
use JUMultiThumb\Helpers\Utils;

defined('_JEXEC') or die;

require_once __DIR__ . '/libraries/vendor/autoload.php';

if (!class_exists('JUImage'))
{
    \JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');
}

class plgContentjumultithumb extends CMSPlugin
{
    protected $modeHelper;
    protected JUImage $juimg;
    protected $app;
    protected ?Document $doc;
    protected $option;
    protected $itemid;

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

        if ($this->app->getName() !== 'site' || !$this->modeHelper::view('Component') || $this->modeHelper::view('Article'))
        {
            return;
        }

        $onlyFirstImage = (int) $this->params->get('Only_For_First_Image');
        $link = $this->modeHelper::link($article);

        $article->text = AutoLinks::handleImgLinks($article->text ?? '', $article->title ?? '', $link, $onlyFirstImage);
        $article->introtext = AutoLinks::handleImgLinks($article->introtext ?? '', $article->title ?? '', $link, $onlyFirstImage);
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

        if (isset($article->text))
        {
            $article->text = @$this->ImgReplace([
                'text' => $article->text,
                'article' => $article,
            ]);
        }

        if (isset($article->fulltext))
        {
            $attribs = json_decode((string) ($article->attribs ?? '')) ?: new \stdClass();
            $useWm = 1;

            if (isset($attribs->watermark_intro_only) && (int) $attribs->watermark_intro_only === 1)
            {
                $useWm = 0;
            }

            $article->fulltext = @$this->ImgReplace([
                'text' => $article->fulltext,
                'article' => $article,
                'use_wm' => $useWm,
            ]);
        }
    }

    private function ImgReplace(array $option = [])
    {
        $onlyImageBlog = $this->params->get('only_image_blog');
        $onlyImageCategory = $this->params->get('only_image_category');
        $onlyImageFeatured = $this->params->get('only_image_featured');
        $article = $option['article'] ?? null;
        $text = $option['text'] ?? '';

        if (!is_object($article))
        {
            return $text;
        }

        $attribs = json_decode((string) ($article->attribs ?? '')) ?: new \stdClass();
        $watermarkO = $attribs->watermark ?? '0';
        $watermarkS = $attribs->watermark_s ?? '0';

        if (($option['use_wm'] ?? 1) == 0)
        {
            $watermarkO = '0';
            $watermarkS = '0';
        }

        $text = preg_replace('#<img(.*?)mce_src="(.*?)"(.*?)>#s', "<img\\1\\3>", $text);
        $text = preg_replace('#<p>\s*<img(.*?)/>\s*</p>#s', "<img\\1\\3>", $text);
        $text = preg_replace('#<p>\s*<img(.*?)/>\s*#s', "<img\\1\\3><p>", $text);

        preg_match_all('/<img[^>]+>/i', $text, $imageAttr);
        if (count(array_filter($imageAttr[0] ?? [])) > 0)
        {
            foreach ($imageAttr[0] as $image)
            {
                $replace = $this->JUMultithumbReplacer([
                    'image' => $image,
                    'article' => $article,
                    'watermark_o' => $watermarkO,
                    'watermark_s' => $watermarkS,
                ]);
                $text = str_replace($image, $replace, $text);
            }
        }

        if (($onlyImageBlog == 1 && $this->modeHelper::view('Blog')) || ($onlyImageCategory == 1 && $this->modeHelper::view('Category')) || ($onlyImageFeatured == 1 && $this->modeHelper::view('Featured')))
        {
            preg_match_all('/(<\s*img\s+src\s*=\s*"\s*("[^"]*"|\'[^\']*\'|[^"\s]+).*?>)/i', $text, $result);
            if (!empty($result[1][0]))
            {
                $img = $result[1][0];
                $text = $img;
            }
        }

        return $text;
    }

    private function JUMultithumbReplacer(array $option = []): string
    {
        $quality = $this->params->get('quality');
        $noimageClass = $this->params->get('noimage_class');
        $thumbFilterColor = $this->params->get('thumb_filtercolor', 0);
        $colorized = $this->params->get('colorized', '25');
        $colorpicker = $this->params->get('colorpicker', '#0000ff');
        $thumbThSetting = $this->params->get('thumb_th_seting', 0);
        $thumbFilters = $this->params->get('thumb_filters', 1);
        $usm = $this->params->get('thumb_unsharp', 1);
        $thumbUnsharpAmount = $this->params->get('thumb_unsharp_amount', 80);
        $thumbUnsharpRadius = $this->params->get('thumb_unsharp_radius', 1);
        $thumbUnsharpThreshold = $this->params->get('thumb_unsharp_threshold', 3);
        $thumbBlur = $this->params->get('thumb_blur', 0);
        $thumbBlurSetting = $this->params->get('thumb_blur_seting', 1);
        $thumbBrit = $this->params->get('thumb_brit', 0);
        $thumbBritSetting = $this->params->get('thumb_brit_seting', 50);
        $thumbCont = $this->params->get('thumb_cont', 0);
        $thumbContSetting = $this->params->get('thumb_cont_seting', 50);
        $items = $this->params->get('items');

        $attribs = json_decode((string) ($option['article']->attribs ?? '')) ?: new \stdClass();
        $useWm = 1;
        if (!empty($attribs->watermark_off))
        {
            $useWm = 0;
        }

        switch ($thumbFilterColor)
        {
            case '1':
                $impFilterColor = ['fltr_1' => 'gray'];
                break;
            case '2':
                $impFilterColor = ['fltr_1' => 'sep'];
                break;
            case '3':
                $impFilterColor = ['fltr_1' => 'th|' . $thumbThSetting];
                break;
            case '4':
                $impFilterColor = ['fltr_1' => 'clr|' . $colorized . '|' . str_replace('#', '', $colorpicker)];
                break;
            default:
                $impFilterColor = [];
                break;
        }

        $usmFilterColor = [];
        if ($usm == 1 && $thumbFilters == 1)
        {
            $usmFilterColor = ['fltr_2' => 'usm|' . $thumbUnsharpAmount . '|' . $thumbUnsharpRadius . '|' . $thumbUnsharpThreshold];
        }

        $blurFilterColor = [];
        if ($thumbBlur == 1 && $thumbFilters == 1)
        {
            $blurFilterColor = ['fltr_3' => 'blur|' . $thumbBlurSetting];
        }

        $britFilterColor = [];
        if ($thumbBrit == 1 && $thumbFilters == 1)
        {
            $britFilterColor = ['fltr_4' => 'brit|' . $thumbBritSetting];
        }

        $contFilterColor = [];
        if ($thumbCont == 1 && $thumbFilters == 1)
        {
            $contFilterColor = ['fltr_5' => 'cont|' . $thumbContSetting];
        }

        $lightbox = $this->params->get('selectlightbox');

        preg_match_all('/(width|height|src|alt|title|class|align|style)=("[^"]*")/i', $option['image'], $imgAttr);
        $countAttr = count($imgAttr[0] ?? []);
        $img = [];

        for ($i = 0; $i < $countAttr; $i++)
        {
            $img[$imgAttr[1][$i]] = str_replace('"', '', $imgAttr[2][$i]);
        }

        $imgsource = Utils::img_source($img['src'] ?? '');
        $imgalt = $img['alt'] ?? '';
        $imgtitle = $img['title'] ?? '';
        $imgalign = $img['align'] ?? '';

        $imgstyle = '';
        if (preg_match('#float:(.*?);#s', $img['style'] ?? '', $imgstyle))
        {
            $imgstyle = $imgstyle[1];
        }

        $imgClass = Utils::img_class([
            'imgclass' => $img['class'] ?? '',
            'imgalign' => $imgalign,
            'imgstyle' => $imgstyle,
            'view' => $this->app->input->get('view'),
        ]);

        $imgAlt = Utils::img_alt($img['alt'] ?? '');
        $imgtitle = mb_strtoupper(mb_substr($imgtitle, 0, 1)) . mb_substr($imgtitle, 1);
        $title = ($imgAlt ?: $imgtitle);
        $title = ($title ?: ($option['article']->title ?? ''));
        $title = ($title ? ' title="' . $title . '"' : '');

        if ($this->params->get('resall') == 0 && ($img['class'] ?? '') !== 'juimage')
        {
            $size = getimagesize(JPATH_SITE . '/' . $imgsource);

            return Utils::image('jumultithumb', 'default', [
                'image' => $imgsource,
                'orig_img' => $imgsource,
                'link_img' => $imgsource,
                'w' => $size[0],
                'h' => $size[1],
                'title' => $title,
                'caption' => $title,
                'lightbox' => $lightbox,
            ]);
        }

        $w = $this->params->get('width');
        $h = $this->params->get('height');
        $zc = $this->params->get('zc');
        $zoomCropBg = $this->params->get('zoom_crop_bg');
        $cropaspect = $this->params->get('cropaspect');
        $farcrop = $this->params->get('farcrop');
        $farcropbg = $this->params->get('farcropbg');
        $noresize = $this->params->get('noresize');
        $nofullimg = $this->params->get('nofullimg');

        $thumbImg = Image::thumb([
            'image' => $imgsource,
            'noresize' => $noresize,
            'zc' => $zc,
            'cropaspect' => $cropaspect,
            'zoom_crop_bg' => $zoomCropBg,
            'w' => $w,
            'h' => $h,
            'farcrop' => $farcrop,
            'farcropbg' => $farcropbg,
            'q' => $quality,
        ]);

        return Utils::image('jumultithumb', 'default', [
            'image' => $thumbImg,
            'orig_img' => $imgsource,
            'link_img' => $imgsource,
            'w' => $w,
            'h' => $h,
            'title' => $title,
            'caption' => $title,
            'lightbox' => $lightbox,
        ]);
    }

    public function onBeforeCompileHead(): bool
    {
        if ($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
        {
            return true;
        }

        if ($this->params->get('uselightbox', '1') == 1 && (($this->modeHelper::view('Article')) || ($this->modeHelper::view('Categories')) || ($this->modeHelper::view('CatBlog'))) && !($this->modeHelper::view('Print')))
        {
            if ($this->params->get('jujq') == 0)
            {
                HTMLHelper::_('jquery.framework');
            }

            $juhead = '';

            switch ($this->params->get('selectlightbox'))
            {
                case 'customjs':
                    $juhead .= "\r";
                    if ($this->params->get('customjsparam'))
                    {
                        $juhead .= "\n            " . $this->params->get('customjsparam');
                    }
                    break;

                default:
                case 'jmodal':
                    HTMLHelper::_('bootstrap.renderModal');
                    break;
            }

            $this->doc->addScriptDeclaration($juhead);
        }

        return true;
    }
}
























































































































































































































































"}]} , 