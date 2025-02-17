<?php

namespace App\Services;

class TransactionService
{
	private $connection;

	public function __construct($connection)
	{
		$this->connection = $connection;
	}

	public function get(): array
	{

		return $this->connection->table('transactions')->get(['id', 'name'])->keyBy('name')->toArray();

	}

}
