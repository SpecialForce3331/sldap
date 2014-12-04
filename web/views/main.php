<?php 
	
	$login = "root";
	$password = "qwerty";
	
	$redirect = "index.html";
	
	session_start();

	if( isset( $_SESSION["session"] ) )
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
					<script type="text/javascript" src="../../js/main.js"></script>
					<script type="text/javascript" src="../../js/jquery-1.11.1.min.js"></script>
					<script type="text/javascript" src="../../jquery-ui-1.11.1.custom/jquery-ui.js"></script>
					<script type="text/javascript" src="../../js/datepicker.ru.js"></script>
                    <script type="text/javascript" src="../../DataTables-1.10.0/media/js/jquery.dataTables.min.js"></script>

					<link rel="stylesheet" href="../../jquery-ui-1.11.1.custom/jquery-ui.css" />
					<link rel="stylesheet" href="../../jquery-ui-1.11.1.custom/jquery-ui.theme.css" />
					<link rel="stylesheet" href="../../DataTables-1.10.0/media/css/jquery.dataTables.css" />
					<link rel="stylesheet" href="../../css/main.css" />
				</head>
		
				<body>
				
					<ul id="selectable-menu">
						<li class="ui-widget-content">
							<div class="cancel" onclick="getMysqlUsers()">Пользователи</div>
						</li>
						<li class="ui-widget-content">
							<div onclick="getLdapUsers()">Добавить пользователей</div>
						</li>
						<li class="ui-widget-content">
							<div onclick="getDenySites()">Список запрещенных сайтов</div>
						</li>
						<li class="ui-widget-content">
							<div onclick="getPatterns()">Шаблоны</div>
						</li>
						<li class="ui-widget-content">
							<div onclick="showStatistic()">Статистика</div>
						</li>
						<li class="ui-widget-content">
							<div onclick="showPreferences()">Настройки</div>
						</li>
					</ul>

					<div id="main"></div>
					<div id="panel"></div>

					<script type>
                        $(function() {
                                $( "#selectable-menu" ).selectable();
                                $( "#selectable-menu" ).on( "selectableselected", function( event, ui ) {
                                    $("li.ui-selected").children().click();
                                } );
                            });
                        </script>
				</body>
			</html>';
	}
?>