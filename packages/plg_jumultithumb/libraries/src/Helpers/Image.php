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
use Joomla\CMS\Log\Log;

if (!class_exists('JUImage') && file_exists(JPATH_LIBRARIES . '/juimage/JUImage.php')) { require_once JPATH_LIBRARIES . '/juimage/JUImage.php'; }

class Image
{
	public static function thumb(array $options = []): string
	{
		$juImg = new JUImage();

		$aspect = '0';
		if($options[ 'zc' ] == 1 && isset($options[ 'cropaspect' ]))
		{
			$aspect = self::aspect($options[ 'image' ], $options[ 'cropaspect' ]);
		}

		if($aspect >= 1 && $options[ 'zc' ] == 4)
		{
			$options[ 'zc' ] = 'T';
		}

		if($aspect >= 1 && $options[ 'zc' ] == 2)
		{
			$new_imgparams = [
				'far' => '1',
				'bg'  => str_replace('#', '', $options[ 'zoom_crop_bg' ])
			];
		}
		else
		{
			$new_imgparams = [
				'zc' => $options[ 'zc' ]
			];
		}

		if($options[ 'zc' ] == 3)
		{
			$new_imgparams = [
				'far' => $options[ 'far_crop' ],
				'bg'  => str_replace('#', '', $options[ 'zoom_crop_bg' ])
			];
		}

		/*$usm_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('unsharp') == 1)
		{
			$usm_filtercolor = [
				'fltr_2' => 'usm|' . $options[ 'typo' ]->get('amount') . '|' . $options[ 'typo' ]->get('radius') . '|' . $options[ 'typo' ]->get('threshold')
			];
		}*/

		/*$blur_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('blur'))
		{
			$blur_filtercolor = [
				'fltr_3' => 'blur|' . $options[ 'typo' ]->get('blur')
			];
		}

		$brit_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('brit'))
		{
			$brit_filtercolor = [
				'fltr_4' => 'brit|' . $options[ 'typo' ]->get('brit')
			];
		}

		$cont_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('cont'))
		{
			$cont_filtercolor = [
				'fltr_5' => 'cont|' . $options[ 'typo' ]->get('cont')
			];
		}
*/
		/*$img_format = [];
		if($options[ 'typo' ]->get('img_format'))
		{
			$img_format = [
				'f' => $options[ 'typo' ]->get('img_format')
			];
		}*/

		$cache = trim((string) ($options['cache'] ?? 'img'), '/');
		$cache = $cache !== '' ? $cache : 'img';
		$imgparams = [
			'w'     => $options[ 'w' ],
			'h'     => $options[ 'h' ],
			'q'     => $options[ 'q' ],
			'cache' => $cache
		];

		$watermarkFilter = self::watermarkFilter($options['watermark'] ?? null, $options, $cache);
		$watermarkParams = [];

		if($watermarkFilter !== null)
		{
			$imgparams['cache'] = $watermarkFilter['cache'];
			$watermarkParams = ['fltr_101' => $watermarkFilter['filter']];
		}

		$blur_bg = [];
		if($aspect >= 0.98 && $options[ 'zc' ] == 5)
		{
			$_imgsource = $juImg->render($options[ 'image' ], [
				'h'     => $options[ 'h' ],
				'cache' => $cache
			]);
			$_imgsource = JPATH_BASE . '/' . $_imgsource;

			$blur_bg = [
				'blur_bg'  => 100,
				'fltr_100' => 'wmi|' . $_imgsource . '|C|100',
			];
		}

		$_imgparams = array_merge($imgparams, $new_imgparams, $blur_bg, $watermarkParams); // $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $img_format,
		$thumb      = $juImg->render($options[ 'image' ], $_imgparams);

		/*if($aspect >= 0.98 && $options[ 'zc' ] == 5 && is_file($_imgsource))
		{
			unlink($_imgsource);
		}*/

		return $thumb;
	}

	public static function watermarkOptions(object $params, object $attributes, string $context, bool $small = false, bool $enabled = true): ?array
	{
		if(!$enabled || !empty($attributes->watermark_off))
		{
			return null;
		}

		$settingsContext = $context === 'gallery' ? 'article' : $context;

		if(in_array($settingsContext, ContextSettings::CONTEXTS, true))
		{
			$setting = $small ? 'watermark_thumbnail' : 'watermark_popup';

			if((int) ContextSettings::get($params, $settingsContext, $setting, 0) !== 1)
			{
				return null;
			}
		}
		else
		{
			if((int) self::getParameter($params, 'wm_tab', 0) !== 1)
			{
				return null;
			}

			$mode = (int) self::getParameter($params, $small ? 'a_watermark_s' : 'a_watermark', 0);

			if(!in_array($mode, [1, 2], true))
			{
				return null;
			}

			$articleField = ($context === 'gallery' ? 'watermark_gallery' : 'watermark') . ($small ? '_s' : '');

			if($mode === 2 && (int) ($attributes->{$articleField} ?? 0) !== 1)
			{
				return null;
			}
		}

		$image = trim((string) self::getParameter($params, 'watermark_image', ''));
		$image = preg_replace('#^images:#', 'images/', $image);
		$image = Utils::img_source($image);

		if($image === '')
		{
			self::logWatermarkWarning('Watermark is enabled, but the selected image is missing or outside the site images directory.');

			return null;
		}

		$suffix = $small ? '_s' : '';

		return [
			'image' => $image,
			'position' => (string) self::getParameter($params, 'wmposition' . $suffix, 'BR'),
			'opacity' => (int) self::getParameter($params, 'wmopst' . $suffix, 75),
			'margin_x' => (int) self::getParameter($params, 'wmx' . $suffix, 0),
			'margin_y' => (int) self::getParameter($params, 'wmy' . $suffix, 0),
		];
	}

	private static function getParameter(object $params, string $name, mixed $default): mixed
	{
		return method_exists($params, 'get')
			? $params->get($name, $default)
			: ($params->{$name} ?? $default);
	}

	private static function watermarkFilter(mixed $watermark, array $options, string $cache): ?array
	{
		if(!is_array($watermark) || empty($watermark['image']))
		{
			return null;
		}

		$watermarkPath = Utils::img_source((string) $watermark['image']);
		$siteRoot = realpath(JPATH_SITE);
		$watermarkFile = $siteRoot === false || $watermarkPath === ''
			? false
			: realpath($siteRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $watermarkPath));

		if($watermarkFile === false || !is_file($watermarkFile) || str_contains($watermarkFile, '|'))
		{
			self::logWatermarkWarning('Watermark could not be opened; check the selected local image file.');

			return null;
		}

		static $watermarkMetadata = [];
		$fileStat = @stat($watermarkFile);
		$metadataKey = $fileStat === false
			? ''
			: $watermarkFile . ':' . $fileStat['mtime'] . ':' . $fileStat['size'];

		if($metadataKey !== '' && !array_key_exists($metadataKey, $watermarkMetadata))
		{
			$watermarkMetadata[$metadataKey] = [
				'dimensions' => @getimagesize($watermarkFile),
				'hash' => hash_file('sha256', $watermarkFile),
			];
		}

		$watermarkDimensions = $watermarkMetadata[$metadataKey]['dimensions'] ?? false;
		$watermarkHash = $watermarkMetadata[$metadataKey]['hash'] ?? false;
		$source = Utils::img_source((string) ($options['image'] ?? ''));
		$sourceFile = $siteRoot === false || $source === ''
			? false
			: realpath($siteRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $source));
		$sourceDimensions = $sourceFile === false ? false : @getimagesize($sourceFile);

		if($watermarkDimensions === false || $sourceDimensions === false || !is_string($watermarkHash))
		{
			self::logWatermarkWarning('Watermark could not be processed; verify that both source and watermark are readable raster images.');

			return null;
		}

		$sourceWidth = (int) $sourceDimensions[0];
		$sourceHeight = (int) $sourceDimensions[1];
		$watermarkWidth = (int) $watermarkDimensions[0];
		$watermarkHeight = (int) $watermarkDimensions[1];

		if($sourceWidth < 1 || $sourceHeight < 1 || $watermarkWidth < 1 || $watermarkHeight < 1)
		{
			self::logWatermarkWarning('Watermark could not be processed because the source image has invalid dimensions.');

			return null;
		}

		$outputWidth = (int) ($options['w'] ?? 0);
		$outputHeight = (int) ($options['h'] ?? 0);
		$scale = min(
			$outputWidth > 0 ? $outputWidth / $sourceWidth : 1,
			$outputHeight > 0 ? $outputHeight / $sourceHeight : 1
		);

		if(($options['zc'] ?? 0) == 0)
		{
			$outputWidth = max(1, (int) round($sourceWidth * $scale));
			$outputHeight = max(1, (int) round($sourceHeight * $scale));
		}
		else
		{
			$outputWidth = $outputWidth > 0 ? $outputWidth : $sourceWidth;
			$outputHeight = $outputHeight > 0 ? $outputHeight : $sourceHeight;
		}

		$maxWidth = min($watermarkWidth, max(1, (int) round($outputWidth * 0.25)));
		$maxHeight = min($watermarkHeight, max(1, (int) round($outputHeight * 0.25)));
		$watermarkScale = min($maxWidth / $watermarkWidth, $maxHeight / $watermarkHeight, 1);
		$scaledWidth = max(1, (int) round($watermarkWidth * $watermarkScale));
		$scaledHeight = max(1, (int) round($watermarkHeight * $watermarkScale));
		$marginX = max(-$scaledWidth, min((int) ($watermark['margin_x'] ?? 0), $outputWidth - $scaledWidth));
		$marginY = max(-$scaledHeight, min((int) ($watermark['margin_y'] ?? 0), $outputHeight - $scaledHeight));
		$position = strtoupper((string) ($watermark['position'] ?? 'BR'));
		$horizontal = in_array($position, ['L', 'TL', 'BL'], true)
			? 'left'
			: (in_array($position, ['R', 'TR', 'BR'], true) ? 'right' : 'center');
		$vertical = in_array($position, ['T', 'TL', 'TR'], true)
			? 'top'
			: (in_array($position, ['B', 'BL', 'BR'], true) ? 'bottom' : 'center');
		$centerX = match($horizontal)
		{
			'left' => $marginX + ($scaledWidth / 2),
			'right' => $outputWidth - $marginX - ($scaledWidth / 2),
			default => $outputWidth / 2,
		};
		$centerY = match($vertical)
		{
			'top' => $marginY + ($scaledHeight / 2),
			'bottom' => $outputHeight - $marginY - ($scaledHeight / 2),
			default => $outputHeight / 2,
		};
		$opacity = max(0, min(100, (int) ($watermark['opacity'] ?? 75)));

		if($opacity === 0)
		{
			return null;
		}

		return [
			'filter' => sprintf(
				'wmi|%s|%dx%d|%d|%d|%d|0',
				$watermarkFile,
				(int) round($centerX),
				(int) round($centerY),
				$opacity,
				$maxWidth,
				$maxHeight
			),
			'cache' => $cache . '/wm_' . substr($watermarkHash, 0, 16),
		];
	}

	private static function logWatermarkWarning(string $message): void
	{
		static $logged = false;

		if(!$logged)
		{
			Log::add($message, Log::WARNING, 'plg_content_jumultithumb');
			$logged = true;
		}
	}

	/**
	 * @param   string  $html
	 * @param   float   $_cropaspect
	 *
	 * @return float|int
	 *
	 * @since 7.0
	 */
	private static function aspect(string $html, float $_cropaspect)
	{
		$juImg = new JUImage();

		$size   = $juImg->size(rawurldecode(JPATH_SITE . '/' . $html));
		$width  = $size->width;
		$height = ($size->height * ($_cropaspect !== '' ? $_cropaspect : '0'));

		return $height / $width;
	}
}