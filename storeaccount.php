<?php
// storeaccount.php
// allows user to add money to an account

// when money is put into an account for prepayment, the transactionID is 0
// when money is put in for some other reason, the transactionID is -1. This can only be done by "off" in future versions
// until "off" is added, it will require "mem"

// this means that when the computer counts how much money should be in the register, it should take the total of sales plus
// account changes with TID 0

// POSTs as scalar or array
// amount - amount that is to be added to account
// item - items which are being prepaid for

// GET
// qty - how many items is it set up for (default 5)

// member(scalar) - member whose account is being altered

$title = "Give Account (no cash)";
include('funcs.inc');
include('friendcomputer.inc');
include('header.php');
$cxn = open_stream();
$message = '';

if ($_SESSION['eve'] != 1 && $_SESSION['adm'] != 1) {
    die("You must have Admin or Event Permission to use this application");
}

//if (isset($_POST['submit']) && $_POST['member'] > 0) {
//    extract($_POST);
//
//    $message .= "Submitting Orders for Member #$member<br>";
//
//    if (!is_array($amount)) $amount[0] = $amount;
//    if (!is_array($item)) $item[0] = $item;
//
//    // submit into account
//    $a = array_sum($amount);
//    if ($a > 0) {
//        foreach ($item as $name) {
//            $notes .= (strlen($name) > 1) ? $name . ',' : '';
//        }
//
//        $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)");
//        $stmt->bind_param("dds", $member, $a, $notes);
//        if ($stmt->execute()) {
//            $message .= $notes . " for $a put in Member's account<br>";
//            $stmt->close();
//            $accountFail = false;
//        } else {
//            $message .= "Failed to submit account for " . $notes . "<br>";
//            $stmt->close();
//            $accountFail = true;
//        }
//    } // end if a > 0
//
//
//    // submit special order/requests
//    if ($accountFail != true) {
//        foreach ($item as $num => $it) {
//            if ($item[$num] == '') continue;
//            // make sure that $a is legit but allow it to be 0 also
//            $a = ($amount[$num] > 0) ? $amount[$num] : 0;
//
//            if ($a >= 0) {
//
//                $stmt = $cxn->prepare("INSERT INTO specialOrders (custID, dateMade, item, price) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)");
//                $stmt->bind_param("dsd", $member, $item[$num], $a);
//                if ($stmt->execute()) {
//                    $message .= ($a > 0) ? "Special order for " . $item[$num] . " submitted<br>"
//                        : "Request for " . $item[$num] . "submitted<br>";
//                } else {
//                    $message .= "Failed to submit " . $item[$num] . "<br>";
//                }
//            } // if $a >= 0
//        } // foreach item
//    } // if account fail
//} else if (isset($_POST['dominate'])) {
if (isset($_POST['give'])) {
    extract($_POST);
    $memberName = printMemberString($member,1);
    $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)");
    $stmt->bind_param("dds", $member, $giveAmount, $giveNotes);
    if ($stmt->execute()) {
        $notes = strip_tags($giveNotes);
        $message .= "Account increased by \$$giveAmount for $memberName<br>
                      Reason: $giveNotes";
    } else {
        $message .= "Failed to submit account increase for $memberName<br>";
    }
    $stmt->close();
} else if (isset($_POST['transfer']) && isset($_POST['fromMember']) && isset($_POST['toMember'])) {
    extract($_POST);
    $fromName = printMemberString($fromMember,1);
    $toName = printMemberString($toMember,1);
    if(getAccountBalance($fromMember) >= $transferAmount) {
        //Remove first
        $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)");
        $subtractAmount = $transferAmount * (-1);
        $transferMessage = "Removing in transfer";
        $stmt->bind_param("dds", $fromMember, $subtractAmount, $transferMessage);
        if ($stmt->execute()) {
            $message .= "Account decreased by \$$transferAmount for $fromName<br>";
        } else {
            $message .= "Failed to submit account decrease for $fromName<br>";
        }
        $stmt->close();

        //Add to other account
        $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, ?)");
        $transferMessage = "Adding in transfer";
        $stmt->bind_param("dds", $toMember, $transferAmount, $transferMessage);
        if ($stmt->execute()) {
            $message .= "Account increased by \$$transferAmount for $toName<br>";
        } else {
            $message .= "Failed to submit account increase for $toName<br>";
        }
        $stmt->close();
    } else {
        $message .= "Insufficient funds in $fromName's account<br>";
    }
}

fcMessage($message);

/*   echo "Special Orders<p>
         Select Customer Name:";
   echo "<form action='storeaccount.php' method='post'>\n";
   selectMember('member', '');
   echo "<p>
         Enter the price prepaid for the item. Enter 0 if it is a request, not a special order.<br>
         If a price is included, it is assumed that cash was taken at this time.<p>\n";

   $qty = (isset($_GET['qty'])) ? $_GET['qty'] : 5;
   
   for($i = 1; $i <= $qty; $i++)
   {
      echo "Item: <input type='text' name='item[$i]' size=40 maxlength=100>
            \$<input type='text' name='amount[$i]' size=8 maxlength=8><p>\n";
   }
   
   echo "<input type='submit' name='submit' value='submit'></form><form action='storeaccount.php' method='post'><p>\n";
*/
if ($_SESSION['eve'] == 1 || $_SESSION['adm'] == 1) {
    echo "<form action='storeaccount.php' method='post'>\n";
    echo "<b>Giving store credit to a member</b><br>";
    echo "Member: ";
    selectMember('member', '');
    echo "<br> Amount: \$<input type='text' name='giveAmount'><br>
            Reason: <input type='text' name='giveNotes'><br>
            <input type='submit' name='give' value='Give credit'><br><hr>";
    echo "<b>Transferring store credit from one member to another</b><br>";
    echo "From: ";
    selectMember('fromMember','');
    echo "<br>To: ";
    selectMember('toMember','');
    echo "<br> Amount: \$<input type='text' name='transferAmount'><br>
            <input type='submit' name='transfer' value='Transfer credit'><br><hr>";
    echo "</form><p>";
    }

include('footer.php');
?>
