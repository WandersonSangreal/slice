<?php

namespace App\Processors;

class ProcessEp extends AbstractProcess
{

	protected string $ext = 'txt';

	protected array $filters = [];
	protected array $usdPatterns = [];
	protected array $brlPatterns = [];

	protected function processFile(string $file): bool
	{

		if (!file_exists($file)) {

			echo "error: file not found: $file" . PHP_EOL;

			return false;

		}

		if ($contents = file_get_contents($file)) {

			$filteredBRL = $this->filterFile($contents, 'BRL');
			$filteredUSD = $this->filterFile($contents, 'USD');

			$valuesBRL = $this->matchValues($filteredBRL, $this->brlPatterns, 'BRL');
			$valuesUSD = $this->matchValues($filteredUSD, $this->usdPatterns, 'USD');

			try {

				$this->insertService->insert(array_merge($valuesBRL, $valuesUSD));

				echo "success: proccessed file: " . basename($file) . PHP_EOL . PHP_EOL;

				return true;

			} catch (\Exception $e) {

				echo "error: processing file: " . $e->getMessage() . PHP_EOL;

				return false;

			}

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

		return preg_replace('!\s+!', ' ', implode("\n", $filtered));

	}

	private function matchValues(string $contents, array $patterns, string $currency)
	{

		return array_map(function ($classification, $pattern) use ($contents, $currency) {

			list($report, $keyword, $limiter, $control) = array_pad(explode('|', $pattern), 4, null);

			$type = null;

			$pattern = '/' . ($report ? (preg_quote($report, '/') . '.*?') : '') .
				preg_quote($keyword, '/') . '.*?\s(\S+' . preg_quote($limiter) . ')/';

			if ($control === '_SECOND_') {

				$pattern = '/' . ($report ? (preg_quote($report, '/') . '.*?') : '') .
					preg_quote($keyword, '/') . '.*?[\d,]+\.\d+DB\s+(\S+' . preg_quote($limiter) . ')/';

			}

			if ($control === '_CR_' || $control === '_DB_') {

				$type = str_replace('_', '', $control);

			}

			if (preg_match($pattern, $contents, $matches)) {

				return [
					'value' => preg_replace('/[^\d.]/', '', trim($matches[1])),
					'type_value' => $type ?? preg_replace('/[^a-zA-Z]/', '', trim($matches[1])),
					'currency' => $currency,
					'transaction_id' => $this->transactions[$classification]->id,
					'date' => date('Y-m-d')
				];

			}

			return [
				'value' => null,
				'type_value' => $type ?? null,
				'currency' => $currency,
				'transaction_id' => $this->transactions[$classification]->id,
				'date' => date('Y-m-d')
			];

		}, $patterns, array_keys($patterns));

	}

}
