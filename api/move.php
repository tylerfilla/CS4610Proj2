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
// - "dir": "up" or "down"
// - "pid": The ID of the target problem
//

require_once "lib/config.php";

// Get parameters
$p_dir = filter_input(INPUT_GET, "dir");
$p_pid = filter_input(INPUT_GET, "pid", FILTER_SANITIZE_NUMBER_INT);

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

/**
 * Move in the up direction.
 *
 * @param \mysqli $sql The SQL connection
 * @param integer $pid The ID of the target problem
 */
function dir_up($sql, $pid)
{
    // Get the ID of the problem followed by the target
    $pid_follows = -1;
    if ($sql_stmt = $sql->prepare("SELECT `follows` FROM `problem` WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move problem (1): $sql->error\"}");
        }
        $pid_follows = $sql_stmt->get_result()->fetch_assoc()["follows"];
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to move problem (1): $sql->error\"}");
    }

    // Stop if the target is already first
    if ($pid_follows == 0) {
        exit("{\"success\": true}");
    }

    // Get the ID of the problem following the target
    $pid_follower = -1;
    if ($sql_stmt = $sql->prepare("SELECT `pid` FROM `problem` WHERE `follows` = ?")) {
        $sql_stmt->bind_param("i", $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move problem (2): $sql->error\"}");
        }
        $pid_follower = $sql_stmt->get_result()->fetch_assoc()["pid"];
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to move problem (2): $sql->error\"}");
    }

    // Move target to the same level of that which it follows
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = (SELECT `follows` FROM (SELECT * FROM `problem`) AS t WHERE `pid` = ?) WHERE `pid` = ?")) {
        $sql_stmt->bind_param("ii", $pid_follows, $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move problem (3): $sql->error\"}");
        }
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to move problem (3): $sql->error\"}");
    }

    // Move supplanted problem down one (after the now-moved-up target)
    if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = ? WHERE `pid` = ?")) {
        $sql_stmt->bind_param("ii", $pid, $pid_follows);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move problem (4): $sql->error\"}");
        }
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to move problem (4): $sql->error\"}");
    }

    // If there was a problem behind the target before it was moved
    if ($pid_follower != -1) {
        // Make original follower follow the current follower
        if ($sql_stmt = $sql->prepare("UPDATE `problem` SET `follows` = ? WHERE `pid` = ?")) {
            $sql_stmt->bind_param("ii", $pid_follows, $pid_follower);
            if (!$sql_stmt->execute()) {
                die("{\"success\": false, \"error\": \"Unable to move problem (5): $sql->error\"}");
            }
            $sql_stmt->close();
        } else {
            die("{\"success\": false, \"error\": \"Unable to prepare to move problem (5): $sql->error\"}");
        }
    }

    exit("{\"success\": true}");
}

/**
 * Move in the down direction.
 *
 * @param \mysqli $sql The SQL connection
 * @param integer $pid The ID of the target problem
 */
function dir_down($sql, $pid)
{
    // Get the ID of the problem following the target
    $pid_follower = -1;
    if ($sql_stmt = $sql->prepare("SELECT `pid` FROM `problem` WHERE `follows` = ?")) {
        $sql_stmt->bind_param("i", $pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to move problem (down-1): $sql->error\"}");
        }
        $pid_follower = $sql_stmt->get_result()->fetch_assoc()["pid"];
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to move problem (down-1): $sql->error\"}");
    }

    // Stop if target follower is already last
    if ($pid_follower == -1) {
        exit("{\"success\": true}");
    }

    // Move the target follower up one
    // This is the lazy equivalent to moving the target down one
    dir_up($sql, $pid_follower);
}

switch ($p_dir) {
case "up":
    dir_up($sql, $p_pid);
    break;
case "down":
    dir_down($sql, $p_pid);
    break;
}

// Close database connection
$sql->close();
