<?php 
	
	$login = "root";
	$password = "qwerty";
	
	$redirect = "index.php";
	
	session_start();
	
	if ( !empty( $_POST["login"] ) && $_POST["login"] == $login && $_POST["password"] == $password )
	{	
		$_SESSION["session"] = session_id();
		showContent();
	}
	else if( isset( $_SESSION["session"] ) )
	{	
		showContent();
	}
	else
	{
		header('Location: '.$redirect );
	}

	function showContent()
	{
		echo '<html>
				<head>
					<meta charset="UTF-8">
					<script type="text/javascript" src="js/main.js"></script>
					<script type="text/javascript" src="js/jquery-1.8.3.js"></script>
					<script type="text/javascript" src="bootstrap/js/bootstrap.js"></script>
					<script type="text/javascript" src="DataTables-1.10.0/media/js/jquery.dataTables.js"></script>
					<link rel="stylesheet" href="bootstrap/css/bootstrap.css" />
					<link rel="stylesheet" href="DataTables-1.10.0/media/css/jquery.dataTables.css" />
					<link rel="stylesheet" href="css/main.css" />
				</head>
		
				<body>
				
					<ul class="nav nav-pills nav-stacked">
						<li>
							<a href=#><div onclick="getMysqlUsers()">Пользователи</div></a>
						</li>
						<li>
							<a href=#><div onclick="getLdapUsers()">Добавить пользователей</div></a>
						</li>
						<li>
							<a href=#><div onclick="getDenySites()">Список запрещенных сайтов</div></a>
						</li>
						<li>
							<a href=#><div onclick="getPatterns()">Шаблоны</div></a>
						</li>
						<li>
							<a href=#><div>Настройки</div></a>
						</li>
					</ul>
				
					<div id="main"></div>
					<div id="panel"></div>
					
				</body>
			</html>';
	}
?>
