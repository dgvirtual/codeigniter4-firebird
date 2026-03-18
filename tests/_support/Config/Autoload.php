<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
        // Search tests/_support/Config/ first (our Database override lives here),
        // then fall back to the framework's app/Config/ for everything else
        // (Exceptions, Logger, Toolbar, etc.) so we don't need stubs for each.
        'Config' => [
            APPPATH . 'Config',
            __DIR__ . '/../../../vendor/codeigniter4/framework/app/Config',
        ],
    ];

    public $classmap = [];
    public $files     = [];
    public $helpers   = [];
}
