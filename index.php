<?php
$lifetime = 60 * 60 * 24 * 3;
session_set_cookie_params($lifetime, '/');
session_start();

error_reporting(E_ALL | E_WARNING | E_NOTICE);
ini_set('display_errors', TRUE);


// echo $_SESSION['access_token'];
// echo '$_SESSION';


$config = include 'config.php';

require './controllers.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];


if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);



// echo $uri;
// echo $_SESSION['access_token'];
// echo $_SESSION['access'];

// echo $method;

// if($uri == '/')
//     require_once 'views/home.php';

// if($uri == '/oauth-callback' && $method == 'GET')
//     echo "get  here";

// if($uri == '/oauth-callback' && $method == 'POST')
//     echo "post here";



switch($uri){
    case '/':
        require_once 'views/home.php';
        break;

    case '/oauth-callback':
        if($method == 'GET')
            callback();
            file_put_contents('./oauth.json', json_encode($_GET));
        break;

    case '/profile':
        $userData = profile();
        require_once 'views/profile.php';
        break;    
        
}