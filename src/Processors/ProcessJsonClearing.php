<?php

namespace App\Processors;

use Exception;

class ProcessJsonClearing extends AbstractProcess
{

	protected int $bytes = 4096;
	protected string $ext = 'json';

	protected function processFile(string $file): bool
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

				$data = explode(PHP_EOL, $buffer . ltrim($chunk, '['));
				$buffer = rtrim(preg_replace('!\s+!', '', array_pop($data)), ']');
				$formatted = '[' . rtrim(preg_replace('!\s+!', '', implode(PHP_EOL, $data)), ',') . ']';

				try {

					$processed = json_decode($formatted, true);

					if (!$processed) {
						echo $formatted;
					}

					$processed = $this->classify($processed);

					echo "streaming [" . sizeof($processed) . "] lines from: " . basename($file) . PHP_EOL;

					$this->insertService->insert($processed);

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

					$processed = $this->classify($processed);

					echo "streaming " . sizeof($processed) . ' lines from: ' . basename($file) . PHP_EOL;

					$this->insertService->insert($processed);

				} catch (Exception $e) {

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

	private function classify(array $values): array
	{

		return array_map(function ($item) {

			if ($item['slice_code'] === '') {
				return [...$item, 'transaction_id' => $this->transactions['UNKNOWN']->id];
			}

			if ($item['clearing_action_code'] === '11' &&
				$item['operation_code'] === '' &&
				$item['clearing_cancel'] === 1 &&
				$item['clearing_interchange_fee_sign'] === 'D') {
				return [...$item, 'transaction_id' => $this->transactions['REVERSO DE COMPRA']->id];
			}

			if ($item['operation_type'] === 1 && $item['operation_code'] === '') {
				return [...$item, 'transaction_id' => $this->transactions['REVERSO DE SAQUE']->id];
			}

			if (in_array($item['operation_type'], [0, 1]) && $item['operation_code'] === '02') {
				return [
					...$item,
					'transaction_id' =>
						($item['clearing_debit'] ? $this->transactions['SAQUE']->id : $this->transactions['REVERSO DE SAQUE']->id)
				];
			}

			if ($item['operation_code'] === '01' && $item['reason_code'] < '2000') {
				return [
					...$item,
					'transaction_id' =>
						($item['clearing_debit'] === 0 ? $this->transactions['REVERSO DE COMPRA']->id : $this->transactions['COMPRA']->id)
				];
			}

			return [...$item, 'transaction_id' => $this->transactions['UNKNOWN-99']->id];

		}, $values);

	}

}
