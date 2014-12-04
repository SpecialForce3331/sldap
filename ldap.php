<?php 

putenv('LDAPTLS_REQCERT=never');
include 'install/checkconf.php';

$server = "ldaps://".$LdapIp."/";
$login = $LdapLogin;
$password = $LdapPassword;
$domain = $LdapDomainContainer;
$groupGUID = $LdapGroupGUID;

$ldapConn = ldap_connect( $server );
ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);

if ( $_POST["action"] == "getLdapUsers" )
{
	if ( $ldapConn )
	{
		$ldapBind = ldap_bind( $ldapConn, $login, $password );
        
        $groupFilter = "(&(objectGUID=".$groupGUID."))";
        $ldapSearch = ldap_search($ldapConn, $domain, $groupFilter, array("distinguishedName"));
        $groupResult = ldap_get_entries( $ldapConn, $ldapSearch );
        $group = $groupResult[0]["distinguishedname"][0];

        $filter ="(&(memberof=".$group.")(cn=*)";

		if ( $ldapBind )
		{
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
                $filter = $filter.")";
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
