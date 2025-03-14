<?php
require_once __DIR__ . "/lib.php";

$db = mysqli_connect("localhost", "test_case1", "test_case1", "test_case1");
if (!$db) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    die();
}

$ts = time();
exec('ps -aux | grep worker.php | grep day1', $output1);
var_dump($output1);
$workers1 = max(count($output1) - 2, 0);

exec('ps -aux | grep worker.php | grep day3', $output3);
$workers3 = max(count($output3) - 2, 0);
var_dump($output3);


$res1 = check_target_count($db, $ts, ALERT_TYPE_1_DAY);
if (!$res1[0]) {
    echo $res1[2] . PHP_EOL;
    die();
}
$queue1 = (int)$res1[1];
$res3 = check_target_count($db, $ts, ALERT_TYPE_3_DAY);
if (!$res3[0]) {
    echo $res3[2] . PHP_EOL;
    die();
}
$queue3 = (int)$res3[1];


if (!mysqli_query(
    $db,
    "INSERT INTO t1_monitor (ts, queue_1d, queue_3d, workers_1d, workers_3d) VALUES ($ts, $queue1, $queue3, $workers1, $workers3)"
)) {
    echo mysqli_error($db);
}


