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



//Database Params

define('DB_HOST', '10.0.1.226');
define('DB_USER', 'root');
define('DB_PASS', 'admiN.321');
define('DB_NAME', 'namaa_badal');


// define('DB_HOST', 'localhost');
// define('DB_USER', 'e1s_badal');
// define('DB_NAME', 'e1s_badal');
// define('DB_PASS', 'bK=75DBPEAt$oUvf');


define('VERSION', '2.0.0');



//app root

define('APPROOT', dirname(dirname(__FILE__)));



// domain

define('DOMAIN', 'https://badal.e1s.me');
// define('DOMAIN', 'http://localhost:8000');



// site folder "leave it blank if its on the main domain"
define('SITEFOLDER', '');



// url root
define('URLROOT', DOMAIN . SITEFOLDER);



// Media FOLDER
define('MEDIAFOLDER', '/media/images');



// Media url root
define('MEDIAURL', URLROOT . '/media/images');



//admin root

define('ADMINROOT', dirname(dirname(__FILE__)) . '/admin');



// Admin url root

define('ADMINURL', URLROOT . '/admin');

// site name

define('SITENAME', 'جمعية نماء الأهلية');

// site name

define('KEYWORDS', 'جمعية خيرية');

// set time zone

date_default_timezone_set("Asia/Kuwait");

//default language

define('DEFAULT_LANGUAGE', 'ar');


//maintenance mode
define('MAINTENANCE', false);
// define('MAINTENANCE', true);

//maintenance mode
define('HASH_KEY', '9d13bef835019583243b97bab19a2308549811ea27347b82e9c28bdd16a5c2b1');
//maintenance mode
define('HASH_IV_KEY', 'b32710e8207fb5a5');
// testmode for payment
define('TEST_MODE', false);

define('PAYFORT_REDIRECT', True);

// notfication domain
define('NOTIFICATION_DOMAIN', 'https://namaa.sa/notify/public');

// setting temp folder

putenv('TMPDIR=/home/snamaa/tmp');

/**

 * print root url

 *

 * @param  mixed $path

 *

 * @return void

 */

function root($path = null)

{

    echo isset($path) ? URLROOT . "/" . $path : URLROOT;
}
