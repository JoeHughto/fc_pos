<?php
/**
 * @file inventoryreport.php
 * @brief inventoryreport.php is a page for reporting on the state of inventory.
 *
 * @todo This page fails silently on submit. Look into that.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - displayErrorDie()
 * - money()
 * 
 * Possible Arguments:
 * SESSION:
 * - ID - The ID of the active user, required for appending to
 *   some queries.
 * - inv - Used to determine whether the active user has inventory
 *   privs.
 * 
 * POST:
 * - change - This value will be set to 1 when we need to do work.
 * - boardcard - If this value is set, it will set GET['boardcard'] to
 *   the same value, which will restrict the items shown to only board
 *   games and card games.
 * - qty - Array of itemIDs and quantities for each. This array will update
 *   all given items to the new quantities.
 * 
 * GET:
 * - boardcard - If this variable is set, it will restrict the items shown to 
 *   only board games and card games.
 * - noblank - If this variable is set, it will restrict the items shown to 
 *   only those with >0 quantity.
 * - sort - The name of the department the active user wants to sort by.
 * 
 * @link http://www.worldsapartgames.org/fc/inventoryreport.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = 'Inventory Report';
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

$cxn = open_stream();

$allowcount = ($_SESSION['inv'] == 1);

// change inventory
if ($_POST['change'] == 1) {
    $_GET['boardcard'] = $_POST['boardcard'];

    echo "<hr><font size=+2>Adjusting Quantities</font><br>\n";

    $sql = "INSERT INTO invEvent (type, staffID, invDate, closed) "
        . "VALUES (1, ". $_SESSION['ID'] . ", DATE_ADD(NOW(), INTERVAL 1 HOUR), 1)";
    if (!query($cxn, $sql)) {
        displayErrorDie("Unable to set invEvent");
    }
    $ied = $cxn->insert_id;
    echo "Inventory Event ID: $ied<br>";

    extract($_POST);
    foreach ($qty as $ID => $newqty) {
        // if a number is put in
        if (is_numeric($newqty)) {
            echo "Item #$ID<br>";

            // find old quantity
            $sql = "SELECT description, qty, price, cost FROM items WHERE ID='$ID'";
            $result = query($cxn, $sql);
            $row = mysqli_fetch_assoc($result);
            $oldqty = $row['qty'];
            $qtychange = $newqty - $oldqty;
            $cost = $row['cost'];
            $price = $row['price'];
            $desc = $row['description'];

            // only do the thing if there is actually a change
            if ($qtychange != 0) {
                // create itemChange
                $sql = "INSERT INTO itemChange (itemID, invEventID, qty, cost, price)
                    VALUES ($ID, $ied, $qtychange, $cost, $price)";
                if (query($cxn, $sql)) {
                    echo "$desc Item Change Created<br>";
                } else {
                    displayErrorDie("Error Submitting itemChange for $desc ($ID)");
                }

                // change item quantity
                $sql = "UPDATE items SET qty=$newqty WHERE ID=$ID";
                if (query($cxn, $sql)) {
                    echo "$desc Qty changed $qtychange to $newqty<br>";
                } else {
                    displayErrorDie("Error Submitting qty for $desc ($ID)");
                }
            }
        }
    }
    echo "<hr>";
}

$validColumns = array('ID', 'department', 'manufacturer', 'price', 'cost', 'inv', 'UPC', 'alternate1', 'alternate2', 'qty', 'description', 'tax');
if (isset($_GET['sort'])) {
    $sort = (in_array($_GET['sort'], $validColumns)) ? 'ORDER BY ' . $_GET['sort'] : '';
}

if ($_GET['noblank'] == 1) {
    $noblank = "WHERE qty > 0";
    if ($_GET['boardcard'] == 1) {
        $boardcard = "AND (department LIKE 'Board Games')";
    }
} elseif ($_GET['boardcard'] == 1) {
    $boardcard = "WHERE (department LIKE 'Board Games')";
}

$sql = "SELECT * FROM items $noblank $boardcard $sort";

$result = query($cxn, $sql);

echo "<form action='inventoryreport.php' method='get'>
    <select name='sort'>\n";
foreach ($validColumns as $vc) {
    echo "<option value='$vc'>$vc</option>\n";
}

$isbc = ($_GET['boardcard'] == 1) ? " checked" : "";

echo "</select><br>
    <input type='checkbox' name='noblank' value=1> Do not show 0 quantity items<br>
    <input type='checkbox' name='boardcard' value=1 $isbc> Board/Card Games Only?<br>
    <input type='submit' name='submit' value='sort'></form><p>";


if ($allowcount) {
    echo "<form action='inventoryreport.php' method='post'>\n";
}
echo "<table cellpadding=3 border><tr>
    <td>ID</td>
    <td width=100>Name</td>
    <td>Price</td>
    <td>Cost</td>
    <td>Qty</td>
    <td>Tax</td>
    <td>Inv</td>
    <td>UPC<br>Alt1<br>Alt2</td>
    <td>Department<br>Manufacturer</td>\n";
if ($allowcount) {
    echo "<td>Current Qty</td>\n";
}
echo "</tr>\n";

while ($row = mysqli_fetch_assoc($result)) {
    extract($row);

    echo "<tr>
        <td><a name='$ID'>$ID</td>
        <td><a href='edititem.php?ID=$ID'>$description</a></td>
        <td>" . money($price) . "</td>
        <td>" . money($cost) . "</td>
        <td>$qty</td>
        <td>" . (($tax == 1) ? "YES" : "NO") . "</td>
        <td>" . (($inv == 1) ? "YES" : "NO") . "</td>
        <td>$UPC<br>$alternate1<br>$alternate2</td>
        <td>$department<br>$manufacturer</td>\n";
    if ($allowcount) {
        echo "<td><input name='qty[$ID]' size=5 maxlength=5></td>\n";
    }
    echo "</tr>";
}

echo "</table><br>";
if ($allowcount) {
    if ($_GET['boardcard'] == 1) {
        echo "<input name='boardcard' value='1' type='hidden'>";
    }
    echo "<input name='change' value='1' type='hidden'>
        <input name='submit' value='submit' type='submit'></form>
        <br>";
}
require 'footer.php';
?>
