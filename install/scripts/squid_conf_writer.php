<?php

    require_once(__DIR__ . '/../checkconf.php');

    $checkConf = new Config();

    $mode = $checkConf->SquidMode;
	$handler = fopen( "squid.conf", "w" ) or die("can not open squid.conf file \n");
	$config;

	if ( $mode === "transparent" )
    {
        $config = '
            acl all src all
            acl localnet dst '.$checkConf->LocalNet.'

            external_acl_type accessCheck ttl=0 %SRC %URI /usr/bin/php -f /var/www/sldap/checkUser.php
            acl allowUsers external accessCheck

            http_access allow allowUsers
            http_access allow all localnet
            http_access deny all

            deny_info http://'.$checkConf->SquidIP.'/error?message=%o&ip=%i all


            http_port 3129
            http_port 3128 intercept

            visible_hostname squid


            access_log '.$checkConf->SquidLogfile.' squid
            dns_v4_first on
			';
    }
    else
    {
        $config = '
            auth_param basic program /usr/lib/squid3/squid_ldap_auth -R -D '.$checkConf->LdapLogin.' -w '.$checkConf->LdapPassword.' -b "'.$checkConf->LdapDomain.'" -f "sAMAccountName=%s" '.$checkConf->LdapIp.'
            auth_param basic children 5 startup=5 idle=1
            auth_param basic realm Squid proxy-caching web server
            auth_param basic credentialsttl 5 hours

            acl users proxy_auth REQUIRED
            external_acl_type accessCheck ttl=0 %URI %LOGIN python3.4 '.$checkConf->sldapDirectory.'/helper.py
            acl allowUsers external accessCheck

            acl all src 0.0.0.0/0

            http_access allow allowUsers
            http_access deny all
            deny_info http://'.$checkConf->SquidIP.'/error?message=%o&ip=%i all
            http_port 3128

            visible_hostname squid

            access_log '.$checkConf->SquidLogfile.' squid
            dns_v4_first on
            ';
    }

	
	$bytesWritten = fwrite( $handler, trim($config) ) or die("can not write config to squid.conf");

	fclose( $handler );

?>
