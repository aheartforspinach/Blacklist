<?php

function task_whitelist($task){
    global $db;

    $updateUsers = array('hasSeenBlacklist' => 0, 'isOnBlacklist' => 0, 'isOnBlacklistAnnulled' => 0);
    $db->update_query('users', $updateUsers);

    // Add an entry to the log
    add_task_log($task, 'Die Blacklist wurde erfolgreich zurückgesetzt');
}