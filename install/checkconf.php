<?php 

require_once(__DIR__ . '/../Exceptions/ConfigException.php');

    class Config
    {
        //создаем необходимые переменные
        public $LdapIp;
        public $LdapLogin;
        public $LdapPassword;
        public $LdapGroupGUID;
        public $LdapGroupAdminGUID;
        public $LdapDomain;

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

        public function __construct()
        {
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
                            $this->LdapIp = trim($value);
                        }
                        else if( trim($key) === "LDAP_login" )
                        {
                            $this->LdapLogin = trim($value);
                        }
                        else if( trim($key) === "LDAP_password" )
                        {
                            $this->LdapPassword = trim($value);
                        }
                        else if( trim($key) === "LDAP_domain" )
                        {
                            $this->LdapDomain = trim( $value );
                        }
                        else if ( trim($key) === "LDAP_group_guid" )
                        {
                            $this->LdapGroupGUID = trim( $value );
                        }
                        else if ( trim($key) === "LDAP_group_admin_guid" )
                        {
                            $this->LdapGroupAdminGUID = trim( $value );
                        }
                        else if( trim($key) === "Domain_prefix" )
                        {
                            $this->DomainPrefix = trim( $value );
                        }
                        else if( trim($key) === "LocalNet" )
                        {
                            $this->LocalNet = trim( $value );
                        }
                        else if( trim($key) === "Squid_IP" )
                        {
                            $this->SquidIP = trim( $value );
                        }
                        else if( trim($key) === "MYSQL_ROOT_login" )
                        {
                            $this->MysqlRootLogin = trim( $value );
                        }
                        else if( trim($key) === "MYSQL_ROOT_password" )
                        {
                            $this->MysqlRootPassword = trim( $value );
                        }
                        else if( trim($key) === "MYSQL_login" )
                        {
                            $this->MysqlLogin = trim( $value );
                        }
                        else if( trim($key) === "MYSQL_password" )
                        {
                            $this->MysqlPassword = trim( $value );
                        }
                        else if( trim($key) === "MYSQL_ip" )
                        {
                            $this->MysqlIp = trim( $value );
                        }
                        else if ( trim($key) === "MYSQL_database" )
                        {
                            $this->MysqlDatabase = trim( $value );
                        }
                        else if ( trim($key) === "SQUID_mode")
                        {
                            $this->SquidMode = trim( $value );
                        }
                        else if ( trim($key) === "SQUID_logfile" )
                        {
                            $this->SquidLogfile = trim( $value );
                        }
                    }
                }
            }

            fclose($handler);

            if (
                empty($this->LdapIp) or
                empty($this->LdapLogin) or
                empty($this->LdapPassword) or
                empty($this->LdapDomain) or
                empty($this->LdapGroupGUID) or
                empty($this->LdapGroupAdminGUID) or
                empty($this->DomainPrefix) or
                empty($this->LocalNet) or
                empty($this->SquidIP) or
                empty($this->MysqlRootLogin) or
                empty($this->MysqlRootPassword) or
                empty($this->MysqlLogin) or
                empty($this->MysqlPassword) or
                empty($this->MysqlIp) or
                empty($this->MysqlDatabase) or
                empty($this->SquidMode) or
                empty($this->SquidLogfile)
            )
            {
                throw new ConfigException();
            }
        }

    }
?>