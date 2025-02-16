<?php

namespace App\Database;

use PDO;
use PDOException;

class PostgresDatabase
{
	private ?PDO $connection = null;

	public function __construct()
	{

		$host = $_ENV['DB_HOST'];
		$database = $_ENV['DB_NAME'];
		$username = $_ENV['DB_USER'];
		$password = $_ENV['DB_PASS'];

		try {

			$this->connection = new PDO("pgsql:host=$host;dbname=$database", $username, $password);

		} catch (PDOException $e) {

			die("error connecting to database: " . $e->getMessage());

		}

	}

	public function getConnection(): PDO
	{

		return $this->connection;

	}

}
