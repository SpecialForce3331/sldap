<?php

//    include 'install/checkconf.php';

    //TODO Разобраться по какой причине в случае инклуда файла процесс php начинает жрать по 15-20% цпу
    $server = "localhost";
    $username = "ldap_squid";
    $password = "qwerty";
    $database = "ldap_squid";

//    $server = $MysqlIp;
//    $username = $MysqlLogin;
//    $password = $MysqlPassword;
//    $database = $MysqlDatabase;

    function getConnection($server, $username, $password, $database, $mysqli)
    {
        //Работа с БД
        if ( !is_null($mysqli) && mysqli_ping( $mysqli ) )
        {
            return $mysqli;
        }
        else
        {
            $mysqli = new mysqli( $server, $username, $password, $database ); //Устанавливаем соединение в базой мускула

            if ( $mysqli->connect_errno )
            {
                echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; //Не удалось установить соединение с базой мускула.
                return null;
            }
            else
            {
                $mysqli->set_charset("utf8"); //Устанавливаем принудительно кодировку в UTF-8!
                return $mysqli;
            }
        }
    }

    $mysqli = null; //Mysql Connection

    while(1) //загоняем в цикл в ожидании запросов от squid
    {
        $mysqli = getConnection($server, $username, $password, $database, $mysqli);
        if( is_null( $mysqli ) )
        {
            break;
        }

        $line = trim( fgets( STDIN ) );

        $line = explode( " ", $line );

        $url = null;
        $login = null;

        if ( !isset( $line[0] ) || !isset( $line[1] ) )
        {
            echo "";
            continue;
        }

        $url = $line[0];
        $login = $line[1];

        $userData = getUserData($mysqli, $login);
        if ( empty( $userData ) )
        {
            echo "ERR\n";
            continue;
        }

        $result = checkAccess($mysqli, $userData, $login, $url);

        if ( empty( $result ) )
        {
            echo "ERR\n";
        }
        else
        {
            echo $result;
        }
        continue;
    }


    function getUserData($mysqli, $login)
    {
        $query = "SELECT patterns.traffic, patterns.access FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id WHERE users.login = '".$login."'";

        $result = $mysqli->query( $query ) or die( "select error" );
        $data = $result->fetch_row();
        if ( !empty($data ) )
        {
            return $data;
        }
        return;
    }

    function checkAccess($mysqli, $userData, $login, $url)
    {
        $login = $login;
        $AllowedTraffic = $userData[0];
        $access = $userData[1];

        //если разрешенный траффик = 0 - безлимит, и есть доступ к запрещенным сайтам
        if ( $AllowedTraffic == 0 && $access == 1 )
        {
            return "OK\n";
        }
        else
        {
            //проверяем не превышен ли траффик
            $query = "SELECT access, patterns.traffic FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id WHERE users.login=\"".$login."\" AND users.trafficForDay >= patterns.traffic";
            $result = $mysqli->query( $query );
            $rows = $result->num_rows;

            if ( $rows > 0 && $AllowedTraffic != 0 ) //если есть пользователь превысивший траффик и у него не безлимит
            {
                return "ERR message=traffic_limit\n";
            }
            else if ( $access == 0 ) //если есть запрет к списку запрещеннных сайтов
            {
                $query = "SELECT url FROM denySites WHERE 1";
                $result = $mysqli->query( $query );
                $rows = $result->num_rows;

                if ( $rows > 0 )
                {
                    $data = $result->fetch_all(MYSQLI_NUM);

                    for ( $i = 0; $i < count( $data ); $i++ ) //перебираем список запрещенныых сайтов и сравниваем с обращением пользователя
                    {
                        $current = trim($data[$i][0]);
//			            $current = parse_url($current, PHP_URL_HOST);
                        if( preg_match( "/$current/i", $url ) )
                        {
                            return "ERR message=deny_site\n";
                        }
                        else if ( $i == ( count( $data ) -1 ) )
                        {
                            return "OK\n";
                        }
                    }
                    return "ERR\n";
                }
                else
                {
                    return "OK\n";
                }

            }
            else //те у кого не кончился траффик и у них есть доступ к запрещенным сайтам
            {
                return "OK\n";
            }

            return "ERR message=user_not_exist\n";//если пользователя в БД вообще нет, то запрещаем
        }
        return "ERR\n";
    }
?>
