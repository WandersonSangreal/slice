<?php

namespace App\Services;

use PDO;
use Exception;
use InvalidArgumentException;

class InsertService
{
	private PDO $pdo;
	private string $table;

	public function __construct(PDO $pdo, string $table)
	{
		$this->pdo = $pdo;
		$this->table = $table;
	}

	public function insert(array $data): bool
	{
		if (empty($data)) {

			return throw new InvalidArgumentException("error: empty data");

		}

		$columns = array_keys(current($data));

		# PARAMS LIMIT 65535
		$values = str_repeat('?,', count(current($data)) - 1) . '?';

		$sql = "INSERT INTO $this->table (" . join(',', $columns) . ") VALUES " .
			str_repeat("($values),", count($data) - 1) . "($values)";

		# USING COPY
		# $sql = "COPY $this->table FROM $tmpCSV WITH (FORMAT csv, HEADER true, DELIMITER ',');";

		# $sql = "INSERT INTO {$this->table} (" . join(',', $columns) . ") VALUES " .
		# "('" . join("'),('", array_map(fn($item) => str_replace("'null'", "null", join("','", array_map(fn($i) => (!isset($i) ? 'null' : $i), $item))), $data)) . "');";

		$stmt = $this->pdo->prepare($sql);

		try {

			$this->pdo->beginTransaction();

			# PARAMS LIMIT 65535
			$stmt->execute(array_merge(...array_map('array_values', $data)));
			# $stmt->execute();

			$this->pdo->commit();

			return true;

		} catch (Exception $e) {

			$this->pdo->rollBack();

			return throw new Exception("error: inserting data: " . $e->getMessage());

		}

	}

}
