# -*- coding: utf-8

import mysql.connector
import os

server = "localhost"
username = "ldap_squid"
password = "qwerty"
database = "ldap_squid"
pathLog = "/var/log/squid/access.log"

conn = mysql.connector.connect(user=username, password=password, host=server, database=database)
cursor = conn.cursor()

def get_connection():
    global conn
    global cursor

    try:
        if not conn.is_connected():
            conn.reconnect()
            cursor = conn.cursor()
            return conn, cursor
        else:
            return conn, cursor
    except Exception as e:
        print(e)
        exit

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


def add_to_db( trafficArray ):
        query = "INSERT INTO usersTraffic ( login, site, bytes, dateTime ) VALUES "

        if len(trafficArray) < 1:
            return

        for traffic in trafficArray:
            user_login, user_url, user_bytes, user_dateTime = traffic

            if traffic == trafficArray[-1]:           
                query = query + "(\"" + user_login + "\",\"" + user_url + "\",\"" + user_bytes + "\",\"" + user_dateTime + "\")"
            else:
                query = query + "(\"" + user_login + "\",\"" + user_url + "\",\"" + user_bytes + "\",\"" + user_dateTime + "\"),"
        conn, cursor = get_connection()
        cursor.execute(query)
        conn.commit()

file = open(pathLog, 'r', encoding="utf-8", errors="ignore")

if os.path.exists('/tmp/sldap_poz.dat'):
    poz_file = open('/tmp/sldap_poz.dat', 'r+')
else:
    poz_file = open('/tmp/sldap_poz.dat', 'w+')

position = poz_file.readline()
if position == '':
    position = 0
else:
    position = int(position)

if position > 0:
    try:
       file.seek(position)
    except OSError:
        position = 0

trafficArray = []

for line in file:
    try:
        parsed_row = line.split()
        for login in login_array:
            if len(parsed_row) >= 7:
                if login[0] == parsed_row[7] and float(parsed_row[0]) > last_update:
                    traffic = []

                    traffic.append( parsed_row[7] )
                    traffic.append( parsed_row[6] )
                    traffic.append( parsed_row[4] )
                    traffic.append( parsed_row[0] )
                    trafficArray.append( traffic )
    except:
        continue

position = file.tell()
poz_file.seek(0)
poz_file.write(str(position))

poz_file.close()
file.close()

add_to_db( trafficArray )

query = "SELECT lastUpdate,trafficForDay,login FROM users WHERE login IN ("

for login in login_array:
    if login == login_array[-1]:
        query = query + "\"" + login[0] + "\")"
    else:
        query = query + "\"" + login[0] + "\","

conn, cursor = get_connection()
cursor.execute(query)

result = cursor.fetchall()

for row in result:
    last_update = row[0]
    current_traffic = row[1]
    login = row[2]
    query = "SELECT SUM(bytes) FROM usersTraffic WHERE dateTime > %s AND login = %s"
    conn, cursor = get_connection()
    cursor.execute(query, (last_update, login))
    row = cursor.fetchone()

    if row[0] is not None:
        traffic = float(round(row[0]/1048576, 2)) + current_traffic

        query = "SELECT dateTime FROM usersTraffic ORDER BY id DESC LIMIT 1"
        conn, cursor = get_connection()
        cursor.execute(query)
        last_update = cursor.fetchone()[0]

        if traffic > 0:
            print(login)
            query = "UPDATE users SET trafficForDay=%s, lastUpdate=%s WHERE login=%s"
            conn, cursor = get_connection()
            cursor.execute(query, (traffic, last_update, login))
            conn.commit()

query = "UPDATE users SET trafficForDay=\"0\" WHERE CURTIME() >= \"23:30:00\""
conn, cursor = get_connection()
cursor.execute(query)
conn.commit()

cursor.close()
conn.close()


