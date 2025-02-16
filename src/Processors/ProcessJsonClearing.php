<?php

namespace App\Processors;

use App\Services\InsertService;

define('L_BRACKETS', '[');
define('R_BRACKETS', ']');
define('COMMA', ',');

class ProcessJsonClearing
{

	private int $bytes = 4096;
	private string $processDir;
	private InsertService $insertService;

	public function __construct(string $processDir, InsertService $insertService, array $config)
	{

		$this->processDir = $processDir;
		$this->insertService = $insertService;
		$this->bytes = array_key_exists('bytes', $config) ? $config['bytes'] : $this->bytes;

	}

	public function processFiles()
	{

		$files = glob("{$this->processDir}/*.json");

		foreach ($files as $file) {

			echo "processing file: " . basename($file) . PHP_EOL . PHP_EOL;

			$success = $this->processFile($file);

			if ($success) {

				is_dir("{$this->processDir}/processed/") || mkdir("{$this->processDir}/processed/");

				rename($file, "{$this->processDir}/processed/" . basename($file));

			} else {

				is_dir("{$this->processDir}/failed/") || mkdir("{$this->processDir}/failed/");

				# rename($file, "{$this->processDir}/failed/" . basename($file));

			}

		}

	}

	private function processFile(string $file): bool
	{

		if (!file_exists($file)) {

			echo "error: file not found: $file" . PHP_EOL;

			return false;

		}

		$error = false;

		if ($stream = fopen($file, 'r')) {

			$buffer = '';

			while (!feof($stream)) {

				$chunk = fread($stream, $this->bytes * 1024);

				$data = explode(PHP_EOL, $buffer . ltrim($chunk, L_BRACKETS));
				$buffer = rtrim(array_pop($data), R_BRACKETS);
				$formatted = L_BRACKETS . rtrim(implode(PHP_EOL, $data), COMMA) . R_BRACKETS;

				try {

					$processed = json_decode($formatted, true);

					echo "streaming " . sizeof($processed) . ' lines from: ' . basename($file) . PHP_EOL;

					$this->insertService->insert($processed);

				} catch (\Exception $e) {

					$buffer = '';
					$error = true;

					echo "error: processing file: " . $e->getMessage() . PHP_EOL;

					break;

				}

			}

			if ($buffer) {

				try {

					$processed = json_decode($buffer, true);

					echo "streaming " . sizeof($processed) . ' lines from: ' . basename($file) . PHP_EOL;

					$this->insertService->insert($processed);

				} catch (\Exception $e) {

					$error = true;

					echo "error: processing file: " . $e->getMessage() . PHP_EOL;

				}

			}

			fclose($stream);

			echo PHP_EOL;

			if (!$error) {

				echo "success: proccessed file: " . basename($file) . PHP_EOL . PHP_EOL;

				return true;

			}

		}

		echo "error: proccessing file: $file" . PHP_EOL . PHP_EOL;

		return false;

	}

}
