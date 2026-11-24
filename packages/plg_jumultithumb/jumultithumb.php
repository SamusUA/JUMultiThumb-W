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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Filesystem\Folder;
use JUMultiThumb\Helpers\AutoLinks;
use JUMultiThumb\Helpers\ContextSettings;
use JUMultiThumb\Helpers\Image;
use JUMultiThumb\Helpers\Utils;

defined('_JEXEC') or die;

require_once __DIR__ . '/libraries/vendor/autoload.php';

class plgContentjumultithumb extends CMSPlugin
{
    protected $modeHelper;
    protected $juimg;
    protected $app;
    protected ?Document $doc;
    protected $option;
    protected $itemid;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $this->loadLanguage();

        $this->app = Factory::getApplication();
        $this->option = (string) $this->app->input->get('option', '');
        if ($this->option !== 'com_content')
        {
            return;
        }

        $this->doc = Factory::getDocument();
        $this->itemid = $this->app->input->getInt('Itemid');

        if (!class_exists('JUImage') && file_exists(JPATH_LIBRARIES . '/juimage/JUImage.php'))
        {
            require_once JPATH_LIBRARIES . '/juimage/JUImage.php';
        }

        if (class_exists('JUImage'))
        {
            $this->juimg = new JUImage();
        }

        $this->modeHelper = '\\JUMultiThumb\\Adapters\\com_content';
    }

    public function onAjaxJumultithumb(): array
    {
        if (!Session::checkToken('get'))
        {
            throw new \RuntimeException(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_ERROR'), 403);
        }

        if (!Factory::getUser()->authorise('core.manage', 'com_plugins'))
        {
            throw new \RuntimeException(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_ERROR'), 403);
        }

        $context = Factory::getApplication()->input->getCmd('context');

        if (!in_array($context, ContextSettings::CONTEXTS, true))
        {
            throw new \InvalidArgumentException(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_ERROR'), 400);
        }

        $imageRoot = realpath(JPATH_ROOT . DIRECTORY_SEPARATOR . 'img');
        $contextPath = $imageRoot === false
            ? false
            : realpath($imageRoot . DIRECTORY_SEPARATOR . $context);

        if ($imageRoot === false || $contextPath === false)
        {
            return ['success' => true, 'message' => Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_EMPTY')];
        }

        $rootPrefix = rtrim(str_replace('\\', '/', $imageRoot), '/') . '/';
        $normalizedContextPath = str_replace('\\', '/', $contextPath);

        if (!is_dir($contextPath)
            || strncasecmp($normalizedContextPath, $rootPrefix, strlen($rootPrefix)) !== 0
            || basename($contextPath) !== $context)
        {
            throw new \RuntimeException(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_ERROR'), 400);
        }

        if (!Folder::delete($contextPath))
        {
            throw new \RuntimeException(Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_ERROR'), 500);
        }

        return ['success' => true, 'message' => Text::_('PLG_JUMULTITHUMB_CLEAR_CACHE_DONE')];
    }

    public function onContentBeforeDisplay($context, $article): string
    {
        if ($this->option !== 'com_content' || !is_object($article) || !isset($article->catid, $article->slug) || !class_exists($this->modeHelper))
        {
            return '';
        }

        if ($this->app->getName() !== 'site' || !$this->modeHelper::view('Component') || $this->modeHelper::view('Article'))
        {
            return '';
        }

        $introImage = (string) ($article->_jumultithumbIntroImage ?? '');
        unset($article->_jumultithumbIntroImage);

        $onlyFirstImage = (int) $this->params->get('Only_For_First_Image');
        $link = $this->modeHelper::link($article);
        if ($link === '')
        {
            return $introImage;
        }

        $article->text = AutoLinks::handleImgLinks($article->text ?? '', $article->title ?? '', $link, $onlyFirstImage);
        $article->introtext = AutoLinks::handleImgLinks($article->introtext ?? '', $article->title ?? '', $link, $onlyFirstImage);

        return $introImage;
    }

    public function onContentPrepare($context, $article): void
    {
        if ($this->option !== 'com_content' || !is_object($article) || !class_exists($this->modeHelper))
        {
            return;
        }

        if ($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
        {
            return;
        }

        $isArticleView = $this->modeHelper::view('Article');
        $introText = isset($article->introtext) && is_string($article->introtext) ? $article->introtext : null;
        $text = isset($article->text) && is_string($article->text) ? $article->text : null;
        $attribs = json_decode((string) ($article->attribs ?? '')) ?: new \stdClass();
        $watermarkAllowed = (int) ($attribs->watermark_intro_only ?? 0) !== 1;
        $processedIntroText = null;

        if ($introText !== null)
        {
            $processedIntroText = @$this->ImgReplace([
                'text' => $introText,
                'article' => $article,
            ]);
            $article->introtext = $processedIntroText;
        }

        if ($text !== null)
        {
            if ($introText !== null && $text === $introText)
            {
                $article->text = $processedIntroText;
            }
            elseif (!$watermarkAllowed && is_string($article->fulltext ?? null)
                && $article->fulltext !== '' && ($fullTextPosition = strrpos($text, $article->fulltext)) !== false)
            {
                $article->text = @$this->ImgReplace([
                    'text' => substr($text, 0, $fullTextPosition),
                    'article' => $article,
                ]) . @$this->ImgReplace([
                    'text' => substr($text, $fullTextPosition),
                    'article' => $article,
                    'use_wm' => 0,
                ]);
            }
            else
            {
                $article->text = @$this->ImgReplace([
                    'text' => $text,
                    'article' => $article,
                ]);
            }
        }

        if (isset($article->fulltext))
        {
            $article->fulltext = @$this->ImgReplace([
                'text' => $article->fulltext,
                'article' => $article,
                'use_wm' => $watermarkAllowed ? 1 : 0,
            ]);
        }

        $articleImages = $article->images ?? new \stdClass();
        if (is_string($articleImages))
        {
            $articleImages = json_decode($articleImages) ?: new \stdClass();
        }
        elseif (!is_object($articleImages))
        {
            $articleImages = new \stdClass();
        }

        $articleImagesModified = false;
        $articleText = is_string($article->text ?? null) ? $article->text : '';
        $introText = is_string($article->introtext ?? null) ? $article->introtext : '';

        if ($isArticleView)
        {
            if (!empty($articleImages->image_intro))
            {
                $articleImages->image_intro = '';
                $articleImagesModified = true;
            }
        }
        else
        {
            $introImage = $this->renderArticleImage($article, $articleImages, 'intro');

            if ($introImage !== '')
            {
                $article->_jumultithumbIntroImage = $introImage;
                $articleImages->image_intro = '';
                $articleImagesModified = true;
            }
        }

        $articleParams = $article->params ?? null;
        $canViewFullText = !is_object($articleParams)
            || !method_exists($articleParams, 'get')
            || (bool) $articleParams->get('access-view', true);
        if ($isArticleView && $canViewFullText)
        {
            $fullTextImage = $this->renderArticleImage($article, $articleImages, 'fulltext');

            if ($fullTextImage !== '')
            {
                $articleText = is_string($article->text ?? null) ? $article->text : '';
                $introText = is_string($article->introtext ?? null) ? $article->introtext : '';
                $updatedText = $introText === ''
                    ? $fullTextImage . $articleText
                    : $this->prependImageToSegment($articleText, $introText, $fullTextImage);

                if ($updatedText !== null)
                {
                    $article->text = $updatedText;
                    $article->introtext = $fullTextImage . $introText;
                    $articleImages->image_fulltext = '';
                    $articleImagesModified = true;
                }
            }
        }

        if ($articleImagesModified)
        {
            $article->images = json_encode($articleImages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }

        $lightboxMode = (string) $this->params->get('selectlightbox', 'photoswipe');
        if ($this->params->get('uselightbox', '1') == 1
            && in_array($lightboxMode, ['photoswipe', 'lightgallery'], true)
            && (!empty($articleImages->image_intro) || !empty($articleImages->image_fulltext)))
        {
            Utils::loadPhotoSwipe();
        }
    }

    private function renderArticleImage(object $article, object $images, string $type): string
    {
        $imageField = 'image_' . $type;
        $source = Utils::img_source(trim((string) ($images->{$imageField} ?? '')));

        if ($source === '' || is_string(parse_url($source, PHP_URL_SCHEME)))
        {
            return '';
        }

        $siteRoot = realpath(JPATH_SITE);
        $sourcePath = $siteRoot === false
            ? false
            : realpath($siteRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $source));

        if ($sourcePath === false || !is_file($sourcePath))
        {
            return '';
        }

        $dimensions = @getimagesize($sourcePath);

        if ($dimensions === false)
        {
            return '';
        }

        $quality = (int) $this->params->get('quality', 85);
        $quality = $quality > 0 ? $quality : 85;
        $attribs = json_decode((string) ($article->attribs ?? '')) ?: new \stdClass();
        $watermarkAllowed = $type !== 'fulltext' || (int) ($attribs->watermark_intro_only ?? 0) !== 1;
        $context = $this->getImageContext();
        $variants = $this->imageVariants(
            $source,
            (int) $dimensions[0],
            (int) $dimensions[1],
            $context,
            $quality,
            $attribs,
            $watermarkAllowed
        );
        $width = $variants['width'];
        $height = $variants['height'];

        $altEmpty = (int) ($images->{$imageField . '_alt_empty'} ?? 0) === 1;
        $alt = $altEmpty ? '' : (string) ($images->{$imageField . '_alt'} ?? '');
        $title = $alt !== '' ? Utils::img_alt($alt) : (string) ($article->title ?? '');
        $caption = (string) ($images->{$imageField . '_caption'} ?? '');
        $figureClassField = 'float_' . $type;
        $imageClassField = 'imgclass_' . $type;
        $articleParams = $article->params ?? null;
        $figureClass = (string) ($images->{$figureClassField} ?? '');
        $imageClass = (string) ($images->{$imageClassField} ?? '');

        if (is_object($articleParams) && method_exists($articleParams, 'get'))
        {
            $figureClass = $figureClass !== '' ? $figureClass : (string) $articleParams->get($figureClassField, '');
            $imageClass = $imageClass !== '' ? $imageClass : (string) $articleParams->get($imageClassField, '');
        }

        $lightbox = $this->params->get('uselightbox', '1') == 1
            ? (string) $this->params->get('selectlightbox', 'photoswipe')
            : '';

        return Utils::image('jumultithumb', 'default', [
            'image' => $variants['thumbnail'],
            'orig_img' => $source,
            'link_img' => $variants['popup'],
            'w' => $width,
            'h' => $height,
            'class' => $figureClass,
            'image_class' => $imageClass,
            'noresize' => $variants['noresize'] ? 1 : 0,
            'mask' => $variants['mask'] ? 1 : 0,
            'fit' => $variants['fit'],
            'title' => $title,
            'alt' => $alt,
            'caption' => 0,
            'caption_text' => $caption,
            'lightbox' => $lightbox,
        ]);
    }

    private function prependImageToSegment(string $text, string $segment, string $image, bool $lastMatch = false): ?string
    {
        if ($segment === '')
        {
            return $text === '' ? $image : null;
        }

        $position = $lastMatch ? strrpos($text, $segment) : strpos($text, $segment);

        if ($position === false)
        {
            return null;
        }

        return substr_replace($text, $image . $segment, $position, strlen($segment));
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

        $watermarkAllowed = (int) ($option['use_wm'] ?? 1) === 1;

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
                    'watermark_allowed' => $watermarkAllowed,
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

    private function getImageContext(): string
    {
        return ContextSettings::forView((string) $this->app->input->getCmd('view'));
    }

    private function imageVariants(
        string $source,
        int $sourceWidth,
        int $sourceHeight,
        string $context,
        int $quality,
        object $attributes,
        bool $watermarkAllowed = true,
        bool $skipThumbnail = false
    ): array {
        $width = ContextSettings::dimension(ContextSettings::get($this->params, $context, 'width', 160));
        $height = ContextSettings::dimension(ContextSettings::get($this->params, $context, 'height', 160));
        $noThumbnail = (int) ContextSettings::get($this->params, $context, 'nothumb', 0) === 1;
        $allowUpscale = (int) ContextSettings::get($this->params, $context, 'allow_upscale', 0) === 1;
        $createThumbnail = !$skipThumbnail
            && !$noThumbnail
            && ($width > 0 || $height > 0)
            && ($allowUpscale || !ContextSettings::sourceBelowTarget($sourceWidth, $sourceHeight, $width, $height));
        $cropThumbnail = (int) ContextSettings::get($this->params, $context, 'crop', 1) === 1;
        $cache = ContextSettings::cachePath($context);
        $thumbnail = $source;
        $displayWidth = $sourceWidth;
        $displayHeight = $sourceHeight;

        if($createThumbnail)
        {
            $watermark = Image::watermarkOptions($this->params, $attributes, $context, true, $watermarkAllowed);
            $thumbnail = Image::thumb([
                'image' => $source,
                'zc' => $cropThumbnail && $width > 0 && $height > 0 ? 1 : 0,
                'w' => $width,
                'h' => $height,
                'q' => $quality,
                'cache' => $cache,
                'watermark' => $watermark,
            ]);

            if($cropThumbnail && $width > 0 && $height > 0)
            {
                $displayWidth = $width;
                $displayHeight = $height;
            }
            elseif (!$cropThumbnail && $width > 0 && $height > 0)
            {
                $displayWidth = $width;
                $displayHeight = $height;
            }
            else
            {
                $scale = min(
                    $width > 0 ? $width / $sourceWidth : INF,
                    $height > 0 ? $height / $sourceHeight : INF
                );
                $displayWidth = max(1, (int) round($sourceWidth * $scale));
                $displayHeight = max(1, (int) round($sourceHeight * $scale));
            }
        }

        $noPopup = (int) ContextSettings::get($this->params, $context, 'nopopup', 0) === 1;
        $cropPopup = (int) ContextSettings::get($this->params, $context, 'popup_maxsize', 0) === 1;
        $popupWidth = ContextSettings::dimension(ContextSettings::get($this->params, $context, 'popup_width', 800));
        $popupHeight = ContextSettings::dimension(ContextSettings::get($this->params, $context, 'popup_height', 500));
        $popup = '';

        if(!$noPopup)
        {
            $popup = $source;

            if($cropPopup && ($popupWidth > 0 || $popupHeight > 0))
            {
                $watermark = Image::watermarkOptions($this->params, $attributes, $context, false, $watermarkAllowed);
                $popup = Image::thumb([
                    'image' => $source,
                    'zc' => $popupWidth > 0 && $popupHeight > 0 ? 1 : 0,
                    'w' => $popupWidth,
                    'h' => $popupHeight,
                    'q' => $quality,
                    'cache' => $cache,
                    'watermark' => $watermark,
                ]);
            }
        }

        return [
            'thumbnail' => $thumbnail,
            'popup' => $popup,
            'width' => $displayWidth,
            'height' => $displayHeight,
            'noresize' => !$createThumbnail,
            'mask' => $createThumbnail && !$cropThumbnail && $width > 0 && $height > 0,
            'fit' => $cropThumbnail ? 'cover' : 'contain',
        ];
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

        if ($imgsource === '')
        {
            return $option['image'];
        }

        $imgalt = $img['alt'] ?? '';
        $imgtitle = $img['title'] ?? '';
        $imgalign = $img['align'] ?? '';

        $imgstyle = '';
        $styleMatches = [];
        if (preg_match('#float:(.*?);#s', $img['style'] ?? '', $styleMatches) === 1)
        {
            $imgstyle = $styleMatches[1] ?? '';
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

        $size = @getimagesize(JPATH_SITE . '/' . $imgsource);

        if ($size === false)
        {
            return $option['image'];
        }

        $attribs = json_decode((string) ($option['article']->attribs ?? '')) ?: new \stdClass();
        $context = $this->getImageContext();
        $skipThumbnail = $this->params->get('resall') == 0 && ($img['class'] ?? '') !== 'juimage';
        $variants = $this->imageVariants(
            $imgsource,
            (int) $size[0],
            (int) $size[1],
            $context,
            (int) $quality,
            $attribs,
            (bool) ($option['watermark_allowed'] ?? true),
            $skipThumbnail
        );

        return Utils::image('jumultithumb', 'default', [
            'image' => $variants['thumbnail'],
            'orig_img' => $imgsource,
            'link_img' => $variants['popup'],
            'w' => $variants['width'],
            'h' => $variants['height'],
            'noresize' => $variants['noresize'] ? 1 : 0,
            'mask' => $variants['mask'] ? 1 : 0,
            'fit' => $variants['fit'],
            'title' => $title,
            'caption' => $title,
            'lightbox' => $lightbox,
        ]);
    }

    public function onBeforeCompileHead(): bool
    {
        if ($this->option !== 'com_content' || !class_exists($this->modeHelper) || $this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
        {
            return true;
        }

        if ($this->params->get('uselightbox', '1') == 1 && (($this->modeHelper::view('Article')) || ($this->modeHelper::view('Categories')) || ($this->modeHelper::view('CatBlog'))) && !($this->modeHelper::view('Print')))
        {
            $juhead = '';

            switch ($this->params->get('selectlightbox'))
            {
                case 'customjs':
                    if ($this->params->get('jujq') == 0)
                    {
                        HTMLHelper::_('jquery.framework');
                    }

                    $juhead .= "\r";
                    if ($this->params->get('customjsparam'))
                    {
                        $juhead .= "\n            " . $this->params->get('customjsparam');
                    }
                    break;

                case 'photoswipe':
                case 'lightgallery':
                    Utils::loadPhotoSwipe();
                    break;

                default:
                    break;
            }

            $this->doc->addScriptDeclaration($juhead);
        }

        return true;
    }
}























































































































































































































































