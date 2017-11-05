<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: keyword.php
// Manage keywords on problems.
//
// GET parameters:
// - "action": "add", "remove", "suggest"
// - "keyword": The keyword text to add, remove, or complete (in suggest mode)
// - "pid": The ID of the target problem, if applicable
//

require_once "lib/config.php";

// Get parameters
$p_action = filter_input(INPUT_GET, "action");
$p_keyword = filter_input(INPUT_GET, "keyword");
$p_pid = filter_input(INPUT_GET, "pid", FILTER_SANITIZE_NUMBER_INT);

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

/**
 * Perform the add action.
 *
 * @param \mysqli $sql The SQL connection
 * @param string  $keyword The keyword text
 * @param integer $pid The ID of the target problem
 */
function action_add($sql, $keyword, $pid)
{
    // Insert the keyword text
    if ($sql_stmt = $sql->prepare("INSERT INTO `keyword` (`pid`, `word`) VALUES (?, ?)")) {
        $sql_stmt->bind_param("is", $pid, $keyword);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to add keyword: $sql->error\"}");
        }
        $sql_stmt->close();
    }

    echo "{\"success\": true}";
}

/**
 * Perform the remove action.
 *
 * @param \mysqli $sql The SQL connection
 * @param string  $keyword The keyword text
 * @param integer $pid The ID of the target problem
 */
function action_remove($sql, $keyword, $pid)
{
    // Remove the keyword text
    if ($sql_stmt = $sql->prepare("DELETE FROM `keyword` WHERE `pid` = ? AND `word` = ?")) {
        $sql_stmt->bind_param("is", $pid, $keyword);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to remove keyword: $sql->error\"}");
        }
        $sql_stmt->close();
    }

    echo "{\"success\": true}";
}

/**
 * Perform the suggest action.
 *
 * @param \mysqli $sql The SQL connection
 * @param string  $keyword The keyword text
 */
function action_suggest($sql, $keyword)
{
    // The suggested keywords
    $suggestions = array();

    // Use the provided keyword as a regex
    // This should provide a pretty nice way of selecting similar keywords
    // This is a low-effort solution that hasn't been vetted for security
    if ($sql_stmt = $sql->prepare("SELECT DISTINCT `word` FROM `keyword` WHERE `word` REGEXP ? ORDER BY `word`")) {
        $sql_stmt->bind_param("s", $keyword);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to suggest keyword: $sql->error\"}");
        }

        $result = $sql_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $suggestions[] = $row["word"];
        }

        $sql_stmt->close();
    }

    echo "{\"success\": true, \"result\": {\"provided\": \"" . addslashes($keyword) . "\", \"suggestions\": [";

    for ($i = 0; $i < count($suggestions); ++$i) {
        if ($i > 0) {
            echo ", ";
        }

        echo "\"$suggestions[$i]\"";
    }

    echo "]}}";
}

switch ($p_action) {
case "add":
    action_add($sql, $p_keyword, $p_pid);
    break;
case "remove":
    action_remove($sql, $p_keyword, $p_pid);
    break;
case "suggest":
    action_suggest($sql, $p_keyword);
    break;
}

// Close database connection
$sql->close();
