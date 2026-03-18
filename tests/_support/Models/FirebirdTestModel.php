<?php

namespace Dgvirtual\CI4Firebird\Tests\Support\Models;

use CodeIgniter\Model;

/**
 * Simple CI4 Model targeting the local Firebird test DB (TEST_CATS table).
 * Used by FirebirdDriverTest to exercise Model-level CRUD.
 */
class FirebirdTestModel extends Model
{
    protected $DBGroup          = 'firebird_test';
    protected $table            = 'TEST_CATS';
    protected $primaryKey       = 'ID';
    protected $useAutoIncrement = false;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $allowedFields    = ['ID', 'NAME'];
}
