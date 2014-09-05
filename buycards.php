<?php
/**
 * @file buycards.php
 * @brief buycards.php is used to give account balance to members who sell cards
 *   to the store for store credit.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - displayErrorDie()
 * - accountTransact()
 * - printMember()
 * - getAccountBalance()
 * - getAvailBalance()
 * - selectMember()
 * 
 * Possible Arguments:
 * SESSION:
 * - ID - Used to add the volunteer's ID to the transaction, as the member who
 *   authorized the transaction.
 * 
 * POST:
 * - submit - When this variable = 'Submit', the button has been pressed, so
 *   we should attend to the data, and ship some store credit.
 * - member - The integer Member ID number, to whose account store credit
 *   should be applied.
 * - price - The amount of money that should be deducted from card sales,
 *   and put into the account of the member selling the cards to the store.
 *
 * @link http://www.worldsapartgames.org/fc/buycards.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Buy Cards";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

$cxn = open_stream();

if ($_SESSION['inv'] != 1) {
    echo "You must have Inventory Priviledges to buy magic cards<p>";
    include 'footer.php';
    die();
}

// check to see if data has been submitted
if ($_POST['submit'] == 'Submit') {
    extract($_POST);
    if (($member > 0) && ($price > 0)) {
        echo "<font size=+3>Processing Transaction</font><p>";
      
        $sql = "INSERT INTO transactions (StaffID, totalPrice, totalCost, tax, payMethod, whensale, closed)
                   VALUES ('" . $_SESSION['ID'] . "', '0', '$price', '0', '0', NOW(), '1')";
        if (query($cxn, $sql)) {
            echo "Transaction Added<br>\n";
        } else {
            displayErrorDie("Unable to add transaction");
        }
      
        $tid = $cxn->insert_id;
        $sql = "INSERT INTO soldItem (transactionID, itemID, price, tax, cost, qty)
                   VALUES ('$tid', '778', '0', '0', '$price', '1')";
        if (query($cxn, $sql)) {
            echo "Item Data Added<br>\n";
        } else {
            displayErrorDie("Unable to add item");
        }
      
        if (accountTransact($member, $price, $tid, "Sale of singles")) {
            echo money($price) . " put into the account of ";
            printMember($member, 1);
            echo "<br>Total Account: " . money(getAccountBalance($member)) .
              "<br>Account Avail: " . money(getAvailBalance($member));
        } else {
            displayErrorDie("Unable to add account");
        }
        echo "<hr>";
    } else {
        echo "<font color=RED>Invalid Member or Price</font><hr>";
    }
}


echo "This application will create an entry in the database which will cause a cost of goods to be applied to magic singles.
<p>
If you are unsure about what you are doing with this application, don't do it. Find someone who knows the answers you seek. Do not guess.
<p>
Member who will receive credit to account for this transaction.<br>
You must select a member or the transaction will not go through.<br><form action='buycards.php' method='post'>\n";
selectMember('member', 0);
echo "<br>Amount of purchase: \$<input name='price' size=8 maxlength=8><br>
<input name='submit' type='submit' value='Submit'></form>\n";

require_once 'footer.php';
?>