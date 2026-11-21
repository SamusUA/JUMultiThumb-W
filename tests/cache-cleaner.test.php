<?php

$siteRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'jumultithumb-cache-cleaner-' . bin2hex(random_bytes(6));
define('JPATH_SITE', $siteRoot);
define('JPATH_LIBRARIES', $siteRoot . DIRECTORY_SEPARATOR . 'libraries');

require_once __DIR__ . '/../packages/plg_jumultithumb/libraries/src/Helpers/Utils.php';

use JUMultiThumb\Helpers\Utils;

$generatedThumbnail = $siteRoot . '/images/article/img/160x160/a/thumb-12345678.jpg';
$generatedWatermark = $siteRoot . '/images/article/img_wm_0123456789abcdef/0/f/large-abcdef12.png';
$originalImage = $siteRoot . '/images/article/original.jpg';
$cacheNote = $siteRoot . '/images/article/img/notes.txt';
$unrecognizedCacheFile = $siteRoot . '/images/article/img/160x160/a/custom-photo.jpg';
$unrecognizedCacheFolder = $siteRoot . '/images/article/img_wm_notes/0/f/custom-photo-12345678.jpg';

$writeFile = static function (string $path): void
{
	if(!is_dir(dirname($path)))
	{
		mkdir(dirname($path), 0777, true);
	}

	file_put_contents($path, 'cache test');
};

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void
{
	$checks++;

	if(!$condition)
	{
		throw new RuntimeException("Failed: {$message}");
	}
};

try
{
	foreach([$generatedThumbnail, $generatedWatermark, $originalImage, $cacheNote, $unrecognizedCacheFile, $unrecognizedCacheFolder] as $path)
	{
		$writeFile($path);
	}

	$result = Utils::clearGeneratedImageCache();

	$assert($result === ['deleted' => 2, 'failed' => 0], 'only recognized generated thumbnail and watermark files are removed');
	$assert(!file_exists($generatedThumbnail) && !file_exists($generatedWatermark), 'generated image cache files are gone');
	$assert(file_exists($originalImage), 'original images are preserved');
	$assert(file_exists($cacheNote), 'unrelated cache-folder files are preserved');
	$assert(file_exists($unrecognizedCacheFile), 'files without the JUImage generated-name pattern are preserved');
	$assert(file_exists($unrecognizedCacheFolder), 'noncanonical watermark folders are preserved');
}
finally
{
	$paths = [$generatedThumbnail, $generatedWatermark, $originalImage, $cacheNote, $unrecognizedCacheFile, $unrecognizedCacheFolder];

	foreach($paths as $path)
	{
		if(is_file($path))
		{
			unlink($path);
		}
	}

	$directories = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($siteRoot, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach($iterator as $entry)
	{
		if($entry->isDir() && !$entry->isLink())
		{
			$directories[] = $entry->getPathname();
		}
	}

	foreach($directories as $directory)
	{
		rmdir($directory);
	}

	rmdir($siteRoot);
}

echo "Passed {$checks} cache-cleaner checks.\n";
