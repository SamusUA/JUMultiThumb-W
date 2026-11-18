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
*       $gallstyle  — css class fom short code
*       $galltitle  — title form short code or plugin setting
*       $gallery    — display gallery
*/

$data = (object) $displayData;
$galleryTitle = (string) ($data->galltitle ?? '');
$galleryStyle = (string) ($data->gallstyle ?? '');
$galleryContent = (string) ($data->gallery ?? '');

?>
<div class="juphotogallery<?php echo($galleryStyle !== '' ? ' ' . htmlspecialchars($galleryStyle, ENT_QUOTES, 'UTF-8') : ''); ?>">
	<?php if($galleryTitle !== '') : ?>
		<h3 class="jutitlegallery">
			<?php echo htmlspecialchars($galleryTitle, ENT_QUOTES, 'UTF-8'); ?>
		</h3>
	<?php endif; ?>

	<div class="jugallerybody row row-photo" itemscope itemtype="http://schema.org/ImageGallery">
		<?php echo $galleryContent; ?>
	</div>
</div>