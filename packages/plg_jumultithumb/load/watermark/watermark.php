<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2023 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @copyright        2026 (C) WEBUS Studio, https://webus-studio.com. All rights reserved.
 * @contributor      AI-assisted adaptation and maintenance by WEBUS Studio for Joomla 5/6 compatibility.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

define('JPATH_BASE', dirname(__DIR__, 4));
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication('administrator');
$user = Factory::getUser();
$lang = Factory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

$cssLink = '<link href="' . Uri::root(true) . '/administrator/templates/atum/css/template.css" rel="stylesheet" type="text/css" />';
$cssLink .= '<link href="' . Uri::root(true) . '/media/system/css/joomla-admin.css" rel="stylesheet" type="text/css" />';

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
?>
<?php if ((int) $user->id < 1) : ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php echo $cssLink; ?>
</head>
<body>
    <?php echo alert(Text::_('PLG_JUMULTITHUMB_LOGIN'), 'notice'); ?>
</body>
</html>
<?php
    return;
endif;
?>

<?php
$style = JPATH_ROOT . '/media/plg_jumultithumb/style.css';

$newCss = '/*
-----------------------------------------------
File:    style.css
Author:  Denys Nosov at https://www.denysdesign.com
Version: ' . date('d.m.Y') . '

License: Free
----------------------------------------------- */

.juimage {
  border: #ccc 1px solid;
  padding: 1px;
}
.juimg-category { }
.juimg-featured { }
.juimg-article { }
.juleft {
  float: left;
  margin: 0 6px 6px 0;
}
.juright {
  float: right;
  margin: 0 0 6px 6px;
}
';

if (!is_dir(dirname($style)))
{
    mkdir(dirname($style), 0777, true);
}

if (!file_exists($style))
{
    file_put_contents($style, $newCss);
    $notice = alert(Text::_('PLG_JUMULTITHUMB_NOTICE1') . '<br>' . Text::_('PLG_JUMULTITHUMB_NOTICE2'), 'notice');
}

if (file_exists($style) && !empty($_POST['txt']))
{
    file_put_contents($style, $_POST['txt']);
    $notice = alert(Text::_('PLG_JUMULTITHUMB_NOTICE3'), 'message');
}

if (file_exists($style) && filesize($style) < 3)
{
    file_put_contents($style, $newCss);
    $notice = alert(Text::_('PLG_JUMULTITHUMB_NOTICE4') . '<br>' . Text::_('PLG_JUMULTITHUMB_NOTICE5'), 'notice');
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
        }
        .left { float: left; }
        .right { float: right; }
    </style>
</head>
<body>
<?php if (isset($notice)) : ?>
    <?php echo $notice; ?>
<?php endif; ?>

<form action="" method="post">
    <?php echo Text::_('PLG_JUMULTITHUMB_CSS_FRONT'); ?>
    <button type="submit" class="btn btn-primary right">
        <i class="icon-apply icon-white"></i>
        <?php echo Text::_('PLG_JUMULTITHUMB_CSS_SAVE'); ?>
    </button>

    <textarea name="txt" style="width: 100%; height: 585px; clear: both;" id="css_source"><?php echo htmlspecialchars(file_get_contents($style), ENT_QUOTES, 'UTF-8'); ?></textarea>
</form>
</body>
</html>
