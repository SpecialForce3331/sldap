# -*- coding: utf-8

import mysql.connector
import sys

server = "localhost"
username = "ldap_squid"
password = "qwerty"
database = "ldap_squid"

try:
    conn = mysql.connector.connect(user=username, password=password, host=server, database=database)
except:
    exit("Can't connect to Mysql Server")


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
        squid_input = data.rsplit(" ")

        conn = get_connection(conn)
        if conn is None:
            exit("can't connect to Mysql server")

        try:
            url = squid_input[0].strip()
            login = squid_input[1].strip()
            login = login.split("@")[0]

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

