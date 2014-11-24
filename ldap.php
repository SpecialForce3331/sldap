<?php 

// $server = "192.168.2.210";
// $login = "squid@akvnzm.ru";
// $password = "123456";
putenv('LDAPTLS_REQCERT=never');
include 'install/checkconf.php';

$server = "ldaps://".$LdapIp."/";
$login = $LdapLogin;
$password = $LdapPassword;
//$domain = "OU=users,OU=УОМР,OU=БирскийТракт,OU=VPN,OU=offices,OU=Users,OU=Location of resources,DC=akvnzm,DC=ru";
$domain = $LdapDomainContainer;

$ldapConn = ldap_connect( $server );
ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

if ( $_POST["action"] == "getLdapUsers" )
{
	if ( $ldapConn )
	{		
		$ldapBind = ldap_bind( $ldapConn, $login, $password );
		
		if ( $ldapBind )
		{	
			
			$filter ="(&(!(objectclass=computer))(!(objectclass=group))(objectCategory=person)(objectclass=user)(cn=*)";
			
			if ( isset($_POST["existUsers"]) && count( $_POST["existUsers"] ) > 0 )
			{
				$existUsers = $_POST["existUsers"];
					
				for ( $i = 0; $i < count($existUsers); $i++ )
				{
					if ( $i == ( count($existUsers) -1 ) )
					{
						$filter = $filter."(!(sAMAccountName=".$existUsers[$i][0]."))";
						$filter = $filter.")";
					}
					else
					{
						$filter = $filter."(!(sAMAccountName=".$existUsers[$i][0]."))";
					}
				
				}
			}
			else
			{
				$filter ="(&(!(objectclass=computer))(!(objectclass=group))(objectCategory=person)(objectclass=user)(cn=*))";
			}
			
			$attribute =  array("samAccountName"); 

			$ldapSearch = ldap_search($ldapConn, $domain, $filter, $attribute);
			$result = ldap_get_entries( $ldapConn, $ldapSearch );
	
			if ( $result != FALSE )
			{
				echo json_encode( array( "result" => $result ));
			}
			else
			{
				echo json_encode( array( "result" => $result ) );
			}
		}
	
		ldap_unbind($ldapConn);
	}
	else
	{
		echo json_encode( array( "result" => "false" ) );
	}
}

?>
