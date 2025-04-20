<?php


define('APP', true);
define('VERSION', '1.0');

//start session
if(!isset($_SESSION))
{
   

    session_start();
}


// Error Reporting
if(!DEBUG)
{
    error_reporting(0);
}
else
{
    ini_set('display_error',1);
    ini_set('error_reporting',E_ALL);
    error_reporting(-1);
}

if(!file_exists(TMP_DIR)){
    @mkdir(TMP_DIR,0755);
}

if(!file_exists(ROOT.'/'.MEDIA_DIR)){
    @mkdir(ROOT.'/'.MEDIA_DIR,0755);
}


// Start Application
include(ROOT.'/includes/class/App.php');
$app = new App();




if(file_exists(ROOT.'/vendor/autoload.php')){
    include ROOT.'/vendor/autoload.php';
}




include(ROOT.'/includes/class/Helper.php');
include(ROOT.'/includes/class/Status.php');
include(ROOT.'/includes/class/Drive.php');
include(ROOT.'/includes/class/HLS.php');

