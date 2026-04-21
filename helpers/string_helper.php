<?php

/*
 * Copyright (C) 2018 Easy CMS Framework Ahmed Elmahdy
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License
 * @license    https://opensource.org/licenses/GPL-3.0
 *
 * @package    Easy CMS MVC framework
 * @author     Ahmed Elmahdy
 * @link       https://ahmedx.com
 *
 * For more information about the author , see <http://www.ahmedx.com/>.
 */

/**
 * generate Random string
 * @param integer $length
 * @return string
 */
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * display and die
 * @param [var or object or array] $var
 */
function dd($var)
{
    var_dump($var);
    die();
}

/**
 * view array content
 *
 * @param [array] $var
 * @return void
 */
function pr($var)
{
    echo "<pre class='text-left ltr'>";
    print_r($var);
    echo "</pre>";
}
/**
 * check if variable exist and not empty
 *
 * @param $var
 * @return bool
 */
function exist($var)
{
    if (isset($var)) {
        if (!empty($var)) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}
/**
 * Sending SMS message
 *
 * @param [string] $username
 * @param [string] $password
 * @param [string] $messageContent
 * @param [string] $mobileNumber
 * @param [string] $sendername
 * @param [string] $server
 * @param string $return
 * @return void
 */
function sendSMS($username, $password, $messageContent, $mobileNumber, $sendername, $server, $gateway = 'default', $return = 'json')
{

    // built url
    if ($gateway == 'taqnyat') {

        $post = $server . '?bearerTokens=' . urlencode($password) . '&sender=' . urlencode($sendername) . '&recipients=' . urlencode($mobileNumber) . '&body=' . urlencode($messageContent);
        $params = [
            "bearerTokens" => $password,
            "sender" => $sendername,
            "recipients" => $mobileNumber,
            "body" => $messageContent,
        ];
        $ch = curl_init();
        $url = $server . '?' . http_build_query($params);
        // curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_URL, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    } else {
        $post = 'username=' . urlencode($username) . '&password=' . urlencode($password) . '&numbers=' . urlencode($mobileNumber)
            . '&message=' . urlencode($messageContent) . '&sender=' . urlencode($sendername) . '&unicode=E&return=' . urlencode($return);
        //open connection
        $ch = curl_init();
        // API URL     
        curl_setopt($ch, CURLOPT_URL, $server);
        //Sending through $_POST request    
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    }
    // excution    
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $respond = curl_exec($ch);

    // close connection    
    curl_close($ch);
    //using the return as a PHP array
    return $respond ? TRUE : FALSE;
    #json_decode($respond);
}

/**
 * Sending Whats App message
 *
 * @param [type] $gateurl
 * @param [type] $accessToken
 * @param [type] $template_name
 * @param [type] $mobileNumber
 * @param [type] $sender_name
 * @param [type] $to
 * @param [type] $parameters array 
 * @param string $return
 * @return void
 */
function sendWhatsAppParameter($gateurl, $accessToken, $template_name, $sender_name, $to, $parameters, $sa = true)
{
    if ($sa) $to = "966" . substr($to, -9, 9);
    //  Parameters
    $params = '?whatsappNumber=' . urlencode($to);
    // url
    $url = $gateurl . $params;
    // header  
    $headers = [
        "Accept: application/json",
        "Content-Type: application/json",
        "Authorization:" . $accessToken,
    ];

    $data = json_encode([
        "parameters" => $parameters,
        "broadcast_name" => $sender_name,
        "template_name" => $template_name,

    ]);

    //open connection
    $ch = curl_init();
    // API URL     
    curl_setopt($ch, CURLOPT_URL, $url);
    //Sending through $_POST request    
    curl_setopt($ch, CURLOPT_POST, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


    // excution    
    $respond = curl_exec($ch);

    // close connection    x
    curl_close($ch);
    //using the return as a PHP array
    return json_encode($respond);
}



function sendPush($title, $message, $donor_id = NULL)
{
    $data = ['title' => $title, 'body' => $message];
    if ($donor_id) $data['donor_id'] = $donor_id;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => URLROOT . '/api/tokens/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            'Cookie: PHPSESSID=d27f437f16b9e581731a8a46da6e1832'
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    return $response ? TRUE : $err;
}
/**
 * repeat string using seprator with incrising value
 *
 * @param string $var
 * @param integer $count
 * @param string $seprator
 * @return string
 */
function strIncRepeat($var, $count, $seprator = ',')
{
    $text = '';
    for ($i = 0; $i < $count; $i++) {
        $text .= $var . $i . $seprator;
    }
    return rtrim($text, ',');
}

/**
 * print variable if exist
 *
 * @param string $var
 * @return void
 */
function printIsset($var)
{
    if (isset($var)) {
        echo $var;
    } else {
        return false;
    }
}

/**
 * print variable if exist
 *
 * @param string $var
 * @return void
 */
function returnIsset($var)
{
    if (isset($var)) {
        return $var;
    } else {
        return false;
    }
}
/**
 * clean Search Var
 *
 * @param string $var
 * @return string
 */
function cleanSearchVar($var)
{
    if (isset($_SESSION['search']['bind'][":$var"])) {
        return str_replace('%', '', $_SESSION['search']['bind'][":$var"]);
    }
}
/**
 * extract array into string lines key : values
 *
 * @param array $array
 * @return string
 */
function arrayLines($array)
{
    $string = '';
    foreach ($array as $key => $value) {
        $string .= "<p> $key :  $value </p>";
    }
    return $string;
}

/**
 * connect to captcha and validate request
 *
 * @return boolean
 */
function recaptcha()
{
    $secretKey = "6LcVHY8bAAAAAC_LjsfXuglkHGv89d2T3HdDDJaR";
    // post request to server
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  '&response=' . urlencode($_POST['g-recaptcha-response']);
    $response = file_get_contents($url);
    $responseKeys = json_decode($response);
    return $responseKeys->success;
}

/**
 * generate random token 
 *
 * @param integer $range
 * @return string
 */
function token($range)
{
    return bin2hex(random_bytes($range));
}

/**
 * encrypt small data ex [cvv, expired year & monuth, ...]
 *
 * @param integer $val
 * @return string
 */
function encrypt($val)
{
    return  ( (0x0000FFFF & $val) << 48) + ((0xFFFF0000 & $val) >> 48 );
}

/**
 * decrypt small data ex [cvv, expired year & monuth, ...]
 *
 * @param string $string
 * @return string
 */
function decrypt($val)
{
    $res = ( (0x0000FFFF | $val) >> 48);
    if(strlen($res) == 1) $res = '0' . $res;
    return  $res;
}


/**
 * openssl encrypt card 
 *
 * @param integer $val
 * @return string
 */
function openssl_encrypt_card($card_number) {
    // $key = hash('sha256', mt_rand());
    // $initial_vector_key = substr(sha1(mt_rand()), 17, 16); //To Generate Random Numbers with Letters.
    $encrypted_card_number = openssl_encrypt($card_number, 'AES-256-CBC', HASH_KEY, 0, HASH_IV_KEY);
    return $encrypted_card_number;
}

/**
 * openssl decrypt card
 *
 * @param string $string
 * @return string
 */
function openssl_decrypt_card($encrypted_card_number) {
    $decrypted_card_number = openssl_decrypt($encrypted_card_number, 'AES-256-CBC', HASH_KEY, 0, HASH_IV_KEY);
    return  $decrypted_card_number ;
}

/**
 * openssl decrypt card
 *
 * @param string $string
 * @return string
 */
function sortSetting($data)
{
    $sort_array = (array)$data;
    asort($sort_array);
    return $sort_array;
}


/**
 * count badal offers
 *
 * @return string
 */
function badalOfferCount()
{
    require_once 'models/Badaloffer.php'; 
    $model = new Badaloffer();
    $offers = $model->getPendingOffers();
    return @$offers->count;
}

/**
 * count badal offers
 *
 * @return string
 */
function badalOrderCount()
{
    require_once 'models/Badalorder.php'; 
    $model = new Badalorder();
    $offers = $model->getPendingOrders();
    return @$offers->count;
}


/**
 * hash Id  
 *
 * @return string
 */
function orderIdentifier($val = null) {
    $result =    (((0x0000FFFF & $val) << 16) + ((0xFFFF0000 & $val) >> 16));
    return $result;

}

/**
 * undo the hash Id  
 *
 * @return string
 */
function getOrderId($val = null) {
    $result1 =    ((0x0000FFFF | $val) >> 16 ) ;
    return  $result1;
}
