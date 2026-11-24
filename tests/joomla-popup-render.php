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
$watermark = 'images/' . $fixture . '-watermark.png';
$sourcePath = JPATH_ROOT . '/' . $source;
$watermarkPath = JPATH_ROOT . '/' . $watermark;
$generated = [];

try
{
	$image = imagecreatetruecolor(400, 300);
	imagefill($image, 0, 0, imagecolorallocate($image, 40, 100, 160));
	imagepng($image, $sourcePath);
	imagedestroy($image);
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
		$params->setValue($plugin, new \Joomla\Registry\Registry($values));
		$marked = $render->invoke($plugin, $source, 400, 300, $context, 85, new \stdClass())['popup'];
		$values[$context . '_watermark_popup'] = 0;
		$values[$context . '_popup_maxsize'] = 1;
		$params->setValue($plugin, new \Joomla\Registry\Registry($values));
		$plain = $render->invoke($plugin, $source, 400, 300, $context, 85, new \stdClass())['popup'];

		foreach([$marked, $plain] as $path)
		{
			$absolute = JPATH_ROOT . '/' . ltrim($path, '/');
			if(!is_file($absolute))
			{
				throw new \RuntimeException('Popup file was not created: ' . $path);
			}
			$generated[] = $absolute;
			$dimensions = getimagesize($absolute);
			if($dimensions[0] !== 120 || $dimensions[1] !== 80)
			{
				throw new \RuntimeException('Popup dimensions differ in ' . $context);
			}
		}

		if(!str_contains($marked, 'img/' . $context . '/popup/watermark/wm_')
			|| !str_contains($plain, 'img/' . $context . '/popup/normal/')
			|| hash_file('sha256', JPATH_ROOT . '/' . ltrim($marked, '/')) === hash_file('sha256', JPATH_ROOT . '/' . ltrim($plain, '/')))
		{
			throw new \RuntimeException('Watermarked and unwatermarked popup copies were not separated for ' . $context);
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
	foreach([$sourcePath, $watermarkPath] as $file)
	{
		if(is_file($file))
		{
			unlink($file);
		}
	}
}
