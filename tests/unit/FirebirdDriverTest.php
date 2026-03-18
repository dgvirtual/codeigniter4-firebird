<?php

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Integration tests for the App\Database\Firebird PDO driver.
 *
 * Requires the `firebird_test` Docker service to be running.
 * If the connection cannot be established the whole suite is skipped.
 *
 * Run with:
 *   vendor/bin/phpunit tests/unit/FirebirdDriverTest.php
 *
 * @internal
 */
final class FirebirdDriverTest extends CIUnitTestCase
{
    /** @var \CodeIgniter\Database\BaseConnection */
    private static $fbdb;

    /** @var \CodeIgniter\Database\Forge */
    private static $fbforge;

    // =========================================================================
    // Suite setup / teardown
    // =========================================================================

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        try {
            self::$fbdb    = db_connect('firebird_test');
            // Force actual connection — getVersion() triggers initialize().
            // Throws if pdo_firebird is not loaded or server unreachable.
            self::$fbdb->getVersion();
            self::$fbforge = \Config\Database::forge('firebird_test');
        } catch (\Throwable $e) {
            // Ensure setUp() sees null and marks every test as skipped.
            self::$fbdb = null;
            return;
        }

        // Clean slate
        self::dropTablesStatic(silent: true);

        // CREATE TEST_CATS
        self::$fbforge->addField([
            'ID'   => ['type' => 'INTEGER', 'null' => false],
            'NAME' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
        ]);
        self::$fbforge->addPrimaryKey('ID');
        self::$fbforge->createTable('TEST_CATS');

        // CREATE TEST_ITEMS
        self::$fbforge->addField([
            'ID'     => ['type' => 'INTEGER', 'null' => false],
            'CAT_ID' => ['type' => 'INTEGER', 'null' => true],
            'NAME'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'PRICE'  => ['type' => 'DECIMAL', 'constraint' => '10,2', 'null' => true],
            'QTY'    => ['type' => 'INTEGER', 'null' => true, 'default' => 0],
            'ACTIVE' => ['type' => 'SMALLINT', 'null' => true, 'default' => 1],
        ]);
        self::$fbforge->addPrimaryKey('ID');
        self::$fbforge->createTable('TEST_ITEMS');

        // Seed data
        self::$fbdb->table('TEST_CATS')->insert(['ID' => 1, 'NAME' => 'Electronics']);
        self::$fbdb->table('TEST_CATS')->insertBatch([
            ['ID' => 2, 'NAME' => 'Clothing'],
            ['ID' => 3, 'NAME' => 'Books'],
        ]);
        self::$fbdb->table('TEST_ITEMS')->insertBatch([
            ['ID' => 1, 'CAT_ID' => 1, 'NAME' => 'Laptop',   'PRICE' => 999.99, 'QTY' => 5,  'ACTIVE' => 1],
            ['ID' => 2, 'CAT_ID' => 1, 'NAME' => 'Phone',    'PRICE' => 499.00, 'QTY' => 10, 'ACTIVE' => 1],
            ['ID' => 3, 'CAT_ID' => 2, 'NAME' => 'T-Shirt',  'PRICE' => 19.99,  'QTY' => 50, 'ACTIVE' => 1],
            ['ID' => 4, 'CAT_ID' => 3, 'NAME' => 'CI4 Book', 'PRICE' => 39.99,  'QTY' => 20, 'ACTIVE' => 1],
            ['ID' => 5, 'CAT_ID' => 1, 'NAME' => 'Tablet',   'PRICE' => 299.00, 'QTY' => 0,  'ACTIVE' => 0],
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$fbdb !== null) {
            self::dropTablesStatic(silent: false);
        }
        parent::tearDownAfterClass();
    }

    /** Skip every test if we never got a connection. */
    protected function setUp(): void
    {
        parent::setUp();
        if (self::$fbdb === null) {
            $this->markTestSkipped('firebird_test connection unavailable.');
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private static function dropTablesStatic(bool $silent): void
    {
        try { self::$fbdb->query('COMMIT'); } catch (\Throwable) {}

        foreach (['TEST_ITEMS', 'TEST_CATS'] as $table) {
            try {
                if (self::$fbdb->tableExists($table)) {
                    self::$fbforge->dropTable($table, false);
                }
            } catch (\Throwable $e) {
                if (! $silent) {
                    throw $e;
                }
            }
        }
    }

    // =========================================================================
    // Connection
    // =========================================================================

    public function testConnectionReturnsFirebirdVersion(): void
    {
        $version = self::$fbdb->getVersion();
        $this->assertStringContainsString('Firebird', $version);
    }

    // =========================================================================
    // Forge — DDL
    // =========================================================================

    public function testTablesExistAfterForgeCreate(): void
    {
        $this->assertTrue(self::$fbdb->tableExists('TEST_CATS'));
        $this->assertTrue(self::$fbdb->tableExists('TEST_ITEMS'));
    }

    public function testForgeAddColumn(): void
    {
        // Add NOTES if it doesn't already exist (idempotent across re-runs)
        $cols = self::$fbdb->getFieldNames('TEST_ITEMS');
        if (! in_array('NOTES', $cols, true)) {
            self::$fbforge->addColumn('TEST_ITEMS', [
                'NOTES' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            ]);
        }

        self::$fbdb->table('TEST_ITEMS')->where('ID', 1)->update(['NOTES' => 'Added via forge']);
        $row = self::$fbdb->table('TEST_ITEMS')->where('ID', 1)->get()->getRowArray();

        $this->assertSame('Added via forge', $row['NOTES']);
    }

    // =========================================================================
    // INSERT
    // =========================================================================

    public function testInsertSingle(): void
    {
        $before = self::$fbdb->table('TEST_CATS')->countAll();

        self::$fbdb->table('TEST_CATS')->insert(['ID' => 50, 'NAME' => 'TmpSingle']);
        $after = self::$fbdb->table('TEST_CATS')->countAll();

        $this->assertSame($before + 1, $after);

        // cleanup
        self::$fbdb->table('TEST_CATS')->where('ID', 50)->delete();
    }

    public function testInsertBatch(): void
    {
        $before = self::$fbdb->table('TEST_CATS')->countAll();

        self::$fbdb->table('TEST_CATS')->insertBatch([
            ['ID' => 51, 'NAME' => 'Batch1'],
            ['ID' => 52, 'NAME' => 'Batch2'],
        ]);
        $after = self::$fbdb->table('TEST_CATS')->countAll();

        $this->assertSame($before + 2, $after);

        // cleanup
        self::$fbdb->table('TEST_CATS')->whereIn('ID', [51, 52])->delete();
    }

    // =========================================================================
    // SELECT
    // =========================================================================

    public function testSelectAll(): void
    {
        $rows = self::$fbdb->table('TEST_CATS')->get()->getResultArray();
        $this->assertCount(3, $rows);
    }

    public function testWhereEquals(): void
    {
        $row = self::$fbdb->table('TEST_ITEMS')->where('ID', 1)->get()->getRowArray();
        $this->assertSame('Laptop', $row['NAME']);
    }

    public function testWhereCompound(): void
    {
        // ACTIVE=1 AND PRICE > 100 → Laptop + Phone (Tablet excluded: inactive)
        $rows = self::$fbdb->table('TEST_ITEMS')
            ->where('ACTIVE', 1)
            ->where('PRICE >', 100)
            ->get()->getResultArray();
        $this->assertCount(2, $rows);
    }

    public function testLike(): void
    {
        $rows = self::$fbdb->table('TEST_ITEMS')->like('NAME', 'oo')->get()->getResultArray();
        $this->assertCount(1, $rows);
        $this->assertSame('CI4 Book', $rows[0]['NAME']);
    }

    // =========================================================================
    // ORDER BY / LIMIT / OFFSET
    // =========================================================================

    public function testOrderByDesc(): void
    {
        $rows  = self::$fbdb->table('TEST_ITEMS')->orderBy('PRICE', 'DESC')->get()->getResultArray();
        $this->assertSame('Laptop', $rows[0]['NAME']);
    }

    public function testLimitOffset(): void
    {
        // FIRST 2 SKIP 2, ordered by ID ASC → T-Shirt(3), CI4 Book(4)
        $rows = self::$fbdb->table('TEST_ITEMS')
            ->orderBy('ID', 'ASC')
            ->limit(2, 2)
            ->get()->getResultArray();

        $this->assertCount(2, $rows);
        $this->assertSame('T-Shirt', $rows[0]['NAME']);
    }

    // =========================================================================
    // Aggregates
    // =========================================================================

    public function testCountAll(): void
    {
        $this->assertSame(5, self::$fbdb->table('TEST_ITEMS')->countAll());
    }

    public function testCountAllResults(): void
    {
        $cnt = self::$fbdb->table('TEST_ITEMS')->where('ACTIVE', 1)->countAllResults();
        $this->assertSame(4, $cnt);
    }

    public function testSelectMax(): void
    {
        $row = self::$fbdb->table('TEST_ITEMS')->selectMax('PRICE', 'MAX_PRICE')->get()->getRowArray();
        $this->assertEqualsWithDelta(999.99, (float) $row['MAX_PRICE'], 0.001);
    }

    public function testSelectMin(): void
    {
        $row = self::$fbdb->table('TEST_ITEMS')
            ->where('ACTIVE', 1)->selectMin('PRICE', 'MIN_PRICE')->get()->getRowArray();
        $this->assertEqualsWithDelta(19.99, (float) $row['MIN_PRICE'], 0.001);
    }

    public function testSelectSum(): void
    {
        $row = self::$fbdb->table('TEST_ITEMS')->selectSum('QTY', 'TOTAL')->get()->getRowArray();
        $this->assertSame(85, (int) $row['TOTAL']);
    }

    public function testSelectAvg(): void
    {
        $row = self::$fbdb->table('TEST_ITEMS')
            ->where('ACTIVE', 1)->selectAvg('PRICE', 'AVG_PRICE')->get()->getRowArray();
        $this->assertGreaterThan(0, (float) $row['AVG_PRICE']);
    }

    public function testGroupByHaving(): void
    {
        // Electronics (CAT_ID=1) has 3 items — only group with COUNT > 1
        $rows = self::$fbdb->table('TEST_ITEMS')
            ->select('CAT_ID, COUNT(*) AS ITEM_COUNT')
            ->groupBy('CAT_ID')
            ->having('COUNT(*) >', 1)
            ->get()->getResultArray();

        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]['CAT_ID']);
    }

    // =========================================================================
    // JOINs
    // =========================================================================

    public function testInnerJoin(): void
    {
        $rows = self::$fbdb->table('TEST_ITEMS')
            ->select('TEST_ITEMS.NAME AS ITEM_NAME, TEST_CATS.NAME AS CAT_NAME')
            ->join('TEST_CATS', 'TEST_CATS.ID = TEST_ITEMS.CAT_ID')
            ->where('TEST_ITEMS.ACTIVE', 1)
            ->orderBy('TEST_ITEMS.PRICE', 'DESC')
            ->get()->getResultArray();

        $this->assertCount(4, $rows);
        $this->assertSame('Laptop', $rows[0]['ITEM_NAME']);
    }

    public function testLeftJoin(): void
    {
        $rows = self::$fbdb->table('TEST_ITEMS')
            ->select('TEST_ITEMS.NAME AS ITEM_NAME, TEST_CATS.NAME AS CAT_NAME')
            ->join('TEST_CATS', 'TEST_CATS.ID = TEST_ITEMS.CAT_ID', 'left')
            ->get()->getResultArray();

        $this->assertCount(5, $rows);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function testUpdateSingle(): void
    {
        self::$fbdb->table('TEST_ITEMS')->where('ID', 2)->update(['PRICE' => 450.00]);
        $row = self::$fbdb->table('TEST_ITEMS')->where('ID', 2)->get()->getRowArray();

        $this->assertEqualsWithDelta(450.00, (float) $row['PRICE'], 0.001);

        // restore
        self::$fbdb->table('TEST_ITEMS')->where('ID', 2)->update(['PRICE' => 499.00]);
    }

    public function testUpdateMultipleRows(): void
    {
        // Insert two temp rows with QTY=-1, then bulk-update them
        self::$fbdb->table('TEST_ITEMS')->insertBatch([
            ['ID' => 60, 'CAT_ID' => 1, 'NAME' => 'Tmp1', 'PRICE' => 1, 'QTY' => -1, 'ACTIVE' => 1],
            ['ID' => 61, 'CAT_ID' => 1, 'NAME' => 'Tmp2', 'PRICE' => 1, 'QTY' => -1, 'ACTIVE' => 1],
        ]);

        self::$fbdb->table('TEST_ITEMS')->where('QTY', -1)->update(['ACTIVE' => 0]);

        $cnt = self::$fbdb->table('TEST_ITEMS')->where('QTY', -1)->where('ACTIVE', 0)->countAllResults();
        $this->assertSame(2, $cnt);

        // cleanup
        self::$fbdb->table('TEST_ITEMS')->whereIn('ID', [60, 61])->delete();
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function testDelete(): void
    {
        self::$fbdb->table('TEST_ITEMS')
            ->insert(['ID' => 99, 'CAT_ID' => 1, 'NAME' => 'Temp', 'PRICE' => 1, 'QTY' => 1, 'ACTIVE' => 1]);

        self::$fbdb->table('TEST_ITEMS')->where('ID', 99)->delete();

        $row = self::$fbdb->table('TEST_ITEMS')->where('ID', 99)->get()->getRowArray();
        $this->assertNull($row);
    }

    // =========================================================================
    // Transactions
    // =========================================================================

    public function testTransactionCommit(): void
    {
        self::$fbdb->transStart();
        self::$fbdb->table('TEST_CATS')->insert(['ID' => 70, 'NAME' => 'TxCommit']);
        self::$fbdb->transComplete();

        $row = self::$fbdb->table('TEST_CATS')->where('ID', 70)->get()->getRowArray();
        $this->assertSame('TxCommit', $row['NAME']);

        // cleanup
        self::$fbdb->table('TEST_CATS')->where('ID', 70)->delete();
    }

    public function testTransactionRollback(): void
    {
        self::$fbdb->transBegin();
        self::$fbdb->table('TEST_CATS')->insert(['ID' => 71, 'NAME' => 'TxRollback']);
        self::$fbdb->transRollback();

        $row = self::$fbdb->table('TEST_CATS')->where('ID', 71)->get()->getRowArray();
        $this->assertNull($row);
    }

    // =========================================================================
    // Raw query
    // =========================================================================

    public function testRawQueryWithBinding(): void
    {
        $row  = self::$fbdb->query('SELECT NAME FROM TEST_CATS WHERE ID = ?', [1])->getRowArray();
        $this->assertSame('Electronics', $row['NAME']);
    }

    // =========================================================================
    // CI4 Model layer
    // =========================================================================

    public function testModelFindAll(): void
    {
        $m = new \Dgvirtual\CI4Firebird\Tests\Support\Models\FirebirdTestModel();
        $this->assertGreaterThanOrEqual(3, count($m->findAll()));
    }

    public function testModelFind(): void
    {
        $m   = new \Dgvirtual\CI4Firebird\Tests\Support\Models\FirebirdTestModel();
        $row = $m->find(1);
        $this->assertSame('Electronics', $row->NAME);
    }

    public function testModelWhere(): void
    {
        $m    = new \Dgvirtual\CI4Firebird\Tests\Support\Models\FirebirdTestModel();
        $rows = $m->where('ID >', 1)->findAll();
        $this->assertGreaterThanOrEqual(2, count($rows));
    }

    public function testModelInsertUpdateDelete(): void
    {
        $m = new \Dgvirtual\CI4Firebird\Tests\Support\Models\FirebirdTestModel();

        $m->insert(['ID' => 80, 'NAME' => 'ModelTest']);
        $this->assertSame('ModelTest', $m->find(80)->NAME);

        $m->update(80, ['NAME' => 'ModelUpdated']);
        $this->assertSame('ModelUpdated', $m->find(80)->NAME);

        $m->delete(80);
        $this->assertNull($m->find(80));
    }
}
