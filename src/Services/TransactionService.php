<?php

namespace App\Services;

use PDO;
use Exception;
use InvalidArgumentException;

class TransactionService
{
	private PDO $pdo;

	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	public function fetchAll(): array
	{

		$stmt = $this->pdo->prepare("SELECT name, id FROM transactions;");

		$stmt->execute();

		return $stmt->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP) ?? [];

	}

}
