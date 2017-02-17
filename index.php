<?php
//$_SESSION["username"] stores the username
//$_SESSION["usertype"] stores whether the user is an admin or a broker
//$_SESSION["userid"] stores the personid in person table
//$username stores the username
//$usertype stores whether the user is an admin or a broker

include_once "config.php";

$username = $_SESSION["username"];
$usertype = $_SESSION["usertype"];
if (strlen($username) == 0)
    header("Location: login.php");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Main Page</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="websiteheader">
        <h1>
            <img src="logo.jpg" width="50px" alt="Brokerage">
            Commodity Broker
            <img src="logo.jpg" width="50px" alt="Brokerage">
        </h1>
    </div>

    <?php include_once "menu.php" ?>

    <div class="websitebody">
    </div>

    <div class="websitefooter">
        <p>For queries, please contact the <a href="mailto:czhe171@aucklanduni.ac.nz">webmaster</a></p>
    </div>
</body>
</html>