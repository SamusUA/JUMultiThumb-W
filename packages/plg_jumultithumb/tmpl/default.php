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

/*
*       Params :
*       $_noresize  — use rule for original image
*       $_img       — thumb image
*       $_link_img  — link for original
*       $_w         — image width
*       $_h         — image height
*       $_caption   — use caption from alt or title
*       $_title     — title for attribute title
*       $_alt       — title for attribute alt
*       $_class     — css class
*       $lightbox   — litebox (lightgallery, colorbox, jmodal)
*/

$data = (object) $displayData;
$noResize = (int) ($data->noresize ?? 0);
$mask = (int) ($data->mask ?? 0) === 1 && (int) ($data->w ?? 0) > 0 && (int) ($data->h ?? 0) > 0;
$linkClass = trim((string) ($data->link_class ?? ''));
$imageClass = trim((string) ($data->image_class ?? ''));
$captionText = (string) ($data->caption_text ?? '');
$maskFrameStyle = $mask
	? ' style="width:100%;max-width:' . (int) $data->w . 'px;aspect-ratio:' . (int) $data->w . ' / ' . (int) $data->h . ';overflow:hidden;"'
	: '';
$maskLinkStyle = $mask ? ' style="display:block;width:100%;max-width:' . (int) $data->w . 'px;"' : '';
$maskImageStyle = $mask
	? ' style="display:block;width:100%;height:100%;object-fit:cover;object-position:center;"'
	: '';

?>
<?php if($noResize === 1): ?>
	<div class="row row-fluid">
	<div class="col-xs-12 span12">
<?php endif; ?>

	<figure class="<?php echo htmlspecialchars($data->class, ENT_QUOTES, 'UTF-8') . ($noResize === 1 ? ' thumbnail' : ''); ?>">
		<?php if($data->link_img) : ?>
		<a href="<?php echo htmlspecialchars($data->link_img, ENT_QUOTES, 'UTF-8'); ?>"<?php echo ($linkClass !== '' ? ' class="' . htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') . '"' : '') . ($data->title !== '' ? ' title="' . htmlspecialchars($data->title, ENT_QUOTES, 'UTF-8') . '"' : '') . $maskLinkStyle . $data->lightbox . $data->pswp_data; ?>>
			<?php endif; ?>
			<?php if($mask): ?>
				<div class="juimage-mask-frame"<?php echo $maskFrameStyle; ?>>
			<?php endif; ?>

			<img
					src="<?php echo htmlspecialchars($data->img, ENT_QUOTES, 'UTF-8'); ?>"
					alt="<?php echo htmlspecialchars($data->alt, ENT_QUOTES, 'UTF-8'); ?>"
				<?php echo $maskImageStyle; ?>
				<?php echo ($imageClass !== '' ? ' class="' . htmlspecialchars($imageClass, ENT_QUOTES, 'UTF-8') . '"' : ''); ?>
				<?php echo ($data->w ? ' width="' . (int) $data->w . '"' : '') . ($data->h ? ' height="' . (int) $data->h . '"' : ''); ?>
			>
			<?php if($mask): ?>
				</div>
			<?php endif; ?>

			<?php if($captionText !== ''): ?>
				<figcaption itemprop="caption" class="text-muted"><?php echo htmlspecialchars($captionText, ENT_QUOTES, 'UTF-8'); ?></figcaption>
			<?php elseif($data->alt != ''): ?>
				<?php if($data->caption == 1): ?>
					<figcaption itemprop="caption" class="text-muted"><?php echo htmlspecialchars($data->alt, ENT_QUOTES, 'UTF-8'); ?></figcaption>
				<?php endif; ?>
			<?php endif; ?>

			<?php if($data->link_img): ?>
		</a>
	<?php endif; ?>

	</figure>

<?php if($noResize === 1): ?>
	</div>
	</div>
<?php endif; ?>