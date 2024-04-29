<?php
$host = "postgres";
$user = "root";
$password = "root";
$db_name = "dolibarr";

$pg_conn_string = "host=$host dbname=$db_name user=$user password=$password";
$pg_con = pg_connect($pg_conn_string);


if (!$pg_con) {
    die("Failed to connect with PostgreSQL: " . pg_last_error());
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>