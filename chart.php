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

$errormessage = "";

$itemcat = $mydb -> query("SELECT * FROM itemcat");
$noitemcat = $itemcat -> countReturnedRows();
if ($noitemcat == 0)
    $errormessage = "alert('The ".'"itemcat"'." table in database is empty. Please contact the administrator!');";

$ordertype = $_REQUEST["ordertype"];
$noitems = 0;
$nochecked = 0;
$itemid = array();
$itemdesc = array();
$itemsum = array();
$percent = array();
$y = array();

if ($_REQUEST["submitter"] == "View")
{
    //get item information in selected categories
    foreach ($itemcat as $row)
    {
        $category = $row["itemcode"];
        if ($_REQUEST[$category] == "Y")
        {
            $nochecked++;
            $items = $mydb -> query("SELECT ItemID, Description FROM items WHERE category = %s", $category);
            //add items in selected categories into the array
            foreach ($items as $item)
            {
                $itemid[] = $item["ItemID"];
                $itemdesc[] = $item["Description"];
                $noitems++;
            }
        }
    }

    //get information of each item in selected order type
    for ($i=0; $i<$noitems; $i++)
    {
        if ($ordertype == "a")
            $table = $mydb -> query("SELECT askqty AS qty, askprice AS price FROM ask WHERE askitem = %i", $itemid[$i]);
        else
            $table = $mydb -> query("SELECT bidqty AS qty, bidprice AS price FROM bid WHERE biditem = %i", $itemid[$i]);
        $sum = 0;
        //get the sum of each selected item
        foreach ($table as $row)
            $sum += $row["qty"] * $row["price"];
        $itemsum[] = $sum;
    }

    //sort quantity from greatest to least
    for ($i=1; $i<$noitems; $i++)
    {
        $tempsum = $itemsum[$i];
        $tempdesc = $itemdesc[$i];
        $j = $i - 1;
        while ($j >= 0 && $itemsum[$j] < $tempsum)
        {
            $itemsum[$j + 1] = $itemsum[$j];
            $itemdesc[$j + 1] = $itemdesc[$j];
            $j--;
        }
        $itemsum[$j + 1] = $tempsum;
        $itemdesc[$j + 1] = $tempdesc;
    }

    if ($noitems > 0)
    {
        //get and compute the y values
        $maxy = ceil($itemsum[0] / 100) * 100;
        if ($maxy == 0)
        {
            $noylabels = 1;
            $maxy = 1;
        }
        else
            $noylabels = 5;
        for ($i=$noylabels; $i>=0; $i--)
            $y[] = (int)($i * $maxy / $noylabels);

        //calculate ratio for each item
        for ($i=0; $i<$noitems; $i++)
        {
            $p = (int)($itemsum[$i] / $maxy * 100);
            if ($p == 0 && $itemsum[$i] > 0)
                $p = 1;
            $percent[] = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chart</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" type="text/css" href="chart.css">
    <?php
    if ($noitems > 0)
    {
    ?>
    <style>
    .histogram-bg-line li
    {
        width: <?php echo (int)(100 / $noitems) ?>%;
    }

    .histogram-content li
    {
        width: <?php echo (int)(100 / $noitems) ?>%;
    }
    </style>
    <?php
    }
    ?>
    <script src="common.js"></script>
    <script>
    function doalert()
    {
        <?php echo $errormessage; ?>
    }

    function checkboxchange(whichcontrol)
    {
        if (whichcontrol.checked)
            chart.nochecked.value++;
        else
            chart.nochecked.value--;
    }

    function checkview()
    {
        var errormessage = "";

        if (!chart.ordertype.item(0).checked && !chart.ordertype.item(1).checked)
        {
            errormessage += "\n Order Type";
            ordertypeerror.innerHTML = "*";
        }
        else
            ordertypeerror.innerHTML = "";
        if (chart.nochecked.value == 0)
        {
            errormessage += "\n Item Type";
            itemtypeerror.innerHTML = "*";
        }
        else
            itemtypeerror.innerHTML = "";
        if (errormessage != "")
        {
            alert("You need to fill out the following fields:" + errormessage);
            return false;
        }
        else
            return true;
    }
    </script>
</head>
<body onload="doalert()">
    <div class="websiteheader">
        <h1>
            <img src="logo.jpg" width="50px" alt="Brokerage">
            Chart
            <img src="logo.jpg" width="50px" alt="Brokerage">
        </h1>
    </div>

    <?php include_once "menu.php" ?>

    <div class="websitebody">
        <form name="chart" method="post" action="chart.php">
            <input type="hidden" name="nochecked" value="<?php echo $nochecked; ?>"/>
            <p>Introduction: This page is going to show you the total value of items in selected item categories and selected order type calculated by formula "quantity * price".</p>
            <table>
                <tr>
                    <td>
                        <input type="radio" name="ordertype" value="a" <?php if ($ordertype == "a") echo "checked"; ?>/>Ask
                    </td>
                    <td>
                        <input type="radio" name="ordertype" value="b" <?php if ($ordertype == "b") echo "checked"; ?>/>Bid
                    </td>
                    <td>
                        <span class="error" id="ordertypeerror"></span>
                    </td>
                </tr>
                <tr>
                    <?php
                    foreach ($itemcat as $row)
                    {
                    ?>
                    <td>
                        <input type="checkbox" name="<?php echo $row['itemcode']; ?>" value="Y" onchange="checkboxchange(chart.<?php echo $row['itemcode']; ?>)" <?php if ($_REQUEST[$row['itemcode']] == "Y") echo "checked"; ?>/><?php echo $row["itemtype"]; ?>
                    </td>
                    <?php
                    }
                    ?>
                    <td>
                        <span class="error" id="itemtypeerror"></span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="submitter" value="View" onclick="return checkview()"/>
                    </td>
                </tr>
            </table>
        </form>

        <?php
        if ($noitems > 0)
        {
        ?>
        <div class="histogram-container" id="histogram-container">
            <div class="histogram-bg-line">
                <?php
                for ($i=0; $i<$noylabels; $i++)
                {
                ?>
                <ul>
                    <?php
                    for ($j=0; $j<$noitems; $j++)
                    {
                    ?>
                    <li><div></div></li>
                    <?php
                    }
                    ?>
                </ul>
                <?php
                }
                ?>
            </div>

            <div class="histogram-content">
                <ul>
                    <?php
                    for ($i=0; $i<$noitems; $i++)
                    {
                    ?>
                    <li>
                        <span class="histogram-box"><a style="height: <?php echo $percent[$i]; ?>%; background: <?php if ($ordertype == 'a') echo 'red'; else echo 'green'; ?>" title="<?php echo $itemsum[$i]; ?>"></a></span>
                        <span class="name"><?php echo $itemdesc[$i]; ?></span>
                    </li>
                    <?php
                    }
                    ?>
                </ul>
            </div>

            <div class="histogram-y">
                <ul>
                    <?php
                    for ($i=0; $i<$noylabels+1; $i++)
                    {
                    ?>
                    <li><?php echo $y[$i]; ?></li>
                    <?php
                    }
                    ?>
                </ul>
            </div>
        </div>
        <?php
        }
        ?>
    </div>

    <div class="websitefooter">
        <p>For queries, please contact the <a href="mailto:czhe171@aucklanduni.ac.nz">webmaster</a></p>
    </div>
</body>
</html>