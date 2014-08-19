<?php
// credittransactions.php
// shows a list of Credit Transactions
// By default shows all transactions of current logged in user

// GET
// ID - MEM only, shows credit transactions for a given user.
// qty - how many transactions to show

include('funcs.inc');
include('credits.inc');
include('header.php');

$ID = $_GET['ID'];

$qty = ($_GET['qty'] > 0) ? $_GET['qty'] : 'ALL';

if(($ID > 0) && ($_SESSION['mem'] == 1))
{
   echo "Hedon Total for " . printMemberString($ID, 1) . ": " . getCreditTotal($ID) . " H<hr>";
   displayCreditTransactions($ID, $qty);
}
else
{
   echo "Your Hedon Total: " . getCreditTotal($_SESSION['ID']) . " H<hr>";
   displayCreditTransactions($_SESSION['ID'], $qty);
}

$version=1;
include('footer.php');
?>