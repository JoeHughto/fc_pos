<?php
/**
 * @file edititem.php
 * @brief edititem.php is a page for modifying any of the information on a given item.
 *
 * @todo Add quantity box to form.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * 
 * inventory.inc:
 * - displayDepartmentListScalar()
 * - displayManufacturerListScalar()
 * 
 * Possible Arguments:
 * SESSION:
 * - inv - Used to determine whether the current user has inventory
 *   privledges.
 * 
 * POST:
 * - ID - The ID of the item we're attempting to edit.
 * - submit - This variable will not be empty when we need to do work.
 * - price - The price we want to assign the item.
 * - saleprice - The Sale Price we want to assign the item.
 * - cost - The value we want to assign to the item's cost.
 * - department - The department we want to assign the item to.
 * - manufacturer - The manufacturer we want to assign the item to.
 * - UPC - The item's UPC Code
 * - alternate1 - Extra slots for additional UPC Codes or search strings.
 * - alternate2 - Extra slots for additional UPC Codes or search strings.
 * - inv - Unknown, should not do anything, investigate further.
 * - tax - Boolean value indicating whether the item is taxable.
 * - desc - This is a hidden description from the database to ensure the
 *   correct description gets included in the SQL query.
 * 
 * GET:
 * - ID - If ID is set, the page will display the UI to edit.
 * 
 * @link http://www.worldsapartgames.org/fc/edititem.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = 'Edit Item';
$version = '1.8d';
require_once 'funcs.inc';
require_once 'inventory.inc';
require_once 'header.php';

$cxn = open_stream();
if ($_SESSION['inv'] != 1) {
    echo "You must have Inventory Permissions to recieve and invoice. If you believe you have recieved this in error, please
        contact the General Manager or Quartermaster.<p>";
    include_once 'footer.php';
    exit();
}

// Check for POST and Make changes
if ($_POST['submit'] == 'submit') {
    extract($_POST);
    $stmt = $cxn->prepare(
        "UPDATE items 
        SET price = '$price',
        salePrice = '$salePrice',
        cost = '$cost',
        department = '$department',
        manufacturer = '$manufacturer',
        UPC = '$UPC',
        alternate1 = '$alternate1',
        alternate2 = '$alternate2',
        inv = '$inv',
        tax = '$tax'
        WHERE ID='$ID';"
    );
    if ($stmt->execute()) {
        echo "Changes Made";
        include_once 'footer.php';
        exit();
    } else {
        echo "Error: " . $cxn->error . "<p>";
        include_once 'footer.php';
        exit();
    }
}

// Display Form
if ($_GET['ID'] > 0) {
    $ID = $_GET['ID'];
    $sql = "SELECT * FROM items WHERE ID='$ID'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_assoc($result);
    extract($row);
    echo "Edit Data For $description, Item #$ID<p>
        <form action='edititem.php' method='post'>
        <input type='hidden' name='ID' value='$ID'>
        Price: <input name='price' value='$price' type='text' size=8 maxsize=8><br>
        Sale Price: <input name='salePrice' value='$salePrice' type='text' size=8 maxsize=8> (Leave blank if none)<br>
        Cost: <input name='cost' value='$cost' type='text' size=8 maxsize=8> (don't change cost unless you need to because it has some minor accounting side effects)<br> 
        Department: ";
    displayDepartmentListScalar($department);
    echo "<br>Manufacturer: ";
    displayManufacturerListScalar($manufacturer);
    echo "<br>UPC: <input type='text' name='UPC' size=35 maxlength=35 value='$UPC'>
        <br>Alt1: <input type='text' name='alternate1' size=35 maxlength=35 value='$alternate1'>
        <br>Alt2: <input type='text' name='alternate2' size=35 maxlength=35 value='$alternate2'>";
    echo "<br>Taxable Item? <input type='checkbox' name='tax' value='1'";
    if ($tax) {
        echo " checked";
    }

    echo "><input type='submit' name='submit' value='submit'></form>";
}

require_once 'footer.php';
?>
