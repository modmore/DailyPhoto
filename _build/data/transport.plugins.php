<?php
$plugins = array();

/** create the plugin object */
$plugins[0] = $modx->newObject('modPlugin');
$plugins[0]->set('name','DailyPhoto');
$plugins[0]->set('description','The plugin that shows a random photo from unsplash on the login screen every day.');
$plugins[0]->set('plugincode', getSnippetContent($sources['plugins'] . 'daily_photo.plugin.php'));

$events = include $sources['data'].'transport.plugin.events.php';
if (is_array($events) && !empty($events)) {
    $plugins[0]->addMany($events);
    $modx->log(xPDO::LOG_LEVEL_INFO,'Packaged in '.count($events).' Plugin Events for DailyPhoto'); flush();
} else {
    $modx->log(xPDO::LOG_LEVEL_ERROR,'Could not find plugin events for DailyPhoto!');
}
unset($events);

return $plugins;
