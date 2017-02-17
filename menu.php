<div class="websitesidebar">
    <ul>
        <li><a href="index.php">Home Page</a></li>
        <?php if ($usertype == "admin") echo '<li><a href="account.php">Add/edit account</a></li>'; ?>
        <?php if ($usertype == "broker") echo '<li><a href="order.php">Order</a></li>'; ?>
        <?php if ($usertype == "admin") echo '<li><a href="chart.php">Charts</a></li>'; ?>
        <li><a href="login.php?submitter=Logout">Logout</a></li>
        <li>Made by Zhen Chen</li>
    </ul>
</div>