<?php

/*
|--------------------------------------------------------------------------
| Database Connection
|--------------------------------------------------------------------------
|
| This file stores the information PHP needs to connect to MySQL.
|
| Because the connection is kept in its own file, any PHP page that needs
| database access can load this file instead of repeating the same
| connection code.
|
| Example:
|
|     require 'db.php';
|
| After db.php is loaded, that file can use the $conn variable.
|
*/


// --------------------------------------------------
// DATABASE CREDENTIALS
// --------------------------------------------------

// The computer where the MySQL server is running.
// "localhost" means the MySQL server is running on this computer.
$host = 'localhost';

// The MySQL account PHP will use to connect.
$username = 'root';

// The password for the MySQL account above.
$password = 'rogjub-kivRap-bowme3';

// The database PHP should connect to.
$database = 'mya_schema';


// --------------------------------------------------
// MYSQL CONNECTION
// --------------------------------------------------

/*
| mysqli stands for "MySQL Improved".
|
| It is a PHP extension that allows PHP to communicate with a MySQL
| database server.
|
| new mysqli(...) creates a connection to MySQL using the credentials
| above and stores that connection in $conn.
|
| Think of $conn as PHP's open connection to the database. Other PHP
| files can use it to send SQL queries to MySQL.
*/

$conn = new mysqli($host, $username, $password, $database);

?>
