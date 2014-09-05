<?php
/**
 * @file salesbymonth.php
 * @brief salesbymonth.php shows stats for every month since we opened, 
 *   including sales, cost and profit
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - lastDayOfMonth()
 * - money()
 * 
 * @link http://www.worldsapartgames.org/fc/salesbymonth.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Sales by Month";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

$cxn = open_stream();

$date = date_create('2008-2-1');

echo "<h2>Sales By Month</h2>
    <table border cellpadding=5><tr><td>Month</td><td>Sales</td><td>Cost</td>
    <td>Profit</td><td>New Customers</td></tr>\n";

while ($date < date_create()) {
    $start = $date->format('Y-m-d 00:00:00');
    $end = $date->format('Y-m-');
    $end .= lastDayOfMonth($date->format('n')) . " 23:59:59";

    $sql = "SELECT sum(totalPrice) sales,
        sum(totalCost) cost
        FROM transactions
        WHERE whensale > '$start'
        AND whensale < '$end'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);
    echo "<tr><td>" . $date->format('M, y') . "</td><td>" . money($sales) 
        . "</td><td>" . money($cost) . "</td><td>" . money(($sales - $cost)) 
        . "</td>";

    $sql = "SELECT count(ID) num
        FROM members
        WHERE memberSince > '$start'
        AND memberSince < '$end'";

    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);
    echo "<td>$num</td></tr>\n";

    $date->modify("+1 month");
}

echo "</table>";
require 'footer.php';
?>