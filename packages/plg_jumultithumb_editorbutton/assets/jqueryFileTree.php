<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

require_once __DIR__ . '/joomla-bootstrap.php';

$app = Factory::getApplication();
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

$token = Session::getFormToken();
if($app->input->getMethod() !== 'POST' || $app->input->post->get($token, '', 'cmd') !== '1')
{
	http_response_code(403);
	exit;
}

$relativePath = rawurldecode($app->input->post->getString('dir', ''));
$relativePath = trim(str_replace('\\', '/', $relativePath), '/');
$segments = explode('/', $relativePath);

if(strtolower($segments[0] ?? '') !== 'images' || in_array('..', $segments, true) || in_array('.', $segments, true))
{
	http_response_code(400);
	exit;
}

$siteRoot = realpath(JPATH_SITE);
$imagesRoot = realpath(JPATH_SITE . '/images');
$directory = $siteRoot === false ? false : realpath($siteRoot . '/' . $relativePath);

if($imagesRoot === false || $directory === false || !is_dir($directory))
{
	http_response_code(404);
	exit;
}

$normalizedImagesRoot = rtrim(str_replace('\\', '/', $imagesRoot), '/');
$normalizedDirectory = str_replace('\\', '/', $directory);
$imagesPrefix = $normalizedImagesRoot . '/';
$isInsideImages = DIRECTORY_SEPARATOR === '\\'
	? strncasecmp($normalizedDirectory, $imagesPrefix, strlen($imagesPrefix)) === 0
	: str_starts_with($normalizedDirectory, $imagesPrefix);

if(!$isInsideImages)
{
	http_response_code(400);
	exit;
}

$entries = scandir($directory);
$folders = [];

foreach($entries === false ? [] : $entries as $entry)
{
	if($entry === '.' || $entry === '..' || is_link($directory . DIRECTORY_SEPARATOR . $entry))
	{
		continue;
	}

	if(is_dir($directory . DIRECTORY_SEPARATOR . $entry))
	{
		$folders[] = $entry;
	}
}

natcasesort($folders);
echo '<ul class="jqueryFileTree">';

foreach($folders as $folder)
{
	$childPath = $relativePath . '/' . $folder . '/';
	echo '<li class="directory collapsed"><a href="#" rel="' . htmlspecialchars($childPath, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">'
		. htmlspecialchars($folder, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
		. '</a></li>';
}

echo '</ul>';