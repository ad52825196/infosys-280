<?php
//$person is the person table in database
//$theperson is a row in person table
//$_SESSION["username"] stores the username
//$_SESSION["usertype"] stores whether the user is an admin or a broker
//$_SESSION["userid"] stores the personid in person table

include_once "config.php";

if ($_REQUEST["submitter"] == "Logout")
    $_SESSION["username"] = "";
if (strlen($_SESSION["username"]) > 0)
    header("Location: index.php");
$username = $_REQUEST["username"];
$password = $_REQUEST["password"];

$errormessage = "";

if ($_REQUEST["submitter"] == "Login")
{
    $person = $mydb -> query("SELECT * FROM person WHERE Personname = %s", $username);
    if ($person -> countReturnedRows() == 0)
        $errormessage = "errorinfield(login.username, 'Username ".'"'.$username.'"'." not found!');";
    else
    {
        $theperson = $person -> fetchRow();
        if ($password != $theperson["password"])
            $errormessage = "errorinfield(login.password, 'Wrong password!');";
        else
        {
            $_SESSION["username"] = $username;
            if ($theperson["isadmin"] == "Y" || $theperson["isadmin"] == "y")
                $_SESSION["usertype"] = "admin";
            else
                $_SESSION["usertype"] = "broker";
            $_SESSION["userid"] = $theperson["Personid"];
            header("Location: index.php");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <script src="common.js"></script>
    <script>
    function doalert()
    {
        <?php echo $errormessage; ?>
    }

    //control whether the login button should be disabled or not
    function check()
    {
        login.submitter.disabled = (login.username.value.length > 0) ? false : true;
    }
    </script>
</head>
<body onload="doalert()">
    <form name="login" method="post" action="login.php">
        <h1 align="center">Enter your login details</h1>
        <hr />
        <table align="center">
            <tr>
                <td align="right">Username:</td>
                <td><input type="text" name="username" value="<?php echo $username; ?>" onblur="check()"/></td>
            </tr>
            <tr>
                <td align="right">Password:</td>
                <td><input type="password" name="password" value="<?php echo $password; ?>"/></td>
            </tr>
            <tr>
                <td><input type="submit" name="submitter" value="Login" <?php if (strlen($username) == 0) echo "disabled"; ?>/></td>
            </tr>
        </table>
    </form>
</body>
</html>