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
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use JUMultiThumb\Helpers\AutoLinks;
use JUMultiThumb\Helpers\GalleryPath;
use JUMultiThumb\Helpers\Image;
use JUMultiThumb\Helpers\Utils;

defined('_JEXEC') or die;

require_once dirname(__DIR__) . '/jumultithumb/libraries/vendor/autoload.php';

class plgContentJUMULTITHUMB_Gallery extends CMSPlugin
{
	protected $modeHelper;
	protected $app;
	protected $doc;
	protected $option;
	protected $itemid;
	/**
	 * plgContentJUMULTITHUMB_Gallery constructor.
	 *
	 * @param $subject
	 * @param $config
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

		$this->app        = Factory::getApplication();
		$this->option     = (string) $this->app->input->get('option', '');
		if($this->option !== 'com_content')
		{
			return;
		}

		$this->doc        = Factory::getDocument();
		$this->itemid     = $this->app->input->getInt('Itemid');
		$this->modeHelper = '\\JUMultiThumb\\Adapters\\com_content';
	}

	/**
	 * @param $context
	 * @param $article
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function onContentBeforeDisplay($context, $article): void
	{
		if($this->option !== 'com_content' || !is_object($article) || !isset($article->catid, $article->slug) || class_exists($this->modeHelper) === false)
		{
			return;
		}

		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
		{
			return;
		}

		if($this->modeHelper::view('Article'))
		{
			return;
		}

		$link          = $this->modeHelper::link($article);
		if($link !== '')
		{
			$article->text = AutoLinks::handleImgLinks((string) ($article->text ?? ''), (string) ($article->title ?? ''), $link, 1);
		}
	}

	public function onBeforeCompileHead(): void
	{
		if($this->option !== 'com_content' || $this->app->getName() !== 'site' || !class_exists($this->modeHelper))
		{
			return;
		}

		if($this->modeHelper::view('Component'))
		{
			Utils::loadLightbox($this->getGalleryLightbox());
		}
	}

	private function getGalleryLightbox(): string
	{
		$plugin = PluginHelper::getPlugin('content', 'jumultithumb');
		$params = $plugin ? json_decode((string) $plugin->params) : null;
		$mode = (string) ($params->selectlightbox ?? 'photoswipe');

		return in_array($mode, ['zoomora', 'glightbox', 'baguettebox'], true)
			&& (string) ($params->uselightbox ?? '1') === '1' ? $mode : 'photoswipe';
	}

	/**
	 * @param $context
	 * @param $article
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onContentPrepare($context, $article): void
	{
		if($this->option !== 'com_content' || !is_object($article) || class_exists($this->modeHelper) === false)
		{
			return;
		}

		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
		{
			return;
		}

		if(!($this->modeHelper::view('Article')) && ($this->params->get('useimgagegallery') == '0') && isset($article->text) && is_string($article->text))
		{
			$regex = "/<p>\s*{gallery\s+(.*?)}\s*</p>/i";
			preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

			foreach($matches as $val)
			{
				if($val)
				{
					$article->text = preg_replace($regex, '', $article->text, 1);

					return;
				}
			}
		}

		$attribs = json_decode((string) ($article->attribs ?? '')) ?: new \stdClass();
		$allowWatermark = (int) ($attribs->watermark_intro_only ?? 0) !== 1;
		$fulltext = is_string($article->fulltext ?? null) ? $article->fulltext : '';

		if(isset($article->text))
		{
			$text = is_string($article->text) ? $article->text : '';
			$fulltextPosition = !$allowWatermark && $fulltext !== '' ? strrpos($text, $fulltext) : false;

			if($fulltextPosition !== false)
			{
				$article->text = $this->GalleryReplace(substr($text, 0, $fulltextPosition), $article)
					. $this->GalleryReplace(substr($text, $fulltextPosition), $article, false);
			}
			else
			{
				$article->text = $this->GalleryReplace($text, $article);
			}
		}

		if(isset($article->fulltext))
		{
			$article->fulltext = $this->GalleryReplace($article->fulltext, $article, $allowWatermark);
		}
	}

	/**
	 * @param $text
	 * @param $article
	 *
	 * @return null|string|string[]
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function GalleryReplace($text, $article, bool $allowWatermark = true)
	{
		$text = is_string($text) ? $text : '';
		$regex = "/{gallery\s+(.*?)}/i";
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);

		if($matches)
		{
			$plugin = PluginHelper::getPlugin('content', 'jumultithumb') ?: (object) ['params' => '{}'];
			$json = json_decode((string) $plugin->params) ?: new \stdClass();
			$attribs = json_decode((string) ($article->attribs ?? '')) ?: new \stdClass();
			$watermarkOriginal = Image::watermarkOptions($json, $attribs, 'gallery', false, $allowWatermark);
			$watermarkThumbnail = Image::watermarkOptions($json, $attribs, 'gallery', true, $allowWatermark);

			foreach($matches as $match)
			{
				$matcheslist = explode('|', $match[ 1 ]);
				$galltitle = trim(str_replace('title=', '', (string) ($matcheslist[1] ?? '')));
				$gallstyle = trim(str_replace('class=', '', (string) ($matcheslist[2] ?? '')));
				$gallwidth = (int) ($this->params->get('gallwidth') ?: 130);
				$gallheight = (int) ($this->params->get('gallheight') ?: 130);
				$gallcropzoom = $this->params->get('gallcropzoom') ?? 1;
				$imageAlt = $galltitle !== ''
					? html_entity_decode($galltitle, ENT_QUOTES | ENT_HTML5, 'UTF-8')
					: (string) ($article->title ?? '');

				if($galltitle === '')
				{
					$galltitle = (string) ($this->params->get('gallery_title') ?? '');
				}

				if($gallstyle === '')
				{
					$gallstyle = (string) ($this->params->get('cssclass') ?? '');
				}

				$img_title = preg_replace('/"/', "'", (string) ($article->title ?? ''));
				$folder = trim((string) ($matcheslist[0] ?? ''));
				$img_folder = GalleryPath::resolve($folder, JPATH_SITE . '/images');
				$siteRoot = realpath(JPATH_SITE);

				$html = '';
				if($img_folder !== null && $siteRoot !== false)
				{
					$images = glob($img_folder . DIRECTORY_SEPARATOR . '{*.[jJ][pP][gG],*.[jJ][pP][eE][gG],*.[gG][iI][fF],*.[pP][nN][gG],*.[bB][mM][pP],*.[tT][iI][fF],*.[tT][iI][fF][fF]}', GLOB_BRACE) ?: [];

					$_gallery = [];
					foreach($images as $imagePath)
					{
						$file = ltrim(str_replace('\\', '/', substr($imagePath, strlen($siteRoot))), '/');

						switch($json->thumb_filtercolor ?? '0')
						{
							case '1':
								$imp_filtercolor = [ 'fltr_1' => 'gray' ];
								break;
							case '2':
								$imp_filtercolor = [ 'fltr_1' => 'sep' ];
								break;
							case '3':
								$imp_filtercolor = [ 'fltr_1' => 'th|' . ($json->thumb_th_seting ?? 0) ];
								break;
							case '4':
								$imp_filtercolor = [ 'fltr_1' => 'clr|' . ($json->colorized ?? 25) . '|' . str_replace('#', '', $json->colorpicker ?? '#0000ff') ];
								break;
							default:
								$imp_filtercolor = [];
								break;
						}

						$usm_filtercolor = [];
						if(($json->thumb_unsharp ?? 0) == 1)
						{
							$usm_filtercolor = [ 'fltr_2' => 'usm|' . ($json->thumb_unsharp_amount ?? 80) . '|' . ($json->thumb_unsharp_radius ?? 1) . '|' . ($json->thumb_unsharp_threshold ?? 3) ];
						}

						$blur_filtercolor = [];
						if(($json->thumb_blur ?? 0) == 1)
						{
							$blur_filtercolor = [ 'fltr_3' => 'blur|' . ($json->thumb_blur_seting ?? 1) ];
						}

						$brit_filtercolor = [];
						if(($json->thumb_brit ?? 0) == 1)
						{
							$brit_filtercolor = [ 'fltr_4' => 'brit|' . ($json->thumb_brit_seting ?? 50) ];
						}

						$cont_filtercolor = [];
						if(($json->thumb_cont ?? 0) == 1)
						{
							$cont_filtercolor = [ 'fltr_5' => 'cont|' . ($json->thumb_cont_seting ?? 50) ];
						}

						if(!$this->modeHelper::view('Article') && ($this->params->get('useimgagegallery') == '1'))
						{
							$_title = mb_strtoupper(mb_substr($img_title, 0, 1)) . mb_substr($img_title, 1);

							//$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor);

							$thumb_img = Image::thumb([
								'image' => $file,
								'zc'    => $this->params->get('cropzoom'),
								'w'     => $this->params->get('width'),
								'h'     => $this->params->get('height'),
								'q'     => $json->quality ?? 85,
								'watermark' => $watermarkThumbnail,
							]);
								$largeImage = Utils::largeImage($file, (int) ($json->quality ?? 85), $watermarkOriginal);

							return Utils::image('jumultithumb_gallery', 'image', [
								'image'    => $thumb_img,
								'orig_img' => $file,
									'link_img' => $largeImage,
								'w'        => $this->params->get('width'),
								'h'        => $this->params->get('height'),
								'title'    => $_title,
								'caption'  => $_title,
								'alt'      => $imageAlt,
								'lightbox' => $this->getGalleryLightbox(),
								'link_class' => $gallstyle,
								'pswp_gallery' => '',
							]);
						}

						$imgparams = [
							'image' => $file,
							'zc'    => $gallcropzoom,
							'w'     => $gallwidth,
							'h'     => $gallheight,
							'q'     => $json->quality ?? 85,
							'watermark' => $watermarkThumbnail,
						];

						$options = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams);

						$thumb_img = Image::thumb($options);
						$largeImage = Utils::largeImage($file, (int) ($json->quality ?? 85), $watermarkOriginal);

						$_title = ($galltitle == '' ? $img_title : $galltitle . '. ' . $img_title);
						$_title = mb_strtoupper(mb_substr($_title, 0, 1)) . mb_substr($_title, 1);

						$_gallery[] = Utils::image('jumultithumb_gallery', 'image', [
							'image'    => $thumb_img,
							'orig_img' => $file,
							'link_img' => $largeImage,
							'w'        => $gallwidth,
							'h'        => $gallheight,
							'title'    => $_title,
							'caption'  => $_title,
							'alt'      => $imageAlt,
							'lightbox' => $this->getGalleryLightbox(),
							'link_class' => $gallstyle,
							'pswp_gallery' => '',
						]);
					}

					$gallery = implode($_gallery);

					$html = Utils::tmpl('jumultithumb_gallery', 'gallery', [
						'gallery'   => $gallery,
						'gallstyle' => $gallstyle,
						'galltitle' => $galltitle
					]);
				}
				elseif($folder !== '')
				{
					Log::add('Gallery folder not found or outside /images: ' . $folder, Log::WARNING, 'plg_content_jumultithumb_gallery');
				}

				$text = preg_replace($regex, $html, $text, 1);
			}
		}

		return $text;
	}
}