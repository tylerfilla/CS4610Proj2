<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: trash.php
// Manage the trash, a temporary place for problems before they get deleted.
//
// GET parameters:
// - "action": "count" to count trashed problems or "undo" to undo the last trashed problem
//

require_once "lib/config.php";

// Get parameters
$p_action = filter_input(INPUT_GET, "action");

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Close database connection
$sql->close();

echo "{\"success\": true}";
