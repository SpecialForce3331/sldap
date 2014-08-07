<?php 

	include 'install/checkconf.php';

    $mode = $SquidMode;
	$handler = fopen( "squid.conf", "w" ) or die("can not open squid.conf file \n");
	$config;

	if ( $mode === "transparent" )
    {
        $config = '
            acl all src all
            acl localnet dst '.$LocalNet.'

            external_acl_type accessCheck ttl=0 %SRC %URI /usr/bin/php -f /var/www/sldap/checkUser.php
            acl allowUsers external accessCheck

            http_access allow allowUsers
            http_access allow all localnet
            http_access deny all

            deny_info http://'.$SquidIP.'/sldap/error_page.php?status=%o&ip=%i all


            http_port 3129
            http_port 3128 intercept

            visible_hostname squid


            access_log '.$SquidLogfile.' squid
			';
    }
    else
    {
        $config = '
            auth_param basic program /usr/lib/squid3/squid_ldap_auth -R -D '.$LdapLogin.' -w '.$LdapPassword.' -b "'.$LdapDomain.'" -f "sAMAccountName=%s" '.$LdapIp.'
            auth_param basic children 5 startup=5 idle=1
            auth_param basic realm Squid proxy-caching web server
            auth_param basic credentialsttl 2 hours

            acl users proxy_auth REQUIRED
            external_acl_type accessCheck ttl=0 %LOGIN %URI php -f '.$sldapDirectory.'/checkUser.php
            acl allowUsers external accessCheck

            acl all src 0.0.0.0/0

            http_access allow allowUsers
            http_access deny all

            http_port 3128

            visible_hostname squid

            access_log '.$SquidLogfile.' squid
            ';
    }

	
	$bytesWritten = fwrite( $handler, trim($config) ) or die("can not write config to squid.conf");

	fclose( $handler );

?>