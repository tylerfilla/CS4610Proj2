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
// GET parameters:
// - "keywords": A comma-separated string of keywords
// - "page_num": The requested page number
// - "page_size": The uniform size of the requested pages
//

require_once "lib/config.php";

// Get parameters
$p_keywords = $_GET["keywords"];

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Get ALL stored math problems
// The problems are stored in a linked list fashion, so this is unfortunately a necessity
// The volume of problems in this project is low, however, so it works for now
$result = $sql->query("SELECT `pid`, `follows` FROM `problem`");
if (!$result) {
    die("{\"success\": false, \"error\": \"Unable to retrieve problems\"}");
}

// A list of problem IDs indexed by problem IDs the associated problems follow
// This allows for easy chained lookups of math problems
$pids_raw = array();

// Load above array from database
// One possible optimization would be to stop after the page ends
while ($row = $result->fetch_assoc()) {
    $pids_raw[$row["follows"]] = $row["pid"];
}

// A list of problem IDs ordered according to user preference
$pids_ordered = array();

// Untangle the linked list structure of the $pids_raw array
$pid = 0;
while ($pid = $pids_raw[$pid]) {
    $pids_ordered[] = $pid;
}

$success_result = "{\"success\": true, \"result\": {\"problems\": [";

for ($i = 0; $i < count($pids_ordered); ++$i) {
    // Add comma before each element after the first
    if ($i > 0) {
        $success_result .= ",";
    }

    $pid = $pids_ordered[$i];
    $content = null;

    if ($sql_stmt = $sql->prepare("SELECT `content` FROM `problem` WHERE `pid` = ?")) {
        // Set up parameter bindings
        $sql_stmt->bind_param("i", $b_pid);

        // Execute the query
        $b_pid = $pid;
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to get content: $sql->error\"}");
        }

        // Fetch content
        $content = $sql_stmt->get_result()->fetch_assoc()["content"];

        // Close the statement
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to get content: $sql->error\"}");
    }

    // Base64-encode the content, because there are just too many things to try to escape
    // We just assume that all the character-level stuff (i.e. encoding) will work out
    $content_b64 = base64_encode($content);

    $success_result .= "{\"pid\": $pid, \"content\": \"$content_b64\"}";
}

$success_result .= "]}}";

// Close database connection
$sql->close();

echo $success_result;
