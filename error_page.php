<?php
/**
 * Created by PhpStorm.
 * User: sizov
 * Date: 7/22/14
 * Time: 10:09 PM
 */
    include 'install/checkconf.php';

    if ( $_GET["status"] == "auth")
    {
        echo "<form action='auth.php' method='POST'>";
        echo "Логин без ".$DomainPrefix.":<input name='login' type='text'/>";
        echo "Пароль:<input name='password' type='password'/>";
        echo "Пароль:<input name='ip' type='hidden' value=".$_GET['ip']."/>";
        echo "<input type='submit'/>";
        echo "</form>";
    }
    $message = "";
    if ( $_GET["message"] == "user_not_exist" )
    {
        $message = "Пользователя с таким логином не существует, обратитесь к системному администратору";
    }
    elseif( $_GET["message"] == "traffic_limit" )
    {
        $message = "Вы превысили лимит по траффику";
    }
    elseif( $_GET["message"] == "deny_site" )
    {
        $message = "Доступ к данному сайту заблокирован администратором";
    }
    echo "
    <html>
        <head>
            <meta charset='UTF-8'/>
        </head>
        <body>
            <div align='center'>".$message."</div>
        </body>
    </html>";
?>