<?php
/**
 * Created by PhpStorm.
 * User: sizov
 * Date: 7/23/14
 * Time: 7:49 PM
 */

    include 'install/checkconf.php';

    $ldap_server = $LdapIp;
    $domain = $DomainPrefix;

    function ldapAuth($ldap_server, $login, $password)
    {
        $ldapConn = ldap_connect( $ldap_server ) or die("can't connect to ldap server ".$ldap_server);
        ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

        if ( $ldapConn )
        {
            $ldapBind = ldap_bind( $ldapConn, $login, $password ) or die("can't auth with: ".$ldapConn." ".$login." ".$password);

            if ( $ldapBind )
            {
                error_log("auth succefull");
                ldap_close($ldapConn);
                return true;
            }
        }
        return false;
    }

    //Работа с БД

    //Данные для подключения к БД
    $mysqlServer = $MysqlIp;
    $mysqlUsername = $MysqlLogin;
    $mysqlPassword = $MysqlPassword;
    $mysqlDatabase = $MysqlDatabase;

    $mysqli = new mysqli( $mysqlServer, $mysqlUsername, $mysqlPassword, $mysqlDatabase ); //Устанавливаем соединение в базой мускула

    if ( $mysqli->connect_errno )
    {
        echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; //Не удалось установить соединение с базой мускула.
    }
    else
    {
        $mysqli->set_charset("utf8"); //Устанавливаем принудительно кодировку в UTF-8!
    }

    if ( !empty( $_POST["login"] ) && !empty( $_POST["password"] ) )
    {
        $login = trim( $_POST["login"] );
        $password = trim( $_POST["password"] );
        $ip = trim($_POST["ip"], "/");

        if ( ldapAuth($ldap_server, $login.$domain, $password) )
        {
            $query = "UPDATE `users` SET `ip` = INET_ATON('".$ip."') WHERE `login`='".$login."'";
            $result = $mysqli->query( $query ) or die("insert ip error");
            header('Location: /sldap/error_page.php?message=Теперь вы можете пользоваться интернетом.');
        }
        else
        {
            header('Location: /sldap/error_page.php?status=auth&ip='.$ip.'&message=Вы не верно ввели логин или пароль.');
        }

    }
    else
    {
        header('Location: /sldap/error_page.php?status=auth&ip='.$ip.'&message=Вы не верно ввели логин или пароль.');
    }


?>