<?php
/**
 * @file count.php
 * @brief count.php is used to track how much cash is in the register, in order
 *   to prevent loss due to volunteer mistakes or theft.
 *
 * This file includes:<br>
 * funcs.inc:<br>
 * &nbsp;&nbsp;Used for the config.inc include<br>
 * &nbsp;&nbsp;printMemberString()<br>
 * &nbsp;&nbsp;printMember()<br>
 * friendcomputer.inc:<br>
 * &nbsp;&nbsp;fcMessage()<br>
 * <br>
 * Possible Arguments:<br>
 * SESSION:<br>
 * &nbsp;&nbsp;ID - Used to track the volunteer who is doing the count.<br>
 * &nbsp;&nbsp;adm - Used to determine whether the current user has admin<br>
 *   privledges. Any user with these will see the ledger for
 *   cash counts and deposits.<br>
 * &nbsp;&nbsp;mem - Used to determine whether the current user has membership
 *   coordinator privledges. Any user with these will see the ledger for
 *   cash counts and deposits.<br>
 * POST:<br>
 * &nbsp;&nbsp;bill - A key=>value array of "bill value"=>"bill quantity"<br>
 * &nbsp;&nbsp;coin - A key=>value array of "coin value * 100"=>"coin quantity"<br>
 * &nbsp;&nbsp;count - Total of all money included in the current count.<br>
 * &nbsp;&nbsp;deposit - Total of the current deposit.<br>
 * &nbsp;&nbsp;confirmed - Cash counts require_once confirmation to add to the database.
 *   This variable allows us to check for that confirmation.<br>
 * 
 * @link http://www.worldsapartgames.org/fc/changeprice.php @endlink
 * 
 * @author     Michael Whitehouse 
 * @author     Creidieki Crouch 
 * @author     Desmond Duval 
 * @copyright  2009-2014 Pioneer Valley Gaming Collective
 * @version    1.8d
 * @since      Project has existed since time immemorial.
 */

$title = "Cash Count";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'friendcomputer.inc';
require_once 'header.php';

$cxn = open_stream();

$ID = $_SESSION['ID'];
$message = '';

// media count
if (is_array($_POST['bill'])) {
    $count = 0.0;
    foreach ($_POST['bill'] as $key => $value) {
        $count += $key * $value;
    }
    foreach ($_POST['coin'] as $key => $value) {
        $count += ($key * $value) / 100;
    }
    $_POST['count'] = $count;
}

// Deal with count
if (($_POST['count'] > 0 || $_POST['deposit'] > 0) && $_POST['confirmed'] == "Confirm") {
    $count = $_POST['count'];
    $deposit = $_POST['deposit'];

    if ($count > 0) {
        // Find out what the count should be
        // Get time of last count
        $sql = "SELECT countTime, count FROM cashCounts WHERE deposit=0 ORDER BY countTime DESC";
        $result = query($cxn, $sql);
        $row = mysqli_fetch_assoc($result);
        $countTime = $row['countTime'];
        $countCount = $row['count'];

        $sql = "SELECT sum(cash) FROM transactions WHERE whenSale >= '$countTime'";
        $transCount = queryOnce($cxn, $sql);

        $sql = "SELECT sum(amount) FROM cashSpend WHERE whenSub >= '$countTime'";
        $spendCount = queryOnce($cxn, $sql);

        $sql = "SELECT sum(count) FROM cashCounts WHERE deposit=1 AND countTime >= '$countTime'";
        $depositCount = queryOnce($cxn, $sql);

        $difference = round(($countCount + $transCount - $spendCount - $depositCount - $count), 2);
        // Compare, Display, Email results
        echo "<h2>Count Results</h2>
            <table border><tr><td>Count:</td><td>" . money($count) . "</td></tr>
            <tr><td>Cash Sales</td><td>" . money($transCount) . "</td></tr>
            <tr><td>Cash Taken Out</td><td>" . money($spendCount) . "</td></tr>
            <tr><td>Deposits During Period</td><td>" . money($depositCount) . "</td></tr>
            <tr><td><b>Cash missing: </b></td><td><b>" . money($difference) . "</b> (positive is bad, negative is extra money)</td></tr>";
        echo "</table>\n";
        if ($difference > 5) {
            echo "Notice: Count is off by over \$5. That's bad.<hr>";
        }

        $datestamp = date("Y-M-j g:i A");

        $emessage = "Count Result
            Counter: " . printMemberString($_SESSION['ID'], 1) ."
            $datestamp

            Previous Count: $countCount
            Transaction Sales: $transCount
            Spend Out: $spendCount
            Deposits: $depositCount
            Count: $count

            Difference: $difference

            Today's Depost: $deposit

            Thank you for being an awesome High Programer.";
        mail("gm@pvgaming.org, mc@pvgaming.org", "FC Deposit Count", $emessage); 

        $sql = "INSERT INTO cashCounts (countTime, count, deposit, staffID) VALUES (NOW(), $count, 0, '$ID')";
        if (query($cxn, $sql)) {
            $message .= "Count of $count deposited correctly.<br>";
        } else {
            $message .= "Count not submitted due to error.<br>";
        }
    }
} elseif ($_POST['count'] > 0 || $_POST['deposit'] > 0) {
    $count = $_POST['count'];
    $deposit = $_POST['deposit'];

    if ($count > 0) {
        // Find out what the count should be
        // Get time of last count
        $sql = "SELECT countTime, count FROM cashCounts WHERE deposit=0 ORDER BY countTime DESC";
        $result = query($cxn, $sql);
        $row = mysqli_fetch_assoc($result);
        $countTime = $row['countTime'];
        $countCount = $row['count'];

        $sql = "SELECT sum(cash) FROM transactions WHERE whenSale >= '$countTime'";
        $transCount = queryOnce($cxn, $sql);

        $sql = "SELECT sum(amount) FROM cashSpend WHERE whenSub >= '$countTime'";
        $spendCount = queryOnce($cxn, $sql);

        $sql = "SELECT sum(count) FROM cashCounts WHERE deposit=1 AND countTime >= '$countTime'";
        $depositCount = queryOnce($cxn, $sql);

        $difference = round(($countCount + $transCount - $spendCount - $depositCount - $count), 2);
        // Compare, Display, Email results
        echo "<h2>Count Results</h2>
            <table border><tr><td>Count:</td><td>" . money($count) . "</td></tr>
            <tr><td>Cash Sales</td><td>" . money($transCount) . "</td></tr>
            <tr><td>Cash Taken Out</td><td>" . money($spendCount) . "</td></tr>
            <tr><td>Deposits During Period</td><td>" . money($depositCount) . "</td></tr>
            <tr><td><b>Cash missing: </b></td><td><b>" . money($difference) . "</b> (positive is bad, negative is extra money)</td></tr>";
        echo "</table>\n";
        if ($difference > 5) {
            echo "Notice: Count is off by over \$5. Please double check the count, and only confirm if you're sure this count is correct.<br>";
          
        }
        echo "<form action='count.php' method='post'><br><input type='submit' name='confirmed' value='Confirm'><B>CLICK HERE TO CONFIRM THIS COUNT, OR CORRECT BELOW </B>";
        $bill = $_POST['bill'];
        $coin = $_POST['coin'];
        //TODO: ADD VALUES TO BOXES, THEY'RE CURRENTLY NOT POPULATING FROM THE POST VALUES
        echo "<h2>Media Count</h2>
            Enter quantity of bills/coins (not value of bills, i.e. three 20's is 3, not 60)<br>
            <input name='bill[100]' type='text' size='6' maxlength='6' value='" . $bill[100] . "'> 100's<br>
            <input name='bill[50]' type='text' size='6' maxlength='6' value='" . $bill[50] . "'> 50's<br>
            <input name='bill[20]' type='text' size='6' maxlength='6' value='" . $bill[20] . "'> 20's<br>
            <input name='bill[10]' type='text' size='6' maxlength='6' value='" . $bill[10] . "'> 10's<br>
            <input name='bill[5]' type='text' size='6' maxlength='6' value='" . $bill[5] . "'> 5's<br>
            <input name='bill[2]' type='text' size='6' maxlength='6' value='" . $bill[2] . "'> 2's<br>
            <input name='bill[1]' type='text' size='6' maxlength='6' value='" . $bill[1] . "'> 1's<br>
            <input name='coin[100]' type='text' size='6' maxlength='6' value='" . $coin[100] . "'> Dollar Coins<br>
            <input name='coin[50]' type='text' size='6' maxlength='6' value='" . $coin[50] . "'> Half Dollars<br>
            <input name='coin[25]' type='text' size='6' maxlength='6' value='" . $coin[25] . "'> Quarters (40 per roll)<br>
            <input name='coin[10]' type='text' size='6' maxlength='6' value='" . $coin[10] . "'> Dimes (50 per roll)<br>
            <input name='coin[5]' type='text' size='6' maxlength='6' value='" . $coin[5] . "'> Nickles (40 per roll)<br>
            <input name='coin[1]' type='text' size='6' maxlength='6' value='" . $coin[1] . "'> Pennies (50 per roll)<br>
            <input name='submit' value='Update Media Count' type='submit'></form><hr>";
    }

    if ($deposit > 0) {
        $sql = "INSERT INTO cashCounts (countTime, count, deposit, staffID) VALUES (NOW(), $deposit, 1, '$ID')";

        if (query($cxn, $sql)) {
            $message .= "Deposit of $deposit deposited correctly.<br>";

            $datestamp = date("Y-M-j g:i A");

            $emessage = "A deposit was logged.
                Depositor: " . printMemberString($_SESSION['ID'], 1) ."
                $datestamp

                Today's Depost: $deposit";

            mail("michael@pvgaming.org, mc@pvgaming.org", "FC Deposit Recorded", $emessage); 
        } else {
            $message .= "Deposit not submitted due to error.<br>";
        }
    } // end if deposit
} else { // Show counting worksheet
    echo "<form action='count.php' method='post'>
        <h2>Media Count</h2>
        Enter quantity of bills/coins (not value of bills, i.e. three 20's is 3, not 60)<br>
        <input type='hidden' name='confirmed' value='false'>
        <input name='bill[100]' type='text' size='6' maxlength='6'> 100's<br>
        <input name='bill[50]' type='text' size='6' maxlength='6'> 50's<br>
        <input name='bill[20]' type='text' size='6' maxlength='6'> 20's<br>
        <input name='bill[10]' type='text' size='6' maxlength='6'> 10's<br>
        <input name='bill[5]' type='text' size='6' maxlength='6'> 5's<br>
        <input name='bill[2]' type='text' size='6' maxlength='6'> 2's<br>
        <input name='bill[1]' type='text' size='6' maxlength='6'> 1's<br>
        <input name='coin[100]' type='text' size='6' maxlength='6'> Dollar Coins<br>
        <input name='coin[50]' type='text' size='6' maxlength='6'> Half Dollars<br>
        <input name='coin[25]' type='text' size='6' maxlength='6'> Quarters (40 per roll)<br>
        <input name='coin[10]' type='text' size='6' maxlength='6'> Dimes (50 per roll)<br>
        <input name='coin[5]' type='text' size='6' maxlength='6'> Nickles (40 per roll)<br>
        <input name='coin[1]' type='text' size='6' maxlength='6'> Pennies (50 per roll)<br>
        <input name='submit' value='Update Media Count' type='submit'></form><hr>";
}

fcMessage($message);

if ($_SESSION['adm'] == 1 || $_SESSION['mem'] == 1) {
    echo "<table cellpadding=5><tr><td>Counter</td><td>Date</td><td>\$\$\$</td><td>Type</td></tr>\n";

    $sql = "SELECT * FROM cashCounts ORDER BY countTime DESC";
    $result = query($cxn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($deposit == 0) {
            echo "<tr><td>";
            printMember($staffID, 1);
            echo "</td><td>$countTime</td><td><font color=GREEN>\$";
            printf("%0.2f", $count);
            echo "</font></td><td>Count</td></tr>";
        } else {
            echo "<tr><td>";
            printMember($staffID, 1);
            echo "</td><td>$countTime</td><td><font color=BLUE>\$";
            printf("%0.2f", $count);
            echo "</font></td><td>Deposit</td></tr>";
        }
    }
}
?>
