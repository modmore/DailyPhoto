<?php

use MODX\Revolution\Transport\modPackageBuilder;

/**
 * @param string $filename The name of the file.
 * @return string The file's content
 * @by splittingred
 */
function getSnippetContent($filename = '') {
    $o = file_get_contents($filename);
    $o = str_replace('<?php','',$o);
    $o = str_replace('?>','',$o);
    $o = trim($o);
    return $o;
}

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

if (!defined('MOREPROVIDER_BUILD')) {
    /* define version */
    define('PKG_NAME','DailyPhoto');
    define('PKG_NAMESPACE','daily_photo');
    define('PKG_VERSION','1.0.0');
    define('PKG_RELEASE','pl');

    /* load modx */
    require_once dirname(dirname(__FILE__)) . '/config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
    $modx= new modX();
    $modx->initialize('mgr');
    $modx->setLogLevel(modX::LOG_LEVEL_INFO);
    $modx->setLogTarget('ECHO');


    echo '<pre>';
    flush();
    $targetDirectory = dirname(dirname(__FILE__)) . '/_packages/';
}
else {
    $targetDirectory = MOREPROVIDER_BUILD_TARGET;
}

$root = dirname(dirname(__FILE__)).'/';
$sources= array (
    'root' => $root,
    'build' => $root .'_build/',
    'events' => $root . '_build/events/',
    'resolvers' => $root . '_build/resolvers/',
    'validators' => $root . '_build/validators/',
    'data' => $root . '_build/data/',
    'plugins' => $root . 'core/components/'.PKG_NAMESPACE.'/elements/plugins/',
    'snippets' => $root.'_build/elements/snippets/',
    'source_core' => $root.'core/components/'.PKG_NAMESPACE,
    'source_assets' => $root.'assets/components/'.PKG_NAMESPACE,
    'lexicon' => $root . 'core/components/'.PKG_NAMESPACE.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_NAMESPACE.'/docs/',
    'model' => $root.'core/components/'.PKG_NAMESPACE.'/model/',
);
unset($root);

$builder = new modPackageBuilder($modx);
$builder->directory = $targetDirectory;
$builder->createPackage(PKG_NAMESPACE,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAMESPACE,false,true,'{core_path}components/'.PKG_NAMESPACE.'/', '{assets_path}components/'.PKG_NAMESPACE.'/');


/* Settings */
$settings = include_once $sources['data'].'transport.settings.php';
$attributes= array(
    xPDOTransport::UNIQUE_KEY => 'key',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => false,
);
if (is_array($settings)) {
    foreach ($settings as $setting) {
        $vehicle = $builder->createVehicle($setting,$attributes);
        $builder->putVehicle($vehicle);
    }
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($settings).' system settings.'); flush();
    unset($settings,$setting,$attributes);
}

/** @var $category modCategory */
$category = $modx->newObject('modCategory');
$category->set('category',PKG_NAME);
/* add plugins */
$plugins = include $sources['data'].'transport.plugins.php';
if (is_array($plugins)) {
    $category->addMany($plugins,'Plugins');
    $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($plugins).' plugins.'); flush();
}
else {
    $modx->log(modX::LOG_LEVEL_FATAL,'Adding plugins failed.');
}
unset($plugins);

/* create category vehicle */
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'Plugins' => array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
                'PluginEvents' => array(
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => false,
                    xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
                ),
            ),
        ),
    ),
);


$vehicle = $builder->createVehicle($category,$attr);
// Add the validator to check server requirements
$vehicle->validate('php', array('source' => $sources['validators'] . 'requirements.script.php'));
// Add resolvers
$vehicle->resolve('file',array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
//$vehicle->resolve('file',array(
//    'source' => $sources['source_assets'],
//    'target' => "return MODX_ASSETS_PATH . 'components/';",
//));

$modx->log(modX::LOG_LEVEL_INFO,'Packaged in resolvers.'); flush();
$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
    'license' => file_get_contents($sources['docs'] . 'license.txt'),
    'readme' => file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => file_get_contents($sources['docs'] . 'changelog.txt'),
//    'setup-options' => array(
//        'source' => $sources['build'].'setup.options.php',
//    ),
));
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in package attributes.'); flush();

$modx->log(modX::LOG_LEVEL_INFO,'Packing...'); flush();
$builder->pack();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");

