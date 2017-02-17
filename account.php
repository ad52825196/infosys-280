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
if ($usertype != "admin")
    header("Location: index.php");

$brokercat = $mydb -> query("SELECT * FROM brokercat");

$mode = 1; //1. Search 2. Search by name with multiple results 3. Data
$errormessage = "";

$personid = "";
$personname = "";
$personpassword = "";
$persontype = ""; //"Y" -> admin "N" -> broker
$address = "";
$category = "";
$phone = "";
$email = "";

if ($_REQUEST["submitter"] == "Save")
{
    $personid = $_REQUEST["personid"];
    $personname = $_REQUEST["personname"];
    $personpassword = $_REQUEST["personpassword"];
    $persontype = $_REQUEST["persontype"];
    $address = $_REQUEST["address"];
    $category = $_REQUEST["category"];
    $phone = $_REQUEST["phone"];
    $email = $_REQUEST["email"];
    if ($personid == "(System Specified)")
    {
        //test whether this username exists. If it exists, report an error
        $person = $mydb -> query("SELECT null FROM person WHERE Personname = %s", $personname);
        if ($person -> countReturnedRows() == 0)
        {
            //compute next person id
            $person = $mydb -> query("SELECT MAX(Personid) AS mx FROM person");
            if ($person -> countReturnedRows() == 0)
                $personid = 1;
            else
            {
                $theperson = $person -> fetchRow();
                $personid = $theperson["mx"] + 1;
            }
            $mydb -> execute("INSERT INTO person (Personid, Personname, isadmin, password) VALUES (%i, %s, %s, %s)", $personid, $personname, $persontype, $personpassword);
            if ($persontype == "N")
                $mydb -> execute("INSERT INTO broker (brokerid, address, brokercat, phone, email) VALUES (%i, %s, %i, %s, %s)", $personid, $address, $category, $phone, $email);
            $errormessage = "alert('Saved new account ".$personid."');";
        }
        else
        {
            $mode = 3;
            $personid = "(System Specified)";
            $errormessage = "alert('Username ".'"'.$personname.'"'." already exists'); account.personname.focus(); account.personname.select();";
        }
    }
    else
    {
        $person = $mydb -> query("SELECT Personname FROM person WHERE Personid = %i", $personid);
        if ($person -> countReturnedRows() > 0)
        {
            $theperson = $person -> fetchRow();
            $person = $mydb -> query("SELECT null FROM person WHERE Personname = %s", $personname);
            if ($personname == $theperson["Personname"] || $person -> countReturnedRows() == 0)
            {
                $mydb -> execute("UPDATE person SET Personname = %s, isadmin = %s, password = %s WHERE Personid = %i", $personname, $persontype, $personpassword, $personid);
                $mydb -> execute("DELETE FROM broker WHERE brokerid = %i", $personid);
                if ($persontype == "N")
                    $mydb -> execute("INSERT INTO broker (brokerid, address, brokercat, phone, email) VALUES (%i, %s, %i, %s, %s)", $personid, $address, $category, $phone, $email);
                $errormessage = "alert('Saved existing account ".$personid."');";
            }
            else
            {
                $mode = 3;
                $personid = $personid;
                $errormessage = "alert('Username ".'"'.$personname.'"'." already exists'); account.personname.focus(); account.personname.select();";
            }
        }
        else
            $errormessage = "alert('Sorry, account with ID ".$personid." was deleted by someone else');";
    }
}
else if ($_REQUEST["submitter"] == "Search")
{
    $personid = $_REQUEST["searchpersonid"];
    $personname = $_REQUEST["searchpersonname"];
    if (strlen($personid) > 0)
    {
        $person = $mydb -> query("SELECT * FROM person WHERE Personid = %i", $personid);
        if ($person -> countReturnedRows() == 0)
            $errormessage = 'alert("ID '.$personid.' not found!");';
        else
        {
            $theperson = $person -> fetchRow();
            $personid = $theperson["Personid"];
            $personname = $theperson["Personname"];
            $personpassword = $theperson["password"];
            $persontype = $theperson["isadmin"];
            if ($persontype == "N" || $persontype == "n")
            {
                $broker = $mydb -> query("SELECT * FROM broker WHERE brokerid = %i", $personid);
                if ($broker -> countReturnedRows() > 0)
                {
                    $thebroker = $broker -> fetchRow();
                    $address = $thebroker["address"];
                    $category = $thebroker["brokercat"];
                    $phone = $thebroker["phone"];
                    $email = $thebroker["email"];
                }
            }
            $mode = 3;
        }
    }
    else if (strlen($personname) > 0)
    {
        $person = $mydb -> query("SELECT * FROM person WHERE Personname LIKE %s;", "%".$personname."%");
        if ($person -> countReturnedRows() == 0)
            $errormessage = 'alert("Username '.$personname.' not found!");';
        else if ($person -> countReturnedRows() == 1)
        {
            $theperson = $person -> fetchRow();
            $personid = $theperson["Personid"];
            $personname = $theperson["Personname"];
            $personpassword = $theperson["password"];
            $persontype = $theperson["isadmin"];
            if ($persontype == "N" || $persontype == "n")
            {
                $broker = $mydb -> query("SELECT * FROM broker WHERE brokerid = %i", $personid);
                if ($broker -> countReturnedRows() > 0)
                {
                    $thebroker = $broker -> fetchRow();
                    $address = $thebroker["address"];
                    $category = $thebroker["brokercat"];
                    $phone = $thebroker["phone"];
                    $email = $thebroker["email"];
                }
            }
            $mode = 3;
        }
        else
        {
            $mode = 2;
        }
    }
}
else if ($_REQUEST["submitter"] == "New Account")
{
    $mode = 3;
    $personid = "(System Specified)";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
    .broker
    {
        visibility: <?php echo $persontype == "N" || $persontype == "n" ? "visible" : "hidden"; ?>;
    }
    </style>
    <script src="common.js"></script>
    <script>
    function doalert()
    {
        <?php echo $errormessage; ?>
    }

    var needcheck = false;

    function changepassword()
    {
        needcheck = true;
    }

    function checkpassword()
    {
        if (!needcheck)
            return true;
        if (account.personpassword.value == account.checkpassword.value)
            needcheck = false;
        return !needcheck;
    }

    function reportpassword()
    {
        if (!checkpassword())
            passworderror.innerHTML = "Unmatched password!";
        else
            passworderror.innerHTML = "";
    }

    function validatephone(whichcontrol)
    {
        var passcheck = true;
        var x = 0;
        var ch;
        var token;
        var havetoken = false;
        var meetslash = false;
        var numdigits = 0;
        var cellphone;
        var whichvalue = whichcontrol.value;
        if (whichvalue.length == 0)
            return true;
        while (x < whichvalue.length && whichvalue.charAt(x) == '0' && x < 2)
            x++;
        ch = whichvalue.charAt(x);
        if (x == whichvalue.length || ch == '0' || ch == '1' || ch == '5' || ch == '8')
            passcheck = false;
        else if (ch == '2')
            cellphone = true;
        else
            cellphone = false;
        x++;
        while (passcheck && x < whichvalue.length)
        {
            ch = whichvalue.charAt(x);
            if (ch >= '0' && ch <= '9')
                numdigits++;
            else if (ch == '/' && cellphone)
                passcheck = false;
            else if (ch == '/')
                meetslash = true;
            else if (havetoken)
            {
                if (ch != token)
                    passcheck = false;
            }
            else if (!havetoken && (ch == ' ' || ch == '-'))
            {
                havetoken = true;
                token = ch;
            }
            else
                passcheck = false;
            x++;
        }
        if (passcheck && (cellphone && numdigits > 7 && numdigits < 10 || !cellphone && numdigits == 7 || !cellphone && meetslash))
            return true;
        return errorinfield(whichcontrol, "Invalid NZ phone number!");
    }

    function validateemail(whichcontrol)
    {
        var passcheck = true;
        var x = 0;
        var ch;
        var before;
        var meetfirst = false;
        var meetsecond = false;
        var meetat = false;
        var meetdot = true;
        var dotposition;
        var whichvalue = whichcontrol.value;
        if (whichvalue.length == 0)
            return true;
        while (passcheck && x < whichvalue.length)
        {
            ch = whichvalue.charAt(x);
            if (!meetat)
            {
                if (ch >= '0' && ch <= '9' || ch >= 'a' && ch <= 'z' || ch >= 'A' && ch <= 'Z' || ch == '_' || ch == '-')
                {
                    meetfirst = true;
                    meetdot = false;
                }
                else if (ch == '.')
                {
                    if (meetdot)
                        passcheck = false;
                    else
                        meetdot = true;
                }
                else if (ch == "@")
                {
                    if (meetdot)
                        passcheck = false;
                    else
                        meetat = true;
                }
                else
                    passcheck = false;
                x++;
            }
            else
            {
                before = whichvalue.charAt(x - 1);
                if (ch >= '0' && ch <= '9' || ch >= 'a' && ch <= 'z' || ch >= 'A' && ch <= 'Z' || ch == '_' || ch == '-')
                {
                    if (!meetdot)
                        meetsecond = true;
                }
                else if (ch == '.')
                {
                    if (before == '.')
                        passcheck = false;
                    else
                    {
                        meetdot = true;
                        dotposition = x;
                    }
                }
                else
                    passcheck = false;
                x++;
            }
        }
        if (passcheck && meetdot && meetat && meetfirst && meetsecond && dotposition + 2 < whichvalue.length)
        {
            for (x=dotposition+1; x<whichvalue.length; x++)
            {
                ch = whichvalue.charAt(x);
                if ((ch < 'a' || ch > 'z') && (ch < 'A' || ch > 'Z'))
                    passcheck = false;
            }
            if (passcheck)
                return true;
        }
        return errorinfield(whichcontrol, "Invalid email address!");
    }

    function personnameselect()
    {
        var myhidden = document.createElement("input");
        myhidden.type = "hidden";
        myhidden.name = "submitter";
        myhidden.value = "Search";
        account.appendChild(myhidden);
        account.submit();
    }

    //control the visibility of broker's data entry according to the persontype
    function changevisible()
    {
        var broker = document.getElementsByClassName("broker");
        var x = 0;
        for (x=0; x<broker.length; x++)
            broker.item(x).style.visibility = account.persontype.value == "N" ? "visible" : "hidden";
    }

    function checksave()
    {
        var errormessage = "";
        var categorychecked = false;
        var x;

        if (account.personname.value.length == 0)
        {
            errormessage += "\n Username";
            personnameerror.innerHTML = "*";
        }
        else
            personnameerror.innerHTML = "";
        if (!checkpassword())
        {
            errormessage += "\n Password";
            passworderror.innerHTML = "Unmatched password!";
        }
        else
            passworderror.innerHTML = "";
        //if this account is a broker, then check broker's data entry
        if (account.persontype.value == "N")
        {
            if (account.address.innerHTML.length == 0)
            {
                errormessage += "\n Address";
                addresserror.innerHTML = "*";
            }
            else
                addresserror.innerHTML = "";
            for (x=0; x<account.category.length; x++)
                if (account.category.item(x).checked)
                    categorychecked = true;
            if (!categorychecked)
            {
                errormessage += "\n Category";
                categoryerror.innerHTML = "Please choose one of the above!";
            }
            else
                categoryerror.innerHTML = "";
            if (account.phone.value.length == 0)
            {
                errormessage += "\n Phone";
                phoneerror.innerHTML = "*";
            }
            else
                phoneerror.innerHTML = "";
            if (account.email.value.length == 0)
            {
                errormessage += "\n Email";
                emailerror.innerHTML = "*";
            }
            else
                emailerror.innerHTML = "";
        }
        if (errormessage != "")
        {
            alert("You need to fill out the following fields:" + errormessage);
            return false;
        }
        else if (account.personpassword.value.length == 0)
            return confirm("Your password is going to be set empty. Are you sure?")
        else
            return confirm("Are you sure you wish to save?");
    }

    function cancel()
    {
        if (confirm("Do you really want to cancel?"))
            window.location = "account.php";
    }
    </script>
</head>
<body onload="doalert()">
    <div class="websiteheader">
        <h1>
            <img src="logo.jpg" width="50px" alt="Brokerage">
            Account
            <img src="logo.jpg" width="50px" alt="Brokerage">
        </h1>
    </div>

    <?php include_once "menu.php" ?>

    <div class="websitebody">
        <form name="account" method="post" action="account.php">
            <?php if ($mode < 3)
            {
            ?>
            <table>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submitter" value="New Account"/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"><b>Search By:</b></td>
                </tr>
                <tr>
                    <td align="right">ID:</td>
                    <td>
                        <input type="text" name="searchpersonid" size="5" onblur="validateint(account.searchpersonid)" <?php if ($mode == 2) echo "disabled"; ?>/>
                    </td>
                </tr>
                <tr>
                    <td align="right">Username:</td>
                    <td>
                        <?php if ($mode == 1)
                        {
                        ?>
                        <input type="text" name="searchpersonname"/>
                        <?php
                        }
                        else
                        {
                        ?>
                        <select name="searchpersonid" onchange="personnameselect()">
                            <option>--Please choose a username--</option>
                            <?php
                            foreach ($person as $row)
                            {
                            ?>
                            <option value="<?php echo $row['Personid']; ?>"><?php echo $row["Personname"]; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submitter" value="Search"/>
                    </td>
                </tr>
            </table>
            <?php
            }
            else
            {
            ?>
            <input type="hidden" name="personid" value="<?php echo $personid; ?>"/>
            <table>
                <tr>
                    <td colspan="2"><b>Data Entry:</b></td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="submitter" value="Save" onclick="return checksave()"/>
                    </td>
                    <td align="right">
                        <input type="button" name="cancelbutt" value="Cancel" onclick="cancel()"/>
                    </td>
                </tr>
                <tr>
                    <td align="right">ID:</td>
                    <td>
                        <?php echo $personid; ?>
                    </td>
                </tr>
                <tr>
                    <td align="right">Username:</td>
                    <td>
                        <input type="text" name="personname" value="<?php echo $personname; ?>"/>
                        <span class="error" id="personnameerror"></span>
                    </td>
                </tr>
                <tr>
                    <td align="right">Password:</td>
                    <td>
                        <input type="password" name="personpassword" value="<?php echo $personpassword; ?>" onkeydown="changepassword()"/>
                    </td>
                </tr>
                <tr>
                    <td align="right" valign="top">Check Password:</td>
                    <td>
                        <input type="password" name="checkpassword" onkeydown="changepassword()" onblur="reportpassword()"/>
                        <br />
                        <span class="error" id="passworderror"></span>
                    </td>
                </tr>
                <tr>
                    <td align="right">Type:</td>
                    <td>
                        <select name="persontype" onchange="changevisible()">
                            <option value="Y" <?php if ($persontype == "Y" || $persontype == "y") echo "selected"; ?>>Admin</option>
                            <option value="N" <?php if ($persontype == "N" || $persontype == "n") echo "selected"; ?>>Broker</option>
                        </select>
                    </td>
                </tr>
                <tr class="broker">
                    <td align="right" valign="top">Address:</td>
                    <td>
                        <textarea name="address" rows="5" cols="30"><?php echo $address; ?></textarea>
                        <span class="error" id="addresserror"></span>
                    </td>
                </tr>
                <tr class="broker">
                    <td align="right" valign="top">Category:</td>
                    <td>
                        <?php
                        foreach ($brokercat as $row)
                        {
                        ?>
                        <input type="radio" name="category" value="<?php echo $row['brokercode']; ?>" <?php if ($category == $row['brokercode']) echo "checked"; ?>/><?php echo $row["codedesc"]." (Limit: ".$row["limit"].")<br />"; ?>
                        <?php
                        }
                        ?>
                        <span class="error" id="categoryerror"></span>
                    </td>
                </tr>
                <tr class="broker">
                    <td align="right">Phone:</td>
                    <td>
                        <input type="text" name="phone" value="<?php echo $phone; ?>" onblur="validatephone(account.phone)"/>
                        <span class="error" id="phoneerror"></span>
                    </td>
                </tr>
                <tr class="broker">
                    <td align="right">Email:</td>
                    <td>
                        <input type="text" name="email" size="30" value="<?php echo $email; ?>" onblur="validateemail(account.email)"/>
                        <span class="error" id="emailerror"></span>
                    </td>
                </tr>
            </table>
            <?php
            }
            ?>
        </form>
    </div>

    <div class="websitefooter">
        <p>For queries, please contact the <a href="mailto:czhe171@aucklanduni.ac.nz">webmaster</a></p>
    </div>
</body>
</html>