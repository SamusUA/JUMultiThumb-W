<?php
/**
 * JUMultiThumb gallery shortcode picker.
 *
 * @license GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\Registry\Registry;

defined('_JEXEC') or die;

$app = Factory::getApplication();
Factory::getLanguage()->load('plg_editors-xtd_jumultithumb_editorbutton', JPATH_ADMINISTRATOR);
$user = $app->getIdentity();
$allowed = $user->authorise('core.create', 'com_content')
	|| $user->authorise('core.edit', 'com_content')
	|| count($user->getAuthorisedCategories('com_content', 'core.create')) > 0;
$articleId = (int) $app->getUserState('com_content.edit.article.id');
$allowed = $allowed || ($articleId > 0 && $user->authorise('core.edit', 'com_content.article.' . $articleId));

if(!$allowed)
{
	http_response_code(403);
	exit;
}

$galleryPlugin = PluginHelper::getPlugin('content', 'jumultithumb_gallery');
$galleryParams = new Registry($galleryPlugin ? $galleryPlugin->params : '{}');
$galleryFolder = trim(str_replace('\\', '/', (string) $galleryParams->get('galleryfolder', '')), '/');
$galleryFolder = preg_replace('#^images/#i', '', $galleryFolder);
$rootFolder = 'images';
$editorId = preg_replace('/[^A-Za-z0-9_:-]/', '', $app->input->getString('editor', 'jform_articletext'));
$editorId = $editorId !== '' ? $editorId : 'jform_articletext';
$token = Session::getFormToken();
$connectorUrl = 'index.php?option=com_ajax&plugin=jumultithumb_editorbutton&group=editors-xtd&format=raw&task=folders';
$languageTag = htmlspecialchars(Factory::getLanguage()->getTag(), ENT_QUOTES, 'UTF-8');
$escapedRootFolder = htmlspecialchars($galleryFolder, ENT_QUOTES, 'UTF-8');

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="<?php echo $languageTag; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_PICK_GALLERY'), ENT_QUOTES, 'UTF-8'); ?></title>
	<style>
		body { font: 14px/1.5 sans-serif; margin: 1rem; }
		label { display: block; font-weight: 600; margin: .75rem 0 .25rem; }
		input { box-sizing: border-box; max-width: 100%; padding: .4rem; width: 100%; }
		#gallery-tree { border: 1px solid #ccc; max-height: 12rem; overflow: auto; padding: .5rem; }
		#gallery-tree ul { list-style: none; margin: .25rem 0 .25rem 1rem; padding: 0; }
		#gallery-tree > ul { margin-left: 0; }
		#gallery-tree a { color: #1769aa; text-decoration: none; }
		#gallery-tree a:hover { text-decoration: underline; }
		#gallery-status { min-height: 1.5em; }
		.actions { margin-top: 1rem; text-align: right; }
	</style>
</head>
<body>
	<label for="gallery-folder"><?php echo htmlspecialchars(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_FOLDER'), ENT_QUOTES, 'UTF-8'); ?></label>
	<input id="gallery-folder" type="text" value="<?php echo $escapedRootFolder; ?>" autocomplete="off" readonly>
	<div id="gallery-tree" aria-label="<?php echo htmlspecialchars(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_FOLDER_TREE'), ENT_QUOTES, 'UTF-8'); ?>"></div>
	<div id="gallery-status" role="status" aria-live="polite"></div>

	<label for="gallery-title"><?php echo htmlspecialchars(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_TITLE'), ENT_QUOTES, 'UTF-8'); ?></label>
	<input id="gallery-title" type="text" autocomplete="off">

	<label for="gallery-class"><?php echo htmlspecialchars(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_CLASS'), ENT_QUOTES, 'UTF-8'); ?></label>
	<input id="gallery-class" type="text" autocomplete="off">

	<div class="actions">
		<button id="insert-gallery" type="button" class="btn btn-primary"><?php echo htmlspecialchars(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_INSERT'), ENT_QUOTES, 'UTF-8'); ?></button>
	</div>

	<script>
	(() => {
		const rootFolder = <?php echo json_encode($rootFolder, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
		const connectorUrl = <?php echo json_encode($connectorUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
		const editorId = <?php echo json_encode($editorId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
		const tokenName = <?php echo json_encode($token, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
		const tree = document.getElementById('gallery-tree');
		const folderInput = document.getElementById('gallery-folder');
		const status = document.getElementById('gallery-status');

		async function loadFolders(path, list) {
			const body = new URLSearchParams({ dir: path, [tokenName]: '1' });
			const response = await fetch(connectorUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
				body
			});

			if (!response.ok) {
				throw new Error(<?php echo json_encode(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_LOAD_ERROR'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
			}

			const wrapper = document.createElement('div');
			wrapper.innerHTML = await response.text();
			list.replaceChildren(...wrapper.children);
		}

		tree.addEventListener('click', async (event) => {
			const link = event.target.closest('a[rel]');
			if (!link) return;
			event.preventDefault();

			const item = link.closest('li');
			const path = link.getAttribute('rel');
			folderInput.value = path.replace(/^images\//i, '').replace(/\/+$/, '');

			if (item.classList.contains('expanded')) {
				item.classList.remove('expanded');
				item.classList.add('collapsed');
				item.querySelector(':scope > ul')?.remove();
				return;
			}

			item.classList.remove('collapsed');
			item.classList.add('expanded');
			const childList = document.createElement('ul');
			item.append(childList);

			try {
				await loadFolders(path, childList);
			} catch (error) {
				status.textContent = error.message;
				childList.remove();
				item.classList.remove('expanded');
				item.classList.add('collapsed');
			}
		});

		loadFolders(rootFolder, tree).catch((error) => {
			status.textContent = error.message;
		});

		document.getElementById('insert-gallery').addEventListener('click', () => {
			const folder = folderInput.value.trim().replace(/\\/g, '/').replace(/^\/+|\/+$/g, '').replace(/^images\//i, '');
			const segments = folder.split('/');

			if (!folder || segments.some((segment) => !segment || segment === '.' || segment === '..' || segment.includes(':'))) {
				status.textContent = <?php echo json_encode(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_INVALID_FOLDER'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
				return;
			}

			const title = document.getElementById('gallery-title').value.trim().replace(/[|{}]/g, '');
			const cssClass = document.getElementById('gallery-class').value.trim().replace(/[|{}]/g, '');
			const shortcode = `{gallery ${folder}${title ? `|title=${title}` : ''}${cssClass ? `|class=${cssClass}` : ''}}`;
			const parentWindow = window.parent;
			const instances = parentWindow.Joomla?.editors?.instances;
			const editor = instances?.[editorId] ?? instances?.get?.(editorId);
			const tinyMce = parentWindow.tinymce?.get(editorId) ?? parentWindow.tinyMCE?.get(editorId);

			if (editor?.replaceSelection) {
				editor.replaceSelection(shortcode);
			} else if (tinyMce) {
				tinyMce.execCommand('mceInsertContent', false, shortcode);
			} else {
				const textarea = parentWindow.document.getElementById(editorId);
				if (!textarea) {
					status.textContent = <?php echo json_encode(Text::_('PLG_JUMULTITHUMB_EDITORBUTTON_INSERT_ERROR'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
					return;
				}
				const start = textarea.selectionStart ?? textarea.value.length;
				const end = textarea.selectionEnd ?? start;
				textarea.setRangeText(shortcode, start, end, 'end');
				textarea.dispatchEvent(new parentWindow.Event('input', { bubbles: true }));
			}

			parentWindow.Joomla?.Modal?.getCurrent?.()?.close?.();
		});
	})();
	</script>
</body>
</html>
