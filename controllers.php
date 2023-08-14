<?php

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
