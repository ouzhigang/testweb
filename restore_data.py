#!/usr/bin/python3

import os, sys
import pymysql

if __name__ == "__main__":

	db = pymysql.connect("127.0.0.1", "root", "root", "testweb")
	
	cursor = db.cursor()
	try:
		cursor.execute("update product set num = 5 where id = 1")
		db.commit()
	except Exception as e:
		db.rollback()
	cursor.close()
	
	db.close()
	
	print("restore data")