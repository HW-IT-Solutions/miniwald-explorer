<?php declare(strict_types=1);

/**
 * Lightweight bootstrap for unit tests that don't require the Shopware kernel.
 * This avoids the overhead of booting the full application for pure unit tests.
 */

// Use Composer's autoloader directly
$autoloadFile = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoloadFile)) {
    // Fall back to the project root autoloader
    $autoloadFile = dirname(__DIR__, 4) . '/vendor/autoload.php';
}

if (!file_exists($autoloadFile)) {
    throw new \RuntimeException('Could not find autoload.php. Please run "composer install".');
}

$loader = require $autoloadFile;

// Register a custom autoloader for the InReNa\Tests\ namespace.
// We cannot use $loader->addPsr4() because the existing InReNa\ prefix
// in the Composer classmap takes precedence and looks in src/Tests/... which doesn't exist.
spl_autoload_register(static function (string $class) {
    $prefix = 'InReNa\\Tests\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
    }
}, true, true); // prepend=true to check before Composer's autoloader
