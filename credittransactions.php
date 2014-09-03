<?php
/**
 * @file credittransactions.php
 * @brief credittransactions.php is a page used to check on a given member's
 *   Hedon transactions.
 * 
 * This file includes:<br>
 * funcs.inc:<br>
 * &nbsp;&nbsp;Used for the config.inc include<br>
 * &nbsp;&nbsp;printMemberString()<br>
 * credits.inc:<br>
 * &nbsp;&nbsp;getCreditTotal()<br>
 * &nbsp;&nbsp;displayCreditTransactions()<br>
 * <br>
 * Possible Arguments:<br>
 * SESSION:<br>
 * &nbsp;&nbsp; mem - Used to determine whether the current user has membership
 *   coordinator privledges.<br>
 * &nbsp;&nbsp;ID - Used to show the current user their own Hedon transactions.<br>
 * GET:<br>
 * &nbsp;&nbsp;ID - If mem is set, ID may be sent as a GET variable to show any other
 *   member's ledger, otherwise the active user's account will be shown.<br>
 * &nbsp;&nbsp;qty - Number of records to display, or 'ALL' to display all records.<br>
 * 
 * @link http://www.worldsapartgames.org/fc/credittransactions.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Hedon Transactions";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'credits.inc';
require_once 'header.php';

$ID = $_GET['ID'];

$qty = ($_GET['qty'] > 0) ? $_GET['qty'] : 'ALL';

if (($ID > 0) && ($_SESSION['mem'] == 1)) {
    echo "Hedon Total for " . printMemberString($ID, 1) . ": "
    .   getCreditTotal($ID) . " H<hr>";
    displayCreditTransactions($ID, $qty);
} else {
    echo "Your Hedon Total: " . getCreditTotal($_SESSION['ID']) . " H<hr>";
    displayCreditTransactions($_SESSION['ID'], $qty);
}
require 'footer.php';
?>