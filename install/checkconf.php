<?php 

    class CheckConf
    {
        //создаем необходимые переменные
        public $LdapIp;
        public $LdapLogin;
        public $LdapPassword;
        public $LdapDomainContainer;
        public $LdapDomain;
        public $LdapGroupGUID;

        public $DomainPrefix;
        public $LocalNet;
        public $SquidIP;

        public $MysqlRootLogin;
        public $MysqlRootPassword;
        public $MysqlLogin;
        public $MysqlPassword;
        public $MysqlIp;
        public $MysqlDatabase;

        public $SquidMode;
        public $SquidLogfile;

        private $parsedVars;

        public function __construct()
        {
            $this->$parsedVars = [];
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
                            $this->$LdapIp = trim($value);
                            array_push($this->$LdapIp, $LdapIp);
                        }
                        else if( trim($key) === "LDAP_login" )
                        {
                            $this->$LdapLogin = trim($value);
                            array_push($this->$LdapLogin, $LdapIp);
                        }
                        else if( trim($key) === "LDAP_password" )
                        {
                            $this->$LdapPassword = trim($value);
                            array_push($this->$LdapPassword, $LdapIp);
                        }
                        else if( trim($key) === "LDAP_container" )
                        {
                            $this->$LdapDomainContainer = trim( $value );
                            array_push($this->$LdapDomainContainer, $LdapIp);
                        }
                        else if( trim($key) === "LDAP_domain" )
                        {
                            $this->$LdapDomain = trim( $value );
                            array_push($this->$LdapDomain, $LdapIp);
                        }
                        else if ( trim($key) === "LDAP_group_guid" )
                        {
                            $this->$LdapGroupGUID = trim( $value );
                            array_push($this->$LdapGroupGUID, $LdapIp);
                        }
                        else if( trim($key) === "Domain_prefix" )
                        {
                            $this->$DomainPrefix = trim( $value );
                            array_push($this->$DomainPrefix, $LdapIp);
                        }
                        else if( trim($key) === "LocalNet" )
                        {
                            $this->$LocalNet = trim( $value );
                            array_push($this->$LocalNet, $LdapIp);
                        }
                        else if( trim($key) === "Squid_IP" )
                        {
                            $this->$SquidIP = trim( $value );
                            array_push($this->$SquidIP, $LdapIp);
                        }
                        else if( trim($key) === "MYSQL_ROOT_login" )
                        {
                            $this->$MysqlRootLogin = trim( $value );
                            array_push($this->$MysqlRootLogin, $LdapIp);
                        }
                        else if( trim($key) === "MYSQL_ROOT_password" )
                        {
                            $this->$MysqlRootPassword = trim( $value );
                            array_push($this->$MysqlRootPassword, $LdapIp);
                        }
                        else if( trim($key) === "MYSQL_login" )
                        {
                            $this->$MysqlLogin = trim( $value );
                            array_push($this->$MysqlLogin, $LdapIp);
                        }
                        else if( trim($key) === "MYSQL_password" )
                        {
                            $this->$MysqlPassword = trim( $value );
                            array_push($this->$MysqlPassword, $LdapIp);
                        }
                        else if( trim($key) === "MYSQL_ip" )
                        {
                            $this->$MysqlIp = trim( $value );
                            array_push($this->$MysqlIp, $LdapIp);
                        }
                        else if ( trim($key) === "MYSQL_database" )
                        {
                            $this->$MysqlDatabase = trim( $value );
                            array_push($this->$MysqlDatabase, $LdapIp);
                        }
                        else if ( trim($key) === "SQUID_mode")
                        {
                            $this->$SquidMode = trim( $value );
                            array_push($this->$SquidMode, $LdapIp);
                        }
                        else if ( trim($key) === "SQUID_logfile" )
                        {
                            $this->$SquidLogfile = trim( $value );
                            array_push($this->$SquidLogfile, $LdapIp);
                        }

                    }

                }
            }

            fclose($handler);

            if ( count($this->parsedVars) > 1 )
            {

            }
        }

    }
?>