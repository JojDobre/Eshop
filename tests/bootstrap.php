<?php

/*
 * Bootstrap testov: okrem Composer autoloadera zaregistruje triedy všetkých modulov,
 * aby ich mohli používať aj jednotkové testy bez spustenej aplikácie Laravel.
 */

use App\Core\Modules\Module;
use Composer\Autoload\ClassLoader;

/** @var ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

foreach (glob(__DIR__.'/../modules/*/module.json') ?: [] as $manifest) {
    $module = Module::fromFile($manifest);

    if ($module->namespace !== null && is_dir($module->sourcePath())) {
        $loader->addPsr4($module->namespace, $module->sourcePath());
    }
}
