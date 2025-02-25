<?php

namespace App\Processors;

use App\Services\InsertService;

abstract class AbstractProcess
{

	protected string $ext;
	protected string $processDir;
	protected array $transactions;
	protected InsertService $insertService;

	public function __construct(string $processDir, InsertService $insertService, array $transactions, array $config)
	{

		$this->processDir = $processDir;
		$this->insertService = $insertService;
		$this->transactions = $transactions;

		$props = get_object_vars($this);

		foreach (array_keys($config) as $key) {

			if (in_array($key, array_keys($props))) {
				$this->{$key} = $config[$key];
			}

		}

	}

	public function processFiles(): array
	{

		$results = [];
		$files = glob("{$this->processDir}/*.{$this->ext}");

		if (empty($files)) {

			echo "no files to process" . PHP_EOL . PHP_EOL;

			return $results;

		}

		foreach ($files as $file) {

			echo "processing file: " . basename($file) . PHP_EOL . PHP_EOL;

			$success = $this->processFile($file);

			if ($success) {

				is_dir("{$this->processDir}/processed/") || mkdir("{$this->processDir}/processed/");

				rename($file, "{$this->processDir}/processed/" . basename($file));

				array_push($results, basename($file));

			} else {

				is_dir("{$this->processDir}/failed/") || mkdir("{$this->processDir}/failed/");

				rename($file, "{$this->processDir}/failed/" . basename($file));

			}

		}

		return $results;

	}

	protected abstract function processFile(string $file): bool;

}
