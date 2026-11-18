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

?>
<div class="juphotogallery<?php echo(isset($data->gallstyle) ? ' ' . htmlspecialchars($data->gallstyle, ENT_QUOTES, 'UTF-8') : ''); ?>">
	<?php if($data->galltitle !== '') : ?>
		<h3 class="jutitlegallery">
			<?php echo htmlspecialchars($data->galltitle, ENT_QUOTES, 'UTF-8'); ?>
		</h3>
	<?php endif; ?>

	<div class="jugallerybody row row-photo" itemscope itemtype="http://schema.org/ImageGallery">
		<?php echo $data->gallery; ?>
	</div>
</div>