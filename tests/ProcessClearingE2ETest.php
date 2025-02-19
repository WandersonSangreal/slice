<?php

require_once __DIR__ . '/../src/Config/bootstrap.php';

use App\Database\PostgresDatabase;
use App\Migrations\MigrateTables;
use App\Processors\ProcessJsonClearing;
use App\Services\InsertService;
use App\Services\TransactionService;
use PHPUnit\Framework\TestCase;

class ProcessClearingE2ETest extends TestCase
{

	private static $connection;

	public static function setUpBeforeClass(): void
	{
		$db = new PostgresDatabase($_ENV['DB_HOST_TEST'], $_ENV['DB_NAME_TEST'], $_ENV['DB_USER_TEST'], $_ENV['DB_PASS_TEST']);

		$schema = $db->getSchema();
		self::$connection = $db->getConnection();

		$migrations = new MigrateTables();

		if ($migrations->check($schema)) {

			$migrations->truncate(self::$connection);

		} else {

			$migrations->migrate($schema, self::$connection);

		}
	}

	public function test_should_clearing_import_file()
	{

		$transactionService = new TransactionService(self::$connection);
		$transactions = $transactionService->get();

		$pathFolder = realpath(__DIR__ . '/../' . $_ENV['CLEARING_PATH']);

		$files = glob("$pathFolder/*.json");

		$insertService = new InsertService(self::$connection, "clearings");

		$processClearing = new ProcessJsonClearing(
			$pathFolder, $insertService, $transactions,
			['bytes' => intval($_ENV['STREAM_BYTES'])]
		);

		$processClearing->processFiles();

		$results = glob("$pathFolder/processed/*.json");

		$this->assertEquals(array_map(fn($item) => basename($item), $results), array_map(fn($item) => basename($item), $files));

	}

	public function test_should_imported_all_clearigns()
	{
		$totalRows = self::$connection->table('clearings')->count('id');

		$this->assertEquals($totalRows, 290404);

	}

}
