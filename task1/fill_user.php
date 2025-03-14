<?php

$db = mysqli_connect("localhost", "test_case1", "test_case1", "test_case1");

if (!$db) {
    echo "Error!" . PHP_EOL;
    die();
}

$user_stmt = mysqli_prepare($db, "INSERT into t1_users (username, email, validts, confirmed) VALUES (?, ?, ?, ?)");
$user_name = '';
$email = '';
$validts = 0;
$confirmed = 0;
mysqli_stmt_bind_param($user_stmt, "ssii", $user_name, $email, $validts, $confirmed);

$time1 = time() + 12 * 3600;
$time2 = time() + 31 * 24 * 3600;

for ($i = 3231246; $i < 5100000; $i++) {
    $user_name = "user$i";
    $email = "email$i@dot.com";
    $validts = rand(1, 100) <= 80 ? 0 : rand($time1,  $time2);
    $confirmed = rand(1, 100) <= 15 ? 1 : 0;

    mysqli_stmt_execute($user_stmt);
}