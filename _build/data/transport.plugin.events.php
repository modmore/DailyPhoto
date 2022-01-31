<?php
$events = array();

$events['OnManagerLoginFormRender'] = $modx->newObject('modPluginEvent');
$events['OnManagerLoginFormRender']->fromArray(array(
    'event' => 'OnManagerLoginFormRender',
    'priority' => 0,
    'propertyset' => 0
),'',true,true);

$events['OnManagerLoginFormPrerender'] = $modx->newObject('modPluginEvent');
$events['OnManagerLoginFormPrerender']->fromArray(array(
    'event' => 'OnManagerLoginFormPrerender',
    'priority' => 0,
    'propertyset' => 0
),'',true,true);

$events['OnSiteRefresh'] = $modx->newObject('modPluginEvent');
$events['OnSiteRefresh']->fromArray(array(
    'event' => 'OnSiteRefresh',
    'priority' => 0,
    'propertyset' => 0
),'',true,true);

return $events;
