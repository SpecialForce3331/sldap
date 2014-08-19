<?php 

include 'install/checkconf.php';

//Работа с БД

	//Данные для подключения к БД
// 	$server = "localhost";
// 	$username = "ldap_squid";
// 	$password = "qwerty";
// 	$database = "ldap_squid";

	$server = $MysqlIp;
	$username = $MysqlLogin;
	$password = $MysqlPassword;
	$database = $MysqlDatabase;

	$mysqli = new mysqli( $server, $username, $password, $database ); //Устанавливаем соединение в базой мускула
	
	if ( $mysqli->connect_errno )
	{
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; //Не удалось установить соединение с базой мускула.
	}
	else
	{
		$mysqli->set_charset("utf8"); //Устанавливаем принудительно кодировку в UTF-8!
	}
	
//Начало секции обработки пользовательских запросов
	
	if ( $_POST["action"] == "getMysqlUsers" ) //получаем пользователей и инфу о них из БД
	{
		$result = $mysqli->query( "SELECT users.login,users.name,users.trafficForDay,patterns.traffic,patterns.name,patterns.access FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id ORDER BY users.name" ) or die("can not get users ".$mysqli->error );;
		
		if ($mysqli->connect_errno)
		{
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;			
		}
		else
		{

            $data = $result->fetch_all( MYSQLI_NUM );

			$answer = array("result" => $data);
			echo json_encode( $answer );
			$result->free();
		}
	}
	else if ( $_POST["action"] == "cleanTraffic" ) //очистка траффика пользователей
	{
		$users = $_POST["data"];
		
		$query = "UPDATE users SET trafficForDay= 0 WHERE login in (";
		
		for( $i = 0; $i < count( $users ); $i += 3 )
		{
			if ( $i == (count( $users ) - 3 ) )
			{
				$query = $query."\"".$users[$i+1]."\")";
			}
			else 
			{
				$query = $query."\"".$users[$i+1]."\",";
			}
			
		}
		
		$mysqli->query( $query ) or die("can not erase users traffic ".$mysqli->error );
		
		echo json_encode( array( "result" => "ok" ));
		
	}
	else if ( $_POST["action"] == "addUsers" ) //добавляем пользователей в БД
	{
		$data = $_POST["data"];

		$query = "INSERT INTO users (login,name,pattern_id) VALUES ";

		for ( $i = 0; $i < count($data); $i += 3 )
		{
			if ( $i == (count($data) - 3 ) )
			{
				$query = $query."(\"".$data[$i+1]."\",\"".$data[$i]."\",\"".$data[$i+2]."\")";
			}
			else
			{
				$query = $query."(\"".$data[$i+1]."\",\"".$data[$i]."\",\"".$data[$i+2]."\"),";
			}
		}
		
		$mysqli->query( $query ) or die("insert error: ".$mysqli->error."\n");
		$data = array("result" => "ok" );
		echo json_encode( $data );
		
	}
	else if ( $_POST["action"] == "deleteUsers" ) //удаляем пользователей из БД
	{
		$data = $_POST["data"];
		
		$query = "DELETE FROM users WHERE login IN (";
		
		for ( $i = 0; $i < count($data); $i += 3 )
		{
			if ( $i == (count($data) - 3 ) )
			{
				$query = $query."\"".$data[$i+1]."\")";
			}
			else
			{
				$query = $query."\"".$data[$i+1]."\",";
			}
		}	
		
		$mysqli->query( $query ) or die( $mysqli->error." delete error");
		$data = array("result" => "ok" );
		echo json_encode( $data );
	}
	else if ( $_POST["action"] == "getPatterns" ) //получаем шаблоны из БД
	{
		$query = "SELECT name,traffic,access,id FROM patterns WHERE 1";
		
		$result = $mysqli->query( $query ) or die( "select error" );
		$data = $result->fetch_all( MYSQLI_NUM );
		echo json_encode( array( "result" => $data ) );
	
	}
	else if ( $_POST["action"] == "createPattern" ) //создаем шаблоны и добавляем в БД
	{
		$name = $_POST["name"];
		$traffic = $_POST["traffic"];
		$access = $_POST["access"];
		
		if ( !empty( $name ) && !empty( $traffic ) )
		{
			$query = "INSERT INTO patterns (name,traffic,access) VALUES ('".$name."','".$traffic."','".$access."')";
			$result = $mysqli->query( $query ) or die("insert pattert error");
			
			echo json_encode( array("result"=>"ok") );
		}
		else
		{
			echo json_encode( array("result"=>"false" ));
		}	
	}
	else if ( $_POST["action"] == "deletePattern" ) //удаляем шаблон из БД
	{
		$patterns = $_POST["patterns"];
		
		$query = "DELETE FROM patterns WHERE name in (";

		for ( $i = 0; $i < count($patterns); $i++ )
		{
			if ( $i == ( count($patterns) - 1 ) )
			{
				$query = $query."\"".$patterns[$i]."\")";
			}
			else
			{
				$query = $query."\"".$patterns[$i]."\",";
			}	
		}
		
		$mysqli->query( $query ) or die( "can not delete patterns: ".$mysqli->error );
		echo json_encode( array( "result" => "ok" ));
	}
	else if ( $_POST["action"] == "applyChangesToUsers" ) //применение изменений к пользователям
	{
		$changes = $_POST["changes"];
		
		for ( $i = 0; $i < count($changes); $i++ )
		{
			$query = "UPDATE users SET pattern_id=\"".$changes[$i][2]."\" WHERE login=\"".$changes[$i][0]."\"";
			$mysqli->query( $query ) or die("cannot update users for changes ".$mysqli->error );
		}	
		echo json_encode( array( "result" => "ok" ));
	}
	else if ( $_POST["action"] == "applyChangesToPatterns" )
	{
		$changes = $_POST["changes"];
		
		for ( $i = 0; $i < count($changes); $i++ )
		{
			$query = "UPDATE patterns SET name=\"".$changes[$i][1]."\", traffic=\"".$changes[$i][2]."\", access=\"".$changes[$i][3]."\" WHERE name=\"".$changes[$i][0]."\"";
			$mysqli->query( $query ) or die("can not update patterns: ".$mysqli->error );
		}
		
		echo json_encode( array( "result" => "ok") );
	}
	else if ( $_POST["action"] == "getDenySites" )
	{
		$query = $mysqli->query( "SELECT url FROM denySites WHERE 1") or die( "can not update patterns ".$mysqli->error );
		$result = $query->fetch_all( MYSQLI_NUM );
		echo json_encode( array("result" => $result ));
	}
	else if( $_POST["action"] == "createDenySite" )
	{
		$url = $_POST["url"];
		
		$query = "INSERT INTO denySites (url) VALUES ";
		
		for( $i = 0; $i < count($url); $i++ )
		{
			if ( !empty( $url[$i] ) )
			{
				if ( $i == ( count( $url ) - 1 ) )
				{
					$query = $query."(\"".$url[$i]."\")";
				}
				else
				{
					$query = $query."(\"".$url[$i]."\"),";
				}
			}

		}
		if ( substr($query, -1) == "," )
		{
			$query = substr($query, 0, -1);
		}
		$mysqli->query( $query ) or die("can not insert site in deny table ".$mysqli->error );
		echo json_encode( array("result" => "ok" ));
	}
	else if ( $_POST["action"] == "deleteDenySite")
	{
		$url = $_POST["url"];
		
		$query = "DELETE FROM denySites WHERE url in (";
		
		for ( $i = 0; $i < count( $url ); $i++ )
		{
			if ( $i == ( count( $url ) - 1 ) )
			{
				$query = $query."\"".$url[$i]."\")";
			}
			else
			{
				$query = $query."\"".$url[$i]."\",";
			}
		}
		
		$mysqli->query( $query ) or die("can not delete deny site ".$mysqli->error );
		
		echo json_encode( array("result" => "ok") );
    }
    else if( $_POST["action"] == "getTop" )
    {
        $type = $_POST["type"];
        $count = $_POST["count"];
        $fromDate = $_POST["fromDate"];
        $toDate = $_POST["toDate"];
        $result = getTop( $mysqli, $type, $count, $fromDate, $toDate );
        echo json_encode( array("result" => "ok", "data" => $result) );
    }
	else
	{
		$data = array("result" => "false" );
		echo json_encode( $data );
	}

    function getPatternDetailsByName($mysqli, $name)
    {
        $query = "SELECT name,traffic,access FROM patterns WHERE name= $name";

        $result = $mysqli->query( $query ) or die( "select error" );
        $patternsData = $result->fetch_all( MYSQLI_NUM );
        return $patternsData;
    }

    function getTop($mysqli, $type, $count, $fromDate, $toDate)
    {
        if( !empty($fromDate) && !empty($toDate) )
        {
            $query = "SELECT SUM(bytes) as bytes, $type FROM usersTraffic WHERE dateTime BETWEEN UNIX_TIMESTAMP(STR_TO_DATE('$fromDate', '%d.%m.%Y')) and UNIX_TIMESTAMP(STR_TO_DATE('$toDate', '%d.%m.%Y')) GROUP BY $type ORDER by SUM(bytes) desc LIMIT 0,$count";
        }
        else
        {
            $query = "SELECT SUM(bytes) as bytes, $type FROM usersTraffic WHERE dateTime=UNIX_TIMESTAMP(DATE(NOW())) GROUP BY $type ORDER by SUM(bytes) desc LIMIT 0,$count";
        }

        $result = $mysqli->query( $query ) or die( "select error" );
        $patternsData = $result->fetch_all( MYSQLI_NUM );
        return $patternsData;
    }
?>
