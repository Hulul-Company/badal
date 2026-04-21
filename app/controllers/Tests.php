<?php





class Tests extends Controller

{



    public function index()

    {

        // echo uniqid();

        // echo  date("h:i:sa");

        echo 'this is the index';

    }



    public function pushNotify()

    {

        $donor_id = 24;

        // $donor_id = 42925;

        sendPush('test', 'test_message', $donor_id);

        echo "send Push";

    }



    public function bcrypt()

    {

        // encrypt cvv & expire date ---------------------

        $str = '750';

        echo  "CVV : " . $str ."<br>";

        $encode =  (((0x0000FFFF & $str) << 48) + ((0xFFFF0000 & $str) >> 48));

        echo  "CVV encrypt : " . $encode ."<br>";

        $dencode =  ((0x0000FFFF | $encode) >> 48);

        echo  "CVV decrypt : " . $dencode ."<br>";



        echo  "<br> ------------------ <br> <br>";



        // encrypt cart number &  Name--------------------

        $card_number = '1234567890123456' ;

        echo  "card number : " . $card_number ."<br>";

        $key = hash('sha256', mt_rand());

        $initial_vector_key = substr(sha1(mt_rand()),17,16); //To Generate Random Numbers with Letters.

        echo  "key  : " . $key ."<br>";

        echo  "initial_vector_key : " . $initial_vector_key ."<br>";

        $encrypted_card_number = openssl_encrypt($card_number, 'AES-256-CBC', $key, 0, $initial_vector_key);

        echo  "card number encrypt : " . $encrypted_card_number ."<br>";

        $decrypted_card_number = openssl_decrypt($encrypted_card_number, 'AES-256-CBC', $key, 0, $initial_vector_key);

        echo "card number decrypt : " . $decrypted_card_number."<br>";



    }



    public function whatsapp()

    {

        // $modal = $this->model('Project');

        $messaging = $this->model('Messaging');

        // echo $messaging->ReciveOrdersApp('0597767751', 'مشروع صدقة جارية', '1984', '100','namaa.sa');

        // echo $messaging->ConfirmedOrdersApp('0597767751', 'مشروع صدقة جارية', '1984', '100');

        $mobile = trim('0597767751');

        $donor = trim('Ahmed ');

        $identifier = trim('7002222222222222');

        $total = trim('1000');

        $project = 'مشروع صدقة جارية';

        $messaging->sendConfirmation([

            'mailto' => 'a6e6s1@gmail.com',

            'mobile' => $mobile,

            'identifier' => $identifier,

            'total' => $total,

            'project' => $project,

            'donor' => $donor,

            'subject' => 'تم تجربة طلب جديد ',

            'msg' => "تم تسجيل طلب جديد بمشروع : ",

        ]);

    }



    public function orderTestData()

    {

        $modal = $this->model('Test');

        $tests = $modal->get('*', null, null, null, null, 'date');

        foreach ($tests as $test) {

            echo "<pre>";

            print_r(json_decode($test->data));

            echo "</pre> \n\t";

        }

    }

    

    public function sendSms()
    {

        echo sendSMS('NAMAA.SA', '993807dc8cf53bff5def5d9b5e6c8d34', 'testing sending', '0597767751', 'NAMAA.SA', 'https://api.taqnyat.sa/v1/messages', 'taqnyat');
    }


    public function smsCurl()
    {
        $post = 'https://api.taqnyat.sa/v1/messages?bearerTokens=993807dc8cf53bff5def5d9b5e6c8d34&sender=NAMAA.SA&recipients=0597767751&body=testingsending';
        $params = [
            "bearerTokens" => '993807dc8cf53bff5def5d9b5e6c8d34',
            "sender" => 'NAMAA.SA',
            "recipients" => '0597767751',
            "body" => 'testing sending',
        ];
        $ch = curl_init();
        $url = 'https://api.taqnyat.sa/v1/messages' . '?' . http_build_query($params);
        // curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_URL, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        // excution    
        $respond = curl_exec($ch);
        // close connection    
        curl_close($ch);
        dd($respond);
        //using the return as a PHP array
        return $respond ? TRUE : FALSE;
    }


    public function sms()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $ch = curl_init();
        $password = '1a58b9ef96054f6433f5e2f4d30a93fe';
        $post = 'https://api.taqnyat.sa/v1/messages?bearerTokens=' . urlencode($password)
            . '&sender=NAMAA.SA&recipients=0597767751&body=test';

        curl_setopt($ch, CURLOPT_URL, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 

        $respond = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        dd([
            'response' => $respond,
            'error' => $error,
            'http_code' => $httpCode
        ]);
    }
    public function myip()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://httpbin.org/get');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $respond = curl_exec($ch);
        curl_close($ch);
        dd(json_decode($respond));
    }
    public function respond()

    {



        var_dump($_GET);

        var_dump($_POST);

    }



    public function redirect()

    {

        $requestParams = array(

            'command' => 'AUTHORIZATION',

            'access_code' => 'zx0IPmPy5jp1vAz8Kpg7',

            'merchant_identifier' => 'CycHZxVj',

            'merchant_reference' => 'XYZ9239-yu898',

            'amount' => '10000',

            'currency' => 'AED',

            'language' => 'en',

            'customer_email' => 'test@payfort.com',

            'signature' => '7cad05f0212ed933c9a5d5dffa31661acf2c827a',

            'order_description' => 'iPhone 6-S',

            'return_url' => 'http://localhost/Blank-MVC/test/respond',

        );

        $request = array_merge($_POST, $requestParams);



        $redirectUrl = 'https://sbcheckout.payfort.com/FortAPI/paymentPage';

        echo "<html xmlns='http://www.w3.org/1999/xhtml'>\n<head></head>\n<body>\n";

        echo "<form action='$redirectUrl' method='post' name='frm'>\n";

        foreach ($request as $a => $b) {

            echo "\t<input type='hidden' name='" . htmlentities($a) . "' value='" . htmlentities($b) . "'>\n";

        }

        echo "\t<script type='text/javascript'>\n";

        echo "\t\tdocument.frm.submit();\n";

        echo "\t</script>\n";

        echo "</form>\n</body>\n</html>";

    }

    public function test2()

    {

        echo '<form name=‘fr’ action=‘redirect(.)php’ method=‘POST’>

        <include type=‘hidden’ name=‘var1’ value=‘val1’>

        <include type=‘hidden’ name=‘var2’ value=‘val2’>

        </form>

        <script type=‘text/javascript’>

        document.fr.submit();

        </script>';

    }





    public function imgWrite()

    {

        if (isset($_POST['submit'])) {

            $text1 = $_POST['text1'];

            $text2 = $_POST['text2'];

            $text3 = $_POST['text3'];

            // $text1Size = strlen($_POST['text1']) * 4;

            // $text2Size = strlen($_POST['text2']) * 6;

            // $text3Size = strlen($_POST['text3']) * 4;

            // var_dump($text1Size);

            // var_dump(imagefontwidth(40) * strlen($text3));

            // var_dump($text3Size);



            $lines = [

                ['x' => 690, 'y' => 130, 'text' => $text1, 'font' => true],

                ['x' => 690, 'y' => 310, 'text' => $text2, 'size' => 40],

                ['x' => 690, 'y' => 530, 'text' => $text3, 'font' => true],

            ];

            echo  '<img src ="' . str_replace(APPROOT, URLROOT, imgWrite(APPROOT . MEDIAFOLDER . '/test.jpg', $lines, APPROOT . MEDIAFOLDER . '/default2.jpg', 20, 'white')) . '" />';

        } else {

            echo '<!doctype html>

            <html lang="en">

              <head>

                <title>Title</title>

                <!-- Required meta tags -->

                <meta charset="utf-8">

                <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

            

                <!-- Bootstrap CSS -->

                <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

              </head>

              <body>

              <div class="row">

                  <div class="col-6 offset-3">

                  <p>Image write</p>

                  <form method="post" class="card p-3">

                  <div class="form-group ">

                    <label for="">text to image</label>

                    <input type="text" name="text1" class="form-control">

                    <input type="text" name="text2" class="form-control">

                    <input type="text" name="text3" class="form-control">

                  </div>

                  <button type="submit" name="submit" class="btn btn-primary">Submit</button>

                  </form> 

                  </div>

              </div>

              

                <!-- Optional JavaScript -->

                <!-- jQuery first, then Popper.js, then Bootstrap JS -->

              </body>

            </html>';

        }

    }



    

    public function smtp()

    {

        if (isset($_POST['send'])) {

            echo (extension_loaded('openssl') ? 'SSL loaded</br>' : 'SSL not loaded</br>') . "\n</br>";

            require_once APPROOT . '/app/models/Page.php';

            $modal = new Page();

    $email = $modal->Email($_POST['email'], 'العنوان بالعربيه', 'هل تصل الرسالة اذا ما كانت بالعربية , you . At the very least you ,will', false, true);



       

        } else {

            echo

            '<form action="" method="post">

                <input type="text" name="email" id="" placeholder="email">

                <input type="submit" value="send" name="send">

            </form>';

        }

    }



    public function rorn()

    {

        $hash = false ?:  null;

        var_dump($hash);

    }



    public function uniqnum()

    {

        $modal = $this->model('Project');

        echo $modal->uniqNum(10068772751);

    }





    public function doquery()

    {

        require_once APPROOT . '/app/models/Page.php';

        $modal = new Page;

        $query = 'ALTER TABLE `app_articles` ADD `section_id` INT NOT NULL AFTER `content`;';

        if (@$modal->queryResult($query)) {

            echo 'done';

        };

    }



    public function applepay()

    {

        // update these with the real location of your two .pem files. keep them above/outside your webroot folder

        define('PRODUCTION_CERTIFICATE_KEY', APPROOT . '/helpers/certificate/namaa.key.pem');

        define('PRODUCTION_CERTIFICATE_PATH',  APPROOT . '/helpers/certificate/namaa.crt.pem');

        // This is the password you were asked to create in terminal when you extracted ApplePay.key.pem

        define('PRODUCTION_CERTIFICATE_KEY_PASS', '1234Five');



        define('PRODUCTION_MERCHANTIDENTIFIER', openssl_x509_parse(file_get_contents(PRODUCTION_CERTIFICATE_PATH))['subject']['UID']); //if you have a recent version of PHP, you can leave this line as-is. http://uk.php.net/openssl_x509_parse will parse your certificate and retrieve the relevant line of text from it e.g. merchant.com.name, merchant.com.mydomain or merchant.com.mydomain.shop

        // if the above line isn't working for you for some reason, comment it out and uncomment the next line instead, entering in your merchant identifier you created in your apple developer account

        // define('PRODUCTION_MERCHANTIDENTIFIER', 'merchant.com.name');

        define('PRODUCTION_DOMAINNAME', $_SERVER["HTTP_HOST"]);

        //you can leave this line as-is too, it will take the domain from the server you run it on e.g. shop.mydomain.com or mydomain.com

        // if the line above isn't working for you, replace it with the one below, updating it for your own domain name

        // define('PRODUCTION_DOMAINNAME', 'mydomain.com');

        define('PRODUCTION_CURRENCYCODE', 'SAR');    // https://en.wikipedia.org/wiki/ISO_4217

        define('PRODUCTION_COUNTRYCODE', 'SA');        // https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2

        define('PRODUCTION_DISPLAYNAME', 'Namaa');

        define('DEBUG', 'false');





        // if (isset($_GET['apple_data'])) {

        //     require_once APPROOT . '/helpers/PayfortApplPayIntegration.php';

        // }

?>

        <!DOCTYPE html>

        <html>



        <head>

            <meta charset="UTF-8">

            <meta name="viewport" content="width=device-width, initial-scale=1">

            <link rel="apple-touch-icon" sizes="120x120" href="images/touch-icon-120.png">

            <link rel="apple-touch-icon" sizes="152x152" href="images/touch-icon-152.png">

            <link rel="apple-touch-icon" sizes="167x167" href="images/touch-icon-167.png">

            <link rel="apple-touch-icon" sizes="180x180" href="images/touch-icon-180.png">

            <link rel="stylesheet" href="<?php echo URLROOT; ?>/templates/namaa/css/main.min.css" />

            <title>Apple Pay Example</title>

        </head>



        <body>

            <div class="apple-pay">

                <div id="wrapperHeader">



                </div>



                <div class="text-center mt-5 pt-5">

                    <input type="number" id="amount" name="amount">

                    <button type="button" id="applePay"></button>



                    <p style="display:none" id="got_notactive">ApplePay is possible on this browser, but not currently activated.</p>

                    <p style="display:none" id="notgot">ApplePay is not available on this browser</p>

                    <p style="display:none" id="success">Transaction completed, thanks. <a href="<?= $_SERVER["SCRIPT_URL"] ?>">reset</a></p>

                    <p style="display:none" id="failed">Transaction failed</p>

                </div>



            </div>





            <script src="<?php echo URLROOT; ?>/templates/namaa/js/jquery.min.js"></script>

            <script src="<?php echo URLROOT; ?>/templates/namaa/js/bootstrap.bundle.min.js"></script>

            <script src="<?php echo URLROOT; ?>/templates/namaa/js/owl.carousel.min.js"></script>

            <script src="<?php echo URLROOT; ?>/templates/namaa/js/wow.min.js"></script>

            <script src="<?php echo URLROOT; ?>/templates/namaa/js/jquery.inputmask.min.js"></script>

            <script src="<?php echo URLROOT; ?>/templates/namaa/js/main.js"></script>



            <script>

                /**applepay js */

                var debug = false;

                if (window.ApplePaySession) {

                    var merchantIdentifier = '<?= PRODUCTION_MERCHANTIDENTIFIER ?>';

                    var promise = ApplePaySession.canMakePaymentsWithActiveCard(merchantIdentifier);

                    promise.then(function(canMakePayments) {

                        if (canMakePayments) {

                            document.getElementById("applePay").style.display = "block";

                            logit('hi, I can do ApplePay');

                        } else {

                            document.getElementById("got_notactive").style.display = "block";

                            logit('ApplePay is possible on this browser, but not currently activated.');

                        }

                    });

                } else {

                    logit('ApplePay is not available on this browser');

                    document.getElementById("notgot").style.display = "block";

                }

                document.getElementById("applePay").onclick = function(evt) {

                    var totalAmount = $('#amount').val();

                    $('#success').html('Test transaction completed with SR ' + totalAmount + ', thanks.');

                    var shippingOption = "";

                    var subTotalDescr = "Test Goodies";

                    var paymentRequest = {

                        currencyCode: '<?= PRODUCTION_CURRENCYCODE ?>',

                        countryCode: '<?= PRODUCTION_COUNTRYCODE ?>',

                        total: {

                            label: '<?= PRODUCTION_DISPLAYNAME ?>',

                            amount: totalAmount

                        },

                        supportedNetworks: ['masterCard', 'visa', 'mada'],

                        merchantCapabilities: ['supports3DS']

                    };

                    var session = new ApplePaySession(1, paymentRequest);

                    // Merchant Validation

                    session.onvalidatemerchant = function(event) {

                        logit(event);

                        var promise = performValidation(event.validationURL);

                        promise.then(function(merchantSession) {

                            session.completeMerchantValidation(merchantSession);

                        });

                    }



                    function performValidation(valURL) {

                        return new Promise(function(resolve, reject) {

                            var xhr = new XMLHttpRequest();

                            xhr.onload = function() {

                                var data = JSON.parse(this.responseText);

                                logit(data);

                                resolve(data);

                            };

                            xhr.onerror = reject;

                            xhr.open('GET', '<?= URLROOT ?>/public/payfort10/apple_pay_comm.php?u=' + valURL);

                            xhr.send();

                        });

                    }

                    session.onshippingcontactselected = function(event) {

                        logit('starting session.onshippingcontactselected');

                        logit('NB: At this stage, apple only reveals the Country, Locality and 4 characters of the PostCode to protect the privacy of what is only a *prospective* customer at this point. This is enough for you to determine shipping costs, but not the full address of the customer.');

                        logit(event);

                        var status = ApplePaySession.STATUS_SUCCESS;

                        var newTotal = {

                            type: 'final',

                            label: '<?= PRODUCTION_DISPLAYNAME ?>',

                            amount: totalAmount

                        };

                        var newLineItems = [{

                            type: 'final',

                            label: subTotalDescr,

                            amount: totalAmount

                        }];

                        session.completeShippingContactSelection(status);

                    }

                    session.onshippingmethodselected = function(event) {

                        logit('starting session.onshippingmethodselected');

                        logit(event);

                        var status = ApplePaySession.STATUS_SUCCESS;

                        var newTotal = {

                            type: 'final',

                            label: '<?= PRODUCTION_DISPLAYNAME ?>',

                            amount: totalAmount

                        };

                        var newLineItems = [{

                            type: 'final',

                            label: subTotalDescr,

                            amount: totalAmount

                        }];

                        session.completeShippingMethodSelection(status, newTotal, newLineItems);

                    }

                    session.onpaymentmethodselected = function(event) {

                        logit('starting session.onpaymentmethodselected');

                        logit(event);

                        var newTotal = {

                            type: 'final',

                            label: '<?= PRODUCTION_DISPLAYNAME ?>',

                            amount: totalAmount

                        };

                        var newLineItems = [{

                            type: 'final',

                            label: subTotalDescr,

                            amount: totalAmount

                        }];

                        session.completePaymentMethodSelection(newTotal, newLineItems);

                    }

                    session.onpaymentauthorized = function(event) {

                        logit('starting session.onpaymentauthorized');

                        logit('NB: This is the first stage when you get the *full shipping address* of the customer, in the event.payment.shippingContact object');

                        logit(event);

                        var promise = sendPaymentToken(event.payment.token);

                        promise.then(function(success) {

                            var status;

                            console.log(success)

                            if (success) {

                                status = ApplePaySession.STATUS_SUCCESS;

                                document.getElementById("applePay").style.display = "none";

                                session.completePayment(status);

                                document.getElementById("success").style.display = "block";

                            } else {

                                status = ApplePaySession.STATUS_FAILURE;

                                session.completePayment(status);

                                document.getElementById("failed").style.display = "block";

                            }

                        });

                    }



                    function sendPaymentToken(paymentToken) {

                        return new Promise(function(resolve, reject) {

                            logit('starting function sendPaymentToken()');

                            logit(paymentToken);





                            $.ajax({

                                type: 'POST',

                                url: '<?= URLROOT ?>/public/payfort10/payfort.php',

                                data: {

                                    apple_data: paymentToken.paymentData.data,

                                    apple_signature: paymentToken.paymentData.signature,

                                    apple_transactionId: paymentToken.paymentData.header.transactionId,

                                    apple_ephemeralPublicKey: paymentToken.paymentData.header.ephemeralPublicKey,

                                    apple_publicKeyHash: paymentToken.paymentData.header.publicKeyHash,

                                    apple_displayName: paymentToken.paymentMethod.displayName,

                                    apple_network: paymentToken.paymentMethod.network,

                                    apple_type: paymentToken.paymentMethod.type,

                                    amount: totalAmount

                                },

                                dataType: "JSON",

                                success: function(data) {

                                    var parsed_json = JSON.parse(data);

                                    var status = parsed_json.status;

                                    if (status == 14) {

                                        resolve(true);

                                    } else {

                                        resolve(false);

                                    }



                                }

                            });





                            logit("this is where you would pass the payment token to your third-party payment provider to use the token to charge the card. Only if your provider tells you the payment was successful should you return a resolve(true) here. Otherwise reject;");

                            logit("defaulting to resolve(true) here, just to show what a successfully completed transaction flow looks like");



                        });

                    }

                    session.oncancel = function(event) {

                        logit('starting session.cancel');

                        logit(event);

                    }

                    session.begin();

                };



                function logit(data) {

                    if (debug == true) {

                        console.log(data);

                    }

                };

                /**end of applepay */

            </script>

        </body>



        </html>





<?php

    }

}

