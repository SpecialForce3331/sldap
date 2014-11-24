<?php 

	//создаем необходимые переменные
	$LdapIp;
	$LdapLogin;
	$LdapPassword;
	$LdapDomainContainer;
	$LdapDomain;

    $DomainPrefix;
    $LocalNet;
    $SquidIP;

    $MysqlRootLogin;
    $MysqlRootPassword;
	$MysqlLogin;
	$MysqlPassword;
	$MysqlIp;
	$MysqlDatabase;

    $SquidMode;
	$SquidLogfile;
	
	//в зависимости от директории из которой инклудят этот файл, определяем директорию.
	$path = explode( "/", getcwd() );

	while( end( $path ) !== "sldap" )
	{
		chdir( "../" );
		$path = explode( "/", getcwd() );

	}
		
	$sldapDirectory = getcwd();//текущая директория
	
	
	
	//директория определена, читаем конфигурационный файл и пишем в переменные
	$handler = fopen( $sldapDirectory."/install/config.cfg", "r" ) or die("can not open file");

	while ( $buffer = fgets( $handler ) )
	{
		if ( $buffer != false )
		{
			$result = explode( " : ", $buffer );

			if( count( $result ) >= 2  )
			{
                $key = trim($result[0]);
                $value = trim($result[1]);
                
				if( trim($key) === "LDAP_ip" )
				{
					$LdapIp = trim($value);
				}
				else if( trim($key) === "LDAP_login" )
				{
					$LdapLogin = trim($value);
				}
				else if( trim($key) === "LDAP_password" )
				{
					$LdapPassword = trim($value);
				}
				else if( trim($key) === "LDAP_container" )
				{
					$LdapDomainContainer = trim( $value );
				}
				else if( trim($key) === "LDAP_domain" )
				{
					$LdapDomain = trim( $value );
				}
                else if( trim($key) === "Domain_prefix" )
                {
                    $DomainPrefix = trim( $value );
                }
                else if( trim($key) === "LocalNet" )
                {
                    $LocalNet = trim( $value );
                }
                else if( trim($key) === "Squid_IP" )
                {
                    $SquidIP = trim( $value );
                }
                else if( trim($key) === "MYSQL_ROOT_login" )
                {
                    $MysqlRootLogin = trim( $value );
                }
                else if( trim($key) === "MYSQL_ROOT_password" )
                {
                    $MysqlRootPassword = trim( $value );
                }
				else if( trim($key) === "MYSQL_login" )
				{
					$MysqlLogin = trim( $value );
				}
				else if( trim($key) === "MYSQL_password" )
				{
					$MysqlPassword = trim( $value );
				}
				else if( trim($key) === "MYSQL_ip" )
				{
					$MysqlIp = trim( $value );
				}
				else if ( trim($key) === "MYSQL_database" )
				{
					$MysqlDatabase = trim( $value );
				}
                else if ( trim($key) === "SQUID_mode")
                {
                    $SquidMode = trim( $value );
                }
				else if ( trim($key) === "SQUID_logfile" )
				{
					$SquidLogfile = trim( $value );
				}
				
			}
			
		}
	}
	
	fclose($handler);
	
?>