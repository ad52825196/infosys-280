<?php
//$_SESSION["username"] stores the username
//$_SESSION["usertype"] stores whether the user is an admin or a broker
//$_SESSION["userid"] stores the personid in person table
//$username stores the username
//$usertype stores whether the user is an admin or a broker
//$userid stores the personid in person table
//$userlimit stores the credit limit of current person

include_once "config.php";

$username = $_SESSION["username"];
$usertype = $_SESSION["usertype"];
$userid = $_SESSION["userid"];
if (strlen($username) == 0)
    header("Location: login.php");
if ($usertype != "broker")
    header("Location: index.php");

if (strlen($_REQUEST["nolines"]) == 0)
    $mode = 1; //1. Search 2. Data 3. Get item by name
else
    $mode = 2;
$errormessage = "";

//get credit limit of current person from database
$brokercat = $mydb -> query("SELECT limit FROM brokercat INNER JOIN broker ON brokercode = brokercat WHERE brokerid = %i", $userid);
if ($brokercat -> countReturnedRows() > 0)
{
    $therow = $brokercat -> fetchRow();
    $userlimit = $therow["limit"];
}
else
    $errormessage = "alert('There is a problem with your account. You do not belong to any user groups. Please contact the administrator to have a check.'); window.location = 'index.php';";

if ($mode > 1)
{
    $orderid = $_REQUEST["orderid"];
    $orderdate = $_REQUEST["orderdate"];
    $ordertype = $_REQUEST["ordertype"]; //"a" -> ask "b" -> bid
    $nolines = $_REQUEST["nolines"];
    $cargoid = array();
    $cargodesc = array();
    $qty = array();
    $paid = array();
    $tempid = "";
    $x = 0;
    for ($x=0; $x<$nolines; $x++)
    {
        $cargoid[$x] = $_REQUEST["CargoID".$x];
        $cargodesc[$x] = $_REQUEST["CargoDesc".$x];
        $qty[$x] = $_REQUEST["Qty".$x];
        $paid[$x] = $_REQUEST["Paid".$x];
    }
}

if ($_REQUEST["submitter"] == "Save")
{
    $total = $_REQUEST["total"];
    if ($ordertype == "b" && $total > $userlimit)
        $errormessage = "alert('Sorry, you cannot make this Bid! The value of this order is ".$total." but your credit limit is ".$userlimit.".');";
    else
    {
        if ($orderid == "(System Specified)")
        {
            $order = $mydb -> query("SELECT MAX(OrderNo) AS mx FROM [order]");
            if ($order -> countReturnedRows() == 0)
                $orderid = 1;
            else
            {
                $theorder = $order -> fetchRow();
                $orderid = $theorder["mx"] + 1;
            }
            $mydb -> execute("INSERT INTO [order] (OrderNo, OrderDate, brokerid, bid_ask) VALUES (%i, %d, %i, %s)", $orderid, substr($orderdate, 3, 2)."/".substr($orderdate, 0, 2)."/".substr($orderdate, 6), $userid, $ordertype);
        }
        else
            $mydb -> execute("UPDATE [order] SET OrderDate = %d, brokerid = %i, bid_ask = %s WHERE OrderNo = %i", substr($orderdate, 3, 2)."/".substr($orderdate, 0, 2)."/".substr($orderdate, 6), $userid, $ordertype, $orderid);
        $mydb -> execute("DELETE FROM ask WHERE orderid = %i", $orderid);
        $mydb -> execute("DELETE FROM bid WHERE orderid = %i", $orderid);
        if ($ordertype == "a")
            for ($x=0; $x<$nolines-1; $x++)
                $mydb -> execute("INSERT INTO ask (orderid, askitem, askqty, askprice, sno) VALUES (%i, %i, %i, %f, %i)", $orderid, $cargoid[$x], $qty[$x], $paid[$x], $x);
        else
            for ($x=0; $x<$nolines-1; $x++)
                $mydb -> execute("INSERT INTO bid (orderid, biditem, bidqty, bidprice, sno) VALUES (%i, %i, %i, %f, %i)", $orderid, $cargoid[$x], $qty[$x], $paid[$x], $x);
        $errormessage = "alert('Order ".$orderid." saved!');";
        $mode = 1;
    }
}
else if ($_REQUEST["submitter"] == "orderAddCargoDesc")
{
    $itemnum = $_REQUEST["itemtosearch"];
    $thefounddesc = $_REQUEST["CargoDesc".$itemnum];
    $thecargolist = $mydb -> query("SELECT ItemID, Description FROM items WHERE Description LIKE %s", "%".$thefounddesc."%");
    switch ($thecargolist -> countReturnedRows())
    {
        case 0:
            $cargodesc[$itemnum] = "";
            $errormessage = "errorinfield(order.CargoID".$itemnum.", 'No Item with Name ".$thefounddesc."'); order.CargoDesc".$itemnum.".focus();";
            break;
        case 1:
            $cargoline = $thecargolist -> fetchRow();
            $cargoid[$itemnum] = $cargoline["ItemID"];
            $flag = 0; //times that the cargoid occurred in the array
            foreach ($cargoid as $c)
                if ($c == $cargoid[$itemnum])
                    $flag++;
            if ($flag == 1)
            {
                $cargodesc[$itemnum] = $cargoline["Description"];
                if (strlen($qty[$itemnum]) == 0 || $_REQUEST["CargoID".$itemnum] != $cargoid[$itemnum] && strlen($_REQUEST["CargoID".$itemnum]) > 0)
                {
                    $qty[$itemnum] = 1;
                    $paid[$itemnum] = 0;
                }
                $errormessage = "order.Qty".$itemnum.".focus(); order.Qty".$itemnum.".select();";
                if ($itemnum == $nolines - 1)
                    $nolines++;
            }
            else
            {
                $cargoid[$itemnum] = $_REQUEST["CargoID".$itemnum];
                $cargodesc[$itemnum] = "";
                $errormessage = "errorinfield(order.CargoID".$itemnum.", 'Item already existed!'); order.CargoDesc".$itemnum.".focus();";
            }
            break;
        default:
            $mode = 3;
            $tempid = $cargoid[$itemnum];
            $errormessage = "order.mycargodesc.focus();";
            break;
    }
}
else if ($_REQUEST["submitter"] == "orderAddCargoID")
{
    $itemnum = $_REQUEST["itemtosearch"];
    $thefoundid = $_REQUEST["CargoID".$itemnum];
    $thecargo = $mydb -> query("SELECT Description FROM items WHERE ItemID = %i", $thefoundid);
    if ($thecargo -> countReturnedRows() == 0)
    {
        $cargoid[$itemnum] = "";
        $errormessage = "errorinfield(order.CargoDesc".$itemnum.", 'No Item with ID ".$thefoundid."'); order.CargoID".$itemnum.".focus();";
    }
    else
    {
        $cargoline = $thecargo -> fetchRow();
        $flag = 0; //times that the cargoid occurred in the array
        foreach ($cargoid as $c)
            if ($c == $thefoundid)
                $flag++;
        if ($flag == 1)
        {
            $cargodesc[$itemnum] = $cargoline["Description"];
            if (strlen($qty[$itemnum]) == 0 || $_REQUEST["CargoDesc".$itemnum] != $cargodesc[$itemnum] && strlen($_REQUEST["CargoDesc".$itemnum]) > 0 || strlen($_REQUEST["mycargodesc"]) > 0 && $cargoid[$itemnum] != $_REQUEST["tempid"])
            {
                $qty[$itemnum] = 1;
                $paid[$itemnum] = 0;
            }
            $errormessage = "order.Qty".$itemnum.".focus(); order.Qty".$itemnum.".select();";
            if ($itemnum == $nolines - 1)
                $nolines++;
        }
        else
        {
            if (strlen($_REQUEST["mycargodesc"]) > 0)
            {
                $cargoid[$itemnum] = $_REQUEST["tempid"];
                $errormessage = "errorinfield(order.CargoID".$itemnum.", 'Item already existed!'); order.CargoDesc".$itemnum.".focus();";
            }
            else
            {
                $cargoid[$itemnum] = "";
                $errormessage = "errorinfield(order.CargoDesc".$itemnum.", 'Item already existed!'); order.CargoID".$itemnum.".focus();";
            }
        }
    }
}
else if ($_REQUEST["submitter"] == "Search")
{
    $orderid = $_REQUEST["searchorderid"];
    if (strlen($orderid) > 0)
    {
        $order = $mydb -> query("SELECT * FROM [order] WHERE OrderNo = %i", $orderid);
        if ($order -> countReturnedRows() == 0)
            $errormessage = "alert('Order with ID ".$orderid." not found!');";
        else
        {
            $theorder = $order -> fetchRow();
            if ($userid != $theorder["brokerid"])
                $errormessage = "alert('Order with ID ".$orderid." is not created by you!');";
            else
            {
                $ordertype = $theorder["bid_ask"];
                if ($ordertype != "a" && $ordertype != "b")
                    $errormessage = "alert('Order with ID ".$orderid." has a problem with bid_ask. Please contact the administrator!');";
                else
                {
                    $mode = 2;
                    $orderid = $theorder["OrderNo"];
                    $orderdate = $theorder["OrderDate"] -> format("d/m/Y");
                    $nolines = 1;
                    $cargoid = array();
                    $cargodesc = array();
                    $qty = array();
                    $paid = array();
                    if ($ordertype == "a")
                    {
                        $orderdetail = $mydb -> query("SELECT * FROM ask INNER JOIN items ON askitem = ItemID WHERE orderid = %i ORDER BY sno", $orderid);
                        foreach ($orderdetail as $row)
                        {
                            $cargoid[$nolines - 1] = $row["ItemID"];
                            $cargodesc[$nolines - 1] = $row["Description"];
                            $qty[$nolines - 1] = $row["askqty"];
                            $paid[$nolines - 1] = $row["askprice"];
                            $nolines++;
                        }
                    }
                    else
                    {
                        $orderdetail = $mydb -> query("SELECT * FROM bid INNER JOIN items ON biditem = ItemID WHERE orderid = %i ORDER BY sno", $orderid);
                        foreach ($orderdetail as $row)
                        {
                            $cargoid[$nolines - 1] = $row["ItemID"];
                            $cargodesc[$nolines - 1] = $row["Description"];
                            $qty[$nolines - 1] = $row["bidqty"];
                            $paid[$nolines - 1] = $row["bidprice"];
                            $nolines++;
                        }
                    }
                }
            }
        }
    }
}
else if ($_REQUEST["submitter"] == "New Order")
{
    $mode = 2;
    $orderid = "(System Specified)";
    $orderdate = "";
    $ordertype = "";
    $nolines = 1;
    $cargoid = array();
    $cargodesc = array();
    $qty = array();
    $paid = array();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script src="common.js"></script>
    <script src="date-en-NZ.js"></script>
    <script src="CalendarPopup.js"></script>
    <script>
    function doalert()
    {
        <?php echo $errormessage; ?>
    }

    var cal = new CalendarPopup();

    function parsedate(whichcontrol)
    {
        var mydate;
        if (whichcontrol.value.length == 0)
            return true;
        mydate = Date.parse(whichcontrol.value);
        if (mydate == null)
            return errorinfield(whichcontrol, "This is not a valid date!");
        else
        {
            whichcontrol.value = mydate.toString("dd/MM/yyyy");
            return true;
        }
    }

    function cargoInput(itemnum, oldvalue, olddesc)
    {
        var whichcargo = document.getElementsByName("CargoID" + itemnum).item(0);
        var cargovalue = whichcargo.value;
        var newvalcontrol = null;
        if (cargovalue.length == 0 && oldvalue.length == 0)
            return true;
        if (cargovalue.length == 0)
        {
            if (!deleteline(itemnum))
            {
                whichcargo.value = oldvalue;
                whichcargo.focus();
                return true;
            }
        }
        if (cargovalue == oldvalue && olddesc.length > 0)
            return true;
        if (validateint(whichcargo))
        {
            newvalcontrol = document.createElement("input");
            newvalcontrol.type = "hidden";
            newvalcontrol.name = "submitter";
            newvalcontrol.value = "orderAddCargoID";
            order.appendChild(newvalcontrol);
            newvalcontrol = document.createElement("input");
            newvalcontrol.type = "hidden";
            newvalcontrol.name = "itemtosearch";
            newvalcontrol.value = itemnum;
            order.appendChild(newvalcontrol);
            order.submit();
        }
    }

    function cargoDesc(itemnum, oldvalue, olddesc)
    {
        var whichcargo = document.getElementsByName("CargoDesc" + itemnum).item(0);
        var cargovalue = whichcargo.value;
        var newvalcontrol = null;
        if (cargovalue.length == 0 && olddesc.length == 0)
            return true;
        if (cargovalue.length == 0)
        {
            if (!deleteline(itemnum))
            {
                whichcargo.value = olddesc;
                whichcargo.focus();
                return true;
            }
        }
        if (cargovalue == olddesc && oldvalue.length > 0)
            return true;
        newvalcontrol = document.createElement("input");
        newvalcontrol.type = "hidden";
        newvalcontrol.name = "submitter";
        newvalcontrol.value = "orderAddCargoDesc";
        order.appendChild(newvalcontrol);
        newvalcontrol = document.createElement("input");
        newvalcontrol.type = "hidden";
        newvalcontrol.name = "itemtosearch";
        newvalcontrol.value = itemnum;
        order.appendChild(newvalcontrol);
        order.submit();
    }

    function selectdesc(itemnum)
    {
        document.getElementsByName("CargoID" + itemnum).item(0).value = order.mycargodesc.value;
        cargoInput(itemnum, '', '');
    }

    function calctotal()
    {
        var finaltotal = 0;
        var x = 0;
        var nolines = parseInt(order.nolines.value);
        var curqty = 0;
        var curpaid = 0;
        for (x=0; x<nolines-1; x++)
        {
            curqty = parseInt(document.getElementsByName("Qty" + x).item(0).value);
            curpaid = parseFloat(document.getElementsByName("Paid" + x).item(0).value);
            finaltotal += curqty * curpaid;
            document.getElementById("subtotal" + x).innerHTML = (curqty * curpaid).toFixed(2);
        }
        total.innerHTML = finaltotal.toFixed(2);
        order.total.value = finaltotal.toFixed(2);
    }

    function validateqty(whichcontrol)
    {
        if (validateint(whichcontrol))
        {
            if (whichcontrol.value.length == 0 || parseInt(whichcontrol.value) <= 0)
                errorinfield(whichcontrol, "Qty must be a positive integer!");
            else
                calctotal();
        }
    }

    function validatepaid(whichcontrol)
    {
        var thefloatvalue;
        if (validatefloat(whichcontrol))
        {
            thefloatvalue = whichcontrol.value;
            if (thefloatvalue.length == 0 || parseFloat(thefloatvalue) < 0)
                errorinfield(whichcontrol, "Price cannot be negative or none!");
            else
            {
                whichcontrol.value = parseFloat(thefloatvalue).toFixed(2);
                calctotal();
            }
        }
    }

    function deleteline(whichone)
    {
        var x = 0;
        var nolines = parseInt(order.nolines.value);
        if (confirm("Really delete line " + (whichone + 1)))
        {
            for (x=whichone; x<nolines-1; x++)
            {
                document.getElementsByName("CargoID" + x).item(0).value = document.getElementsByName("CargoID" + (x + 1)).item(0).value;
                document.getElementsByName("CargoDesc" + x).item(0).value = document.getElementsByName("CargoDesc" + (x + 1)).item(0).value;
                document.getElementsByName("Qty" + x).item(0).value = document.getElementsByName("Qty" + (x + 1)).item(0).value;
                document.getElementsByName("Paid" + x).item(0).value = document.getElementsByName("Paid" + (x + 1)).item(0).value;
            }
            nolines--;
            order.nolines.value = nolines;
            order.submit();
        }
        else
            return false;
    }

    function checksave()
    {
        var errormessage = "";

        if (order.orderdate.value.length == 0)
        {
            errormessage += "\n Order Date";
            orderdateerror.innerHTML = "*";
        }
        else
            orderdateerror.innerHTML = "";
        if (!order.ordertype.item(0).checked && !order.ordertype.item(1).checked)
        {
            errormessage += "\n Order Type";
            ordertypeerror.innerHTML = "*";
        }
        else
            ordertypeerror.innerHTML = "";
        if (order.nolines.value == 1)
        {
            errormessage += "\n Detail Lines";
            ordererror.innerHTML = "You must enter at least one item to generate an order!";
        }
        else
            ordererror.innerHTML = "";
        if (errormessage != "")
        {
            alert("You need to fill out the following fields:" + errormessage);
            return false;
        }
        else
            return confirm("Are you sure you wish to save?");
    }

    function cancel()
    {
        if (confirm("Do you really want to cancel?"))
            window.location = "order.php";
    }
    </script>
</head>
<body onload="doalert()">
    <div class="websiteheader">
        <h1>
            <img src="logo.jpg" width="50px" alt="Brokerage">
            Order
            <img src="logo.jpg" width="50px" alt="Brokerage">
        </h1>
    </div>

    <?php include_once "menu.php" ?>

    <div class="websitebody">
        <form name="order" method="post" action="order.php">
            <?php if ($mode < 2)
            {
            ?>
            <table>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submitter" value="New Order"/>
                    </td>
                </tr>
                <tr>
                    <td align="right">Search Order ID:</td>
                    <td>
                        <input type="text" name="searchorderid" size="5" onblur="validateint(order.searchorderid)"/>
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
            <table>
                <tr>
                    <td align="right">Order No:</td>
                    <td>
                        <?php echo $orderid; ?>
                    </td>
                </tr>
                <tr>
                    <td align="right">Order Date:</td>
                    <td>
                        <input type="text" name="orderdate" value="<?php echo $orderdate; ?>" size="8" onblur="parsedate(order.orderdate)"/>
                        <a href="#" name="calbutt" onclick="cal.select(order.orderdate, 'calbutt', 'dd/MM/yyyy'); return false;"><img src="calendar.bmp"/></a>
                        <span class="error" id="orderdateerror"></span>
                    </td>
                </tr>
                <tr>
                    <td align="right">Order Type:</td>
                    <td>
                        <input type="radio" name="ordertype" value="a" <?php if ($ordertype == "a") echo "checked"; ?>/>Ask
                        <input type="radio" name="ordertype" value="b" <?php if ($ordertype == "b") echo "checked"; ?>/>Bid
                        <span class="error" id="ordertypeerror"></span>
                    </td>
                </tr>

                <tr>
                    <th align="right">SNo:</th>
                    <th>ID</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
                <?php
                $finaltotal = 0;
                for ($x=0; $x<$nolines; $x++)
                {
                    if (strlen($cargoid[$x]) > 0)
                        $finaltotal += $qty[$x] * $paid[$x];
                ?>
                <tr id="row<?php echo $x; ?>">
                    <td align="right">
                        <?php echo ($x + 1)."."; ?>
                    </td>
                    <td>
                        <input type="text" name="CargoID<?php echo $x; ?>" value="<?php echo $cargoid[$x]; ?>" onblur="cargoInput(<?php echo $x; ?>, '<?php echo $cargoid[$x]; ?>', '<?php echo $cargodesc[$x];?>')"/>
                    </td>
                    <td>
                        <?php
                        if ($mode == 3 && $itemnum == $x)
                        {
                        ?>
                        <select name="mycargodesc" style="width: 205px;" onchange="selectdesc(<?php echo $itemnum; ?>)">
                            <option value="0">--Please choose an item--</option>
                            <?php
                            foreach ($thecargolist as $row)
                            {
                            ?>
                            <option value="<?php echo $row['ItemID']; ?>"><?php echo $row["Description"]; ?></option>
                            <?php
                            }
                            ?>
                        </select>
                        <?php
                        }
                        else
                        {
                        ?>
                        <input type="text" size="30" name="CargoDesc<?php echo $x; ?>" value="<?php echo $cargodesc[$x]; ?>" onblur="cargoDesc(<?php echo $x; ?>, '<?php echo $cargoid[$x]; ?>', '<?php echo $cargodesc[$x];?>')"/>
                        <?php
                        }
                        ?>
                    </td>
                    <td>
                        <input type="text" size="5" name="Qty<?php echo $x; ?>" value="<?php echo $qty[$x]; ?>" onblur="validateqty(order.Qty<?php echo $x; ?>)" <?php if ($x == $nolines - 1) echo "disabled"; ?>/>
                    </td>
                    <td>
                        <input type="text" size="8" name="Paid<?php echo $x; ?>" value="<?php echo sprintf("%.2f", $paid[$x]); ?>" onblur="validatepaid(order.Paid<?php echo $x; ?>)" <?php if ($x == $nolines - 1) echo "disabled"; ?>/>
                    </td>
                    <td>
                        <span id="subtotal<?php echo $x; ?>"><?php if ($x < $nolines - 1) echo sprintf("%.2f", $qty[$x] * $paid[$x]); ?></span>
                    </td>
                    <td>
                        <?php
                        if ($x < $nolines - 1)
                        {
                        ?>
                        <input type="button" name="delbutt<?php echo $x; ?>" value="X" onclick="deleteline(<?php echo $x; ?>)"/>
                        <?php
                        }
                        ?>
                    </td>
                </tr>
                <?php
                }
                ?>
                <tr>
                    <td></td>
                    <td colspan="3"><span class="error" id="ordererror"></span></td>
                    <td align="right"><b>Total:</b></td>
                    <td>
                        <span id="total"><?php echo sprintf("%.2f", $finaltotal); ?></span>
                    </td>
                </tr>

                <tr>
                    <td>
                        <input type="submit" name="submitter" value="Save" onclick="return checksave()" <?php if ($mode != 2) echo "disabled"; ?>/>
                    </td>
                    <td colspan="6" align="right">
                        <input type="button" name="cancelbutt" value="Cancel" onclick="cancel()"/>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="orderid" value="<?php echo $orderid; ?>"/>
            <input type="hidden" name="nolines" value="<?php echo $nolines; ?>"/>
            <input type="hidden" name="tempid" value="<?php echo $tempid; ?>"/>
            <input type="hidden" name="total" value="<?php echo sprintf("%.2f", $finaltotal); ?>"/>
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