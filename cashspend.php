<?php
/**
 * @file cashspend.php
 * @brief cashspend.php is used to track the removal of cash from the register.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - printMemberString()
 * 
 * Possible Arguments:
 * SESSION:
 * - ID - Used to add the volunteer's ID to the transaction, as the member who
 *   authorized the transaction.
 * 
 * POST:
 * - amount - The total amount that was taken from the register.
 * - reason - An explanation for why that money was taken.
 * 
 * @link http://www.worldsapartgames.org/fc/cashspend.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Cash Spend Report";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

$cxn = open_stream();

if ($_POST['amount'] > 0) {
    $amount = $_POST['amount'];
    $reason = $_POST['reason'];
   
    $stmt = $cxn->prepare(
        "INSERT INTO cashSpend (submitter, amount, reason, whenSub)
        VALUES (?, ?, ?, NOW())"
    );
    $stmt->bind_param("ids", $_SESSION['ID'], $amount, $reason);
    if ($stmt->execute()) {
        echo "<b>Cash Spend Submitted</b><br>\n" .
            money($amount) . " removed from register for reason:<br>
            $reason<hr>";
    } else {
        echo "<b>Error</b><br>
            Cash Spend not submitted properly<hr>";
    }
}

$sql = "SELECT * FROM cashSpend";
$result = query($cxn, $sql);

echo "<h2>Previous Cash Spends</h2>
      <table border><tr><td>Date</td><td>Staff</td><td>Amount</td><td>Reason</td></tr>\n";

while ($row = mysqli_fetch_assoc($result)) {
    extract($row);
    echo "<tr><td>$whenSub</td><td>" . printMemberString($submitter, 1) .
        "</td><td>" . money($amount) . "</td><td>$reason</td></tr>\n";
}
echo "</table>";
require_once 'footer.php';
?>