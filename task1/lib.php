<?php

const ALERT_TYPE_3_DAY = 'day3';
const ALERT_TYPE_1_DAY = 'day1';

const DAY = 86400;

function check_email($email) {
    sleep(rand(1, 60));
    return rand(1,100) > 20 ? 1 : 0;
}

function send_email($from, $to, $text) {
    sleep(rand(1, 10));
}

function get_params($alert_type = ALERT_TYPE_3_DAY, $current_ts = null) {
    if ($current_ts === null) {
        $current_ts = time();
    }

    switch ($alert_type) {
        case ALERT_TYPE_3_DAY:
            $table_name = 't1_last_sends_3d';
            $already_send_ts = $current_ts - 3 * DAY;
            $expire_ts = $current_ts + 3 * DAY;
            $skip_ts = $expire_ts - DAY;
            break;
        case ALERT_TYPE_1_DAY:
            $table_name = 't1_last_sends_1d';
            $already_send_ts = $current_ts - DAY;
            $expire_ts = $current_ts + DAY;
            $skip_ts = $expire_ts;
            break;
        default:
            return [false, 1001, 'Unknown $alert_type value: ' . $alert_type];
    }

    return [true, $table_name, $current_ts, $already_send_ts, $expire_ts, $skip_ts];
}

function check_target_count ($db_connect, $current_ts = null, $alert_type = ALERT_TYPE_3_DAY) {
    $params = get_params($alert_type, $current_ts);
    if (!$params[0]) {
        return $params;
    } else {
        [, $table_name, $current_ts, $already_send_ts, $expire_ts, $skip_ts] = $params;
    }

    $sql = <<<SQL
SELECT count(*) as cnt FROM t1_users u LEFT JOIN `$table_name` t ON (t.user_id = u.id AND t.ts >= ?) 
    WHERE t.user_id IS NULL AND (u.confirmed = 1 OR u.valid = 1 OR u.checked = 0)
    AND u.validts > 0 AND (u.validts BETWEEN ? AND ?) AND NOT(u.validts > ?)
SQL;

    $select_stmt = mysqli_prepare($db_connect, $sql);
    mysqli_stmt_bind_param($select_stmt, 'iiii', $already_send_ts, $current_ts, $expire_ts, $skip_ts);
    mysqli_stmt_bind_result($select_stmt, $total);

    if (mysqli_stmt_execute($select_stmt)) {
        mysqli_stmt_fetch($select_stmt);
    } else {
        return [false, 2001, "Error fetching data from DB: " . mysqli_error($db_connect)];
    }

    mysqli_stmt_close($select_stmt);

    return [true, $total];
}

function process_chunk($db_connect, $current_ts = null, $alert_type = ALERT_TYPE_3_DAY, $parts_count = 1, $part_index = 0, $time_limit = 1500) {
    $start_time = time();

    $params = get_params($alert_type, $current_ts);
    if (!$params[0]) {
        return $params;
    } else {
        [, $table_name, $current_ts, $already_send_ts, $expire_ts, $skip_ts] = $params;
    }

    $sql = <<<SQL
SELECT id, email, validts, confirmed, checked, valid FROM t1_users u LEFT JOIN `$table_name` t ON (t.user_id = u.id AND t.ts >= ?) 
    WHERE t.user_id IS NULL AND (u.confirmed = 1 OR u.valid = 1 OR u.checked = 0)
    AND u.validts > 0 AND (u.validts BETWEEN ? AND ?) AND NOT(u.validts > ?)
SQL;

    if ($parts_count > 1) {
        $sql .= " AND MOD(u.id, ?) = ?";
    }

    $sql .= " ORDER BY u.validts LIMIT 2000";

    $select_stmt = mysqli_prepare($db_connect, $sql);
    if ($parts_count > 1) {
        mysqli_stmt_bind_param($select_stmt, 'iiiiii', $already_send_ts, $current_ts, $expire_ts, $skip_ts, $parts_count, $part_index);
    } else {
        mysqli_stmt_bind_param($select_stmt, 'iiii', $already_send_ts, $current_ts, $expire_ts, $skip_ts);
    }

    if (!mysqli_stmt_execute($select_stmt)) {
        return [false, 1002, "Error fetching data from DB: " . mysqli_error($db_connect)];
    }
    $select_results = mysqli_stmt_get_result($select_stmt);
    mysqli_stmt_close($select_stmt);

    $user_id = 0;
    $stmt_insert_send_mark = mysqli_prepare($db_connect, "INSERT INTO `$table_name` (user_id, ts) VALUES (?, ?) as t ON DUPLICATE KEY UPDATE ts = t.ts");
    mysqli_stmt_bind_param($stmt_insert_send_mark, 'ii', $user_id, $current_ts);

    $stmt_update_check_valid = mysqli_prepare($db_connect, "UPDATE t1_users SET valid = ?, checked = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update_check_valid, 'ii', $valid, $user_id);

    $uc = 0;
    while($row = mysqli_fetch_assoc($select_results)) {
        $uc++;
        $user_id = (int)$row['id'];
        $confirmed = (int)$row['confirmed'];
        $checked = (int)$row['checked'];
        $email = (string)$row['email'];
        $validts = (int)$row['validts'];
        $valid = (int)$row['valid'];
        if (!$confirmed && !$checked) {
            $valid = check_email($email);
            echo "Worker {$part_index} checked email for user {$user_id}." . PHP_EOL;
            if (!mysqli_stmt_execute($stmt_update_check_valid)) {
                return [false, 1003, "Error fetching data from DB: " . mysqli_error($db_connect)];
            }
        } else {
            echo "Worker {$part_index} skipped check email for user {$user_id}. Reason: " . ($checked ? 'already checked' : 'confirmed' ) . PHP_EOL;
        }
        if ($confirmed || $valid) {
            send_email("our-cool@email.com", $email, "Your subscription expired at " . date("Y-m-d H:i", $validts));
            echo "Worker {$part_index} send email for user {$user_id}." . PHP_EOL;
        } else {
            echo "Worker {$part_index} skip email for user {$user_id} - not valid." . PHP_EOL;
        }
        if (!mysqli_stmt_execute($stmt_insert_send_mark)) {
            return [false, 1004, "Error fetching data from DB: " . mysqli_error($db_connect)];
        }

        if ($time_limit > 0 && time() > $start_time + $time_limit) {
            echo "Worker {$part_index} processed {$uc} users and done by timeout." . PHP_EOL;
            return [true, true];
        }

    }

    echo "Worker {$part_index} processed {$uc} users and done with chunk." . PHP_EOL;

    return [true, $user_id !== 0];
}
