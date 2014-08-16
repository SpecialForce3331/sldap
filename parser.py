# -*- coding: utf-8

import mysql.connector

server = "localhost"
username = "ldap_squid"
password = "qwerty"
database = "ldap_squid"
pathLog = "/var/log/squid3/access.log";

try:
    conn = mysql.connector.connect(user=username, password=password, host=server, database=database)
    cursor = conn.cursor()
except:
    exit("Can't connect to Mysql Server")

query = "SELECT login FROM users WHERE 1"
cursor.execute(query)
login_array = cursor.fetchall()

query = "SELECT dateTime FROM usersTraffic ORDER BY id DESC LIMIT 1"
cursor.execute(query)
last_update = cursor.fetchone()[0]

def add_to_db(login, url, bytes, dateTime):
        query = "INSERT INTO usersTraffic ( login, cite, bytes, dateTime ) VALUES (%s, %s, %s, %s)"
        cursor.execute(query, (login, url, bytes, dateTime))


file = open(pathLog, 'r')

for line in file:
    parsed_row = line.split()

    for login in login_array:
        if len(parsed_row) >= 7:
            if login[0] == parsed_row[7] and float(parsed_row[0]) > last_update:
                add_to_db(parsed_row[7], parsed_row[6], parsed_row[4], parsed_row[0])
file.close()
