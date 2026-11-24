<?php

if($argc !== 2 || !is_file($argv[1] . '/includes/framework.php'))
{
	fwrite(STDERR, "Usage: php tests/joomla-popup-render.php <isolated-joomla-root>\n");
	exit(1);
}

define('_JEXEC', 1);
define('JPATH_BASE', realpath($argv[1]));
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['DOCUMENT_ROOT'] = JPATH_BASE;
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$container = \Joomla\CMS\Factory::getContainer();
$container->alias('session', 'session.web.site')
	->alias('JSession', 'session.web.site')
	->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
	->alias(\Joomla\Session\Session::class, 'session.web.site')
	->alias(\Joomla\Session\SessionInterface::class, 'session.web.site');
\Joomla\CMS\Factory::$application = $container->get(\Joomla\CMS\Application\SiteApplication::class);
\Joomla\CMS\Factory::getApplication()->createExtensionNamespaceMap();
\Joomla\CMS\Factory::getApplication()->setTemplate('cassiopeia');
require_once JPATH_PLUGINS . '/content/jumultithumb/jumultithumb.php';

$fixture = 'jumultithumb-popup-test-' . bin2hex(random_bytes(6));
$source = 'images/' . $fixture . '-source.png';
$portrait = 'images/' . $fixture . '-portrait.png';
$small = 'images/' . $fixture . '-small.png';
$watermark = 'images/' . $fixture . '-watermark.png';
$sourcePath = JPATH_ROOT . '/' . $source;
$portraitPath = JPATH_ROOT . '/' . $portrait;
$smallPath = JPATH_ROOT . '/' . $small;
$watermarkPath = JPATH_ROOT . '/' . $watermark;
$generated = [];

try
{
	foreach([[$sourcePath, 400, 300], [$portraitPath, 300, 400], [$smallPath, 40, 30]] as [$path, $width, $height])
	{
		$image = imagecreatetruecolor($width, $height);
		imagefill($image, 0, 0, imagecolorallocate($image, 40, 100, 160));
		imagefilledrectangle($image, 0, 0, (int) ($width / 5), $height - 1, imagecolorallocate($image, 255, 0, 0));
		imagefilledrectangle($image, (int) ($width * 4 / 5), 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 255, 0));
		imagepng($image, $path);
		imagedestroy($image);
	}
	$image = imagecreatetruecolor(40, 40);
	imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
	imagepng($image, $watermarkPath);
	imagedestroy($image);

	$plugin = (new \ReflectionClass(\plgContentjumultithumb::class))->newInstanceWithoutConstructor();
	$params = new \ReflectionProperty(\Joomla\CMS\Plugin\CMSPlugin::class, 'params');
	$render = new \ReflectionMethod($plugin, 'imageVariants');

	foreach(['article', 'categories', 'category', 'featured', 'module'] as $context)
	{
		$values = [
			$context . '_nothumb' => 1,
			$context . '_popup_width' => 120,
			$context . '_popup_height' => 80,
			$context . '_popup_maxsize' => 0,
			$context . '_watermark_popup' => 1,
			'watermark_image' => $watermark,
			'wmopst' => 100,
		];
		foreach([[$source, 400, 300, 106, 80], [$portrait, 300, 400, 60, 80]] as [$imageSource, $sourceWidth, $sourceHeight, $expectedWidth, $expectedHeight])
		{
			$legacy = \JUMultiThumb\Helpers\Image::thumb([
				'image' => $imageSource,
				'zc' => 1,
				'w' => 120,
				'h' => 80,
				'q' => 85,
				'cache' => 'img/' . $context . '/popup/normal',
				'watermark' => null,
			]);
			$generated[] = JPATH_ROOT . '/' . ltrim($legacy, '/');
			$values[$context . '_watermark_popup'] = 1;
			$values[$context . '_popup_maxsize'] = 0;
			$params->setValue($plugin, new \Joomla\Registry\Registry($values));
			$marked = $render->invoke($plugin, $imageSource, $sourceWidth, $sourceHeight, $context, 85, new \stdClass())['popup'];
			$values[$context . '_watermark_popup'] = 0;
			$values[$context . '_popup_maxsize'] = 1;
			$params->setValue($plugin, new \Joomla\Registry\Registry($values));
			$plain = $render->invoke($plugin, $imageSource, $sourceWidth, $sourceHeight, $context, 85, new \stdClass())['popup'];

			foreach([$marked, $plain] as $path)
			{
				$absolute = JPATH_ROOT . '/' . ltrim($path, '/');
				if(!is_file($absolute))
				{
					throw new \RuntimeException('Popup file was not created: ' . $path);
				}
				$generated[] = $absolute;
				$dimensions = getimagesize($absolute);
				if($dimensions[0] !== $expectedWidth || $dimensions[1] !== $expectedHeight)
				{
					throw new \RuntimeException('Popup dimensions differ in ' . $context . ': ' . json_encode($dimensions));
				}
			}

			$image = imagecreatefromjpeg(JPATH_ROOT . '/' . ltrim($plain, '/'));
			$left = imagecolorsforindex($image, imagecolorat($image, 0, (int) ($expectedHeight / 2)));
			$right = imagecolorsforindex($image, imagecolorat($image, $expectedWidth - 1, (int) ($expectedHeight / 2)));
			imagedestroy($image);
			if($left['red'] < 200 || $right['green'] < 200)
			{
				throw new \RuntimeException('Popup lost image edges or added padding in ' . $context);
			}

			if($plain === $legacy
				|| !str_contains($marked, 'img/' . $context . '/popup/watermark/wm_')
				|| !str_contains($plain, 'img/' . $context . '/popup/normal/')
				|| hash_file('sha256', JPATH_ROOT . '/' . ltrim($marked, '/')) === hash_file('sha256', JPATH_ROOT . '/' . ltrim($plain, '/')))
			{
				throw new \RuntimeException('Popup cache variants were not separated for ' . $context);
			}
		}

		foreach([0, 1] as $watermarked)
		{
			$values[$context . '_watermark_popup'] = $watermarked;
			$params->setValue($plugin, new \Joomla\Registry\Registry($values));
			$smallPopup = $render->invoke($plugin, $small, 40, 30, $context, 85, new \stdClass())['popup'];
			$smallAbsolute = JPATH_ROOT . '/' . ltrim($smallPopup, '/');
			$generated[] = $smallAbsolute;
			$smallDimensions = getimagesize($smallAbsolute);
			if($smallDimensions[0] !== 40 || $smallDimensions[1] !== 30)
			{
				throw new \RuntimeException('Small popup image was enlarged in ' . $context);
			}
		}
	}

	(new \ReflectionProperty($plugin, 'app'))->setValue($plugin, \Joomla\CMS\Factory::getApplication());
	(new \ReflectionProperty($plugin, 'option'))->setValue($plugin, 'com_users');
	(new \ReflectionProperty($plugin, 'modeHelper'))->setValue($plugin, '\\JUMultiThumb\\Adapters\\com_content');
	$params->setValue($plugin, new \Joomla\Registry\Registry([
		'resall' => 1,
		'uselightbox' => 1,
		'selectlightbox' => 'photoswipe',
		'module_nothumb' => 1,
		'module_popup_maxsize' => 0,
		'module_watermark_popup' => 0,
	]));
	$module = (object) ['text' => '<img src="' . $source . '" alt="Module image">'];
	$plugin->onContentPrepare('mod_articles.content', $module);
	if(!str_contains($module->text, 'data-pswp-gallery="jumultithumb-module"')
		|| !str_contains($module->text, 'jumultithumb-image'))
	{
		throw new \RuntimeException('Module content did not use the separate module PhotoSwipe group');
	}
	$again = (object) ['text' => $module->text];
	$plugin->onContentPrepare('mod_articles.content', $again);
	if($again->text !== $module->text)
	{
		throw new \RuntimeException('Repeated module preparation modified an already processed image');
	}
	$other = (object) ['text' => '<img src="' . $source . '">'];
	$plugin->onContentPrepare('com_users.profile', $other);
	if($other->text !== '<img src="' . $source . '">')
	{
		throw new \RuntimeException('Unrelated component content was processed');
	}

	echo "Joomla popup rendering passed for all contexts.\n";
}
finally
{
	foreach($generated as $file)
	{
		if(is_file($file))
		{
			unlink($file);
		}
	}
	foreach([$sourcePath, $portraitPath, $smallPath, $watermarkPath] as $file)
	{
		if(is_file($file))
		{
			unlink($file);
		}
	}
}
