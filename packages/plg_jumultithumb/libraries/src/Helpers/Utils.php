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
		$base = Uri::base();

		if($base !== '' && str_starts_with($src, $base))
		{
			$src = substr($src, strlen($base));
		}
		elseif(is_string(parse_url($src, PHP_URL_SCHEME)))
		{
			return $src;
		}

		$path = parse_url($src, PHP_URL_PATH);

		if(!is_string($path) || $path === '')
		{
			return '';
		}

		$segments = [];

		foreach(explode('/', str_replace('\\', '/', rawurldecode($path))) as $segment)
		{
			if($segment === '' || $segment === '.')
			{
				continue;
			}

			if($segment === '..')
			{
				if($segments !== [])
				{
					array_pop($segments);
				}

				continue;
			}

			$segments[] = $segment;
		}

		$relativePath = implode('/', $segments);

		if($relativePath === '')
		{
			return '';
		}

		$siteRoot = realpath(JPATH_SITE);

		if($siteRoot === false)
		{
			return '';
		}

		$absolutePath = $siteRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
		$resolvedPath = realpath($absolutePath);

		if($resolvedPath === false)
		{
			return '';
		}

		$normalizedRoot = rtrim(str_replace('\\', '/', $siteRoot), '/');
		$normalizedPath = str_replace('\\', '/', $resolvedPath);
		$rootPrefix = $normalizedRoot . '/';
		$isWithinSite = DIRECTORY_SEPARATOR === '\\'
			? strncasecmp($normalizedPath, $rootPrefix, strlen($rootPrefix)) === 0
			: str_starts_with($normalizedPath, $rootPrefix);

		return $isWithinSite ? $relativePath : '';
	}

	public static function siteUrl(string $path): string
	{
		$path = trim($path);

		if($path === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $path))
		{
			return $path;
		}

		return rtrim(Uri::root(true), '/') . '/' . ltrim($path, '/');
	}

	public static function largeImage(string $image, int $quality = 85, ?array $watermark = null): string
	{
		return Image::thumb([
			'image' => $image,
			'zc' => 0,
			'w' => 1200,
			'h' => 900,
			'q' => $quality,
			'watermark' => $watermark,
		]);
	}

	public static function img_alt(string $alt): string
	{
		return mb_strtoupper(mb_substr($alt, 0, 1)) . mb_substr($alt, 1);
	}

	public static function img_class(array $options = []): string
	{
		$img_class = '';
		if($options[ 'imgalign' ] !== '')
		{
			$img_class = 'ju' . trim($options[ 'imgalign' ]) . ' ';
		}
		elseif($options[ 'imgstyle' ] !== '')
		{
			$img_class = 'ju' . trim($options[ 'imgstyle' ]) . ' ';
		}

		return 'juimage ' . $options[ 'imgclass' ] . ' ' . $img_class . 'juimg-' . $options[ 'view' ];
	}

	public static function image(string $plugin, string $name, array $options = []): string
	{
		$lightboxMode = (string) ($options['lightbox'] ?? '');
		$linkImage = (string) ($options['link_img'] ?? '');
		$originalImage = (string) ($options['orig_img'] ?? '');
		$lightbox = '';
		$linkClass = trim((string) ($options['link_class'] ?? ''));
		$lightboxData = '';
		$pswpData = '';
		$link = self::siteUrl($linkImage);

		if(in_array($lightboxMode, ['photoswipe', 'lightgallery'], true) && $linkImage !== '')
		{
			[$width, $height] = self::imageDimensions($linkImage, $originalImage);

			if($width > 0 && $height > 0)
			{
				self::loadPhotoSwipe();

				$pswpData = ' data-pswp-width="' . $width . '" data-pswp-height="' . $height . '"';
				$group = (string) ($options['pswp_gallery'] ?? 'jumultithumb-inline');

				if($group !== '')
				{
					$pswpData .= ' data-pswp-gallery="' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '"';
				}
			}
		}
		elseif($lightboxMode === 'colorbox')
		{
			$linkClass = trim('lightbox ' . $linkClass);
			$lightbox = ' rel="lightbox[gall]"';
		}
		else
		{
			$lightbox = ' rel="{handler: \'image\', marginImage: {x: 50, y: 50}}"';
		}

		return Utils::tmpl($plugin, $name, [
			'img'            => self::siteUrl((string) ($options['image'] ?? '')),
			'w'              => $options['w'] ?? 0,
			'h'              => $options['h'] ?? 0,
			'class'          => $options['class'] ?? '',
			'image_class'    => $options['image_class'] ?? '',
			'noresize'       => $options['noresize'] ?? 0,
			'mask'           => $options['mask'] ?? 0,
			'fit'            => $options['fit'] ?? 'cover',
			'alt'            => $options['alt'] ?? '',
			'caption'        => $options['caption'] ?? '',
			'caption_text'   => $options['caption_text'] ?? '',
			'title'          => $options['title'] ?? '',
			'link_img'       => self::siteUrl($linkImage),
			'orig_img'       => self::siteUrl($originalImage),
			'link'           => $link,
			'link_class'     => $linkClass,
			'lightbox'       => $lightbox,
			'lightbox_data'  => $lightboxData,
			'pswp_data'      => $pswpData,
		]);
	}

	private static function imageDimensions(string $image, string $fallback = ''): array
	{
		foreach([$image, $fallback] as $candidate)
		{
			$relativePath = self::img_source($candidate);

			if($relativePath === '' || is_string(parse_url($relativePath, PHP_URL_SCHEME)))
			{
				continue;
			}

			$path = JPATH_SITE . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

			if(!is_file($path))
			{
				continue;
			}

			$dimensions = getimagesize($path);

			if($dimensions !== false)
			{
				return [(int) $dimensions[0], (int) $dimensions[1]];
			}
		}

		return [0, 0];
	}

	public static function loadPhotoSwipe(): void
	{
		$document = Factory::getDocument();
		$assetPath = 'plugins/content/jumultithumb/assets/photoswipe';
		$assetBase = rtrim(Uri::root(true), '/') . '/' . $assetPath . '/';

		$document->addStyleSheet($assetBase . 'photoswipe.css');
		$webAssetManager = $document->getWebAssetManager();
		$scriptName = 'plg_content_jumultithumb.photoswipe-init';

		if($webAssetManager->assetExists('script', $scriptName))
		{
			$webAssetManager->useScript($scriptName);
		}
		else
		{
			$webAssetManager->registerAndUseScript(
				$scriptName,
				$assetPath . '/photoswipe-init.js',
				[],
				['type' => 'module']
			);
		}
	}

	/**
	 * @param string $plugin
	 * @param string $name
	 * @param array  $variables
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public static function tmpl(string $plugin, string $name, array $variables = []): string
	{
		$template = Factory::getApplication()->getTemplate();

		$override = JPATH_SITE . '/templates/' . $template . '/html/' . $plugin . '/';
		$tmpl     = JPATH_SITE . '/plugins/content/' . $plugin . '/tmpl/';
		$filename = $override . $name . '.php';

		if(is_file($filename))
		{
			//print_r((new FileLayout($name, $override))->setDebug(true));

			return (new FileLayout($name, $override))->render($variables);
		}

		//$t = (new FileLayout($name, $tmpl))->debug();
		//print_r($t);

		return (new FileLayout($name, $tmpl))->render($variables);
	}
}