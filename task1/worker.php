<?php

const AVG_3D_PROCESSING_TIME = 35;
const AVG_1D_PROCESSING_TIME = 10;

require_once __DIR__ . "/lib.php";

$db = mysqli_connect("localhost", "test_case1", "test_case1", "test_case1");
if (!$db) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    die();
}


$params_correct = $_SERVER['argc'] >= 4
    && ($_SERVER['argv'][1] === ALERT_TYPE_3_DAY || $_SERVER['argv'][1] === ALERT_TYPE_1_DAY)
    && (is_numeric($_SERVER['argv'][2]) && (int)$_SERVER['argv'][2] > 0)
    && (($_SERVER['argv'][3] === 'auto' && is_numeric($_SERVER['argv'][4]) && (int)$_SERVER['argv'][4] > 0) || (is_numeric($_SERVER['argv'][3]) && (int)$_SERVER['argv'][3] > 0));

if (!$params_correct) {
    echo <<<HELP
Usage: php worker.php <alert_type> <time_limit> <parts_count> [<total_processes_limit>]
  - <alert_type> = 'day1' | 'day3'
  - <time_limit> = number > 0 // Time limit for worker life
  - <parts_count> = 'auto' | number > 0 // Number for processes
  - <total_processes_limit> = number > 0 // if <parts_count> is 'auto' - total limits for started processes

HELP;
    die();
}


$ts = time();
$type = (string)$_SERVER['argv'][1];
$time_limit = (int)$_SERVER['argv'][2];

$result = check_target_count($db, $ts, $type);
if (!$result[0]) {
    echo <<<ERROR
Master error: 
 Code: {$result[1]}
 Message: {$result[2]}
 
ERROR;
    die();
}

$total_users = (int)$result[1];

if ($total_users === 0) {
    echo "Nothing to sent" . PHP_EOL;
    die();
}

if ($_SERVER['argv'][3] === 'auto') {
    if ($type === ALERT_TYPE_1_DAY) {
        $parts_count = min(ceil(($total_users * AVG_1D_PROCESSING_TIME) / $time_limit) + 1, (int)$_SERVER['argv'][4]);
    } else {
        $parts_count = min(ceil(($total_users * AVG_3D_PROCESSING_TIME) / $time_limit) + 1, (int)$_SERVER['argv'][4]);
    }
} else {
    $parts_count = (int)$_SERVER['argv'][3];
}

echo <<<START
Start workers with next params:
 - Alert Type: {$type}
 - Time Limit: {$time_limit}
 - Worker Count: {$parts_count}
 - Time Point: {$ts}
 
 - Users in the queue: {$total_users} 

START;

$children = [];

for ($part_index = 0; $part_index < $parts_count; $part_index++) {
    $pid = pcntl_fork();
    if ($pid !== 0) {
        $children[] = $pid;
    } else {
        $db = mysqli_connect("localhost", "test_case1", "test_case1", "test_case1");
        if (!$db) {
            echo "Worker #{$part_index}: Error: Unable to connect to MySQL." . PHP_EOL;
            die();
        }

        $timeout_ts = $ts + $time_limit;
        $time_left = $timeout_ts - time();
        $time_border = $type === ALERT_TYPE_1_DAY ? AVG_1D_PROCESSING_TIME : AVG_3D_PROCESSING_TIME;
        while ($time_left > AVG_3D_PROCESSING_TIME) {
            $chunk_result = process_chunk($db, $ts, $type, $parts_count, $part_index, $time_left);
            if (!$chunk_result[0]) {
                echo <<<ERROR
Worker#{$part_index} error: 
 Code: {$result[1]}
 Message: {$result[2]}

ERROR;
              die();
            } else {
                echo "Worker#{$part_index} done chunk, time to deadline lefts: $time_left" . PHP_EOL;
            }
            $time_left = $timeout_ts - time();

            if (!$chunk_result[1] && $time_left > AVG_3D_PROCESSING_TIME) { // Не было писем для обработки, можно поспать
                $ts = time();
                sleep(min(ceil($time_left / 2), AVG_3D_PROCESSING_TIME * 5));
            }

        }

        echo "Worker#{$part_index} done work, time to deadline lefts: $time_left" . PHP_EOL;
        die;
    }
}

while(count($children) > 0) {
    foreach($children as $key => $pid) {
        $res = pcntl_waitpid($pid, $status, WNOHANG);
        if($res == -1 || $res > 0)
            unset($children[$key]);
    }
    sleep(1);
}

echo "Master: All workers done" . PHP_EOL;

