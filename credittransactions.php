<?php
/**
 * CreditTransactions.php is a page used to check on a given member's
 *   Hedon transactions.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Ledger_View
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Crideke Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/credittransactions.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   printMemberString()
 * credits.inc:
 *   getCreditTotal()
 *   displayCreditTransactions()
 */
$title = "Hedon Transactions";
require_once 'funcs.inc';
require_once 'credits.inc';
require_once 'header.php';

/**
 * Possible Arguments:
 * SESSION:
 *   mem - Used to determine whether the current user has membership
 *     coordinator privledges.
 *   ID - Used to show the current user their own Hedon transactions.
 * GET:
 *   ID - If mem is set, ID may be sent as a GET variable to show any other
 *     member's ledger, otherwise the active user's account will be shown.
 *   qty - Number of records to display, or 'ALL' to display all records.
 */
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

$version=1.7;
require_once 'footer.php';
?>