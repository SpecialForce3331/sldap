<?php 

    putenv('LDAPTLS_REQCERT=never');

    class Ldap
    {
        private $server;
        private $login;
        private $password;
        private $domain;
        private $groupUserGUID;
        private $groupAdminGUID;
        private $ldapConn;
        private $ldapBind;
        private $DomainPrefix;

        function __construct($config)
        {
            $this->server = "ldaps://".$config->LdapIp."/";
            $this->login = $config->LdapLogin;
            $this->password = $config->LdapPassword;
            $this->domain = $config->LdapDomain;
            $this->groupUserGUID = $config->LdapGroupGUID;
            $this->groupAdminGUID = $config->LdapGroupAdminGUID;
            $this->DomainPrefix = $config->DomainPrefix;

            $this->ldapConn = ldap_connect( $this->server );
            ldap_set_option( $this->ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3 );
            ldap_set_option( $this->ldapConn, LDAP_OPT_REFERRALS, 0);

            $this->ldapBind = ldap_bind( $this->ldapConn, $this->login, $this->password );
        }

        public function getLdapUsers($existUsers, $type)
        {
            $groupGUID = $type === "users" ? $this->groupUserGUID : $this->groupAdminGUID;

            $groupFilter = "(&(objectGUID=".$groupGUID."))";
            $ldapSearch = ldap_search($this->ldapConn, $this->domain, $groupFilter, array("distinguishedName"));
            $groupResult = ldap_get_entries( $this->ldapConn, $ldapSearch );
            $group = $groupResult[0]["distinguishedname"][0];

            $filter ="(&(!(objectclass=computer))(!(objectclass=group))(memberof:1.2.840.113556.1.4.1941:=".$group.")(cn=*)";

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
                    return json_encode( array( "result" => "false" ));
                }
            }
        }

        public function ldapAdminAuth($login, $password)
        {
            $ldapBind = ldap_bind($this->ldapConn, $login.$this->DomainPrefix, $password);

            $groupGUID = $this->groupAdminGUID;

            $groupFilter = "(&(objectGUID=".$groupGUID."))";
            $ldapSearch = ldap_search($this->ldapConn, $this->domain, $groupFilter, array("distinguishedName"));
            $groupResult = ldap_get_entries( $this->ldapConn, $ldapSearch );
            $group = $groupResult[0]["distinguishedname"][0];

            //1.2.840.113556.1.4.1941 - recurcive flag search
            $filter ="(&(!(objectclass=computer))(!(objectclass=group))(memberof:1.2.840.113556.1.4.1941:=".$group.")(cn=*))";

            if ( $ldapBind )
            {
                $attribute =  array("samAccountName");

                $ldapSearch = ldap_search($this->ldapConn, $this->domain, $filter, $attribute);
                $result = ldap_get_entries( $this->ldapConn, $ldapSearch );

                foreach( $result as $sam )
                {
                    $sam = $sam["samaccountname"][0];
                    if ( strtolower($sam) === strtolower($login) )
                    {
                        return true;
                    }
                }
                return false;
            }
            else
            {
                return false;
            }
        }

        function __destruct()
        {
            ldap_close($this->ldapConn);
        }
    }

?>
