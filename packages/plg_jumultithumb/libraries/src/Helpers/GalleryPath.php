<?php

namespace JUMultiThumb\Helpers;

class GalleryPath
{
	public static function resolve(string $folder, string $imagesRoot): ?string
	{
		$folder = trim(str_replace('\\', '/', rawurldecode($folder)));

		if($folder === '' || preg_match('#^(?:[a-z][a-z0-9+.-]*:|//)#i', $folder))
		{
			return null;
		}

		$folder = preg_replace('#^(?:\.\./)+(?=images(?:/|$))#i', '', $folder);
		$folder = preg_replace('#^(?:\./)+#', '', $folder);
		$folder = ltrim($folder, '/');

		if(str_starts_with(strtolower($folder), 'images/'))
		{
			$folder = substr($folder, 7);
		}
		elseif(strtolower($folder) === 'images')
		{
			$folder = '';
		}

		$segments = [];

		foreach(explode('/', $folder) as $segment)
		{
			if($segment === '' || $segment === '.')
			{
				continue;
			}

			if($segment === '..' || str_contains($segment, ':') || str_contains($segment, "\0"))
			{
				return null;
			}

			$segments[] = $segment;
		}

		if($segments === [])
		{
			return null;
		}

		$root = realpath($imagesRoot);
		if($root === false)
		{
			return null;
		}

		$directory = realpath($root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments));
		if($directory === false || !is_dir($directory))
		{
			return null;
		}

		$normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
		$normalizedDirectory = str_replace('\\', '/', $directory);
		$rootPrefix = $normalizedRoot . '/';
		$isWithinImages = DIRECTORY_SEPARATOR === '\\'
			? strncasecmp($normalizedDirectory, $rootPrefix, strlen($rootPrefix)) === 0
			: str_starts_with($normalizedDirectory, $rootPrefix);

		return $isWithinImages ? $directory : null;
	}
}
