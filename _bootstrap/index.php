<?php
/* Get the core config */
if (!file_exists(dirname(dirname(__FILE__)).'/config.core.php')) {
    die('ERROR: missing '.dirname(dirname(__FILE__)).'/config.core.php file defining the MODX core path.');
}

echo "<pre>";
/* Boot up MODX */
echo "Loading modX...\n";
require_once dirname(dirname(__FILE__)).'/config.core.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modX();
echo "Initializing manager...\n";
$modx->initialize('mgr');
$modx->getService('error','error.modError', '', '');

$componentPath = dirname(dirname(__FILE__));

/* Namespace */
if (!createObject('modNamespace',array(
    'name' => 'daily_photo',
    'path' => $componentPath.'/core/components/daily_photo/',
    'assets_path' => $componentPath.'/assets/components/daily_photo/',
),'name', false)) {
    echo "Error creating namespace daily_photo.\n";
}

/* Path settings */
if (!createObject('modSystemSetting', array(
    'key' => 'daily_photo.core_path',
    'value' => $componentPath.'/core/components/daily_photo/',
    'xtype' => 'textfield',
    'namespace' => 'daily_photo',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating daily_photo.core_path setting.\n";
}

if (!createObject('modSystemSetting', array(
    'key' => 'daily_photo.assets_path',
    'value' => $componentPath.'/assets/components/daily_photo/',
    'xtype' => 'textfield',
    'namespace' => 'daily_photo',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating daily_photo.assets_path setting.\n";
}

/* Fetch assets url */
$url = 'http';
if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on')) {
    $url .= 's';
}
$url .= '://'.$_SERVER["SERVER_NAME"];
if ($_SERVER['SERVER_PORT'] != '80') {
    $url .= ':'.$_SERVER['SERVER_PORT'];
}
$requestUri = $_SERVER['REQUEST_URI'];
$bootstrapPos = strpos($requestUri, '_bootstrap/');
$requestUri = rtrim(substr($requestUri, 0, $bootstrapPos), '/').'/';
$assetsUrl = "{$url}{$requestUri}assets/components/daily_photo/";

if (!createObject('modSystemSetting', array(
    'key' => 'daily_photo.assets_url',
    'value' => $assetsUrl,
    'xtype' => 'textfield',
    'namespace' => 'daily_photo',
    'area' => 'Paths',
    'editedon' => time(),
), 'key', false)) {
    echo "Error creating daily_photo.assets_url setting.\n";
}

if (!createObject('modPlugin', array(
    'name' => 'DailyPhoto',
    'static' => true,
    'static_file' => $componentPath.'/core/components/daily_photo/elements/plugins/daily_photo.plugin.php',
), 'name', true)) {
    echo "Error creating DailyPhoto Plugin.\n";
}
$vcPlugin = $modx->getObject('modPlugin', array('name' => 'DailyPhoto'));
if ($vcPlugin) {
    if (!createObject('modPluginEvent', array(
        'pluginid' => $vcPlugin->get('id'),
        'event' => 'OnManagerLoginFormRender',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent.\n";
    }
    if (!createObject('modPluginEvent', array(
        'pluginid' => $vcPlugin->get('id'),
        'event' => 'OnManagerLoginFormPrerender',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent.\n";
    }
    if (!createObject('modPluginEvent', array(
        'pluginid' => $vcPlugin->get('id'),
        'event' => 'OnSiteRefresh',
        'priority' => 0,
    ), array('pluginid','event'), false)) {
        echo "Error creating modPluginEvent.\n";
    }
}

$settings = include dirname(dirname(__FILE__)).'/_build/data/settings.php';
foreach ($settings as $key => $opts) {
    if (!createObject('modSystemSetting', array(
        'key' => 'daily_photo.' . $key,
        'value' => $opts['value'],
        'xtype' => (isset($opts['xtype'])) ? $opts['xtype'] : 'textfield',
        'namespace' => 'daily_photo',
        'area' => $opts['area'],
        'editedon' => time(),
    ), 'key', false)) {
        echo "Error creating daily_photo.".$key." setting.\n";
    }
}

echo "Done.";

// Refresh the cache
$modx->cacheManager->refresh();


/**
 * Creates an object.
 *
 * @param string $className
 * @param array $data
 * @param string $primaryField
 * @param bool $update
 * @return bool
 */
function createObject ($className = '', array $data = array(), $primaryField = '', $update = true) {
    global $modx;
    /* @var xPDOObject $object */
    $object = null;

    /* Attempt to get the existing object */
    if (!empty($primaryField)) {
        if (is_array($primaryField)) {
            $condition = array();
            foreach ($primaryField as $key) {
                $condition[$key] = $data[$key];
            }
        }
        else {
            $condition = array($primaryField => $data[$primaryField]);
        }
        $object = $modx->getObject($className, $condition);
        if ($object instanceof $className) {
            if ($update) {
                $object->fromArray($data);
                return $object->save();
            } else {
                $condition = $modx->toJSON($condition);
                echo "Skipping {$className} {$condition}: already exists.\n";
                return true;
            }
        }
    }

    /* Create new object if it doesn't exist */
    if (!$object) {
        $object = $modx->newObject($className);
        $object->fromArray($data, '', true);
        return $object->save();
    }

    return false;
}
