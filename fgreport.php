<?php
/**
 * FGReport.php is a page displaying our highest ranked Frequent Gamers.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Report_View
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/fgreport.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   printMemberString()
 * member.inc:
 *   taxExempt()
 *   memberSalesThisMonth()
 *   memberSalesLastMonth()
 *   FG_discount()
 *   FG_discountNow()
 */
$title = 'Frequent Gamer Report';
require_once 'funcs.inc';
require_once 'member.inc';
require_once 'header.php';

$cxn = open_stream();
$sql = "SELECT MAX(ID) FROM members WHERE taxexempt IS NULL OR taxexempt = 'NULL'";
$result = query($cxn, $sql);
$row = mysqli_fetch_row($result);
$count = $row[0];

for ($member = 1; $member <= $count; $member++) {
    $sales = (!taxExempt($member)) ? memberSalesThisMonth($member) : 0;
    $lastmonth = memberSalesLastMonth($member);
    $disc = FG_discount($lastmonth);
    $newdisc = FG_discount($sales);
    $tonext = $FGDISCOUNT[$newdisc + 1] - $sales;

    $namea[$member] = $memberInfo['name'][$member] = printMemberString($member, 5);
    $salesa[$member] = $memberInfo['sales'][$member] = $sales;
    $memnum[$member] = $member;
}

array_multisort($salesa, SORT_DESC, $namea, SORT_DESC, $memnum, SORT_DESC);

echo "<p>First, second and third place are the customers which shall recieve special benefits. First place gets a $15 credit,
    Second place gets $10 and Third gets $5.
    <p>Sales for the current month by customer/member<p>
    <table border cellpadding=3><tr><td>Rank</td><td>Name</td><td>This Month</td><td>To Next Place</td><td>To Third</td></tr>\n";

reset($memberInfo);
foreach ($namea as $key => $name) {  
    if ($salesa[$key] > 0) {
        echo "<tr><td>" . ($key + 1) . "</td><td>$name(" . $memnum[$key] . ")</td>
            <td>" . money($salesa[$key]) . "</td>
            <td align=right>" . (($key > 0) ? (money($salesa[$key - 1] - $salesa[$key])) : "Top") . "</td>
            <td>" . money($salesa[2] - $salesa[$key]) . "</td></tr>\n";
    }
}
echo "</table><p>";

unset ($namea, $salesa, $memnum);

echo "<hr><b>Last Month</b><p>";
for ($member = 1; $member <= $count; $member++) {
    $sales = (!taxExempt($member)) ? memberSalesLastMonth($member) : 0;
    $newdisc = FG_discount($sales);

    $namea[$member] = $memberInfo['name'][$member] = printMemberString($member, 5);
    $salesa[$member] = $memberInfo['sales'][$member] = $sales;
    $memnum[$member] = $member;
}

array_multisort($salesa, SORT_DESC, $namea, SORT_DESC, $memnum, SORT_DESC);

echo "<table border cellpadding=3><tr><td>Rank</td><td>Name</td><td>This Month</td><td>Discount</td></tr>\n";

reset($memberInfo);
foreach ($namea as $key => $name) {  
    if ($salesa[$key] > 0) {
        echo "<tr><td>" . ($key + 1) . "</td><td>$name({$memnum[$key]})</td><td>" 
        .   money($salesa[$key]) . "</td><td>"
        .   FG_discountNow($memnum[$key]) . "</td></tr>\n";
    }
}
echo "</table><p>";
?>