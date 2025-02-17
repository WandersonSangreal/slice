<?php

namespace App\Services;

use Exception;
use InvalidArgumentException;

class InsertService
{
	private $connection;
	private string $table;

	public function __construct($connection, string $table)
	{
		$this->connection = $connection;
		$this->table = $table;
	}

	public function insert(array $data): bool
	{
		if (empty($data)) {

			return throw new InvalidArgumentException("error: empty data");

		}

		$columns = array_keys(current($data));
		$values = str_repeat('?,', count(current($data)) - 1) . '?';

		$sql = "INSERT INTO {$this->table} (" . join(',', $columns) . ") VALUES " .
			str_repeat("($values),", count($data) - 1) . "($values)";

		# USING COPY, FILE ISSUES
		# $sql = "COPY $this->table FROM $tmpCSV WITH (FORMAT csv, HEADER true, DELIMITER ',');";

		$pdo = $this->connection->getPDO();

		$stmt = $pdo->prepare($sql);

		try {

			$pdo->beginTransaction();

			# PARAMS LIMIT 65535
			$stmt->execute(array_merge(...array_map('array_values', $data)));

			# SLOWER
			# $this->connection->table($this->table)->insert($data);

			$pdo->commit();

			return true;

		} catch (Exception $e) {

			$pdo->rollBack();

			return throw new Exception("error: inserting data: " . $e->getMessage());

		}

	}

}
