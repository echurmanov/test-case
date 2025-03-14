<?php

header("Content-Type: application/json");

$db = mysqli_connect("localhost", "test_case1", "test_case1", "test_case1");
if (!$db) {
    echo json_encode(["success" => false, "message" => "DB Error"]);
    die();
}

$result = mysqli_query($db, "SELECT * FROM t1_monitor WHERE ts > " . (time() - 2 * 3600) . " ORDER BY ts");
if (!$result) {
    echo json_encode(["success" => false, "message" => "DB Error"]);
}
$raw_data = mysqli_fetch_all($result, MYSQLI_ASSOC);

$skip_step = ceil(count($raw_data) / 4);
$data = [
    'labels' => [],
    'q1' => [],
    'q3' => [],
    'w1' => [],
    'w3' => []
];

$i = 0;

foreach ($raw_data as $idx => $row) {
    //if ($i % 5 === 0) {
        $data['labels'][] = date("H:i", (int)$row['ts']);
    //} else {
    //    $data['labels'][] = '';
    //}

    $data['q1'][] = (int)$row['queue_1d'];
    $data['q3'][] = (int)$row['queue_3d'];
    $data['w1'][] = (int)$row['workers_1d'];
    $data['w3'][] = (int)$row['workers_3d'];
    $i++;
}

echo json_encode(['success' => true, 'data' => $data]);
