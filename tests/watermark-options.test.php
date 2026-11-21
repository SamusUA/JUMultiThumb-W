<?php

namespace Joomla\CMS\Uri
{
	class Uri
	{
		public static function base(): string
		{
			return '';
		}
	}
}

namespace
{
	$siteRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jumultithumb-watermark-' . bin2hex(random_bytes(6));
	define('JPATH_SITE', $siteRoot);
	define('JPATH_LIBRARIES', $siteRoot . DIRECTORY_SEPARATOR . 'libraries');

	require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/Utils.php';
	require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/Image.php';

	use JUMultiThumb\Helpers\Image;

	$imagesDirectory = $siteRoot . DIRECTORY_SEPARATOR . 'images';
	$watermarkFile = $imagesDirectory . DIRECTORY_SEPARATOR . 'watermark.png';
	mkdir($imagesDirectory, 0777, true);
	file_put_contents($watermarkFile, 'watermark fixture');

	$checks = 0;
	$assert = static function (bool $condition, string $message) use (&$checks): void
	{
		$checks++;

		if(!$condition)
		{
			throw new RuntimeException("Failed: {$message}");
		}
	};

	$params = (object) [
		'wm_tab' => 1,
		'watermark_image' => 'images/watermark.png',
		'a_watermark' => 1,
		'a_watermark_s' => 0,
		'wmposition' => 'TL',
		'wmposition_s' => 'BR',
		'wmopst' => 80,
		'wmopst_s' => 60,
		'wmx' => 4,
		'wmx_s' => 8,
		'wmy' => 6,
		'wmy_s' => 10,
	];
	$attributes = new \stdClass();

	try
	{
		$masterOffParams = clone $params;
		$masterOffParams->wm_tab = 0;
		$assert(Image::watermarkOptions($masterOffParams, $attributes, 'image') === null, 'master switch disables the full-size watermark');
		$assert(Image::watermarkOptions($masterOffParams, $attributes, 'image', true) === null, 'master switch disables the thumbnail watermark');

		$originalOnly = Image::watermarkOptions($params, $attributes, 'image');
		$assert($originalOnly !== null && $originalOnly['position'] === 'TL', 'original option applies to full-size images');
		$assert(Image::watermarkOptions($params, $attributes, 'image', true) === null, 'original option alone does not watermark thumbnails');

		$params->a_watermark_s = 1;
		$both = Image::watermarkOptions($params, $attributes, 'image');
		$thumbnail = Image::watermarkOptions($params, $attributes, 'image', true);
		$assert($both !== null && $thumbnail !== null && $thumbnail['position'] === 'BR', 'thumbnail option adds a thumbnail watermark independently');

		$params->a_watermark = 0;
		$assert(Image::watermarkOptions($params, $attributes, 'image') === null, 'thumbnail option does not enable full-size watermarking');
		$assert(Image::watermarkOptions($params, $attributes, 'image', true) !== null, 'thumbnail option remains enabled when full-size watermarking is off');

		$params->a_watermark = 2;
		$params->a_watermark_s = 2;
		$attributes->watermark = 1;
		$attributes->watermark_s = 0;
		$assert(Image::watermarkOptions($params, $attributes, 'image') !== null, 'article full-size selection uses its own article flag');
		$assert(Image::watermarkOptions($params, $attributes, 'image', true) === null, 'article thumbnail selection uses its own article flag');

		$attributes->watermark_gallery = 1;
		$attributes->watermark_gallery_s = 1;
		$assert(Image::watermarkOptions($params, $attributes, 'gallery') !== null, 'gallery full-size selection uses its gallery flag');
		$assert(Image::watermarkOptions($params, $attributes, 'gallery', true) !== null, 'gallery thumbnail selection uses its separate gallery flag');

		$attributes->watermark_off = 1;
		$assert(Image::watermarkOptions($params, $attributes, 'gallery') === null, 'article-level master exclusion disables full-size gallery watermark');
		$assert(Image::watermarkOptions($params, $attributes, 'gallery', true) === null, 'article-level master exclusion disables thumbnail gallery watermark');
	}
	finally
	{
		unlink($watermarkFile);
		rmdir($imagesDirectory);
		rmdir($siteRoot);
	}

	echo "Passed {$checks} watermark-options checks.\n";
}
