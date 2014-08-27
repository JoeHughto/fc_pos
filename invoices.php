<?php
/**
 * Invoices.php gives an active user with inventory privs the
 *   chance to put invoices into record for determining Purchase budget.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Report_Form
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/invoices.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 */
$title = "Invoice Record";
$version = "1.8d";
require 'funcs.inc';
require 'header.php';

/**
 * Possible Arguments:
 * SESSION:
 *   inv - Used to determine whether the active user has inventory
 *     privs.
 * POST:
 *   vendor - The name of the vendor who sent the submitted invoice.
 *   amount - The total price on the submitted invoice.
 *   year - The year on the submitted invoice.
 *   month - The month on the submitted invoice.
 *   day - The day on the submitted invoice.
 */
if ($_SESSION['inv'] != 1) {
    echo "You must have Inventory Permission to use this application";
    include 'footer.php';
    die();
}

$cxn = open_stream();

extract($_POST);
if (isset($vendor) && isset($amount)) {
    $date = $year . '-' . $month . '-' . $day;
    $stmt = $cxn->prepare("INSERT INTO invoices (invdate, vendor, amount) VALUES (?,?,?)");
    $stmt->bind_param("ssd", $date, $vendor, $amount);
    if ($stmt->execute()) {
        echo "Invoice submitted<hr>";
    } else {
        echo "Invoice FAILED<hr>";
    }
}

// display stuff
$sql = "SELECT * FROM invoices ORDER BY invdate DESC";
$result = query($cxn, $sql);
$invsum = 0; // total invoices ever
$disp = ''; // string that will be displayed

while ($row = mysqli_fetch_assoc($result)) {
    extract($row);

    $invsum += $amount;
    $disp .= "<tr><td>$invdate</td><td>$vendor</td><td>" . money($amount) . "</td></tr>";
}

$sql = "SELECT (SUM(price * qty))  FROM soldItem";
$result = query($cxn, $sql);
$row = mysqli_fetch_row($result);
$sales = $row[0];
$budget = ($sales * .6) - $invsum;

echo "Remaining Inventory Budget: " . money($budget) . "<br>
    Total Sales: " . money($sales) . "<br>
    Total Invoices: " . money($invsum) . "<p>";

echo "Insert Invoice Information<br>
    <form action='invoices.php' method='post'>
    Invoice Date: ";
echo selectInputDate('month', 'day', 'year', 2008, 2015, date('n'), date('j'), date('Y')); 
echo "<br>
    Invoice to: <input type='text' name='vendor' maxlength=40><br>
    Invoice Amount: <input type='text' name='amount' maxlength=8><br>
    <input type='submit' name='submit' value='submit'></form><p>\n";

echo "<table>$disp</table>";

require 'footer.php';
?>