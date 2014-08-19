<?php
// accounttransactions.php
// a report of account transactions and referral bonuses
// GET: ID - userID
//      qty - number of transactions to list
//      start - start date of refs, if none, do not show refs

include('funcs.inc');
include('header.php');

if($_GET['ID'] > 0) 
   $ID = $_GET['ID'];
else
   $ID = $_SESSION['ID'];

if($_GET['qty'] > 0)
   $qty = $_GET['qty'];
else
   $qty = 0; // no limit

// if refs are to be displayed
if(checkDateNum($_GET['start']))
{
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

include('footer.php');
?>