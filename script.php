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

define('DS', DIRECTORY_SEPARATOR);
define('JPATH_BASE', dirname(__DIR__, 3));

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Registry\Registry;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication('administrator');
$user = Factory::getUser();
$lang = Factory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

if ((int) $user->id < 1)
{
    echo Text::_('PLG_JUMULTITHUMB_LOGIN');

    return;
}

$plugin = PluginHelper::getPlugin('content', 'jumultithumb_gallery');
$rootfolder = 'images/';

if ($plugin && !empty($plugin->params))
{
    $params = new Registry($plugin->params);
    $galleryFolder = $params->get('galleryfolder', '');

    if ($galleryFolder !== '')
    {
        $rootfolder = 'images/' . trim($galleryFolder, '/\\') . '/';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo mb_strtolower($lang->getTag()); ?>">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <title><?php echo Text::_('PLG_JUMULTITHUMB_GALLERY_INSERT_TAG'); ?></title>

    <link href="<?php echo Uri::root(true); ?>/administrator/templates/atum/css/template.css" rel="stylesheet" data-asset-name="template.atum.ltr" data-asset-dependencies="fontawesome" />
    <link href="assets/jqueryFileTree.css" rel="stylesheet" type="text/css" />

    <script src="<?php echo Uri::root(true); ?>/media/vendor/jquery/js/jquery.min.js"></script>
    <script src="assets/jqueryFileTree.js?v3"></script>

    <script>
        jQuery.noConflict();
        jQuery(document).ready(function () {
            jQuery('.selects').fileTree({
                root: '<?php echo $rootfolder; ?>',
                script: 'assets/jqueryFileTree.php',
                expandSpeed: 1000,
                collapseSpeed: 1000,
                multiFolder: false
            });
        });

        function insertJUGallery() {
            var folder = document.getElementById("folder").value,
                title = document.getElementById("title").value,
                cssclass = document.getElementById("cssclass").value;

            if (folder !== '') {
                folder = "" + folder + "";
            }

            if (title === '' && cssclass !== '') {
                title = "|";
            } else if (title !== '') {
                title = "|" + title;
            } else {
                title = "";
            }

            if (cssclass !== '') {
                cssclass = "|" + cssclass;
            }

            var tag = "{gallery " + folder + title + cssclass + "}";

            if (window.parent && window.parent.Joomla && window.parent.Joomla.editors && window.parent.Joomla.editors.instances && window.parent.Joomla.editors.instances['jform_articletext']) {
                window.parent.Joomla.editors.instances['jform_articletext'].replaceSelection(tag);
            }

            if (window.parent && window.parent.Joomla && window.parent.Joomla.Modal) {
                var modal = window.parent.Joomla.Modal.getCurrent();
                if (modal) {
                    modal.close();
                }
            }

            return false;
        }
    </script>
    <style>
        body {
            background: transparent;
        }

        fieldset {
            margin: 5px 0 !important;
        }
    </style>
</head>
<body>
<form class="form-horizontal">
    <div class="control-group">
        <label class="control-label" for="folder"><?php echo Text::_('PLG_JUMULTITHUMB_GALLERY_SELECT_FOLDER'); ?>:</label>
        <div class="controls">
            <div class="selects"></div>
            <br>
            <input id="folder" class="folderurl uneditable-input" name="selectfolder" disabled="disabled" style="width:30%">
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="title"><?php echo Text::_('PLG_JUMULTITHUMB_GALLERY_TITLE'); ?>:</label>
        <div class="controls">
            <input type="text" id="title" name="title" size="60" value="" />
        </div>
    </div>
    <div class="control-group">
        <label class="control-label" for="cssclass"><?php echo Text::_('PLG_JUMULTITHUMB_GALLERY_CSS_CLASS'); ?>:</label>
        <div class="controls">
            <input type="text" id="cssclass" name="cssclass" size="60" />
        </div>
    </div>
    <div class="control-group">
        <div class="controls">
            <button onclick="insertJUGallery();" class="btn btn-success">
                <?php echo Text::_('PLG_JUMULTITHUMB_GALLERY_INSERT_TAG'); ?>
            </button>
        </div>
    </div>
</form>
</body>
</html>
