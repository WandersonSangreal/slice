<?php

require_once __DIR__ . '/src/Config/bootstrap.php';

use App\Database\PostgresDatabase;
use App\Migrations\MigrateTables;
use App\Services\InsertService;
use App\Services\TransactionService;

if (class_exists('Swoole\Process')) {

	define('L_BRACKETS', '[');
	define('R_BRACKETS', ']');
	define('COMMA', ',');

	function processFiles($processDir)
	{

		$results = [];
		$files = glob("{$processDir}/*.json");

		if (empty($files)) {

			echo "no files to process" . PHP_EOL . PHP_EOL;

			return $results;

		}

		$sliced = array_chunk($files, ((int)$_ENV['PROCESS_FILE_LIMIT'] ?? 1));

		foreach ($sliced as $chunck) {

			foreach ($chunck as $file) {

				$processing = new \Swoole\Process(function () use ($file, $processDir) {

					processFile($file, $processDir);

				});

				$processing->start();

			}

			\Swoole\Process::wait(true);

		}

		die();

	}

	function processFile(string $file, string $processDir): bool
	{

		if (!file_exists($file)) {

			echo "error: file not found: $file" . PHP_EOL;

			return false;

		}

		$error = false;
		$bytes = intval($_ENV['STREAM_BYTES']);

		if ($stream = fopen($file, 'r')) {

			# CONTEXT
			$db = new PostgresDatabase($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

			$connection = $db->getConnection();
			$insertService = new InsertService($connection, "clearings");

			$transactionService = new TransactionService($connection);
			$transactions = $transactionService->get();

			$buffer = '';

			while (!feof($stream)) {

				$chunk = fread($stream, $bytes * 1024);

				$data = explode(PHP_EOL, $buffer . ltrim($chunk, L_BRACKETS));
				$buffer = rtrim(preg_replace('!\s+!', '', array_pop($data)), R_BRACKETS);
				$formatted = L_BRACKETS . rtrim(preg_replace('!\s+!', '', implode(PHP_EOL, $data)), COMMA) . R_BRACKETS;

				try {

					$processed = json_decode($formatted, true);

					if (!$processed) {
						echo $formatted;
					}

					$processed = classify($processed, $transactions);

					echo "streaming [" . sizeof($processed) . "] lines from: " . basename($file) . PHP_EOL;

					$insertService->insert($processed);

				} catch (Exception $e) {

					$buffer = '';
					$error = true;

					echo "error: processing file: " . $e->getMessage() . PHP_EOL;

					break;

				}

			}

			if ($buffer) {

				try {

					$processed = json_decode($buffer, true);

					$processed = classify($processed, $transactions);

					echo "streaming " . sizeof($processed) . ' lines from: ' . basename($file) . PHP_EOL;

					$insertService->insert($processed);

				} catch (Exception $e) {

					$error = true;

					echo "error: processing file: " . $e->getMessage() . PHP_EOL;

				}

			}

			fclose($stream);

			echo PHP_EOL;

			if (!$error) {

				echo "success: proccessed file: " . basename($file) . PHP_EOL . PHP_EOL;

				is_dir("{$processDir}/processed/") || mkdir("{$processDir}/processed/");

				rename($file, "{$processDir}/processed/" . basename($file));

				array_push($results, basename($file));

				return true;

			}

		}

		echo "error: proccessing file: $file" . PHP_EOL . PHP_EOL;

		is_dir("{$processDir}/failed/") || mkdir("{$processDir}/failed/");

		rename($file, "{$processDir}/failed/" . basename($file));

		return false;

	}

	function classify(array $values, array $transactions): array
	{

		return array_map(function ($item) use ($transactions) {

			if ($item['slice_code'] === '') {
				return [...$item, 'transaction_id' => $transactions['UNKNOWN']->id];
			}

			if ($item['clearing_action_code'] === '11' &&
				$item['operation_code'] === '' &&
				$item['clearing_cancel'] === 1 &&
				$item['clearing_interchange_fee_sign'] === 'D') {
				return [...$item, 'transaction_id' => $transactions['REVERSO DE COMPRA']->id];
			}

			if ($item['operation_type'] === 1 && $item['operation_code'] === '') {
				return [...$item, 'transaction_id' => $transactions['REVERSO DE SAQUE']->id];
			}

			if (in_array($item['operation_type'], [0, 1]) && $item['operation_code'] === '02') {
				return [
					...$item,
					'transaction_id' =>
						($item['clearing_debit'] ? $transactions['SAQUE']->id : $transactions['REVERSO DE SAQUE']->id)
				];
			}

			if ($item['operation_code'] === '01' && $item['reason_code'] < '2000') {
				return [
					...$item,
					'transaction_id' =>
						($item['clearing_debit'] === 0 ? $transactions['REVERSO DE COMPRA']->id : $transactions['COMPRA']->id)
				];
			}

			return [...$item, 'transaction_id' => $transactions['UNKNOWN-99']->id];

		}, $values);

	}


	$db = new PostgresDatabase($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);

	$schema = $db->getSchema();
	$connection = $db->getConnection();

	$migrations = new MigrateTables();

	if ($migrations->check($schema)) {

		$migrations->truncate($connection);

	} else {

		$migrations->migrate($schema, $connection);

	}

	processFiles(__DIR__ . $_ENV['CLEARING_PATH']);

}
