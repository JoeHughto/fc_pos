<?php
/**
 * Changeprice.php is a page that was used to change the prices of existing
 * items in the system. If it is not provided with some GET arguments, it
 * does nothing.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category   Inventory_Modifier
 * @package    FriendComputer
 * @author     Michael Whitehouse 
 * @author     Creidieki Crouch 
 * @author     Desmond Duval 
 * @copyright  2009-2014 Pioneer Valley Gaming Collective
 * @license    TBD
 * @version    GIT:$ID$
 * @link       http://www.worldsapartgames.org/fc/changeprice.php
 * @since      Project has existed since time immemorial.
 * @deprecated since version 1.2
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 */
$title = "Change Price";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

/**
 * Possible Arguments:
 * SESSION:
 *   inv - Used to determine whether the current user has inventory
 *     privledges.
 * POST:
 *   ID - The ID of the item we're attempting to change the price of.
 *   price - The price we want to assign the item.
 *   cost - The value we want to assign to the item's cost.
 *   desc - This is a hidden description from the database to ensure the
 *     correct description gets included in the SQL query.
 * GET:
 *   ID - If ID is set, the page will display the UI to change the price of
 *     the given item.
 */
$cxn = open_stream();
if ($_SESSION['inv'] != 1) {
    echo "You must have Inventory Priviledges to adjust prices and costs<p>";
    include 'footer.php';
    die();
}

if ($_POST['ID'] > 0) {
    extract($_POST);
    if ($price > 0) {
        $psql = "price = $price";
    }
    if ($cost > 0) {
        $csql = ", cost = $cost";
    }
    $sql = "UPDATE items SET $psql $csql WHERE ID=$ID";
    if (query($cxn, $sql)) {
        echo "$desc updated<br>
            Price set to " . money($price) . "<br>
            <a href='inventoryreport.php#$ID'>Return to Inventory Report</a><br>\n";
        if ($cost > 0) {
            echo "Cost set to " . money($cost) . "<br>\n";
        }
    }
}

extract($_GET);

if ($ID > 0) {
  
    $sql = "SELECT * FROM items WHERE ID='$ID'";
    $row = queryAssoc($cxn, $sql);
    extract($row);
   
    echo "<font size=+3>Adjust Price/Cost</font><br>
         <b>$description</b><br>
         <form action='changeprice.php' method='post'>
         Price: \$ <input name='price' value='$price' type='text' size=8 maxlength=8><br>\n";
    if ($_GET['cost'] == 'show') {
        echo "Cost: \$ <input name='cost' value='$cost' type='text' size=8 maxlength=8><br>\n";
    }
    echo "<input type='hidden' name='ID' value='$ID'>
         <input type='hidden' name='desc' value='$description'>
         <input name='dominate' type='submit' value='dominate'></form>\n";
}

require_once 'footer.php';
?>