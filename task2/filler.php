<?php

$db = mysqli_connect("localhost", "teset_case2", "test_case2", "test_case2");

if (!$db) {
	echo "Error!" . PHP_EOL;
	die();
}

$user_stmt = mysqli_prepare($db, "INSERT IGNORE db_users (username) VALUES (?)");
$user_name = 'user0';
mysqli_stmt_bind_param($user_stmt, "s", $user_name);

for ($i = 0; $i < 1000; $i++) {
    $user_name = "user$i";
    mysqli_stmt_execute($user_stmt);
}

$users = mysqli_query($db, "SELECT * FROM db_users");
$user_id = 0;
$amount = 0;
$payed = false;
$status = 'progress';
$order_stmt = mysqli_prepare($db, "INSERT INTO db_orders (user_id, amount, payed) VALUES (?, ?, ?)");
$order_id = 0;

$payment_stmt = mysqli_prepare($db, "INSERT INTO db_payments (order_id, amount, status) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($order_stmt, "idi", $user_id, $amount, $payed);
mysqli_stmt_bind_param($payment_stmt, "ids", $order_id, $amount, $status);

// Bad users
for ($user_id = 0; $user_id < 10; $user_id++) {
    $orders_number = rand(8, 25);
    for ($o = 0; $o < $orders_number; $o++) {
        $amount = 10 + rand(1, 100);
        $payed = rand(1, 100) > 66 ? 1 : 0;
        var_dump($payed);
        $order_stmt->execute();
        $order_id = mysqli_stmt_insert_id($order_stmt);

        $payment_number = rand(2, 10);
        for ($i = 0; $i < $payment_number; $i++) {
            $status = rand(1, 100) > 75 ? 'success' : 'fail';
            $payment_stmt->execute();
        }

        if ($payed) {
            $status = 'success';
            $payment_stmt->execute();
        }
    }
}

//Good users
for ($user_id = 10; $user_id < 20; $user_id++) {
    $orders_number = rand(8, 25);
    for ($o = 0; $o < $orders_number; $o++) {
        $amount = 10 + rand(1, 100);
        $payed = rand(1, 100) > 15 ? 1 : 0;
        var_dump($payed);
        $order_stmt->execute();
        $order_id = mysqli_stmt_insert_id($order_stmt);

        $payment_number = rand(2, 10);
        for ($i = 0; $i < $payment_number; $i++) {
            $status = rand(1, 100) > 7 ? 'success' : 'fail';
            $payment_stmt->execute();
        }

        if ($payed) {
            $status = 'success';
            $payment_stmt->execute();
        }
    }
}


//Avg users
for ($user_id = 20; $user_id < 1000; $user_id++) {
    $orders_number = rand(8, 25);
    for ($o = 0; $o < $orders_number; $o++) {
        $amount = 10 + rand(1, 100);
        $payed = rand(1, 100) > 50 ? 1 : 0;
        $order_stmt->execute();
        $order_id = mysqli_stmt_insert_id($order_stmt);

        $payment_number = rand(2, 10);
        for ($i = 0; $i < $payment_number; $i++) {
            $status = rand(1, 100) > 50 ? 'success' : 'fail';
            $payment_stmt->execute();
        }

        if ($payed) {
            $status = 'success';
            $payment_stmt->execute();
        }
    }
}




