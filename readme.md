Creating a complete PHP project for the authorization_code flow with your test identity server requires several files and configuration settings. Below, is a step-by-step guide to achieve this.

1. Set Up the Project Structure:

Create a directory for your PHP project. Let's call it "oauth2"
Index refactored to use front end controller
```php
<?php
$lifetime = 60 * 60 * 24 * 3;
session_set_cookie_params($lifetime, '/');
session_start();

error_reporting(E_ALL | E_WARNING | E_NOTICE);
ini_set('display_errors', TRUE);

$config = include 'config.php';

require './controllers.php';

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];


if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

switch($uri){
    case '/':
        require_once 'views/home.php';
        break;

    case '/oauth-callback':
        if($method == 'GET')
            callback();
        break;

    case '/profile':
        $userData = profile();
        require_once 'views/profile.php';
        break;    
        
}

```

2. Create Configuration Files:

Create a file named "config.php" in the project directory. This file will contain the configuration settings for your identity server, client ID, redirect URIs, etc.

```php
// config.php

 $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
            
 $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

return [
    'identity_server' => 'https://id-sandbox.cashtoken.africa/oauth',
    'client_id' => 'wprQYMZBqqx-dgszFUfQG',
    'scope' => 'openid email profile',
    'response_type' => 'code',
    'redirect_uris' => [
        'http://localhost:3000/oauth-callback',
        'http://localhost:3000/callback'
    ],
    'state' => 12345,
    'nonce' => 67890,
    // 'response_mode' => 'form_post',
    'code_challenge' => $codeChallenge,
    'code_verifier' => $codeVerifier
];
```

3. Implement the Authorization Code Flow:
Refactored to use frontend controller

Create an index.php file in the project directory, which will serve as the landing page. This page will contain a "Login" button that redirects the user to the identity server for authentication.

```php
// index.php

    function login(){

        global $config;

        $authUrl = $config['identity_server'] . '/authorize?' . http_build_query([
            'response_type' => $config['response_type'],
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uris'][0], // Use the first redirect URI for simplicity
            'scope' => $config['scope'],
            'nonce' => $config['nonce'],
            'state' => $config['state'],
            'code_challenge' => $config['code_challenge'],
            'code_challenge_method' => 'S256',
        ]);

        return $authUrl;
    }
```

4. Create the Callback Page:

Create a file named "oauth-callback.php" in the project directory. This page handles the callback from the identity server and exchanges the authorization code for an access token.

```php
// oauth-callback.php

<?php
    function callback(){
        if (isset($_GET['code'])) {
            global $config;
            
            // file_put_contents();

            $code = $_GET['code'];
        
            // Exchange authorization code for an access token
            $tokenUrl = $config['identity_server'] . '/token';
            $tokenPayload = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $config['redirect_uris'][0], // Use the first redirect URI for simplicity
                'client_id' => $config['client_id'],
                'code_verifier' => $config['code_verifier'],
            ];
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $tokenUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenPayload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
        
            $tokenData = json_decode($response, true);
            file_put_contents('./token2', $response);
        
            // Parse the response to get the access token (in a real scenario, handle errors)
            $tokenResponse = json_decode($response, true);
            
            $accessToken = $tokenResponse['access_token'];
            
            $_SESSION['access_token'] = $accessToken;
        
            // // Now you can use the access token to retrieve user data from the identity server
            $userInfoUrl = $config['identity_server'] . '/userinfo';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenPayload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'header' => "Authorization: Bearer " . $accessToken,
            ]);
            $userInfoResponse = curl_exec($ch);
            curl_close($ch);
           
        
            // Parse and display user data (in a real scenario, handle errors)
            $userData = json_decode($userInfoResponse, true);

            header("Location: http://localhost:3000", true, 301);

        }  else {
            // Handle error
            echo "Error: Authorization code not found.";
        }
    }
```

5. Implement the Sign Out:

"signout.php" page will handle user sign-out by clearing the session.

```php
// signout.php

<?php
    function signout(){
        if (!isset($_SESSION['access_token'])) {
            // Redirect to the landing page if the user is not authenticated
            header('Location: /');
            exit;
        }
        
        $accessToken = $_SESSION['access_token'];

        $userSignoutUrl = $config['identity_server'] . '/signout';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userSignoutUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
        ]);
        $userInfoResponse = curl_exec($ch); // do nothing
        curl_close($ch);
    
        session_destroy();
        header('Location: index.php');
        exit;
    }
?>
```

6. Create a Protected Profile Page:

"profile.php" page is protected and can only be accessed if the user is authenticated.

```php
// profile.php

<?php
    function profile(){
        if (!isset($_SESSION['access_token'])) {
            // Redirect to the landing page if the user is not authenticated
            header('Location: /');
            exit;
        }
        
        $accessToken = $_SESSION['access_token'];

        $userInfoUrl = $config['identity_server'] . '/userinfo';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $userInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $accessToken,
        ]);
        $userInfoResponse = curl_exec($ch);
        curl_close($ch);
        
        // Parse and display user data (in a real scenario, handle errors)
        $userData = json_decode($userInfoResponse, true);

        return $userData;
    }
```

7. Run the Application:

To run the application, you'll need a local web server with PHP support. 

1. git clone project.
2. cd ./oauth2.
3. php -S localhost:3000.

The "index.php" page should show a "Login" button. Clicking the "Login" button will initiate the authorization_code flow, and after successful authentication, the user will be redirected to the "profile.php" page displaying their profile information. The user can also sign out using the "Sign Out" link on the profile page.