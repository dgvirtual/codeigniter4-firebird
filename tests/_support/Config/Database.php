<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database config for the standalone test suite.
 *
 * Default credentials match the Docker one-liner in the README.
 * Override them without touching this file by copying phpunit.xml.dist
 * to phpunit.xml and adjusting the <env> entries there.
 */
class Database extends Config
{
    public string $defaultGroup = 'firebird_test';

    public array $firebird_test = [];

    public function __construct()
    {
        parent::__construct();

        $this->firebird_test = [
            'DSN'      => getenv('FIREBIRD_DSN') ?: '',
            'hostname' => getenv('FIREBIRD_HOST') ?: 'localhost',
            'port'     => getenv('FIREBIRD_PORT') ?: '3050',
            'username' => getenv('FIREBIRD_USER') ?: 'SYSDBA',
            'password' => getenv('FIREBIRD_PASSWORD') ?: 'masterkey',
            'database' => getenv('FIREBIRD_DATABASE') ?: '/firebird/data/test.fdb',
            'DBDriver' => 'Dgvirtual\CI4Firebird',
            'DBPrefix' => '',
            'pConnect' => false,
            'DBDebug'  => true,
            'charset'  => 'UTF8',
            'DBCollat' => '',
            'swapPre'  => '',
        ];
    }
}
