<?php declare(strict_types=1);

use Shopware\Core\TestBootstrapper;

$loader = (new TestBootstrapper())
    ->addCallingPlugin()
    ->addActivePlugins('MiniwaldExplorer')
    ->setForceInstallPlugins(true)
//    needed for database schema update
    ->setForceInstall(true)
    ->bootstrap()
    ->getClassLoader();

$loader->addPsr4('InReNa\\Tests\\', __DIR__);
