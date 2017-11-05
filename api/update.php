<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: update.php
// Update an existing math problem.
//
// POST parameters:
// - "pid": The ID of the target problem
// - "content": The text content of the new problem
//

require_once "lib/config.php";

// Get parameters
$p_pid = filter_input(INPUT_POST, "pid");
$p_content = filter_input(INPUT_POST, "content");

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Update target problem's content
if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `content` = ? WHERE `pid` = ?")) {
    $sql_stmt->bind_param("i", $p_content, $p_pid);
    if (!$sql_stmt->execute()) {
        die("{\"success\": false, \"error\": \"Unable to update problem: $sql->connect_error\"}");
    }
}

// Close database connection
$sql->close();

echo "{\"success\": true}";
