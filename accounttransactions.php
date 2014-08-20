<?php
/**
 * AccountTransactions.php shows users their ledger of store credit transactions.
 *
 * Long description for file (if any)...
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
 * @link      http://www.worldsapartgames.org/fc/accounttransactions.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   checkDateNum()
 *   displayRefs()
 *   getAccountBalance()
 *   getAvailableBalance()
 *   displayAccount()
 * header.php for the top bar and menu.
 */
require 'funcs.inc';
require 'header.php';

if ($_GET['ID'] > 0) {
    $ID = $_GET['ID'];
} else {
    $ID = $_SESSION['ID'];
}

if ($_GET['qty'] > 0) {
    $qty = $_GET['qty'];
} else {
    $qty = 0; // no limit
}

// if refs are to be displayed
if (checkDateNum($_GET['start'])) {
    echo "<font size=+3>Referral Bonuses</font><br>\n";
    displayRefs($_GET['start'], $ID);
    echo"<hr>";
}   

$total = getAccountBalance($ID);
$avail = getAvailBalance($ID);
echo "<font size=+3>Store Account Transactions</font><br>
Total Account: " . money($total) . "<br>
Total Available: " . money($avail) . "<hr>";
displayAccount($qty, $ID);

require 'footer.php';
?>