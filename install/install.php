<?php

//сначала проверим заполнен ли файл конфигурации
include 'checkconf.php';


if( !empty($LdapIp) &&
    !empty($LdapLogin) &&
    !empty($LdapPassword) &&
    !empty($LdapDomainContainer) &&
    !empty($LdapDomain) &&
    !empty($MysqlRootLogin) &&
    !empty($MysqlRootPassword) &&
    !empty($MysqlLogin) &&
    !empty($MysqlPassword) &&
    !empty($MysqlIp) &&
    !empty($MysqlDatabase) &&
    !empty($SquidLogfile) &&
    !empty($sldapDirectory) &&
    !empty($DomainPrefix) &&
    !empty($LocalNet) &&
    !empty($SquidIP)
)
{
//создаем БД и добавляем туда таблицы для будущей работы
    shell_exec( "mysql -u ".$MysqlRootLogin." -p".$MysqlRootPassword." < ".$sldapDirectory."/install/ldap_squid.sql" );
    echo "\n База данных с таблицами создана, перехожу к созданию конфигурационного файла squid.conf \n";

//создаем конфигурационный файл squid.conf
    shell_exec( "php -f ".$sldapDirectory."/install/scripts/squid_conf_writer.php " );
    echo "\n Конфигурационный файл squid.conf создан ".$sldapDirectory."/squid.conf, вы можете скормить его squid полностью \n или скопировать из него и частями вставить в основной конфигурационный файл squid.\n Перехожу к добавлению парсера логов в cron \n";

//Формируем путь к парсеру логов squid и добавляем его в cron
    $handler = fopen( $sldapDirectory."/install/scripts/crontask", "w" );

    fwrite( $handler, "*/1 * * * * cd ".$sldapDirectory." && php -f parser.php \n" );

    fclose( $handler );

    shell_exec( "/usr/bin/crontab ".$sldapDirectory."/install/scripts/crontask" );
    echo "\n Парсер логов сформирован и добавлен в cron \n";


}
else
{
    echo "Вы не заполнили одно или несколько полей в файле конфигурации config.cfg \n";
}



?>