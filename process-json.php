<?php

# ini_set('memory_limit', '4M');
# header('Content-Type: text/plain; charset=utf-8');

class jsondecode_filter extends php_user_filter
{
	public function filter($in, $out, &$consumed, $closing): int
	{
		while ($bucket = stream_bucket_make_writeable($in)) {

			$bucket->data = json_decode($bucket->data, true);

			$consumed += $bucket->datalen;

			stream_bucket_append($out, $bucket);

		}
		return PSFS_PASS_ON;
	}
}

function streamJSONFile($file)
{
	$begin = microtime(true);

	if (!file_exists($file)) {

		return throw new Exception("file not found");

	}

	if ($stream = fopen($file, 'r')) {

		$buffer = '';

		while (!feof($stream)) {

			$chunk = fread($stream, 8 * 1024);

			$data = explode(PHP_EOL, $buffer . ltrim($chunk, '['));
			$buffer = rtrim(array_pop($data), ']');

			# echo rtrim(implode(PHP_EOL, $data), ',');

			echo sizeof(json_decode('[' . rtrim(implode(PHP_EOL, $data), ',') . ']', true));

			# echo $chunk;
			echo PHP_EOL . PHP_EOL;

		}

		echo $buffer;

		echo PHP_EOL . PHP_EOL;

		/*

		# stream_filter_append($stream, 'jsondecode');

		$i = 0;
		$chunck = '';

		while (($lines = stream_get_line($stream, 1024 * 1024, PHP_EOL)) !== false) {

			$chunck .= $lines;

			if (str_ends_with($lines, '},') || str_ends_with($lines, '}')) {

				# echo $i . PHP_EOL;
				# echo trim($chunck, "[,") . PHP_EOL . PHP_EOL;

				$chunck = '';
				$i++;

			}

		}

		*/

		fclose($stream);
	}

	echo 'memory: ' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB' . PHP_EOL;

	$end = microtime(true);

	echo "execution time: " . number_format($end - $begin, 6) . " seconds" . PHP_EOL;

}

$file = "files/json/tests/VISA_TRANSACTIONAL_CLEARING_20240705_01.json";

try {

	stream_filter_register('jsondecode', jsondecode_filter::class);

	streamJSONFile($file);

} catch (Exception $exception) {

	echo $exception->getMessage();

}

