<?php
//reids锁

/*
以下设置测试通过

php-fpm设置
pm = static
pm.max_children = 900
pm.start_servers = 150
pm.min_spare_servers = 150
pm.max_spare_servers = 900
pm.max_requests = 30240

nginx设置
worker_processes 8;
worker_cpu_affinity 00000001 00000010 00000100 00001000 00010000 00100000 01000000 10000000;
pid /run/nginx.pid;

worker_rlimit_nofile 102400;
events {
        use epoll;
        worker_connections 102400;
        multi_accept on;

}

		fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 16 16k;
        fastcgi_busy_buffers_size 16k;
        fastcgi_temp_file_write_size 16k;
        fastcgi_cache_valid 200 302 1h;
        fastcgi_cache_valid 301 1d;
        fastcgi_cache_valid any 1m;
        fastcgi_cache_min_uses 1;
        fastcgi_cache_use_stale error timeout invalid_header http_500;

        server_names_hash_bucket_size 128;
        client_header_buffer_size 4k;
        large_client_header_buffers 4 4k;
        client_max_body_size 8m;

        open_file_cache max=204800 inactive=20s;
        open_file_cache_min_uses 1;
        open_file_cache_valid 30s;
        
*/

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
		//等待下一次访问或返回网络阻塞的错误
		sleep(1);
	}
}

echo json_encode($res);
