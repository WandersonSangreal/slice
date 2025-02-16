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

		$values = str_repeat('?,', count(current($data)) - 1) . '?';

		$sql = "INSERT INTO $this->table (" . join(',', $columns) . ") VALUES " .
			str_repeat("($values),", count($data) - 1) . "($values)";

		$stmt = $this->pdo->prepare($sql);

		try {

			$this->pdo->beginTransaction();

			$stmt->execute(array_merge(...array_map('array_values', $data)));

			$this->pdo->commit();

			return true;

		} catch (Exception $e) {

			$this->pdo->rollBack();

			return throw new Exception("error: inserting data: " . $e->getMessage());

		}

	}

}
