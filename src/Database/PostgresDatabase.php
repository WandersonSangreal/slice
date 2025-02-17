<?php

namespace App\Database;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager;

class PostgresDatabase
{
	private $schema = null;
	private $connection = null;

	public function __construct()
	{

		try {

			$manager = new Manager;

			$manager->addConnection([
				"driver" => "pgsql",
				"host" => $_ENV['DB_HOST'],
				"database" => $_ENV['DB_NAME'],
				"username" => $_ENV['DB_USER'],
				"password" => $_ENV['DB_PASS']
			]);

			$manager->setAsGlobal();
			$manager->bootEloquent();

			$this->schema = $manager::schema();
			$this->connection = $manager::connection();

		} catch (Exception $e) {

			die("error connecting to database: " . $e->getMessage());

		}

	}

	public function getSchema()
	{
		return $this->schema;
	}

	public function getConnection()
	{

		return $this->connection;

	}

}
