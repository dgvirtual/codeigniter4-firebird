<?php

namespace Config;

/**
 * Paths config for the standalone test suite.
 *
 * - systemDirectory / appDirectory point at the framework's own copies so that
 *   Boot::loadConstants() can find Constants.php, Autoload.php, etc.
 * - writableDirectory / testsDirectory point at the package root.
 * - CONFIGPATH (set in phpunit.xml.dist) points at tests/_support/Config/ so
 *   that our Database override is picked up by CI4's config factory.
 */
class Paths
{
    public string $systemDirectory   = __DIR__ . '/../../../vendor/codeigniter4/framework/system';
    public string $appDirectory      = __DIR__ . '/..';   // tests/_support/
    public string $writableDirectory = __DIR__ . '/../../../vendor/codeigniter4/framework/writable';
    public string $testsDirectory    = __DIR__ . '/../../../tests';
    public string $viewDirectory     = __DIR__ . '/../Views';
    public string $envDirectory      = __DIR__ . '/../../../';
}
