<?php 
		
	echo '<html>
			<head>
				<meta charset="UTF-8">
				<link rel="stylesheet" href="css/index.css" />
			</head>
			
				<body>
					<center>
						<form action="mysql.php" method="POST">
						    <input type="text" style="display: none" name="action" value="auth"/></br>
							<span>Login</span><br><input name="login" type="text" /><br>
							<span>Password</span><br><input name="password" type="password" /><br>
							<input type="submit" value="Вход" />
						</form>
					</center>
				</body>
			
			</html>';

?>
