<?php
/**
 * @file header.php
 * @brief header.php is a file that prints our menu and helpful information
 *   when imported.
 * 
 * @todo This file should have a function that echos this information, possibly
 *   several functions that echo some each, to split code up some, and make
 *   it more modular.
 * @todo This file uses external functions but does not import or require 
 *   any other files.
 * 
 * @link http://www.worldsapartgames.org/fc/index.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

echo "<HTML>";
echo "<HEAD>";

echo "<TITLE>$title</TITLE>\n";

echo "</HEAD>
    <BODY LINK='333366' ALINK='333366' VLINK='333366'>
    <TABLE><TR><TD WIDTH=150 ROWSPAN=2 VALIGN=TOP>
    <IMG SRC='../data/shield.png' ALT='Worlds Apart Shield' WIDTH=150><br>";

echo "Hello, ";
printMember($_SESSION['ID'], 3);
echo "<b><font size=-6>&nbsp;(<a href='index.php?logout=1'>Logout</a>)</font></b><br><br>";

echo "<b><a href='index.php'>Master Page</a></b><hr>";

$now = date("Y-m-d");
$date = date_create();
$date->modify("+1 day");
$tomorrow = date_format($date, "Y-m-d");
$yesterday = date_create();
$yesterday->modify("-1 day");
$yesterday = date_format($yesterday, "Y-m-d");
$nextWeek = date_create();
$nextWeek->modify("+1 week");
$nextweek = date_format($nextWeek, "Y-m-d");
$twoWeeks = date_create();
$twoWeeks->modify("+2 week");
$twoWeeks = date_format($twoWeeks, "Y-m-d");
$month1 = date("Y-m-1");
$month31 = date("Y-m-" . lastDayOfMonth($date->format("m")));
$date->modify("-1 month");
$lastmonth1 = date_format($date, "Y-m-1");

echo "<b>Volunteer!</b><br>";
if ($_SESSION['reg'] == 1) {
    echo "<a href='register.php'>Sales Register</a><br>";
}
if ($_SESSION['reg'] == 1) {
    echo "<a href='viewreceipts.php?start=$yesterday&end=$now'>View Receipts</a><br>";
}
$cxn = open_stream();

$sql = "SELECT * FROM bounties";
$result = query($cxn, $sql);

$numrows = $cxn->affected_rows;
if ($numrows > 0) {
    echo"<a href='bounties.php'>Bounties ($numrows)</a>";
} else {
    echo"<a href='bounties.php'>Bounties</a>";
}
if ($_SESSION['reg'] == 1) {
    echo "<br><a href='schedule.php?start=$now&end=$twoWeeks'>Schedule</a><br>";
}
if ($_SESSION['reg'] == 1) {
    echo "<a href='viewspecialorders.php'>Special Order Report</a><br>";
}
if ($_SESSION['reg'] == 1) {
    echo "<br>";
}
if ($_SESSION['reg'] == 1) {
    echo "<a href='count.php'>Cash Count</a>";
}
echo "<hr>";

echo "<b>My Account</b><br>";
echo "<a href='profile.php'>Update Profile</a><br>";
echo "<a href='accounttransactions.php?start=$lastmonth1'>Account Transactions</a><br>";
echo "<a href='futurepacks.php'>Future Packs</a><br>";
echo "<a href='snackcard/index.html' target='_BLANK'>Snack Card</a><br>";
echo "<a href='credittransactions.php'>Hedon Transactions</a>";
echo "<hr>";

echo "<b>Card Case</b><br>";
if ($_SESSION['inv'] == 1) {
    echo "<a href='buycards.php'>Buy Cards</a><br>";
}
echo "<a href='http://joehughto.com/newerPrices.php' target='_BLANK'>WAG Price Guide</a><br>";
echo "<a href='http://magic.tcgplayer.com/all_magic_sets.asp' target='_BLANK'>TCGPlayer Values</a><br>";
echo "<a href='http://magiccards.info/' target='_BLANK'>Card Oracle Search</a>";
echo "<hr>";

if ($_SESSION['reg'] == 1 || $_SESSION['mem'] == 1) {
    echo "<b>Members</b><br>";
    echo "<a href='showmembers.php'>Member Search</a><br>";
    if ($_SESSION['mem'] == 1) {
        echo "<a href='viewhowfound.php'>How Found Report</a>";
    }
    echo "<hr>";
}

echo "<b>Events</b><br>";
if ($_SESSION['eve'] == 1) {
    echo "<a href='setevents.php'>Set Up Events</a><br>";
}
if ($_SESSION['eve'] == 1) {
    echo "<a href='prizepool.php'>Prize Pool Events</a><br>";
}
echo "<a href='league.php?league=$mleagueID'>League Results</a>";
echo "<hr>";

if ($_SESSION['inv'] == 1) {
    echo "<b>Inventory</b><br>";
    echo "<a href='receiveinvoice.php'>Receive Invoice</a><br>";
    echo "<a href='mandepinv.php'>Manufacturers/Departments</a><br>";
    echo "<a href='invoices.php'>Invoice Budget</a><br>";
    echo "<a href='inventoryreport.php'>Inventory Report</a>";
    echo "<hr>";
}

if ($_SESSION['inv'] == 1) {
    echo "<b>Sales</b><br>";
    echo "<a href='popularsale.php'>Popsale Report</a><br>";
    echo "<a href='salesreport.php?list=yes&start=$now&end=$tomorrow'>Today's Sales</a><br>";
    echo "<a href='salesreport.php?list=yes&start=$month1&end=$month31'>This Month's Sales</a><br>";
    echo "<a href='fgreport.php'>Frequent Gamer Report</a>";
    echo "<hr>";
}

if ($_SESSION['adm'] == 1) {
    echo "<b>Admin</b><br>";
    $cxn = open_stream();

    $sql = "SELECT * FROM hedonReqs";
    $result = query($cxn, $sql);

    $numrows = $cxn->affected_rows;
    if ($numrows > 0) {
        echo"<a href='hedonrequests.php'>Hedon Requests ($numrows)</a>";
    } else {
        echo"<a href='hedonrequests.php'>Hedon Requests</a>";
    }
    echo "<br><a href='checkedinreport.php'>Checked In Report</a><br>";
    echo "<a href='oph.php'>Other People's Hedons</a><br>";
    echo "<a href='storeaccount.php'>Give Account</a>";
    echo "<hr>";
}

// let user make deposits and spends
if ($_SESSION['reg'] == 1) {
    echo "<b>Record Deposits</b><br>
        <form action='count.php' method='post'>
        Deposit: \$<input type='text' name='deposit' size=6 maxlength=6><br>
        <input type='submit' name='submit' value='Submit Count'></form>\n";

    echo "<b>Record Cash Spend</b><br>
        <form action='cashspend.php' method='post'>
        Amount: \$<input type='text'name='amount' size=6 maxlength=6><br>
        Reason: <input type='text' name='reason' size=25 maxlength=100><br>
        <input type='submit' name='submit' value='Submit Spend'></form>\n";
    echo "<hr>";
}

echo "</TD><TD width=800 valign=top>
    <TABLE><TR><TD valign=top>
    <a href='league.php?league=$mleagueID'>Magic League<BR>Quick Link</a></B>
    <TD>
    New League Runs 5-4-14 -> 5-31-14<br>
    </TD></TR></TABLE><BR>
    <FONT SIZE=+2>$title</FONT>";
?>

