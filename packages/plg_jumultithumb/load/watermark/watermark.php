<?php
// Joomla 5.0 compatibility updates in watermark.php

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

// Replace JText with Text class
$watermarkText = Text::sprintf('Your watermark text');

// Assuming other existing code...

// Example of updating for Factory::getApplication()
$app = Factory::getApplication();

// Remaining original code...
?>