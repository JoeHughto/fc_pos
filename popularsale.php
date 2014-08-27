<?php
/**
 * PopularSale.php is a page which displays two lists, one list of items which
 *   are out of stock, and have sold in the last 3 months, and one list of items
 *   which are in stock, but haven't sold in at least 6 months.
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
 * @link      http://www.worldsapartgames.org/fc/popularsale.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 */
$title = "Popular Sale Report";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

/**
 * Possible Arguments:
 * No arguments are supported by this application.
 */

$cxn = open_stream();

// list of items we need to reorder
$sql = "SELECT * FROM items WHERE qty=0 AND (department LIKE 'Board Games' OR department LIKE 'Card Games')";
$result = query($cxn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    extract($row);

    $sql = "SELECT t.whensale rawdate,
        s.transactionID TID
        FROM transactions t
        JOIN soldItem s
        ON t.ID = s.transactionID
        WHERE s.itemID = $ID
        AND s.onSale = 0
        ORDER BY t.whensale DESC
        LIMIT 0,1";
    $transactionresult = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($transactionresult)) {
        $rawdate = $row['rawdate'];
        $TID = $row['TID'];

        $saledate = date_create($rawdate);
        $nowdate = date_create();
        for ($i = 1; $i < 4; $i++) {
            $nowdate->modify("-1 month");
            if ($saledate >= $nowdate) {
                $monthsSinceLastSale[$ID] = $i;
                break;
            }
        }
        $itemName[$ID] = $description;
        $transaction[$ID] = $TID;
        $whendat[$ID] = $rawdate;
    }
}

arsort($monthsSinceLastSale);
echo "Board and card games that are out of stock that sold in the last 90 days<p>
    <table bgcolor=CCFFCC cellpadding=5 border><tr><td>ID#</td><td>Name</td><td>Months Since Last Sale</td><td>Sale Date</td></tr>\n";

foreach ($monthsSinceLastSale as $key => $value) {
    if ($value <= 3) {
        echo "<tr><td>$key</td><td><a href='edititem.php?ID=$key'>{$itemName[$key]}</a></td><td>$value</td>
            <td><a href='viewreceipts.php?view={$transaction[$key]}' target='otherpage'>{$whendat[$key]}</a></td></tr>\n";
    }
}
echo "</table><p>";

// List of items to put on sale
echo "<hr>";

$sql = "SELECT * FROM items WHERE qty>0 "
    . "AND (department LIKE 'Board Games' OR department LIKE 'Card Games')";
$result = query($cxn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    extract($row);

    $sql = "SELECT t.whensale rawdate,
        s.transactionID TID
        FROM transactions t
        JOIN soldItem s
        ON t.ID = s.transactionID
        WHERE s.itemID = $ID
        ORDER BY t.whensale DESC
        LIMIT 0,1";
    $transactionresult = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($transactionresult)) {
        $rawdate = $row['rawdate'];
        $TID = $row['TID'];

        $saledate = date_create($rawdate);
        $nowdate = date_create();
        for ($i = 0; $i < 6; $i++) {
            $nowdate->modify("-6 month");
            if ($saledate < $nowdate) {
                $halves[$ID] = $i;
            }
        }
        $whendat[$ID] = $rawdate;
        $transaction[$ID] = $TID;
    } else {
        $halves[$ID] = 6;
        $whendat[$ID] = "Never Sold";
    }

    if ($halves[$ID] == 0) {
        unset($halves[$ID]);
    }
    $itemName[$ID] = $description;
}

arsort($halves);
echo "Board and card games that may have thick layers of dust on them<p>
    <table bgcolor=FFCCCC cellpadding=5 border><tr><td>ID#</td><td>Name</td><td>Half Years<Br>Since Last Sale</td><td>Sale Date</td></tr>\n";

foreach ($halves as $key => $value) {
    if ($whendat[$key] == "Never Sold") {
        echo "<tr><td>$key</td><td><a href='edititem.php?ID=$key' target='nevsol'>{$itemName[$key]}</a></td><td>$value</td>
            <td>Never Sold</td></tr>\n";
    } else {
        echo "<tr><td>$key</td><td><a href='edititem.php?ID=$key'>{$itemName[$key]}</a></td><td>$value</td>
            <td><a href='viewreceipts.php?view={$transaction[$key]}' target='otherpage'>{$whendat[$key]}</a></td></tr>\n";
    }
}
echo "</table><p>";


require 'footer.php';
?>