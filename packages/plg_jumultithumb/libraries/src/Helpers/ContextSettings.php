<?php

namespace JUMultiThumb\Helpers;

final class ContextSettings
{
	public const CONTEXTS = ['article', 'categories', 'category', 'featured'];

	public static function forView(string $view): string
	{
		return in_array($view, self::CONTEXTS, true) ? $view : 'article';
	}

	public static function get(object $params, string $context, string $name, mixed $default): mixed
	{
		if(!in_array($context, self::CONTEXTS, true))
		{
			throw new \InvalidArgumentException('Unsupported image context.');
		}

		$key = $context . '_' . $name;

		return method_exists($params, 'get')
			? $params->get($key, $default)
			: ($params->{$key} ?? $default);
	}

	public static function cachePath(string $context): string
	{
		if(!in_array($context, self::CONTEXTS, true))
		{
			throw new \InvalidArgumentException('Unsupported image context.');
		}

		return 'img/' . $context;
	}

	public static function dimension(mixed $value): int
	{
		$dimension = (int) $value;

		return $dimension > 1 ? $dimension : 0;
	}

	public static function sourceBelowTarget(int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight): bool
	{
		return ($targetWidth > 0 && $sourceWidth < $targetWidth)
			|| ($targetHeight > 0 && $sourceHeight < $targetHeight);
	}
}
