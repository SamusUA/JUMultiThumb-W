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
*       $_img       — thumb image
*       $_link_img  — link for original
*       $_w         — image width
*       $_h         — image height
*       $_caption   — use caption from alt or title
*       $_title     — title for attribute title
*       $_alt       — title for attribute alt
*       $_class     — css class
*       $lightbox_data  — litebox group data (lightgallery)
*       $lightbox   — litebox (lightgallery, colorbox, jmodal)
*/

$data = (object) $displayData;

?>
<div class="col-xs-4 col-sm-3"<?php echo $data->lightbox_data; ?>>
	<div class="<?php echo htmlspecialchars($data->class, ENT_QUOTES, 'UTF-8'); ?> thumbnail">
		<figure
				class="galleryobjcts"
		>
			<?php if($data->link_img) : ?>
			<a
					href="<?php echo htmlspecialchars($data->link, ENT_QUOTES, 'UTF-8'); ?>"
				<?php echo ($data->title ? ' title="' . htmlspecialchars($data->title, ENT_QUOTES, 'UTF-8') . '"' : '') . $data->lightbox . $data->pswp_data; ?>
			>
				<?php endif; ?>

				<img
						src="<?php echo htmlspecialchars($data->img, ENT_QUOTES, 'UTF-8'); ?>"
						alt="<?php echo htmlspecialchars($data->alt, ENT_QUOTES, 'UTF-8'); ?>"
						width="<?php echo (int) $data->w; ?>"
						height="<?php echo (int) $data->h; ?>"
				/>

				<?php if($data->alt !== ''): ?>
					<?php if($data->caption == 1): ?>
						<figcaption class="text-muted">
							<?php echo $data->alt; ?>
						</figcaption>
					<?php endif; ?>
				<?php endif; ?>

				<?php if($data->link_img): ?>
			</a>
		<?php endif; ?>

		</figure>
	</div>
</div>