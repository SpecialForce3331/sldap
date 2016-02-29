sldap
=====

Need:
apache2
mysql-server
php5,
php5-mysqlnd,
php5-ldap,
python3,
python3-pip
pip3 mysql-connector-python

* в репозитории ubuntu для связки python3-mysql есть пакет [python3-mysql.connector]
* может не работать со стандартными группами безопасности Windows Server. Для работы прокси в AD нужно создать новую группу, например "Пользователи прокси", после чего указать ее GUID в конфиге config.cfg в виде:
LDAP_group_guid : //AA//BB//CC...//ZZ

====

If squid NOT open or very slow open HTTPS sites, add option "dns_v4_first on" into squid config file.


