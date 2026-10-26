<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2023 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */


defined('_JEXEC') or die;

define('JPATH_BASE', dirname(__DIR__, 5));
define('MAX_SIZE', 500);

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication('administrator');
$user = Factory::getUser();
$lang = Factory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

$watermarkBase = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark';

function alert($text, $error = 'notice')
{
    if ($error === 'message')
    {
        $error = 'alert-info';
    }

    if ($error === 'notice')
    {
        $error = 'alert-error';
    }

    return '<div class="alert ' . $error . '">' . $text . '</div>';
}

function getExtension($str)
{
    $pos = strrpos($str, '.');

    if ($pos === false)
    {
        return '';
    }

    return strtolower(substr($str, $pos + 1));
}

$cssLink = '<link href="' . Uri::root(true) . '/administrator/templates/atum/css/template.css" rel="stylesheet" type="text/css" />';
$cssLink .= '<link href="' . Uri::root(true) . '/media/system/css/joomla-admin.css" rel="stylesheet" type="text/css" />';

if ((int) $user->id < 1)
{
    echo '<!DOCTYPE html><html><head>' . $cssLink . '</head><body>' . alert(Text::_('PLG_JUMULTITHUMB_LOGIN'), 'notice') . '</body></html>';
    return;
}

$errors = 0;

if (isset($_POST['Submit'], $_FILES['image']['name']))
{
    $filename = stripslashes($_FILES['image']['name']);
    $extension = getExtension($filename);

    if ($extension !== 'png')
    {
        if (isset($_POST['watermark']) && $_POST['watermark'] === 'big')
        {
            $unknownext = alert(Text::_('PLG_JUMULTITHUMB_NOTICE6'), 'notice');
        }
        elseif (isset($_POST['watermark']) && $_POST['watermark'] === 'small')
        {
            $unknownext_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE6'), 'notice');
        }
        $errors = 1;
    }
    else
    {
        $size = (int) $_FILES['image']['size'];

        if ($size > MAX_SIZE * 1000024)
        {
            if (isset($_POST['watermark']) && $_POST['watermark'] === 'big')
            {
                $limitimg = alert(Text::_('PLG_JUMULTITHUMB_NOTICE7'), 'notice');
            }
            elseif (isset($_POST['watermark']) && $_POST['watermark'] === 'small')
            {
                $limitimg_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE7'), 'notice');
            }

            $errors = 1;
        }

        $imageName = '';

        if (isset($_POST['watermark']) && $_POST['watermark'] === 'big')
        {
            $imageName = 'w.png';
        }
        elseif (isset($_POST['watermark']) && $_POST['watermark'] === 'small')
        {
            $imageName = 'ws.png';
        }

        if ($imageName !== '' && !($size > MAX_SIZE * 1000024))
        {
            $newName = $watermarkBase . '/' . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $newName))
            {
                if (isset($_POST['watermark']) && $_POST['watermark'] === 'big')
                {
                    $uploadunsuccessfull = alert(Text::_('PLG_JUMULTITHUMB_NOTICE8'), 'notice');
                }
                elseif (isset($_POST['watermark']) && $_POST['watermark'] === 'small')
                {
                    $uploadunsuccessfull_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE8'), 'notice');
                }

                $errors = 1;
            }
        }
    }
}

if (isset($_POST['Submit']) && !$errors)
{
    if (isset($_POST['watermark']) && $_POST['watermark'] === 'big')
    {
        $uploadsucess = alert(Text::_('PLG_JUMULTITHUMB_NOTICE9'), 'message');
    }
    elseif (isset($_POST['watermark']) && $_POST['watermark'] === 'small')
    {
        $uploadsucess_s = alert(Text::_('PLG_JUMULTITHUMB_NOTICE9'), 'message');
    }
}

if ($app->input->getCmd('del') === 'big')
{
    if (is_file($watermarkBase . '/w.png'))
    {
        unlink($watermarkBase . '/w.png');
        $noticewb = alert(Text::_('PLG_JUMULTITHUMB_NOTICE10'), 'message');
    }
    else
    {
        $noticewb = alert(Text::_('PLG_JUMULTITHUMB_NOTICE11'), 'notice');
    }
}
elseif ($app->input->getCmd('del') === 'small')
{
    if (is_file($watermarkBase . '/ws.png'))
    {
        unlink($watermarkBase . '/ws.png');
        $noticews = alert(Text::_('PLG_JUMULTITHUMB_NOTICE10'), 'message');
    }
    else
    {
        $noticews = alert(Text::_('PLG_JUMULTITHUMB_NOTICE11'), 'notice');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php echo $cssLink; ?>
    <style>
        body {
            background: transparent;
            font-size: 102%;
            margin: 0 20px;
            padding: 0;
        }
        fieldset {
            margin: 0 0 5px 0 !important;
        }
        .img {
            margin: 0;
            max-width: 300px;
            padding: 50px;
            background-color: #818181;
            background-image:
                linear-gradient(45deg, #707070 25%, transparent 25%, transparent 74%, #707070 75%, #707070),
                linear-gradient(45deg, #707070 25%, transparent 25%, transparent 74%, #707070 75%, #707070);
            background-size: 21px 21px;
            background-position: 0 0, 11px 11px;
        }
    </style>
</head>
<body>
<?php
if (isset($noticewb)) { echo $noticewb; }
if (isset($noticews)) { echo $noticews; }
if (isset($uploadsucess)) { echo $uploadsucess; }
if (isset($unknownext)) { echo $unknownext; }
if (isset($limitimg)) { echo $limitimg; }
if (isset($uploadunsuccessfull)) { echo $uploadunsuccessfull; }
if (isset($uploadsucess_s)) { echo $uploadsucess_s; }
if (isset($unknownext_s)) { echo $unknownext_s; }
if (isset($limitimg_s)) { echo $limitimg_s; }
if (isset($uploadunsuccessfull_s)) { echo $uploadunsuccessfull_s; }
?>

<fieldset class="adminform" style="clear: both; float: left; width: 48%;">
    <legend>Watermark for original image</legend>

    <form name="wb" method="post" enctype="multipart/form-data" class="well well-small" action="">
        <input type="file" name="image" />
        <input name="Submit" type="submit" class="btn btn-primary" value="<?php echo Text::_('PLG_JUMULTITHUMB_NOTICE15'); ?>" />
        <input type="hidden" name="watermark" value="big" />
    </form>

    <form name="wbdel" method="post" action="">
        <input name="Submit" type="submit" class="btn btn-inverse" value="<?php echo Text::_('PLG_JUMULTITHUMB_NOTICE16'); ?>" />
        <input type="hidden" name="del" value="big" />
    </form>

    <div style="clear: both; padding-top: 10px;">
        <?php
        if (is_file($watermarkBase . '/w.png'))
        {
            echo '<img src="' . Uri::root() . 'plugins/content/jumultithumb/load/watermark/w.png?v=' . time() . '" alt="" class="img" />';
        }
        else
        {
            echo '<a href="https://denysdesign.com" target="_blank" title="Denys Design Studio"><img src="' . Uri::root() . 'plugins/content/jumultithumb/load/watermark/juw.png?v=' . time() . '" alt="" class="img" /></a>';
        }
        ?>
    </div>
</fieldset>

<fieldset class="adminform" style="float: right; width: 48%;">
    <legend>Watermark for thumbnail image</legend>

    <form name="ws" method="post" enctype="multipart/form-data" class="well well-small" action="">
        <input type="file" name="image" />
        <input name="Submit" type="submit" class="btn btn-primary" value="<?php echo Text::_('PLG_JUMULTITHUMB_NOTICE15'); ?>" />
        <input type="hidden" name="watermark" value="small" />
    </form>

    <form name="wbdel" method="post" action="">
        <input name="Submit" type="submit" class="btn btn-inverse" value="<?php echo Text::_('PLG_JUMULTITHUMB_NOTICE16'); ?>" />
        <input type="hidden" name="del" value="small" />
    </form>

    <div style="clear: both; padding-top: 10px;">
        <?php
        if (is_file($watermarkBase . '/ws.png'))
        {
            echo '<img src="' . Uri::root() . 'plugins/content/jumultithumb/load/watermark/ws.png?v=' . time() . '" alt="" class="img" />';
        }
        else
        {
            echo '<a href="https://joomla-ua.org" target="_blank" title="Joomla! Ukraine"><img src="' . Uri::root() . 'plugins/content/jumultithumb/load/watermark/juws.png?v=' . time() . '" alt="" class="img" /></a>';
        }
        ?>
    </div>
</fieldset>
</body>
</html>
