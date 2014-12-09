<?php 

    putenv('LDAPTLS_REQCERT=never');

    class Ldap
    {
        private $server;
        private $login;
        private $password;
        private $domain;
        private $groupGUID;
        private $ldapConn;
        private $ldapBind;

        function __construct($config)
        {
            $this->server = "ldaps://".$config->LdapIp."/";
            $this->login = $config->LdapLogin;
            $this->password = $config->LdapPassword;
            $this->domain = $config->LdapDomain;
            $this->groupGUID = $config->LdapGroupGUID;

            $this->ldapConn = ldap_connect( $this->server );
            ldap_set_option( $this->ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
            ldap_set_option( $this->ldapConn, LDAP_OPT_REFERRALS, 0);

            $this->ldapBind = ldap_bind( $this->ldapConn, $this->login, $this->password );
        }

        public function getLdapUsers($existUsers)
        {
            $groupFilter = "(&(objectGUID=".$this->groupGUID."))";
            $ldapSearch = ldap_search($this->ldapConn, $this->domain, $groupFilter, array("distinguishedName"));
            $groupResult = ldap_get_entries( $this->ldapConn, $ldapSearch );
            $group = $groupResult[0]["distinguishedname"][0];

            $filter ="(&(memberof=".$group.")(cn=*)";

            if ( $this->ldapBind )
            {
                if ( !empty($existUsers) && count( $existUsers ) > 0 )
                {
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

                $ldapSearch = ldap_search($this->ldapConn, $this->domain, $filter, $attribute);
                $result = ldap_get_entries( $this->ldapConn, $ldapSearch );

                if ( $result != FALSE )
                {
                    return json_encode( array( "result" => $result ));
                }
                else
                {
                    return json_encode( array( "result" => $result ) );
                }
            }
        }

        public function ldapAuth($login, $password)
        {
            $ldapConn = ldap_connect( $this->server );
            ldap_set_option( $ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
            ldap_set_option( $ldapConn, LDAP_OPT_REFERRALS, 0);

            $ldapBind = ldap_bind( $this->ldapConn, $login, $password ) or die("can't auth with: ".$this->ldapConn." ".$login." ".$password);

            if ( $ldapBind )
            {
                ldap_close($ldapConn);
                return true;
            }
            else
            {
                ldap_close($ldapConn);
                return false;
            }
        }

        function __destruct()
        {
            ldap_close($this->ldapConn);
        }
    }

?>
