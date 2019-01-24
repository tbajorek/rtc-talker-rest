<?php

namespace Test;

use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractDbTest extends TestCase {
    protected static $container = null;
    protected $em;

    public static function setUpBeforeClass()
    {
        if(self::$container === null) {
            self::$container = require dirname(__DIR__) . '/bootstrap.php';
        }
    }

    protected function setUp()
    {
        $this->em = self::$container[EntityManager::class];
    }

    protected function resetDatabase() : void {
        $connection = $this->em->getConnection();
        $schemaManager = $connection->getSchemaManager();
        $tables = $schemaManager->listTables();
        $query = 'SET FOREIGN_KEY_CHECKS = 0;';
        foreach($tables as $table) {
            $query .= 'TRUNCATE ' . $table->getName() . ';';
        }
        $query .= 'SET FOREIGN_KEY_CHECKS = 1;';
        $connection->executeQuery($query);
    }
}