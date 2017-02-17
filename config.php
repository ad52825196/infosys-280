<?php
ini_set("error_reporting", E_ALL & ~E_NOTICE);
ini_set("display_errors", 1);

include_once $_SERVER['DOCUMENT_ROOT']."/flourish/toinclude.php";
$connstr = "ISOMTEACHING5\INFOSYS280";
$mydb = new fDatabase("mssql", "finance", "mytest", "", $connstr, 61495);

session_start();
?>