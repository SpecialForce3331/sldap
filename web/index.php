<?php

require_once __DIR__.'/../vendor/autoload.php';
require_once(__DIR__.'/../install/checkconf.php');
require_once(__DIR__.'/views/ldap.php');
require_once(__DIR__.'/views/mysql.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$error = "";
$config;
$ldap;
$mysql;

try {
    $config = new Config();
    $ldap = new Ldap($config);
    $mysql = new Mysql($config);
}
catch(Exception $e)
{
    $error = $e;
}

$filename = __DIR__.preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);
if (php_sapi_name() === 'cli-server' && is_file($filename)) {
    return false;
}

$app = new Silex\Application();
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

$app->get('/', function() use ($app, $error, $ldap)
{
    if ( !empty($error) )
    {
        return $error;
    }
    else
    {
        return $app['twig']->render('index.html');
    }
});

$app->post('/login', function(Request $request) use ($app, $mysql)
{
    $login = $request->get("login");
    $password = $request->get("password");
    if( $mysql->adminLogin($login, $password) )
    {
        error_log("success");
        $app['session']->set('user', array('login' => $login));
        return $app->redirect("/main");
    }
    else
    {
        return $app->redirect("/");
    }
});

$app->get('/main', function() use ($app)
{
    return $app['twig']->render('main.html');
});

$app->post('/api', function(Request $request) use ($app, $mysql, $ldap)
{
    $action = $request->get("action");

    if ( $action === "getMysqlUsers" )
    {
        return $mysql->getMysqlUsers();
    }
    elseif( $action === "getLdapUsers")
    {
        return $ldap->getLdapUsers($mysql->getExistUsers());
    }
    elseif( $action === "cleanTraffic" )
    {
        return $mysql->cleanTraffic($request->get("users"));
    }
    elseif( $action === "addUsers" )
    {
        return $mysql->addUsers($request->get("data"));
    }
    elseif( $action === "deleteUsers" )
    {
        return $mysql->deleteUsers($request->get("data"));
    }
    elseif( $action === "getPatterns" )
    {
        return $mysql->getPatterns();
    }
    elseif( $action === "createPattern" )
    {
        $name = $request->get("name");
        $traffic = $request->get("traffic");
        $access = $request->get("access");
        return $mysql->createPattern($name, $traffic, $access);
    }
    elseif( $action === "deletePattern" )
    {
        $patterns = $request->get("patterns");
        return $mysql->deletePattern($patterns);
    }
    elseif( $action === "applyChangesToUsers" )
    {
        return $mysql->applyChangesToUsers($request->get("changes"));
    }
    elseif( $action === "applyChangesToPatterns" )
    {
        return $mysql->applyChangesToPatterns($request->get("changes"));
    }
    elseif( $action === "getDenySites" )
    {
        return $mysql->getDenySites();
    }
    elseif( $action === "createDenySite" )
    {
        return $mysql->createDenySite($request->get("url"));
    }
    elseif( $action === "deleteDenySite" )
    {
        return $mysql->deleteDenySite($request->get("url"));
    }
    elseif( $action === "editDenySite" )
    {
        return $mysql->editDenySite($request->get("changes"));
    }
    elseif( $action === "getPermissionPatterns" )
    {
        return $mysql->getPermissionPatterns();
    }
    elseif( $action === "getPermissionsById" )
    {
        return $mysql->getPermissionsById($request->get("id"));
    }
    elseif( $action === "applyChangesToPermissions" )
    {
        $id = $request->get("id");
        $name = $request->get("name");
        $permissions = $request->get("permissions");
        return $mysql->applyChangesToPermissions($id, $name, $permissions);
    }
    elseif( $action === "getPatternDetailsByName" )
    {
        return $mysql->getPatternDetailsByName($request->get("name"));
    }
    elseif( $action === "getTop" )
    {
        $type = $request->get("type");
        $count = $request->get("count");
        $fromDate = $request->get("fromDate");
        $toDate = $request->get("toDate");
        $login = $request->get("login");
        return $mysql->getTop($type, $count, $fromDate, $toDate, $login);
    }
    elseif( $action === "showAdmins" )
    {
        return $mysql->showAdmins();
    }
    elseif( $action === "getPermissions" )
    {
        return $mysql->getPermissions();
    }
    elseif( $action === "createAdminAccount" )
    {
        $login = $request->get("login");
        $password = $request->get("password");
        $retype_password = $request->get("retype_password");
        $permission_id = $request->get("permission_id");
        return $mysql->createAdminAccount($login, $password, $retype_password, $permission_id);
    }
    elseif( $action === "applyChangesToAdmin")
    {
        return $mysql->applyChangesToAdmin($request->get("changes"));
    }
});

$app->run();

