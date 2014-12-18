<?php

//сначала проверим заполнен ли файл конфигурации
require_once(__DIR__ . '/checkconf.php');

$checkConf = new Config();

if( !empty($checkConf->LdapIp) &&
    !empty($checkConf->LdapLogin) &&
    !empty($checkConf->LdapPassword) &&
    !empty($checkConf->LdapDomain) &&
    !empty($checkConf->MysqlRootLogin) &&
    !empty($checkConf->MysqlRootPassword) &&
    !empty($checkConf->MysqlLogin) &&
    !empty($checkConf->MysqlPassword) &&
    !empty($checkConf->MysqlIp) &&
    !empty($checkConf->MysqlDatabase) &&
    !empty($checkConf->SquidLogfile) &&
    !empty($checkConf->sldapDirectory) &&
    !empty($checkConf->DomainPrefix) &&
    !empty($checkConf->LocalNet) &&
    !empty($checkConf->SquidIP)
)
{
//создаем БД и добавляем туда таблицы для будущей работы
    shell_exec( "mysql -u ".$checkConf->MysqlRootLogin." -p".$checkConf->MysqlRootPassword." < ".$checkConf->sldapDirectory."/install/ldap_squid.sql" );
    echo "\n База данных с таблицами создана, перехожу к созданию конфигурационного файла squid.conf \n";

//создаем конфигурационный файл squid.conf
    shell_exec( "php -f ".$checkConf->sldapDirectory."/install/scripts/squid_conf_writer.php " );
    echo "\n Конфигурационный файл squid.conf создан ".$checkConf->sldapDirectory."/squid.conf, вы можете скормить его squid полностью \n или скопировать из него и частями вставить в основной конфигурационный файл squid.\n Перехожу к добавлению парсера логов в cron \n";

//Формируем путь к парсеру логов squid и добавляем его в cron
    $handler = fopen( $checkConf->sldapDirectory."/install/scripts/crontask", "w" );

    fwrite( $handler, "*/1 * * * * cd ".$checkConf->sldapDirectory." && php -f parser.php \n" );

    fclose( $handler );

    shell_exec( "/usr/bin/crontab ".$checkConf->sldapDirectory."/install/scripts/crontask" );
    echo "\n Парсер логов сформирован и добавлен в cron \n";


}
else
{
    echo "Вы не заполнили одно или несколько полей в файле конфигурации config.cfg \n";
}



?>