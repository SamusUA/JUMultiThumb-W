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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

defined('JPATH_BASE') or die;

class JFormFieldModal_Upload extends FormField
{
    protected $type = 'Modal_Upload';

    protected function getInput()
    {
        $link = Uri::root() . 'plugins/content/jumultithumb/load/watermark/watermark.php';

        $modalParams = [
            'title'      => Text::_('PLG_JUMULTITHUMB_WATERMARK_UPLOAD'),
            'url'        => $link,
            'height'     => '100%',
            'width'      => '100%',
            'bodyHeight' => 70,
            'modalWidth' => 80,
        ];

        $html = HTMLHelper::_('bootstrap.renderModal', 'modal-' . $this->id, $modalParams);
        $html .= '<button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modal-' . $this->id . '">'
            . Text::_('PLG_JUMULTITHUMB_WATERMARK_UPLOAD')
            . '</button>';

        return $html;
    }
}
