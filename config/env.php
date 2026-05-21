<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!file_exists($autoload)) {
    die('Autoload file not found: ' . $autoload);
}

require_once $autoload;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();