<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2026 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

define('_JEXEC', 1);
define('JPATH_BASE', __DIR__ . '/../../../../..');
define('MAX_SIZE', '500');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app        = Factory::getApplication();
$joomlaUser = Factory::getUser();
$lang       = Factory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

/**
 * @param $text
 * @param $error
 *
 * @return string
 *
 * @since 7.0
 */
function alert($text, $error)
{
	if($error === 'message')
	{
		$error = 'alert-info';
	}

	if($error === 'notice')
	{
		$error = 'alert-error';
	}

	return '<div class="alert ' . $error . '">' . $text . '</div>';
}

/**
 * @param $str
 *
 * @return bool|string
 *
 * @since 7.0
 */
function getExtension($str)
{
	$i = strrpos($str, '.');

	if(!$i)
	{
		return '';
	}

	$l = strlen($str) - $i;

	return substr($str, $i + 1, $l);
}

$csslink = '<link href="../../../../../media/templates/administrator/atum/css/template.css" rel="stylesheet" type="text/css" />'
         . PHP_EOL
         . '<link href="../../../../../media/jui/css/bootstrap.css" rel="stylesheet" type="text/css" />';

if($joomlaUser->get('id') < 1)
{
	?>
    <!DOCTYPE html>
    <html>
    <head><?php echo $csslink; ?></head>
    <body><?php echo alert(Text::_('PLG_JUMULTITHUMB_LOGIN'), 'notice'); ?></body>
    </html>
	<?php
	return;
}

$errors = 0;
if(isset($_POST['Submit'], $_FILES['image']['name']))
{
	$filename  = stripslashes($_FILES['image']['name']);
	$extension = getExtension($filename);
	$extension = strtolower($extension);

	if($extension != 'png')
	{
		if(isset($_POST['watermark']) && $_POST['watermark'] == 'big')
		{
			$unknownext = alert(Text::_('PLG_JUMULTITHUMB_NOTICE6'), 'notice');
		}
        elseif(isset($_POST['watermark']) && $_POST['watermark'] == 'small')
		{
			$unknownext_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE6'), 'notice');
		}
		$errors = 1;
	}
	else
	{
		$size = $_FILES['image']['size'];
		if($size > MAX_SIZE * 1000024)
		{
			if(isset($_POST['watermark']) && $_POST['watermark'] == 'big')
			{
				$limitimg = alert(Text::_('PLG_JUMULTITHUMB_NOTICE7'), 'notice');
			}
            elseif(isset($_POST['watermark']) && $_POST['watermark'] == 'small')
			{
				$limitimg_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE7'), 'notice');
			}

			$errors = 1;
		}

		if(isset($_POST['watermark']))
		{
			if($_POST['watermark'] == 'big')
			{
				$image_name = 'w.png';
			}
	        elseif($_POST['watermark'] == 'small')
			{
				$image_name = 'ws.png';
			}
		}

		if(!($size > MAX_SIZE * 1000024))
		{
			$newname = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/' . $image_name;

			if(!move_uploaded_file($_FILES['image']['tmp_name'], $newname))
			{
				if(isset($_POST['watermark']) && $_POST['watermark'] == 'big')
				{
					$uploadunsuccessfull = alert(Text::_('PLG_JUMULTITHUMB_NOTICE8'), 'notice');
				}
                elseif(isset($_POST['watermark']) && $_POST['watermark'] == 'small')
				{
					$uploadunsuccessfull_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE8'), 'notice');
				}
				$errors = 1;
			}
		}
	}
}

if(isset($_POST['Submit']) && !$errors)
{
	if(isset($_POST['watermark']) && $_POST['watermark'] === 'big')
	{
		$uploadsucess = alert(Text::_('PLG_JUMULTITHUMB_NOTICE9'), 'message');
	}
    elseif(isset($_POST['watermark']) && $_POST['watermark'] === 'small')
	{
		$uploadsucess_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE9'), 'message');
	}
}

if($app->input->getCmd('del') === 'big')
{
	if(is_file(JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/w.png'))
	{
		unlink(JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/w.png');
		$noticewb = alert(Text::_('PLG_JUMULTITHUMB_NOTICE10'), 'message');
	}
	else
	{
		$noticewb = alert(Text::_('PLG_JUMULTITHUMB_NOTICE11'), 'notice');
	}
}
elseif($app->input->getCmd('del') === 'small')
{
	if(is_file(JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/ws.png'))
	{
		unlink(JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/ws.png');
		$noticews = alert(Text::_('PLG_JUMULTITHUMB_NOTICE10'), 'message');
	}
	else
	{
		$noticews = alert(Text::_('PLG_JUMULTITHUMB_NOTICE11'), 'notice');
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?php echo Text::_('PLG_JUMULTITHUMB_WATERMARK'); ?></title>
	<?php echo $csslink; ?>
</head>
<body>
<?php if(isset($notice)) { echo $notice; } ?>
<?php if(isset($unknownext)) { echo $unknownext; } ?>
<?php if(isset($unknownext_s)) { echo $unknownext_s; } ?>
<?php if(isset($limitimg)) { echo $limitimg; } ?>
<?php if(isset($limitimg_s)) { echo $limitimg_s; } ?>
<?php if(isset($uploadunsuccessfull)) { echo $uploadunsuccessfull; } ?>
<?php if(isset($uploadunsuccessfull_s)) { echo $uploadunsuccessfull_s; } ?>
<?php if(isset($uploadsucess)) { echo $uploadsucess; } ?>
<?php if(isset($uploadsucess_s)) { echo $uploadsucess_s; } ?>
<?php if(isset($noticewb)) { echo $noticewb; } ?>
<?php if(isset($noticews)) { echo $noticews; } ?>

<form method="post" enctype="multipart/form-data">
	<!-- Watermark upload form will be implemented here -->
</form>
</body>
</html>
