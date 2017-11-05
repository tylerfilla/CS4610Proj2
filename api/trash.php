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
// - "action": "empty" to empty the trash,
//             "count" to count problems in trash,
//             "move" to move a problem to the trash,
//             "undo" to undo the last move to the trash
// - "pid": [optional] The ID of the target problem
//

require_once "lib/config.php";

// Get parameters
$p_action = filter_input(INPUT_GET, "action");
$p_pid = filter_input(INPUT_GET, "pid");

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

/**
 * Perform the empty action.
 *
 * @param \mysqli $sql The SQL connection
 */
function action_empty($sql)
{
    // Delete all trashed problems
    $result = $sql->query("DELETE FROM `problem` WHERE `follows` = -1;");
    if (!$result) {
        die("{\"success\": false, \"error\": \"Unable to empty trash: $sql->connect_error\"}");
    }

    // TODO: Do we need to delete all keywords, or will the foreign key constraint do this?

    echo "{\"success\": true}";
}

/**
 * Perform the count action.
 *
 * @param \mysqli $sql The SQL connection
 */
function action_count($sql)
{
    // Count all trashed problems
    $result = $sql->query("SELECT COUNT(*) AS 'count' FROM `problem` WHERE `follows` = -1;");
    if (!$result) {
        die("{\"success\": false, \"error\": \"Unable to count trash: $sql->connect_error\"}");
    }

    // Retrieve count
    $count = $result->fetch_assoc()["count"];

    echo "{\"success\": true, \"result\": {\"count\": $count}}";
}

/**
 * Perform the move action.
 *
 * @param \mysqli $sql The SQL connection
 * @param integer $pid The ID of the target problem
 */
function action_move($sql, $pid)
{
    // Get the problem that the target problem follows
    $target_follows = -1;
    if ($sql_stmt = $sql->prepare("SELECT `follows` FROM `problem` WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move to trash: $sql->connect_error\"}");
        }
        $target_follows = $sql_stmt->get_result()->fetch_assoc()["follows"];
    }

    // Exclude target problem from linked list structure
    // The target problem will become inaccessible from the GUI
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = ? WHERE `follows` = ?")) {
        $sql_stmt->bind_param("ii", $target_follows, $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move to trash: $sql->connect_error\"}");
        }
    }

    // Make target problem follow nothing else in the list
    // It will be completely disconnected from every other problem
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = -1 WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move to trash: $sql->connect_error\"}");
        }
    }

    // Update trashed timestamp on target problem
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `trashed` = NOW() WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move to trash: $sql->connect_error\"}");
        }
    }

    echo "{\"success\": true}";
}

/**
 * Perform the undo action.
 *
 * @param \mysqli $sql The SQL connection
 */
function action_undo($sql)
{
    // Get the ID of the latest problem to be moved to the trash
    // This will be targeted as the next problem to be restored
    $result = $sql->query("SELECT `pid` FROM `problem` WHERE `trashed` = (SELECT MAX(`trashed`) FROM `problem`);");
    if (!$result) {
        die("{\"success\": false, \"error\": \"Unable to undo last move to trash: $sql->connect_error\"}");
    }
    $target_pid = $result->fetch_assoc()["pid"];

    // Make former first problem follow the target problem
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = ? WHERE `follows` = 0")) {
        $sql_stmt->bind_param("i", $target_pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to undo last move to trash: $sql->connect_error\"}");
        }
    }

    // Now insert the target problem first
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = 0 WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $target_pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to undo last move to trash: $sql->connect_error\"}");
        }
    }

    // Clear the now-reinstated target problem's trashed timestamp
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `trashed` = NULL WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $target_pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to undo last move to trash: $sql->connect_error\"}");
        }
    }
}

switch ($p_action) {
case "empty":
    action_empty($sql);
    break;
case "count":
    action_count($sql);
    break;
case "move":
    action_move($sql, $p_pid);
    break;
case "undo":
    action_undo($sql);
    break;
}

// Close database connection
$sql->close();
