<?php

/*
 * Tyler Filla
 * CS 4610
 * Project 2
 */

//
// Endpoint: search.php
// Search for problems by keywords.
//
// GET parameters:
// - "keywords": A comma-separated string of keywords
// - "page_num": The requested page number
// - "page_size": The uniform size of the requested pages
//

require_once "lib/config.php";

// Get parameters
$p_keywords = filter_input(INPUT_GET, "keywords");
$p_page_num = filter_input(INPUT_GET, "page_num", FILTER_SANITIZE_NUMBER_INT);
$p_page_size = filter_input(INPUT_GET, "page_size", FILTER_SANITIZE_NUMBER_INT);

// Open database connection
$sql = new mysqli($db_host, $db_username, $db_password, $db_name);

if ($sql->connect_errno) {
    die("{\"success\": false, \"error\": \"Unable to connect to database: $sql->connect_error\"}");
}

// Explode keyword array
$keywords = array();
if ($p_keywords) {
    $keywords = explode(",", $p_keywords);
}

// Get ALL stored math problems
$result = $sql->query("SELECT `pid`, `follows` FROM `problem`");
if (!$result) {
    die("{\"success\": false, \"error\": \"Unable to retrieve problems\"}");
}

// An array of IDs of matched problems
$matched_pids = array();

// A map from IDs of matched problems to the matched keywords
$matched_keywords = array();

// Query for keywords and collect matches
foreach ($keywords as $keyword) {
    if ($sql_stmt = $sql->prepare("SELECT `pid` FROM `keyword` WHERE `word` = ?")) {
        $sql_stmt->bind_param("s", $keyword);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to query keyword: $sql->error\"}");
        }

        // Process query result
        $result = $sql_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pid = $row["pid"];

            // Store the problem ID if it doesn't already exist
            if (!in_array($pid, $matched_pids)) {
                $matched_pids[] = $pid;
            }

            // Use the problem ID as an index to store the keyword
            if (!$matched_keywords[$pid]) {
                $matched_keywords[$pid] = array();
            }
            $matched_keywords[$pid][] = $keyword;
        }

        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to query keyword: $sql->error\"}");
    }
}

$success_result = "{\"success\": true, \"result\": {\"keywords\": [";

// List all keywords for convenience
for ($i = 0; $i < count($keywords); ++$i) {
    if ($i > 0) {
        $success_result .= ", ";
    }

    $success_result .= "\"" . addslashes($keywords[$i]) . "\"";
}

// Compute pagination information (in order numbers, not problem IDs)
$page_last = -1;
$page_first_problem = 1;
$page_last_problem = count($matched_pids);
if ($p_page_num) {
    $page_last = ceil(count($matched_pids) / $p_page_size);
    $page_first_problem = ($p_page_num - 1) * $p_page_size + 1;
    $page_last_problem = $page_first_problem + $p_page_size - 1;
}

$success_result .= "], \"problems\": [";

// List all matched problems
for ($i = $page_first_problem - 1; $i < $page_last_problem; ++$i) {
    $m_pid = $matched_pids[$i];
    $m_keywords = $matched_keywords[$m_pid];
    $m_content = null;

    if (!$m_pid) {
        break;
    }

    if ($sql_stmt = $sql->prepare("SELECT `content` FROM `problem` WHERE `pid` = ?")) {
        $sql_stmt->bind_param("i", $m_pid);
        if (!$sql_stmt->execute()) {
            die("{\"success\": false, \"error\": \"Unable to get content: $sql->error\"}");
        }
        $m_content = $sql_stmt->get_result()->fetch_assoc()["content"];
        $sql_stmt->close();
    } else {
        die("{\"success\": false, \"error\": \"Unable to prepare to get content: $sql->error\"}");
    }

    if ($i > $page_first_problem - 1) {
        $success_result .= ",";
    }

    // Base64-encode the content, because there are just too many things to try to escape
    // We just assume that all the character-level stuff (i.e. encoding) will work out
    $content_b64 = base64_encode($m_content);

    $success_result .= "{\"pid\": $m_pid, \"num_keywords\": " . count($m_keywords) . ", \"keywords\": [";

    // List matching keywords
    for ($j = 0; $j < count($m_keywords); ++$j) {
        if ($j > 0) {
            $success_result .= ", ";
        }

        $success_result .= "\"" . addslashes($m_keywords[$j]) . "\"";
    }

    $success_result .= "], \"content\": \"$content_b64\"}";
}

$success_result .= "]}}";

// Close database connection
$sql->close();

echo $success_result;
