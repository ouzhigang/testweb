<?php
//reids锁

set_time_limit(6000);

function rand_str($type, $length) {
	$str1 = "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z";
	$str2 = "A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z";
	$str3 = "0,1,2,3,4,5,6,7,8,9";
	
	if ($type == 1) {
		$content = $str1;
	}
	else if ($type == 2) {
		$content = $str2;
	}
	else if ($type == 3) {
		$content = $str3;
	}
	else if ($type == 4) {
		$content = $str1 . "," . $str2;
	}
	else if ($type == 5) {
		$content = $str2 . "," . $str3;
	}
	else if ($type == 6) {
		$content = $str1 . "," . $str3;
	}
	else if ($type == 7) {
		$content = $str1 . "," . $str2 . "," . $str3;
	}
	
	$strs = explode(",", $content);
	$output = "";
	for($i = 0; $i < $length; $i++) { 
		do {
			$r = rand(0, strlen($content));
		}
		while(empty($strs[$r]));
		$output .= $strs[$r];
	}
	
	return $output;
}

$dbh = new PDO('mysql:host=127.0.0.1;dbname=testweb', 'root', 'root');  
$dbh->exec('set names utf8'); 

$redis = new Redis();
$redis->pconnect('127.0.0.1', 6379);

$id = 1;

$res = array();

$k = "redis_lock";

$set_ok = false;
while(!$set_ok) {
	$v = rand_str(7, 32);
	$now = time();

	$set_ok = $redis->setNx($k, $v);
	
	if($set_ok) {
		$redis->expireAt($k, $now + 30);
		
		$stmt = $dbh->query('select * from product where id = ' . $id);
		$rows = $stmt->fetchAll();

		$num = intval($rows[0]["num"]);
		if($num > 0) {
			$stmt = $dbh->prepare("update product set num = num - 1 where id = " . $id);
			$stmt->execute();
		
			$res["code"] = 0;
			$res["msg"] = "剩余" . ($num - 1) . "个商品";
		}
		else {
			$res["code"] = 1;
			$res["msg"] = "已经没有商品了";
		}
	
		if($redis->get($k) == $v) {
			$redis->delete($k);
		}
	}
	else {
		//等待下一次访问
		sleep(1);
	}
}

echo json_encode($res);
