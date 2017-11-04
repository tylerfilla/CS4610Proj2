<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: query.php
// Query a list of math problems.
//
// POST parameters:
// - "keywords": A comma-separated string of keywords.
//

$db_host = "localhost";
$db_username = "root";
$db_password = "thisisthepassword";
$dp_name = "mathprobdb";

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Close database connection
$sql->close();
