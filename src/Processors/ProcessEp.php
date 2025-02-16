<?php

namespace App\Processors;

use App\Services\InsertService;

class ProcessEp
{
	private string $processDir;
	private array $filters = [];
	private array $usdPatterns = [];
	private array $brlPatterns = [];
	private InsertService $insertService;

	public function __construct(string $processDir, InsertService $insertService, array $transactions, array $config)
	{

		$this->processDir = $processDir;
		$this->insertService = $insertService;

		$this->filters = array_key_exists('filters', $config) ? $config['filters'] : $this->filters;
		$this->usdPatterns = array_key_exists('usdPatterns', $config) ? $config['usdPatterns'] : $this->usdPatterns;
		$this->brlPatterns = array_key_exists('brlPatterns', $config) ? $config['brlPatterns'] : $this->brlPatterns;

	}

	public function processFiles()
	{

		$files = glob("{$this->processDir}/*.txt");

		foreach ($files as $file) {

			echo "processing file: " . basename($file) . PHP_EOL;

			$success = $this->processFile($file);

			if ($success) {

				rename($file, "{$this->processDir}/processed/" . basename($file));

			} else {

				rename($file, "{$this->processDir}/failed/" . basename($file));

			}

		}

	}

	private function processFile(string $file): bool
	{

		if (!file_exists($file)) {

			echo "error: file not found: $file" . PHP_EOL;

			return false;

		}

		if ($contents = file_get_contents($file)) {

			$filteredBRL = $this->filterFile($contents, 'BRL');
			$filteredUSD = $this->filterFile($contents, 'USD');

			$valuesBRL = $this->matchValues($filteredBRL, $this->brlPatterns);
			$valuesUSD = $this->matchValues($filteredUSD, $this->usdPatterns);

			return true;

		}

		echo "error: opening file: $file" . PHP_EOL;

		return false;

	}

	private function filterFile(string $contents, string $currency): string
	{

		$pages = explode("\f", $contents);

		$filtered = array_filter($pages, fn($e) => str_contains($e, $currency));

		foreach ($this->filters as $word) {

			$filtered = array_filter($filtered, fn($e) => !str_contains($e, $word));

		}

		return implode("\n", $filtered);

	}

	private function matchValues(string $contents, array $patterns)
	{

		return array_map(function ($classification, $pattern) use ($contents) {

			list($report, $keyword, $limiter, $position) = array_pad(explode('|', $pattern), 4, null);

			$pattern = '/' . ($report ? (preg_quote($report, '/') . '.*?') : '') .
				preg_quote($keyword, '/') . '.*?\s(\S+' . preg_quote($limiter) . ')/';

			if ($position === '_SECOND_') {

				$pattern = '/' . ($report ? (preg_quote($report, '/') . '.*?') : '') .
					preg_quote($keyword, '/') . '.*?[\d,]+\.\d+DB\s+(\S+' . preg_quote($limiter) . ')/';

			}

			if (preg_match($pattern, $contents, $matches)) {

				return [$classification => trim($matches[1])];

			}

			return [$classification => null];

		}, $patterns, array_keys($patterns));

	}

}
