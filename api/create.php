<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: create.php
// Create a new math problem and make it first.
//
// POST parameters:
// - "content": The text content of the new problem
//

require_once "lib/config.php";

// Get parameters
$p_content = $_POST["content"];

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Step 1: Add the problem to the database
// Warning: This step leaves the new problem inaccessible from the user interface
if ($sql_stmt = $sql->prepare("INSERT INTO `problem` (`content`, `follows`) VALUES (?, ?)")) {
    // Set up parameter bindings
    $sql_stmt->bind_param("si", $b_content, $b_follows);

    // Insert content and a dummy follows value
    $b_content = $p_content;
    $b_follows = 1;
    if (!$sql_stmt->execute()) {
        die("{\"success\": false, \"error\": \"Unable to perform step 1: $sql->error\"}");
    }

    // Close the statement
    $sql_stmt->close();
} else {
    die("{\"success\": false, \"error\": \"Unable to prepare step 1: $sql->error\"}");
}

// Get ID of new problem
$pid = $sql->insert_id;

// Step 2: Make the first problem follow the new problem
// Warning: This step leaves ALL problems inaccessible from the user interface
if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = ? WHERE `follows` = 0")) {
    // Set up parameter bindings
    $sql_stmt->bind_param("i", $b_pid);

    // Execute the query
    $b_pid = $pid;
    if (!$sql_stmt->execute()) {
        die("{\"success\": false, \"error\": \"Unable to perform step 2: $sql->error\"}");
    }

    // Close the statement
    $sql_stmt->close();
} else {
    die("{\"success\": false, \"error\": \"Unable to prepare step 2: $sql->error\"}");
}

// Step 3: Make the new problem first
if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = 0 WHERE `pid` = ?")) {
    // Set up parameter bindings
    $sql_stmt->bind_param("i", $b_pid);

    // Execute the query
    $b_pid = $pid;
    if (!$sql_stmt->execute()) {
        die("{\"success\": false, \"error\": \"Unable to perform step 3: $sql->error\"}");
    }

    // Close the statement
    $sql_stmt->close();
} else {
    die("{\"success\": false, \"error\": \"Unable to prepare step 3: $sql->error\"}");
}

// Close database connection
$sql->close();

echo "{\"success\": true, \"pid\": $pid}";
