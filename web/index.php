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
    echo($e->getMessage());
    exit();
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
    return $app['twig']->render('index.html');
});

$app->post('/login', function(Request $request) use ($app, $mysql, $ldap)
{
    $login = $request->get("login");
    $password = $request->get("password");

    if( $login === "root" and ( $user_id = $mysql->adminLogin($login, $password) ) )
    {
        $app['session']->set('user', array('id' => $user_id, 'login' => $login));
        return $app->redirect("/main");
    }
    else if( ( $user_id = $mysql->checkAdminExist($login) ) and $ldap->ldapAdminAuth($login, $password) )
    {
        $app['session']->set('user', array('id' => $user_id, 'login' => $login));
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
})->before(function() use($app)
{
    if ( null === $user = $app['session']->get('user') )
    {
        return $app->redirect('/');
    }
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
        $type = $request->get("type");
        $existUsers = $type === "users" ? $mysql->getExistAccounts("users") : $mysql->getExistAccounts("admins");
        return $ldap->getLdapUsers($existUsers, $type);
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
    elseif( $action === "getAdmins" )
    {
        return $mysql->getAdmins();
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
    elseif( $action === "deleteAdmins" )
    {
        return $mysql->deleteAdmins($request->get("data"));
    }
    elseif( $action === "createLdapAdminAccounts" )
    {
        return $mysql->createLdapAdminAccounts($request->get("data"));
    }
    elseif( $action === "applyChangesToAdmin")
    {
        return $mysql->applyChangesToAdmin($request->get("changes"));
    }
})->before(function() use($app)
{
    if ( null === $user = $app['session']->get('user') )
    {
        return $app->redirect('/');
    }
});

$app->run();

