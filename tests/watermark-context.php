<?php

namespace Joomla\CMS\Log
{
	class Log
	{
		public static function add(string $message, int $priority, string $category): void
		{
		}
	}
}

namespace JUMultiThumb\Helpers
{
	class Utils
	{
		public static function img_source(string $source): string
		{
			return $source;
		}
	}
}

namespace
{
	use JUMultiThumb\Helpers\ContextSettings;
	use JUMultiThumb\Helpers\Image;

	require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/ContextSettings.php';

	class JUImage
	{
		public static array $calls = [];

		public function render(string $image, array $parameters): string
		{
			self::$calls[] = ['image' => $image, 'parameters' => $parameters];

			return ($parameters['cache'] ?? 'img') . '/rendered.jpg';
		}
	}

	require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/Image.php';

	function expectSame(mixed $expected, mixed $actual, string $message): void
	{
		if($expected !== $actual)
		{
			throw new \RuntimeException($message . ': expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jumultithumb-watermark-' . bin2hex(random_bytes(6));
	$imageDir = $root . DIRECTORY_SEPARATOR . 'images';
	mkdir($imageDir, 0777, true);
	define('JPATH_SITE', $root);

	$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r9sAAAAASUVORK5CYII=');
	file_put_contents($imageDir . DIRECTORY_SEPARATOR . 'source.png', $png);
	file_put_contents($imageDir . DIRECTORY_SEPARATOR . 'watermark.png', $png);

	$params = (object) ['watermark_image' => 'images/watermark.png'];
	$attributes = new \stdClass();

	foreach(ContextSettings::CONTEXTS as $context)
	{
		$params->{$context . '_watermark_thumbnail'} = 1;
		$params->{$context . '_watermark_popup'} = 1;

		$watermark = Image::watermarkOptions($params, $attributes, $context, true);
		expectSame('images/watermark.png', $watermark['image'] ?? null, 'Thumbnail watermark settings were not read for ' . $context);
		expectSame('images/watermark.png', Image::watermarkOptions($params, $attributes, $context, false)['image'] ?? null, 'Popup watermark settings were not read for ' . $context);

		$output = Image::thumb([
			'image' => 'images/source.png',
			'zc' => 1,
			'w' => 160,
			'h' => 160,
			'q' => 85,
			'cache' => ContextSettings::cachePath($context),
			'watermark' => $watermark,
		]);

		$call = end(JUImage::$calls);
		expectSame('img/' . $context . '/wm_' . substr(hash_file('sha256', $imageDir . DIRECTORY_SEPARATOR . 'watermark.png'), 0, 16), $call['parameters']['cache'], 'Watermark cache was not isolated to ' . $context);
		expectSame(true, isset($call['parameters']['fltr_101']), 'Watermark filter was not passed to image renderer for ' . $context);
		expectSame($call['parameters']['cache'] . '/rendered.jpg', $output, 'Watermarked image output did not use the watermark cache for ' . $context);
	}

	unlink($imageDir . DIRECTORY_SEPARATOR . 'source.png');
	unlink($imageDir . DIRECTORY_SEPARATOR . 'watermark.png');
	rmdir($imageDir);
	rmdir($root);

	echo "Watermark context tests passed.\n";
}
