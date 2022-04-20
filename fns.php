<?php
session_start();
$now = time();
if (isset($_SESSION['expire']) && $now > $_SESSION['expire']) {
    // this session has worn out its welcome; kill it and start a brand new one
    session_unset();
    session_destroy();
    session_start();
}
if (isset($_SESSION["messagetype"]) && $_SESSION["messagetype"] != "" && $_SESSION["message"] != "" ) {
    $ERROR_TYPE = $_SESSION["messagetype"];
    $ERROR_MSG = $_SESSION["message"];
    $_SESSION["messagetype"] = "";
    $_SESSION["message"] = "";
}
define("Functions", true);
require_once("inc/functions.php");
require_once("inc/config.php");
require_once("inc/mysql.php");
require_once("inc/mqtt.php");
