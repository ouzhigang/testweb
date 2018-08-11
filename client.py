#!/usr/bin/python3

import sys, os
import grequests

if __name__ == "__main__":
	
	#运行请求数
	num = 3000
	
	#访问的url
	url = "http://127.0.0.1/php/index.php"
	
	urls = []
	for i in range(0, num):
		urls.append(url)
	
	rs = (grequests.get(u) for u in urls)
	for res in grequests.map(rs):
		if res != None:
			if res.status_code == 200:
				print(res.json())
			else:
				print("错误:" + str(res.status_code))
			