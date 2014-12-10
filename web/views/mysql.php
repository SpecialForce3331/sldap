<?php 

    class Mysql
    {

        private $server;
        private $username;
        private $password;
        private $database;
        private $mysqli;

        function __construct($config)
        {
            $this->server = $config->MysqlIp;
            $this->username = $config->MysqlLogin;
            $this->password = $config->MysqlPassword;
            $this->database = $config->MysqlDatabase;
            $this->mysqli = new mysqli( $this->server, $this->username, $this->password, $this->database ); //Устанавливаем соединение в базой мускула

            if ( $this->mysqli->connect_errno )
            {
                echo "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error; //Не удалось установить соединение с базой мускула.
            }
            else
            {
                $this->mysqli->set_charset("utf8"); //Устанавливаем принудительно кодировку в UTF-8!
            }
        }

        function __destruct()
        {
            $this->mysqli->close();
        }

        function adminLogin($login, $password)
        {
            $statement = $this->mysqli->prepare("SELECT id FROM admins WHERE login=? AND password=?") or die ( "не удалось подготовить запрос: ".$this->mysqli->error );
            $statement->bind_param('ss', $login, $password);
            $statement->execute() or die( "не удалось получить данные администратора ".$statement->error );
            $result = $statement->get_result();
            $count = $result->num_rows;

            if ( $count > 0 )
            {
                return true;
            }
            else
            {
                return false;
            }
        }

        function getMysqlUsers()
        {
            $result = $this->mysqli->query("SELECT users.login,users.name,users.trafficForDay,patterns.traffic,patterns.name,patterns.access FROM users LEFT JOIN patterns ON users.pattern_id = patterns.id ORDER BY users.name") or die("can not get users " . $this->mysqli->error);;

            if ($this->mysqli->connect_errno) {
                echo "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
            } else {
                $data = $result->fetch_all(MYSQLI_NUM);

                $answer = array("result" => $data);
                $result->free();
                return json_encode($answer);
            }
        }

        function cleanTraffic($users)
        {
            if ( !$this->checkPermissions($this->mysqli, "editUsers") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
                return;
            }

            $query = "UPDATE users SET trafficForDay= 0 WHERE login in (";

            for( $i = 0; $i < count( $users ); $i += 3 )
            {
                if ( $i == (count( $users ) - 3 ) )
                {
                    $query = $query."\"".$users[$i+1]."\")";
                }
                else
                {
                    $query = $query."\"".$users[$i+1]."\",";
                }

            }

            $this->mysqli->query( $query ) or die("can not erase users traffic ".$this->mysqli->error );

            return json_encode( array( "result" => "ok", "message" => "Траффик пользователей успешно очищен." ));
        }

        function addUsers($data)
        {
            if ( !$this->checkPermissions($this->mysqli, "addUsers") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
                return;
            }

            $query = "INSERT INTO users (login,name,pattern_id) VALUES ";

            for ( $i = 0; $i < count($data); $i += 3 )
            {
                if ( $i == (count($data) - 3 ) )
                {
                    $query = $query."(\"".$data[$i+1]."\",\"".$data[$i]."\",\"".$data[$i+2]."\")";
                }
                else
                {
                    $query = $query."(\"".$data[$i+1]."\",\"".$data[$i]."\",\"".$data[$i+2]."\"),";
                }
            }

            $this->mysqli->query( $query ) or die("insert error: ".$this->mysqli->error."\n");
            $data = array("result" => "ok", "message" => "Выбранные пользователи успешно добавлены." );
            return json_encode( $data );
        }

        function deleteUsers($data)
        {
            if ( !$this->checkPermissions($this->mysqli, "deleteUsers") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
                return;
            }

            $query = "DELETE FROM users WHERE login IN (";

            for ( $i = 0; $i < count($data); $i += 3 )
            {
                if ( $i == (count($data) - 3 ) )
                {
                    $query = $query."\"".$data[$i+1]."\")";
                }
                else
                {
                    $query = $query."\"".$data[$i+1]."\",";
                }
            }

            $this->mysqli->query( $query ) or die( $this->mysqli->error." delete error");
            $data = array("result" => "ok", "message" => "Выбранные пользователи успешно удалены." );
            return json_encode( $data );
        }

        function getPatterns()
        {
            $query = "SELECT name,traffic,access,id FROM patterns WHERE 1";

            $result = $this->mysqli->query( $query ) or die( "select error" );
            $data = $result->fetch_all( MYSQLI_NUM );
            return json_encode( array( "result" => $data ) );
        }

        function createPattern($name, $traffic, $access)
        {
            if ( !$this->checkPermissions($this->mysqli, "createPatterns") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            if ( !empty( $name ) && !empty( $traffic ) )
            {
                $query = "INSERT INTO patterns (name,traffic,access) VALUES ('".$name."','".$traffic."','".$access."')";
                $result = $this->mysqli->query( $query ) or die("insert pattert error");

                return json_encode( array("result"=>"ok", "message" => "Шаблон успешно создан.") );
            }
            else
            {
                return json_encode( array("result"=>"false", "message" => "Вы заполнили не все поля" ));
            }
        }

        function deletePattern($patterns)
        {
            if ( !$this->checkPermissions($this->mysqli, "deletePatterns") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            $query = "DELETE FROM patterns WHERE name in (";

            for ( $i = 0; $i < count($patterns); $i++ )
            {
                if ( $i == ( count($patterns) - 1 ) )
                {
                    $query = $query."\"".$patterns[$i]."\")";
                }
                else
                {
                    $query = $query."\"".$patterns[$i]."\",";
                }
            }

            $this->mysqli->query( $query ) or die( "can not delete patterns: ".$this->mysqli->error );
            return json_encode( array( "result" => "ok", "message" => "Шаблон(ы) успешно удален" ));
        }

        function applyChangesToUsers($changes)
        {
            if ( !$this->checkPermissions($this->mysqli, "editUsers") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            for ( $i = 0; $i < count($changes); $i++ )
            {
                $query = "UPDATE users SET pattern_id=\"".$changes[$i][2]."\" WHERE login=\"".$changes[$i][0]."\"";
                $this->mysqli->query( $query ) or die("cannot update users for changes ".$this->mysqli->error );
            }
            return json_encode( array( "result" => "ok", "message" => "Изменения успешно применены." ));
        }

        function applyChangesToPatterns($changes)
        {
            if ( !$this->checkPermissions($this->mysqli, "editPatterns") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            $changes = $_POST["changes"];

            for ( $i = 0; $i < count($changes); $i++ )
            {
                $query = "UPDATE patterns SET name=\"".$changes[$i][1]."\", traffic=\"".$changes[$i][2]."\", access=\"".$changes[$i][3]."\" WHERE name=\"".$changes[$i][0]."\"";
                $this->mysqli->query( $query ) or die("can not update patterns: ".$this->mysqli->error );
            }

            return json_encode( array( "result" => "ok", "message" => "Изменения успешно применены." ) );
        }

        function getDenySites()
        {
            $query = $this->mysqli->query( "SELECT id, url FROM denySites WHERE 1") or die( "can not update patterns ".$this->mysqli->error );
            $result = $query->fetch_all( MYSQLI_NUM );
            return json_encode( array("result" => $result ));
        }

        function createDenySite($url)
        {
            if ( !$this->checkPermissions($this->mysqli, "addDenySites") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
                return;
            }

            $query = "INSERT INTO denySites (url) VALUES ";

            for( $i = 0; $i < count($url); $i++ )
            {
                if ( !empty( $url[$i] ) )
                {
                    if ( $i == ( count( $url ) - 1 ) )
                    {
                        $query = $query."(\"".$url[$i]."\")";
                    }
                    else
                    {
                        $query = $query."(\"".$url[$i]."\"),";
                    }
                }

            }
            if ( substr($query, -1) == "," )
            {
                $query = substr($query, 0, -1);
            }
            $this->mysqli->query( $query ) or die("can not insert site in deny table ".$this->mysqli->error );
            return json_encode( array("result" => "ok", "message" => "Запрещенный сайт(ы) успешно создан(ы)."  ));
        }

        function deleteDenySite($url)
        {
            if ( !$this->checkPermissions($this->mysqli, "deleteDenySites") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            $query = "DELETE FROM denySites WHERE url in (";

            for ( $i = 0; $i < count( $url ); $i++ )
            {
                if ( $i == ( count( $url ) - 1 ) )
                {
                    $query = $query."\"".$url[$i]."\")";
                }
                else
                {
                    $query = $query."\"".$url[$i]."\",";
                }
            }

            $this->mysqli->query( $query ) or die("can not delete deny site ".$this->mysqli->error );

            return json_encode( array("result" => "ok", "message" => "Запрещенный сайт(ы) успешно удален(ы)."  ) );
        }

        function editDenySite($changes)
        {
            if ( !$this->checkPermissions($this->mysqli, "editDenySites") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            foreach ( $changes as $change)
            {
                $this->applyChangesToDenySites($change[1], $change[0]);
            }
            return json_encode( array( "result" => "ok", "message" => "Успешное обновление запрещенного(ых) сайта(ов)." ));
        }

        function getPermissionPatterns()
        {
            $query = "SELECT * FROM permissions WHERE 1";

            $result = $this->mysqli->query( $query ) or die( "select error" );
            $patternsData = $result->fetch_assoc();
            return json_encode( array("result" => "ok", "data" => $patternsData) );
        }

        function getPermissionsById($id)
        {
            $statement = $this->mysqli->prepare("SELECT * FROM permissions WHERE id=?") or die ( "не удалось подготовить запрос: ".$this->mysqli->error );
            $statement->bind_param('i', $id);
            $statement->execute() or die( "не удалось получить шаблоны прав по id ".$statement->error );
            $result = $statement->get_result();
            $permissions = $result->fetch_assoc();

            return json_encode( array("result" => "ok", "data" => $permissions) );
        }

        function applyChangesToPermissions($id, $name, $permissions)
        {
            if ( !$this->checkPermissions($this->mysqli, "editPermissions") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
                return;
            }

            $columns = [];
            $values = [];

            foreach ( $permissions as $row )
            {
                $column = $row[0];
                $value = ( $row[1] == "true" ? 1 : 0);
                array_push( $columns, $column);
                array_push( $values, $value);
            }

            if ( !empty( $id ) && empty( $name ) )
            {
                $query = "";
                for ( $i=0; $i < count( $columns ); $i++ )
                {
                    if ( $i == count( $columns )-1 )
                    {
                        $query = $query." ".$columns[$i]."=".$values[$i];
                    }
                    else
                    {
                        $query = $query." ".$columns[$i]."=".$values[$i].",";
                    }

                }
                $statement = $this->mysqli->prepare("UPDATE permissions SET ". $query ." WHERE id=?") or die ( "не удалось подготовить запрос: ".$this->mysqli->error );

                $statement->bind_param('i', $id );
                $statement->execute() or die( "не удалось выполнить обновление учетной(учетных) записи(записей) администратора ".$statement->error );
            }
            else if ( empty( $id ) && !empty( $name ) )
            {
                $statement = $this->mysqli->prepare("INSERT INTO permissions (name,". implode(",",$columns) .") VALUES(?,".implode(",",$values).")") or die ( "не удалось подготовить запрос: ".$this->mysqli->error );
                $statement->bind_param('s', $name);
                $statement->execute() or die( "не удалось выполнить обновление учетной(учетных) записи(записей) администратора ".$statement->error );
            }

            return json_encode( array("result" => "ok", "message" => "Успешно внесены изменения шаблона прав" ) );
        }

        function getExistUsers()
        {
            $result = $this->mysqli->query("SELECT users.login FROM users") or die("can not get exist users " . $this->mysqli->error);;

            if ($this->mysqli->connect_errno) {
                echo "Failed to connect to MySQL: (" . $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error;
            }
            else
            {
                $data = $result->fetch_all(MYSQLI_NUM);
                $result->free();
                return $data;
            }
        }

        function getPatternDetailsByName($name)
        {
            $query = "SELECT name,traffic,access FROM patterns WHERE name= $name";

            $result = $this->mysqli->query( $query ) or die( "select error" );
            $patternsData = $result->fetch_all( MYSQLI_NUM );
            return $patternsData;
        }

        function getTop($type, $count, $fromDate, $toDate, $login)
        {
            $whereLogin = "";
            if ( !empty($login) )
            {
                $whereLogin = "and login = '$login'";
            }

            if( !empty($fromDate) && !empty($toDate) )
            {
                $query = "SELECT SUM(bytes) as bytes, $type FROM usersTraffic WHERE dateTime BETWEEN UNIX_TIMESTAMP(STR_TO_DATE('$fromDate', '%d.%m.%Y')) and UNIX_TIMESTAMP(STR_TO_DATE('$toDate', '%d.%m.%Y')) $whereLogin GROUP BY $type ORDER by SUM(bytes) desc LIMIT 0,$count";
            }
            else
            {
                $query = "SELECT SUM(bytes) as bytes, $type FROM usersTraffic WHERE ( dateTime BETWEEN UNIX_TIMESTAMP(CURDATE() - interval 1 day) AND UNIX_TIMESTAMP(CURDATE() + interval 1 day) ) $whereLogin GROUP BY $type ORDER by SUM(bytes) desc LIMIT 0,$count";
            }

            $result = $this->mysqli->query( $query ) or die( "select error\n".$this->mysqli->error );
            $patternsData = $result->fetch_all( MYSQLI_NUM );
            return json_encode( array("result" => "ok", "data" => $patternsData) );
        }

        function getAdmins()
        {
            $query = "SELECT admins.id, admins.login, permissions.name, permissions.id FROM admins LEFT JOIN permissions ON admins.permission_id = permissions.id";
            $result = $this->mysqli->query( $query ) or die( $this->mysqli->error." select error" );
            $admins = $result->fetch_all( MYSQLI_NUM );
            return json_encode( array("data" => $admins) );
        }

        function getPermissions()
        {
            $query = "SELECT id, name FROM permissions";
            $result = $this->mysqli->query( $query ) or die( $this->mysqli->error." select error" );
            $permissions = $result->fetch_all( MYSQLI_NUM );
            return json_encode( array("result" => "ok", "data" => $permissions) );
        }

        function createAdminAccount($login, $password, $retype_password, $permission_id)
        {
            if ( !$this->checkPermissions($this->mysqli, "createAdmins") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            if( $password != $retype_password )
            {
                return json_encode( array("result" => "error", "message" => "Введенные пароли не совпадают!") );
            }
            else if( empty($login) or empty($password) or empty($retype_password) or empty($permission_id) )
            {
                return json_encode( array("result" => "error", "message" => "Вы не заполнили одно из полей!") );
            }
            else {
                $statement = $this->mysqli->prepare("INSERT INTO admins (login, password, permission_id) VALUES (?, ?, ?)") or die ("не удалось подготовить запрос: " . $this->mysqli->error);
                $statement->bind_param('ssi', $login, $password, $permission_id);
                $statement->execute() or die("не удалось выполнить добавление учетной записи администратора " . $statement->error);
                return json_encode(array("result" => "ok", "message" => "Учетная запись администратора успешно создана"));

            }
        }

        public function createLdapAdminAccounts($data)
        {
            $sql = "INSERT INTO admins (login, permission_id) VALUES ";

            for( $i = 0; $i < count($data); $i++ )
            {
                if ( $i === count($data)-1 )
                {
                    $sql = $sql."(\"".$data[$i][0]."\",\"".$data[$i][1]."\")";
                }
                else
                {
                    $sql = $sql."(\"".$data[$i][0]."\",\"".$data[$i][1]."\"),";
                }

            }

            $statement = $this->mysqli->prepare($sql) or die ("не удалось подготовить запрос: " . $this->mysqli->error);
            $statement->execute() or die("не удалось выполнить добавление учетной(ых) записи(ей) администратора(ов) " . $statement->error);
            return json_encode(array("result" => "ok", "message" => "Учетная(ые) запись(и) администратора(ов) успешно создана(ы)"));
        }

        public function deleteAdmins($data)
        {
            $sql = "DELETE FROM admins WHERE id IN (";

            for ( $i = 0; $i < count($data); $i++ )
            {
                if ( $i === count($data)-1 )
                {
                    $sql = $sql.$data[$i][0].")";
                }
                else
                {
                    $sql = $sql.$data[$i][0].",";
                }
            }
            $statement = $this->mysqli->prepare($sql) or die ("не удалось подготовить запрос: " . $this->mysqli->error);
            $statement->execute() or die("не удалось выполнить удаление учетной(ых) записи(ей) администратора(ов) " . $statement->error);
            return json_encode(array("result" => "ok", "message" => "Учетная(ые) запись(и) администратора(ов) успешно удален(ы)"));
        }

        public function applyChangesToAdmin($changes)
        {
            if ( !$this->checkPermissions($this->mysqli, "editAdmins") )
            {
                return json_encode( array( "result" => "error", "message" => "У вас недостаточно прав для выполнения этой операции." ));
            }

            foreach( $changes as $change )
            {
                $id = $change[0];
                $login = $change[1];
                $password = $change[2];
                $retype_password = $change[3];
                $permission_id = $change[4];

                if( $password != $retype_password )
                {
                    return json_encode( array("result" => "error", "message" => "Введенные пароли не совпадают!") );
                }
                else if( empty($login) or empty($password) or empty($retype_password) or empty($permission_id) )
                {
                    return json_encode( array("result" => "error", "message" => "Вы не заполнили одно из полей!") );
                }
                else
                {
                    $statement = $this->mysqli->prepare("UPDATE admins SET login=?, password=?, permission_id=? WHERE id=?") or die ( "не удалось подготовить запрос: ".$this->mysqli->error );
                    $statement->bind_param('ssii', $login, $password, $permission_id, $id);
                    $statement->execute() or die( "не удалось выполнить обновление учетной(учетных) записи(записей) администратора ".$statement->error );

                    return json_encode( array("result" => "ok") );
                }
            }
       }

        private function applyChangesToDenySites($url, $id)
        {
            $statement = $this->mysqli->prepare("UPDATE denySites SET url=? WHERE id=?") or die ( "не удалось подготовить запрос: ".$this->mysqli->error );
            $statement->bind_param('si', $url, $id);
            $statement->execute() or die( "не удалось выполнить обновление запрещенного(ых) сайта(ов) ".$statement->error );
        }

        private function checkPermissions($permission)
        {
            if ( $permission == "addUsers" or
                $permission == "editUsers" or
                $permission == "deleteUsers" or
                $permission == "createPatterns" or
                $permission == "editPatterns" or
                $permission == "deletePatterns" or
                $permission == "addDenySites" or
                $permission == "editDenySites" or
                $permission == "deleteDenySites" or
                $permission == "createAdmins" or
                $permission == "editAdmins" or
                $permission == "deleteAdmins" or
                $permission == "createPermissions" or
                $permission == "editPermissions" or
                $permission == "deletePermissions"
            )
            {
                $query = "SELECT ".$permission." FROM permissions LEFT JOIN admins ON (permissions.id = admins.permission_id) WHERE admins.id = ".$_SESSION["user_id"];
                $result = $this->mysqli->query( $query ) or die( $this->mysqli->error." | select permission error" );
                $access = $result->fetch_row();

                $answer = $access[0] == 1 ? true : false;
                return $answer;
            }
            else
            {
                return "Запрошенных прав не существует";
            }
        }

    }
?>
