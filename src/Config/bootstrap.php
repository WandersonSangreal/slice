<?php

require_once realpath(__DIR__ . '/../../vendor/autoload.php');

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(realpath(__DIR__ . '/../../'));
$dotenv->load();
