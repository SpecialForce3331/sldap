# -*- coding: utf-8

import mysql.connector

server = "localhost"
username = "ldap_squid"
password = "qwerty"
database = "ldap_squid"
pathLog = "/var/log/squid/access.log";

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
last_update = cursor.fetchone()
if last_update is None:
    last_update = 0.0
else:
    last_update = last_update[0]


def add_to_db(user_login, user_url, user_bytes, user_dateTime):
        query = "INSERT INTO usersTraffic ( login, site, bytes, dateTime ) VALUES (%s, %s, %s, %s)"
        cursor.execute(query, (user_login, user_url, user_bytes, user_dateTime))


file = open(pathLog, 'r', encoding="utf-8", errors="ignore")

for line in file:
    try:
        parsed_row = line.split()
        for login in login_array:
            if len(parsed_row) >= 7:
                if login[0] == parsed_row[7] and float(parsed_row[0]) > last_update:
                    add_to_db(parsed_row[7], parsed_row[6], parsed_row[4], parsed_row[0])
    except:
        continue
file.close()

query = "SELECT lastUpdate,trafficForDay,login FROM users WHERE login IN ("

for login in login_array:
    if login == login_array[-1]:
        query = query + "\"" + login[0] + "\")"
    else:
        query = query + "\"" + login[0] + "\","

cursor.execute(query)
result = cursor.fetchall()

for row in result:
    last_update = row[0]
    current_traffic = row[1]
    login = row[2]
    query = "SELECT SUM(bytes) FROM usersTraffic WHERE dateTime > %s AND login = %s"
    cursor.execute(query, (last_update, login))
    row = cursor.fetchone()

    if row[0] is not None:
        traffic = float(round(row[0]/1048576, 2)) + current_traffic

        if traffic > 0:
            query = "SELECT dateTime FROM usersTraffic WHERE login=%s ORDER BY id DESC LIMIT 1"
            cursor.execute(query, (login,))
            last_update = cursor.fetchone()[0]
            print(login)
            query = "UPDATE users SET trafficForDay=%s, lastUpdate=%s WHERE login=%s"
            cursor.execute(query, (traffic, last_update, login))

query = "UPDATE users SET trafficForDay=\"0\" WHERE CURTIME() >= \"23:58:00\""
cursor.execute(query)
conn.commit()
