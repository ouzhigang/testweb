<?php
//没加锁的

$dbh = new PDO('mysql:host=127.0.0.1;dbname=testweb', 'root', 'root');  
$dbh->exec('set names utf8'); 

$id = 1;

$stmt = $dbh->query('select * from product where id = ' . $id);
$rows = $stmt->fetchAll();

$num = intval($rows[0]["num"]);
if($num > 0) {
	$stmt = $dbh->prepare("update product set num = num - 1 where id = " . $id);
	$stmt->execute();
	
	echo json_encode(array(
		"code" => 0,
		"msg" => "剩余" . ($num - 1) . "个商品"
	));
}
else {
	echo json_encode(array(
		"code" => 1,
		"msg" => "已经没有商品了"
	));
}
