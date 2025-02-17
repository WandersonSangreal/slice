<?php

namespace App\Database;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager;

class PostgresDatabase
{
	private $schema = null;
	private $connection = null;

	public function __construct($host, $dbname, $user, $pass)
	{

		try {

			$manager = new Manager;

			$manager->addConnection([
				"driver" => "pgsql",
				"host" => $host,
				"database" => $dbname,
				"username" => $user,
				"password" => $pass
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
