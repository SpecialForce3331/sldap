<?php 
	
//--------------------------------настройки для работы с БД--------------------------------
	
	//Данные для подключения к БД
	
	
	include 'install/checkconf.php';
	
	$server = $MysqlIp;
	$username = $MysqlLogin;
	$password = $MysqlPassword;
	$database = $MysqlDatabase;
	$pathLog = $SquidLogfile;

	
	$mysqli = new mysqli( $server, $username, $password, $database ); //Устанавливаем соединение в базой мускула
	
	if ( $mysqli->connect_errno )
	{
		echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; //Не удалось установить соединение с базой мускула.
	}
	else
	{
		$mysqli->set_charset("utf8"); //Устанавливаем принудительно кодировку в UTF-8!
	}
//--------------------------------конец настройки--------------------------------
	echo "success connect to DB \n";
	
	
	
//----------------------------Получаем логины из БД--------------------------------
	$result = $mysqli->query( "SELECT login FROM users WHERE 1 " );
	$logins = $result->fetch_all( MYSQLI_NUM );

//--------------------------------закончили--------------------------------



	echo "start parse the logfile \n";
//--------------------------------Парсим лог --------------------------------

	$handler = fopen( $pathLog, "r" );

	$traffic = array();

	while( ( $temp = fgets( $handler ) ) !== false )
	{
		$parse = split("[ ]+", $temp ); //разбираем строку из файла лога на состовляющие

		for ( $i = 0; $i < count( $logins ); $i++ ) //для каждого логина в массиве проверяем текущую строку на совпадение логина
		{
			if ( strcasecmp($parse[7], $logins[$i][0]) == 0 )
			{
				array_push( $traffic, array( $parse[7], $parse[6], $parse[4], $parse[0] ) );
			}
		}
	}

	fclose( $handler );
//--------------------------------хватит парсить лог--------------------------------


	echo "parse data and add to DB \n";
//--------------------------------Обработка данных--------------------------------

	if ( count( $traffic ) > 0 )//если есть данные, разбираем на логин, сайт, байты, время и пишем в БД
	{
		$query = "SELECT dateTime FROM usersTraffic ORDER BY id DESC LIMIT 1";
		$result = $mysqli->query($query);
		$lastDate = $result->fetch_row();
		$lastDate = $lastDate[0]; //получаем дату последнего обновления траффика в базе

		$query = "INSERT INTO usersTraffic ( login, cite, bytes, dateTime ) VALUES "; //первая часть запроса, которая не изменится
		$firstQuery = $query;

		for ( $i = 0; $i < count( $traffic ); $i++ )
		{
			if ( $traffic[$i][3] > $lastDate ) //проверяем, чтобы траффик не повторялся
			{
				$query = $query."(\"".$traffic[$i][0]."\",\"".$traffic[$i][1]."\",\"".$traffic[$i][2]."\",\"".$traffic[$i][3]."\"),";
			}
		}
		if( $firstQuery != $query )
		{
			if ( substr($query, -1) == "," )
			{
				$query = substr($query, 0, -1);
			}
			$mysqli->query( $query );
		}

	}

	echo "count traffic for user \n";
//-------------------------------подсчитываем траффик за день для каждого пользователя----------------------------
	for( $i = 0; $i < count( $logins ); $i++ )
	{
		$query = $mysqli->query( "SELECT lastUpdate,trafficForDay FROM users WHERE login=\"".$logins[$i][0]."\"" );
		$result = $query->fetch_row();
		$lastUpdate = $result[0];
		$currentTraffic = $result[1];

		$query = $mysqli->query("SELECT SUM(bytes) FROM usersTraffic WHERE dateTime > \"".$lastUpdate."\" AND login =\"".$logins[$i][0]."\"");
		$result = $query->fetch_row();
		$traffic = round( $result[0]/1048576, 2 ) + $currentTraffic;

		if ( $traffic != 0 )
		{
			$query = $mysqli->query( "SELECT dateTime FROM usersTraffic WHERE login=\"".$logins[$i][0]."\" ORDER BY id DESC LIMIT 1" );
			$result = $query->fetch_row();
			$currentUpdate = $result[0];

			$mysqli->query("UPDATE users SET trafficForDay=\"".$traffic."\", lastUpdate= \"".$currentUpdate."\" WHERE login =\"".$logins[$i][0]."\"");
		}

//-----------Очищаем суточный траффик-------------
		$query = $mysqli->query( "UPDATE users SET trafficForDay=\"0\" WHERE CURTIME() >= \"23:58:00\" ");

	}
	echo "finish \n";
?>