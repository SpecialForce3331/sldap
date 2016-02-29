# -*- coding: utf-8

import mysql.connector
import sys
import os

server = ""
username = ""
password = ""
database = ""

try:
    dir = os.path.dirname(os.path.realpath(__file__))
    configFile = open(dir + "/install/config.cfg", "r")
    config = configFile.readlines()
    configFile.close()

    for line in config:
        if line.startswith("#") or line.startswith(" ") or line.startswith("\n"):
            continue
        key, value = line.split(" : ")
        if key == "MYSQL_ip":
            server = value.strip()
        elif key == "MYSQL_login":
            username = value.strip()
        elif key == "MYSQL_password":
            password = value.strip()
        elif key == "MYSQL_database":
            database = value.strip()

    if not server or not username or not password or not database:
        exit("Config file is incorrect")

    conn = mysql.connector.connect(user=username, password=password, host=server, database=database)
except FileNotFoundError:
    exit("Config file not found")
except mysql.connector.Error:
    exit("Can't connect to mysql server or database")


def get_connection(conn):
    if conn.is_connected():
        return conn
    else:
        print("conn is closed, trying to open new connection")
        try:
            conn = mysql.connector.connect(user=username, password=password, host=server, database=database)
            return conn
        except:
            return None


def get_user_data(user_login):
    user_login = (user_login,)
    cursor = conn.cursor()

    query = "SELECT " \
            "patterns.traffic, " \
            "patterns.access " \
            "FROM users " \
            "LEFT JOIN patterns " \
            "ON users.pattern_id = patterns.id " \
            "WHERE users.login =%s"

    cursor.execute(query, user_login)
    result = cursor.fetchone()
    conn.commit()
    cursor.close()
    return result


def check_access(allow_traffic, user_access, user_login, url_to_open):

    if allow_traffic == 0 and user_access == 1:  # Если траффик безлимит, и есть доступ к запрещенным сайтам
        return "OK\n"

    user_login = (user_login,)

    cursor = conn.cursor()

    query = "SELECT " \
            "COUNT(1) " \
            "FROM users " \
            "LEFT JOIN patterns ON users.pattern_id = patterns.id " \
            "WHERE users.login=%s AND users.trafficForDay >= patterns.traffic"

    cursor.execute(query, user_login)
    result = cursor.fetchone()
    conn.commit()

    if result[0] == 1 and allow_traffic > 0:
        return "ERR message=traffic_limit\n"

    if access == 0:
        query = "SELECT url FROM denySites WHERE 1"
        cursor.execute(query)
        result = cursor.fetchall()
        conn.commit()
        for deny_url in result:
            if url_to_open.find(deny_url[0]) > -1:
                return "ERR message=deny_site\n"

    cursor.close()
    return "OK\n"


while (True):

    try:
        data = sys.stdin.readline()
        squid_input = data.split(" ", 1)

        conn = get_connection(conn)
        if conn is None:
            exit("can't connect to Mysql server")

        try:
            url = squid_input[0].strip()
            login = squid_input[1].strip()
            login = login.split("@")[0]
            login = login.replace("%20", " ")

            traffic, access = get_user_data(login)

            answer = check_access(traffic, access, login, url)

            sys.stdout.write(answer)
            sys.stdout.flush()
            continue

        except Exception as ex:
            sys.stdout.write("ERR message=user_not_exist\n")
            sys.stdout.flush()
            continue

    except IOError:
        conn.close()
        exit("helper exiting normally")

