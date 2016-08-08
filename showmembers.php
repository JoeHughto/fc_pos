<?php

// GET
// sort = 'num' - by member number
// showmembers.php

require_once 'funcs.inc';
require_once 'credits.inc';
require_once 'member.inc';
require_once 'header.php';
$cxn = open_stream();

$search = (!isset($_GET['search'])) ? '' : "WHERE (CONCAT(fname, ' ', lname) "
    . "LIKE '%" . $_GET['search'] . "%' OR email LIKE '%" .$_GET['search'] . "%'";
if (is_numeric($_GET['search'])) {
	$search = $search . " OR ID=" . $_GET['search'];
}
if (isset($_GET['search'])) {
	$search = $search . ")";
}

$order = ($_GET['sort'] == 'num') ? '' : "ORDER BY registerUse DESC, lname";
$page = (!is_numeric($_GET['page']) ? 1 : $_GET['page']);
$limit = "LIMIT " . (($page - 1) * 50) . ", 50";
$sql = "SELECT * FROM members $search $order $limit";
$result = query($cxn, $sql);
echo "<form method='get'><input type='text' name='search' value='" 
    . $_GET['search'] . "'><input type='submit' value='Member Search'></form>";
echo "Members marked with * have register privileges.<br>";
printPaginator($page);
//echo "<a href='showmembers.php?sort=num'>Sort by member number</a>";
echo "<table cellpadding=3 border><tr><td>Name</td><td>Login</td>";
if ($_SESSION['reg'] == 1)
    echo "<td>Phone</td>";
if ($_SESSION['mem'] == 1)
    echo "<td>Credits</td><td>Account</td><td>FG Disc</td>";
echo "<td>Member?</td>";
echo "</tr>";
while ($row = mysqli_fetch_assoc($result)) {
    extract($row);
    if ($registerUse == 1) {
    	$lname = "*" . $lname;
    }
    echo "<tr><td><a href='inputmember.php?ID=$ID'>$lname, $fname #$ID</td>"
        . "<td>$login</a></td>";
    if ($_SESSION['reg'] == 1) {
        echo "<td>" . formPhoneNumber($phone1) . "</td>";
    }
    if ($_SESSION['mem'] == 1) {
        echo "<td>Cr: <a href='credittransactions.php?ID=$ID'>" 
            . getCreditTotal($ID) . "</a></td>";
        echo "<td><a href='accounttransactions.php?ID=$ID&start=2010-1-1'>" 
            . money(getAccountBalance($ID)) . "</a></td>";
        echo "<td>";
        echo FG_discountNow($ID);
        echo "</td>";
    }

    echo "<td>";
    switch (checkMember($ID)) {
        case 1 : echo 'Guest';
            break;
        case 2 : echo 'Working';
            break;
        case 3 : echo 'Contributing';
            break;
        case 10: echo 'Double';
            break;
    }
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
printPaginator($page);

function printPaginator($page = 1) {
    if ($page > 1) {
        echo "<a href='showmembers.php?page=" . ($page - 1);
        if (isset($_GET['search'])) {
            echo "&search=" . $_GET['search'];
        }
        echo "'>Previous</a>&nbsp;&nbsp;&nbsp;";
    }
    echo "<a href='showmembers.php?page=" . ($page + 1);
    if (isset($_GET['search'])) {
        echo "&search=" . $_GET['search'];
    }
    echo "'>Next</a>";
}

?>