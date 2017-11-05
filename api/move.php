<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: move.php
// Move an existing math problem up or down by one.
//
// GET parameters:
// - "pid": The ID of the target problem
// - "dir": "up" to move up one or "down" to move down one
//

require_once "lib/config.php";

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Close database connection
$sql->close();

echo "{\"success\": true}";
