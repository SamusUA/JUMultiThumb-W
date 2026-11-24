<?php

namespace Joomla\CMS\Plugin
{
	class CMSPlugin
	{
		protected $params;
	}
}

namespace Joomla\CMS\Form
{
	class Form {}
	class FormRule {}
}

namespace Joomla\Registry
{
	class Registry
	{
		public function __construct(private array $values) {}

		public function get(string $key, mixed $default = null): mixed
		{
			$value = $this->values;
			foreach(explode('.', $key) as $part)
			{
				if(!is_array($value) || !array_key_exists($part, $value))
				{
					return $default;
				}
				$value = $value[$part];
			}
			return $value;
		}
	}
}

namespace Joomla\CMS\Log
{
	class Log
	{
		public const WARNING = 4;
		public static array $messages = [];

		public static function add(string $message, int $priority, string $category): void
		{
			self::$messages[] = $message;
		}
	}
}

namespace JUMultiThumb\Helpers
{
	class Image
	{
		public static array $calls = [];

		public static function watermarkOptions(object $params, object $attributes, string $context, bool $small = false, bool $enabled = true): ?array
		{
			return $enabled && (int) $params->get($context . ($small ? '_watermark_thumbnail' : '_watermark_popup'), 0) === 1
				? ['image' => 'watermark.png']
				: null;
		}

		public static function thumb(array $options): string
		{
			self::$calls[] = $options;
			return $options['cache'] . '/rendered.jpg';
		}
	}
}

namespace
{
	use Joomla\CMS\Log\Log;
	use Joomla\Registry\Registry;
	use JUMultiThumb\Helpers\ContextSettings;
	use JUMultiThumb\Helpers\Image;

	define('_JEXEC', 1);
	require_once __DIR__ . '/../packages/plg_jumultithumb/jumultithumb.php';
	require_once __DIR__ . '/../packages/plg_jumultithumb/rules/popupdimension.php';

	function check(bool $condition, string $message): void
	{
		if(!$condition)
		{
			throw new \RuntimeException($message);
		}
	}

	$plugin = (new \ReflectionClass(\plgContentjumultithumb::class))->newInstanceWithoutConstructor();
	$paramsProperty = new \ReflectionProperty(\Joomla\CMS\Plugin\CMSPlugin::class, 'params');
	$imageVariants = new \ReflectionMethod($plugin, 'imageVariants');
	$rule = new \JFormRulePopupdimension();

	foreach(ContextSettings::CONTEXTS as $context)
	{
		$base = [
			$context . '_nothumb' => 1,
			$context . '_popup_width' => 800,
			$context . '_popup_height' => 500,
			$context . '_popup_maxsize' => 0,
			$context . '_watermark_popup' => 1,
		];
		$element = new \SimpleXMLElement('<field watermark_field="' . $context . '_watermark_popup" />');
		$enabled = new Registry(['params' => [$context . '_watermark_popup' => 1]]);
		$disabled = new Registry(['params' => [$context . '_watermark_popup' => 0]]);
		foreach([null, '', '0', '1', '-2', 'abc'] as $invalid)
		{
			check(!$rule->test($element, $invalid, 'params', $enabled), $context . ': invalid watermark dimension was accepted');
		}
		check($rule->test($element, '800', 'params', $enabled), $context . ': valid watermark dimension was rejected');
		check($rule->test($element, null, 'params', $disabled), $context . ': optional dimension was rejected');

		$render = static function(array $values, bool $allowed = true) use ($plugin, $paramsProperty, $imageVariants, $context): array {
			$paramsProperty->setValue($plugin, new Registry($values));
			Image::$calls = [];
			Log::$messages = [];
			return $imageVariants->invoke($plugin, 'images/source.jpg', 1600, 1000, $context, 85, new \stdClass(), $allowed);
		};

		$result = $render($base);
		check(count(Image::$calls) === 1, $context . ': watermark must create a popup when size processing is off');
		check(Image::$calls[0]['w'] === 800 && Image::$calls[0]['h'] === 500 && Image::$calls[0]['zc'] === 0 && Image::$calls[0]['aoe'] === 0, $context . ': popup must preserve the whole image within its size limits without enlargement');
		check(Image::$calls[0]['watermark'] !== null && Image::$calls[0]['cache'] === 'img/' . $context . '/popup/watermark', $context . ': watermarked popup must have its own cache');
		check($result['popup'] === 'img/' . $context . '/popup/watermark/rendered.jpg', $context . ': popup URL is incorrect');

		$plain = $base;
		$plain[$context . '_watermark_popup'] = 0;
		$plain[$context . '_popup_maxsize'] = 1;
		$render($plain);
		check(Image::$calls[0]['watermark'] === null && Image::$calls[0]['cache'] === 'img/' . $context . '/popup/normal', $context . ': unwatermarked popup must have its own cache');
		check(Image::$calls[0]['zc'] === 0, $context . ': unwatermarked popup must not crop');

		$withThumbnail = $plain;
		$withThumbnail[$context . '_nothumb'] = 0;
		$withThumbnail[$context . '_width'] = 160;
		$withThumbnail[$context . '_height'] = 160;
		foreach([0, 1] as $crop)
		{
			$withThumbnail[$context . '_crop'] = $crop;
			$render($withThumbnail);
			check(count(Image::$calls) === 2 && Image::$calls[0]['zc'] === $crop && Image::$calls[1]['zc'] === 0, $context . ': thumbnail crop must stay independent of the popup');
		}

		$plain[$context . '_popup_maxsize'] = 0;
		$result = $render($plain);
		check(Image::$calls === [] && $result['popup'] === 'images/source.jpg', $context . ': disabled watermark and size processing must preserve the original');

		$invalid = $base;
		$invalid[$context . '_popup_height'] = 1;
		$result = $render($invalid);
		check(Image::$calls === [] && $result['popup'] === '' && Log::$messages !== [], $context . ': invalid saved dimensions must be reported without an unwatermarked popup');

		$noPopup = $base;
		$noPopup[$context . '_nopopup'] = 1;
		$result = $render($noPopup);
		check(Image::$calls === [] && $result['popup'] === '', $context . ': disabled popup must not render a copy');

		$result = $render($base, false);
		check(Image::$calls === [] && $result['popup'] === 'images/source.jpg', $context . ': per-article watermark opt-out must be respected');
	}

	echo "Popup watermark tests passed.\n";
}
