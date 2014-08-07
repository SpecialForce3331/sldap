<?php

//    include 'install/checkconf.php';

    //Работа с БД
//TODO Разобраться по какой причине в случае инклуда файла процесс php начинает жрать по 15-20% цпу
     	$server = "localhost";
     	$username = "ldap_squid";
     	$password = "qwerty";
     	$database = "ldap_squid";

//    $server = $MysqlIp;
//    $username = $MysqlLogin;
//    $password = $MysqlPassword;
//    $database = $MysqlDatabase;

    $mysqli = new mysqli( $server, $username, $password, $database ); //Устанавливаем соединение в базой мускула

    if ( $mysqli->connect_errno )
    {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; //Не удалось установить соединение с базой мускула.
    }
    else
    {
        $mysqli->set_charset("utf8"); //Устанавливаем принудительно кодировку в UTF-8!
    }


    while(1) //загоняем в цикл в ожидании запросов от squid
    {
        $line = trim( fgets( STDIN ) );
        $line = explode( " ", $line );

        $src_ip = null;
        $url = null;
        $login = null;
        $countLines = count( $line );

        if ( $countLines < 1 )
        {
            echo "ERR\n";
            continue;
        }
        elseif( $countLines == 1 )
        {
            $src_ip = $line[0];
        }
        elseif( $countLines == 2 )
        {
            $src_ip = $line[0];
            $url = $line[1];
        }
        elseif( $countLines == 3 )
        {
            $src_ip = $line[0];
            $url = $line[1];
            $login = $line[2];
        }

        $userData = getUserData($mysqli, $src_ip, $login);

        if ( !empty( $userData ) && empty( $url ) )
        {
            $login = $userData[0];
            echo "OK user=".$login."\n";
            continue;
        }
        elseif( !empty( $userData ) && !empty( $url ) )
        {
            checkAccess($mysqli, $userData, $url);
            continue;
        }
        else
        {
            echo "ERR message=auth\n";
            continue;
        }
    }


    function getUserData($mysqli, $ip, $login)
    {
        $query = "";

        if ( is_null( $login ) )
        {
            $query = "SELECT login, patterns.traffic, patterns.access FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id WHERE users.ip = INET_ATON('".$ip."')";
        }
        else
        {
            $query = "SELECT login, patterns.traffic, patterns.access FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id WHERE users.login = '".$login."'";
        }


        $result = $mysqli->query( $query ) or die( "select error" );
        $data = $result->fetch_row();
        if ( !empty($data[0]) )
        {
            return $data;
        }
        return "";
    }

    function checkAccess($mysqli, $userData, $url)
    {
        $login = $userData[0];
        $AllowedTraffic = $userData[1];
        $access = $userData[2];

        //если разрешенный траффик = 0 - безлимит, и есть доступ к запрещенным сайтам
        if ( $AllowedTraffic == 0 && $access == 1 )
        {
            echo "OK\n";
        }
        else
        {
            //проверяем не превышен ли траффик
            $query = "SELECT access, patterns.traffic FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id WHERE users.login=\"".$login."\" AND users.trafficForDay >= patterns.traffic";
            $result = $mysqli->query( $query );
            $rows = $result->num_rows;

            if ( $rows > 0 && $AllowedTraffic != 0 ) //если есть пользователь превысивший траффик и у него не безлимит
            {
                echo "ERR\n";
                return;
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

                        if( preg_match( "/$current/i", $url ) )
                        {
                            echo "ERR\n";
                            return;
                        }
                        else if ( $i == ( count( $data ) -1 ) )
                        {
                            echo "OK\n";
                            return;
                        }
                    }
                }
                else
                {
                    echo "OK\n";
                    return;
                }

            }
            else //те у кого не кончился траффик и у них есть доступ к запрещенным сайтам
            {
                echo "OK\n";
                return;
            }

            echo "ERR\n";//если пользователя в БД вообще нет, то запрещаем
            return;
        }
    }
?>
