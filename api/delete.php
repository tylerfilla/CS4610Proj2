<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: delete.php
// Delete an existing math problem, optionally forever.
//
// GET parameters:
// - "pid": The ID of the target problem
// - "trash": "true" to just move the problem to the trash or "false" (or omitted) to permanently delete
//

require_once "lib/config.php";

// Get parameters
$p_pid = filter_input(INPUT_GET, "pid", FILTER_SANITIZE_NUMBER_INT);
$p_trash = filter_input(INPUT_GET, "trash", FILTER_SANITIZE_NUMBER_INT);

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Close database connection
$sql->close();

echo "{\"success\": true}";
