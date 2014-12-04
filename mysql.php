<?php 

include 'install/checkconf.php';

    session_start();

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

    header('Content-Type: application/json');
//Начало секции обработки пользовательских запросов

	if ( $_POST["action"] == "auth" )
    {
        $login = $_POST["login"];
        $password = $_POST["password"];

        $statement = $mysqli->prepare("SELECT id FROM admins WHERE login=? AND password=?") or die ( "не удалось подготовить запрос: ".$mysqli->error );
        $statement->bind_param('ss', $login, $password);
        $statement->execute() or die( "не удалось получить данные администратора ".$statement->error );
        $result = $statement->get_result();
        $answer = $result->fetch_row();
        $id = $answer[0];
        if ( !is_null($id) )
        {
            $_SESSION["session"] = session_id();
            $_SESSION["user_id"] = $id;
            header('Location: main.php' );
        }
        else
        {
            header('Location: index.php' );
        }
    }
	else if ( $_POST["action"] == "getMysqlUsers" ) //получаем пользователей и инфу о них из БД
    {
        $result = $mysqli->query("SELECT users.login,users.name,users.trafficForDay,patterns.traffic,patterns.name,patterns.access FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id ORDER BY users.name") or die("can not get users " . $mysqli->error);;

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        } else {
            $data = $result->fetch_all(MYSQLI_NUM);

            $answer = array("result" => $data);
            echo json_encode($answer);
            $result->free();
        }
    }
	else if ( $_POST["action"] == "cleanTraffic" ) //очистка траффика пользователей
	{
        if ( !checkPermissions($mysqli, "editUsers") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

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
		
		echo json_encode( array( "result" => "ok", "message" => "Траффик пользователей успешно очищен." ));
		
	}
	else if ( $_POST["action"] == "addUsers" ) //добавляем пользователей в БД
	{
        if ( !checkPermissions($mysqli, "addUsers") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

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
		$data = array("result" => "ok", "message" => "Выбранные пользователи успешно добавлены." );
		echo json_encode( $data );
		
	}
	else if ( $_POST["action"] == "deleteUsers" ) //удаляем пользователей из БД
	{
        if ( !checkPermissions($mysqli, "deleteUsers") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

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
		$data = array("result" => "ok", "message" => "Выбранные пользователи успешно удалены." );
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
        if ( !checkPermissions($mysqli, "createPatterns") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

		$name = $_POST["name"];
		$traffic = $_POST["traffic"];
		$access = $_POST["access"];
		
		if ( !empty( $name ) && !empty( $traffic ) )
		{
			$query = "INSERT INTO patterns (name,traffic,access) VALUES ('".$name."','".$traffic."','".$access."')";
			$result = $mysqli->query( $query ) or die("insert pattert error");
			
			echo json_encode( array("result"=>"ok", "message" => "Шаблон успешно создан.") );
		}
		else
		{
			echo json_encode( array("result"=>"false", "message" => "Вы заполнили не все поля" ));
		}	
	}
	else if ( $_POST["action"] == "deletePattern" ) //удаляем шаблон из БД
	{

        if ( !checkPermissions($mysqli, "deletePatterns") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

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
		echo json_encode( array( "result" => "ok", "message" => "Шаблон(ы) успешно удален" ));
	}
	else if ( $_POST["action"] == "applyChangesToUsers" ) //применение изменений к пользователям
	{
        if ( !checkPermissions($mysqli, "editUsers") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

		$changes = $_POST["changes"];
		
		for ( $i = 0; $i < count($changes); $i++ )
		{
			$query = "UPDATE users SET pattern_id=\"".$changes[$i][2]."\" WHERE login=\"".$changes[$i][0]."\"";
			$mysqli->query( $query ) or die("cannot update users for changes ".$mysqli->error );
		}	
		echo json_encode( array( "result" => "ok", "message" => "Изменения успешно применены." ));
	}
	else if ( $_POST["action"] == "applyChangesToPatterns" )
	{
        if ( !checkPermissions($mysqli, "editPatterns") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

		$changes = $_POST["changes"];
		
		for ( $i = 0; $i < count($changes); $i++ )
		{
			$query = "UPDATE patterns SET name=\"".$changes[$i][1]."\", traffic=\"".$changes[$i][2]."\", access=\"".$changes[$i][3]."\" WHERE name=\"".$changes[$i][0]."\"";
			$mysqli->query( $query ) or die("can not update patterns: ".$mysqli->error );
		}
		
		echo json_encode( array( "result" => "ok", "message" => "Изменения успешно применены." ) );
	}
	else if ( $_POST["action"] == "getDenySites" )
	{
		$query = $mysqli->query( "SELECT id, url FROM denySites WHERE 1") or die( "can not update patterns ".$mysqli->error );
		$result = $query->fetch_all( MYSQLI_NUM );
		echo json_encode( array("result" => $result ));
	}
	else if( $_POST["action"] == "createDenySite" )
	{
        if ( !checkPermissions($mysqli, "addDenySites") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

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
		echo json_encode( array("result" => "ok", "message" => "Запрещенный сайт(ы) успешно создан(ы)."  ));
	}
	else if ( $_POST["action"] == "deleteDenySite")
	{
        if ( !checkPermissions($mysqli, "deleteDenySites") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

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
		
		echo json_encode( array("result" => "ok", "message" => "Запрещенный сайт(ы) успешно удален(ы)."  ) );
    }
    else if( $_POST["action"] == "editDenySite")
    {
        if ( !checkPermissions($mysqli, "editDenySites") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

        $changes = $_POST["changes"];

        foreach ( $changes as $change)
        {
            applyChangesToDenySites($mysqli, $change[1], $change[0]);
        }
        echo json_encode( array( "result" => "ok", "message" => "Успешное обновление запрещенного(ых) сайта(ов)." ));

    }
    else if( $_POST["action"] == "getTop" )
    {
        $type = $_POST["type"];
        $count = $_POST["count"];
        $fromDate = $_POST["fromDate"];
        $toDate = $_POST["toDate"];
        $login = empty($_POST["login"]) ? "" : $_POST["login"];
        $result = getTop( $mysqli, $type, $count, $fromDate, $toDate, $login );
        echo json_encode( array("result" => "ok", "data" => $result) );
    }
    else if( $_POST["action"] == "showAdmins" )
    {
        $result = showAdmins($mysqli);
        echo json_encode( array("result" => "ok", "data" => $result) );
    }
    else if( $_POST["action"] == "getPermissions" )
    {
        $result = getPermissions($mysqli);
        echo json_encode( array("result" => "ok", "data" => $result) );
    }
    else if( $_POST["action"] == "createAdminAccount" )
    {
        if ( !checkPermissions($mysqli, "createAdmins") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }
        $login = $_POST["login"];
        $password = $_POST["password"];
        $retype_password = $_POST["retype_password"];
        $permission_id = $_POST["permission_id"];

        if( $password != $retype_password )
        {
            echo json_encode( array("result" => "error", "message" => "Введенные пароли не совпадают!") );
        }
        else if( empty($login) or empty($password) or empty($retype_password) or empty($permission_id) )
        {
            echo json_encode( array("result" => "error", "message" => "Вы не заполнили одно из полей!") );
        }
        else
        {
            $result = createAdminAccount($mysqli, $login, $password, $permission_id);
            echo json_encode( array("result" => "ok", "data" => $result, "message" => "Учетная запись администратора успешно создана") );
        }

    }
    else if( $_POST["action"] == "applyChangesToAdmin" )
    {
        if ( !checkPermissions($mysqli, "editAdmins") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

        $changes = $_POST["changes"];

        foreach( $changes as $change )
        {
            $id = $change[0];
            $login = $change[1];
            $password = $change[2];
            $retype_password = $change[3];
            $permission_id = $change[4];

            if( $password != $retype_password )
            {
                echo json_encode( array("result" => "error", "message" => "Введенные пароли не совпадают!") );
            }
            else if( empty($login) or empty($password) or empty($retype_password) or empty($permission_id) )
            {
                echo json_encode( array("result" => "error", "message" => "Вы не заполнили одно из полей!") );
            }
            else
            {
                applyChangesToAdmin($mysqli, $login, $password, $permission_id, $id);
                echo json_encode( array("result" => "ok", "data" => $result) );
            }
        }
    }
    else if( $_POST["action"] == "getPermissionPatterns" )
    {
        $query = "SELECT * FROM permissions WHERE 1";

        $result = $mysqli->query( $query ) or die( "select error" );
        $patternsData = $result->fetch_assoc();
        echo json_encode( array("result" => "ok", "data" => $patternsData) );
    }
    else if( $_POST["action"] == "getPermissionsById" )
    {
        $id = $_POST["id"];
        $statement = $mysqli->prepare("SELECT * FROM permissions WHERE id=?") or die ( "не удалось подготовить запрос: ".$mysqli->error );
        $statement->bind_param('i', $id);
        $statement->execute() or die( "не удалось получить шаблоны прав по id ".$statement->error );
        $result = $statement->get_result();
        $permissions = $result->fetch_assoc();

        echo json_encode( array("result" => "ok", "data" => $permissions) );
    }
    else if( $_POST["action"] == "applyChangesToPermissions" )
    {
        if ( !checkPermissions($mysqli, "editPermissions") )
        {
            echo json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            return;
        }

        $columns = [];
        $values = [];

        $id = $_POST["id"];
        $name = $_POST["name"];
        $permissions = $_POST["permissions"];
        foreach ( $permissions as $row )
        {
            $column = $row[0];
            $value = ( $row[1] == "true" ? 1 : 0);
            array_push( $columns, $column);
            array_push( $values, $value);
        }

        if ( !empty( $id ) && empty( $name ) )
        {
            $query = "";
            for ( $i=0; $i < count( $columns ); $i++ )
            {
                if ( $i == count( $columns )-1 )
                {
                    $query = $query." ".$columns[$i]."=".$values[$i];
                }
                else
                {
                    $query = $query." ".$columns[$i]."=".$values[$i].",";
                }

            }
            $statement = $mysqli->prepare("UPDATE permissions SET ". $query ." WHERE id=?") or die ( "не удалось подготовить запрос: ".$mysqli->error );

            $statement->bind_param('i', $id );
            $statement->execute() or die( "не удалось выполнить обновление учетной(учетных) записи(записей) администратора ".$statement->error );
        }
        else if ( empty( $id ) && !empty( $name ) )
        {
            $statement = $mysqli->prepare("INSERT INTO permissions (name,". implode(",",$columns) .") VALUES(?,".implode(",",$values).")") or die ( "не удалось подготовить запрос: ".$mysqli->error );
            $statement->bind_param('s', $name);
            $statement->execute() or die( "не удалось выполнить обновление учетной(учетных) записи(записей) администратора ".$statement->error );
        }

        echo json_encode( array("result" => "ok", "message" => "Успешно внесены изменения шаблона прав" ) );
    }
	else
	{
		$data = array("result" => "error", "message" => "Запрошенного действия не существует." );
		echo json_encode( $data );
	}

    function getExistUsers($mysqli)
    {
        $result = $mysqli->query("SELECT users.login FROM users") or die("can not get exist users " . $mysqli->error);;

        if ($mysqli->connect_errno) {
            echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
        } else {
            $data = $result->fetch_all(MYSQLI_NUM);
            $result->free();
            return $data;
        }
    }

    function getPatternDetailsByName($mysqli, $name)
    {
        $query = "SELECT name,traffic,access FROM patterns WHERE name= $name";

        $result = $mysqli->query( $query ) or die( "select error" );
        $patternsData = $result->fetch_all( MYSQLI_NUM );
        return $patternsData;
    }

    function getTop($mysqli, $type, $count, $fromDate, $toDate, $login)
    {
        $whereLogin = "";
        if ( !empty($login) )
        {
            $whereLogin = "and login = '$login'";
        }

        if( !empty($fromDate) && !empty($toDate) )
        {
            $query = "SELECT SUM(bytes) as bytes, $type FROM usersTraffic WHERE dateTime BETWEEN UNIX_TIMESTAMP(STR_TO_DATE('$fromDate', '%d.%m.%Y')) and UNIX_TIMESTAMP(STR_TO_DATE('$toDate', '%d.%m.%Y')) $whereLogin GROUP BY $type ORDER by SUM(bytes) desc LIMIT 0,$count";
        }
        else
        {
            $query = "SELECT SUM(bytes) as bytes, $type FROM usersTraffic WHERE ( dateTime BETWEEN UNIX_TIMESTAMP(CURDATE() - interval 1 day) AND UNIX_TIMESTAMP(CURDATE() + interval 1 day) ) $whereLogin GROUP BY $type ORDER by SUM(bytes) desc LIMIT 0,$count";
        }

        $result = $mysqli->query( $query ) or die( "select error\n".$mysqli->error );
        $patternsData = $result->fetch_all( MYSQLI_NUM );
        return $patternsData;
    }

    function showAdmins($mysqli)
    {
        $query = "SELECT admins.id, admins.login, permissions.name, permissions.id FROM admins LEFT JOIN permissions ON admins.permission_id = permissions.id";
        $result = $mysqli->query( $query ) or die( $mysqli->error." select error" );
        $admins = $result->fetch_all( MYSQLI_NUM );
        return $admins;
    }

    function getPermissions($mysqli)
    {
        $query = "SELECT id, name FROM permissions";
        $result = $mysqli->query( $query ) or die( $mysqli->error." select error" );
        $permissions = $result->fetch_all( MYSQLI_NUM );
        return $permissions;
    }

    function createAdminAccount($mysqli, $login, $password, $permission_id)
    {
        $statement = $mysqli->prepare("INSERT INTO admins (login, password, permission_id) VALUES (?, ?, ?)") or die ( "не удалось подготовить запрос: ".$mysqli->error );
        $statement->bind_param('ssi', $login, $password, $permission_id);
        $statement->execute() or die( "не удалось выполнить добавление учетной записи администратора ".$statement->error );
    }

    function applyChangesToAdmin($mysqli, $login, $password, $permission_id, $id)
    {
        $statement = $mysqli->prepare("UPDATE admins SET login=?, password=?, permission_id=? WHERE id=?") or die ( "не удалось подготовить запрос: ".$mysqli->error );
        $statement->bind_param('ssii', $login, $password, $permission_id, $id);
        $statement->execute() or die( "не удалось выполнить обновление учетной(учетных) записи(записей) администратора ".$statement->error );
    }

    function applyChangesToDenySites($mysqli, $url, $id)
    {
        $statement = $mysqli->prepare("UPDATE denySites SET url=? WHERE id=?") or die ( "не удалось подготовить запрос: ".$mysqli->error );
        $statement->bind_param('si', $url, $id);
        $statement->execute() or die( "не удалось выполнить обновление запрещенного(ых) сайта(ов) ".$statement->error );
    }

    function checkPermissions($mysqli, $permission)
    {
        if ( $permission == "addUsers" or
            $permission == "editUsers" or
            $permission == "deleteUsers" or
            $permission == "createPatterns" or
            $permission == "editPatterns" or
            $permission == "deletePatterns" or
            $permission == "addDenySites" or
            $permission == "editDenySites" or
            $permission == "deleteDenySites" or
            $permission == "createAdmins" or
            $permission == "editAdmins" or
            $permission == "deleteAdmins" or
            $permission == "createPermissions" or
            $permission == "editPermissions" or
            $permission == "deletePermissions"
        )
        {
            $query = "SELECT ".$permission." FROM permissions LEFT JOIN admins ON (permissions.id = admins.permission_id) WHERE admins.id = ".$_SESSION["user_id"];
            $result = $mysqli->query( $query ) or die( $mysqli->error." | select permission error" );
            $access = $result->fetch_row();

            $answer = $access[0] == 1 ? true : false;
            return $answer;
        }
        else
        {
            return "Запрошенных прав не существует";
        }
    }
?>
