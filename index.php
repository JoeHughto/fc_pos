<?php
/**
 * index.php is the master page, presenting members with some easy info
 *   and tools, as well as links to other helpful features of FriendComputer.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Account_View
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   determineCurrentShift()
 *   getAvailBalance()
 *   accountTransact()
 *   money()
 *   printMemberString()
 *   check_email_address()
 *   selectMember()
 *   dayToNum()
 *   lateReg()
 *   getAccountBalance()
 *   getAccountPacks()
 * friendcomputer.inc:
 *   fcMessage()
 * member.inc:
 *   peopleReferred()
 *   FG_showInfoNoSale()
 * credits.inc:
 *   requestCredits()
 *   transferCredits()
 *   getCreditTotal()
 *   displayHedonRequests()
 *   displayMembershipStatus()
 */
$securePage = false;
$title = "Worlds Apart Games - Master Page";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'friendcomputer.inc';
require_once 'member.inc';
require_once 'credits.inc';

/**
 * Possible Arguments:
 * SESSION:
 *   ID - Several features require the active user's member ID to process.
 *   mem - Used to determine whether the current user has membership
 *     coordinator privledges.
 *   reg - Used to determine whether the current user has register
 *     privledges.
 * POST:
 *   submit - When this variable is filled, we need to do work.
 *     The values this variable can have are:
 *       'submit request',
 *       'submit email',
 *       'submit transfer'.
 *   hedonAmount - Used with a submit request to request a number of hedons.
 *   hedonNote -  Used with submit request to annotate a request.
 *   trashbutton - If this variable is filled, it means we need to give hedons.
 *   bottlebutton - If this variable is filled, it means we need to give hedons.
 *   paperbutton - If this variable is filled, it means we need to give hedons.
 *   humidifier - If this variable is filled, it means we need to give hedons.
 *   smallthing - If this variable is filled, it means we need to give hedons.
 *   account_submit - This is a submit button that should be merged into "submit".
 *   account_recip - The ID of the member you're transferring store credit to.
 *   account_amount - The amount of store credit you want to transfer.
 *   removeEmail - This variable is an email that should be removed from FC
 *   credMem - This is the ID of the member you're transferring Hedons to.
 *   credAmount - This is the number of Hedons you're transferring.
 *   credNote - This is a note, for giving a reason for the transfer.
 *   credReason - This is an integer Reason Code, as defined in credits.inc
 *   checkin - This variable will be filled if active user is trying to check in.
 * GET:
 *   logout - This variable will be filled if active user is trying to logout.
 */
if ($_POST['submit'] == 'submit request') {

    extract($_POST);

    // process credit transactions if appropriate
    if ($hedonAmount > 0) {
        $message .= "Requesting Hedons...<br>";

        $hedonNote = strip_tags($hedonNote);
        if (requestCredits($_SESSION['ID'], $hedonAmount, $hedonNote)) {
            $message .= "Your request for $hedonAmount Hedons has been "
                . "processed, and will be dealt with as soon as possible.";
        } else {
            $message .= "Request failed, contact a High Programmer immediately!";
        }
    }
}

require 'header.php';

$cxn = open_stream();

$SID = $_SESSION['ID'];

// check for GET data for a logout
if ($_GET['logout'] == 1) {
    foreach ($_SESSION as $key => $value) {
        unset($_SESSION[$key]);
    }

    die (
        "<h1>Login</h1>
        <hr>
        <form action='index.php' method='post'>
        Username: <input type='text' name='username'><p>
        Password: <input type='password' name='password'><p>
        <input type='submit' name='submit' value='Login'>
        </form><a href='forgotpassword.php'>Forgot your password?</a><p>"
    );

}

// get info to display current time
date_default_timezone_set('America/New_York');

$date = date("l, F jS, o g:i A");
$shiftNow = determineCurrentShift();
//date_default_timezone_set('America/New_York');
$time = localtime();
//displayError("<br> Debug: Local time is " . $time[2] . ":" . $time[1] . "<br> Debug: Current shift is " . $shiftNow . "<br>Disregard the following line telling you to email the high programmer");

// process trash button
if ($_POST['trashbutton'] == 1) {
    if (transferCredits(1, $_SESSION['ID'], .5, "Taking out the trash", 1)) {
        $message .= "You have been given 1/2 Hedon for taking out the trash.<p>
            Thank you for being AWESOME!!!<p>\n";
    }
}
// process trash button
if ($_POST['bottlebutton'] == 1) {
    if (transferCredits(1, $_SESSION['ID'], .5, "Taking out the bottles", 1)) {
        $message .= "You have been given 1/2 Hedon for taking out the bottles.<p>
            Thank you for being AWESOME!!!<p>\n";
    }
}
// process trash button
if ($_POST['paperbutton'] == 1) {
    if (transferCredits(1, $_SESSION['ID'], .5, "Taking out the paper", 1)) {
        $message .= "You have been given 1/2 Hedon for taking out the paper.<p>
            Thank you for being AWESOME!!!<p>\n";
    }
}
// process trash button
if ($_POST['humidifier'] == 1) {
    if (transferCredits(1, $_SESSION['ID'], .5, "Clearing the humidifer", 1)) {
        $message .= "You have been given 1/2 Hedon for clearing the humidifier.<p>
            Thank you for being AWESOME!!!<p>\n";
    }
}
// process smallthing button
if ($_POST['smallthing'] == 1) {
    if (transferCredits(1, $_SESSION['ID'], .25, "Clearing the humidifer", 1)) {
        $message .= "You have been given 1/4 Hedon for doing something small.<p>
            Thank you for being AWESOME!!!<p>\n";
    }
}

// Account Transfer
if ($_POST['account_submit'] == 1) {
    $recip = $_POST['account_recip'];
    $amount = $_POST['account_amount'];
    $avail = getAvailBalance($_SESSION['ID']);

    if ($amount <= $avail) {
        if ($recip > 0 && $amount > 0) {
            // fork over the dough
            if (accountTransact(
                $_SESSION['ID'], -$amount, 0, "TXed to $recip"
            )
            ) {
                if (accountTransact(
                    $recip, $amount, 0, "TXed from {$_SESSION['ID']}"
                )
                ) {
                    $message .= money($amount) . " transfered to "
                        . printMemberString($recip, 1) . "<p>\n";
                } else {
                    $message .= "Error giving account to "
                        . printMemberString($recip, 1) . "<p>\n";
                }
            } else {
                $message .= "Error transferring account.<p>\n";
            }
        }
    } else {
        $message .= "<font color=RED>You tried to transfer " . money($amount) . ".
            <br>You have " . money($avail) . " available.<br>Math Fail</font><p>\n";
    }
}

// check for removing email
if ($_POST['submit'] == 'submit email') {
    $email = $_POST['removeEmail'];
    if (check_email_address($email)) {
        $sql = "SELECT fname, lname FROM members WHERE email='$email'";
        $result = query($cxn, $sql);
        if ($row = mysqli_fetch_assoc($result)) {
            extract($row);
            $sql = "UPDATE members SET optOut=1 WHERE email='$email'";
            if (query($cxn, $sql)) {
                $message .= "Email for $fname $lname opted out of list.<p>";
            } else {
                $message .= "Error setting optout for $fname $lname.<p>";
            }
        } else {
            $message .= "$email not found in database.<p>";
        }
    } else {
        $message .= "'$email' not proper email address.<p>";
    }
}

// check for POST data. If there is some, report results.
if ($_POST['submit'] == 'submit transfer') {

    extract($_POST);

    // process credit transactions if appropriate
    if ($credMem > 0 && $credAmount > 0
        && $credReason > 0 && $credReason <= 3
    ) {
        $message .= "Processing Hedons<br>";

        $credNote = strip_tags($credNote);
        if (transferCredits(
            $_SESSION['ID'], $credMem, $credAmount, $credNote, $credReason
        )
        ) {
            switch($credReason)
            {
            case 1:
                $message .= "You gave $credAmount Hedons for shift coverage to ";
                break;
            case 2:
                $message .= "You gave $credAmount Hedons from the officer fund to ";
                break;
            case 3:
                $message .= "You transfered $credAmount Hedons to ";
                break;
            }
            printMemberString($credMem, 1);
            $message .= "<br>Reason: $credNote<p>";
        } else {
            $message .= "<b>Insufficient Hedons for your transfer.</b><br>
                You tried to transfer $credAmount.<p>";
        }
    }
} // end check for POST submit


// process checkin
if ($_POST['checkin'] == 1) {
    // check to see if the staff person has been paid. Pay if they have not
    $sql = "SELECT checkedIn FROM schedule WHERE day=CURDATE() AND shift='$shiftNow'";
    //echo "<b>DEBUG:</b><br>MySQL Query: $sql.<p>";
    $row = queryAssoc($cxn, $sql);
    $payCredits = ($row['checkedIn'] == 0) ? true : false;

    $sql = "UPDATE schedule SET checkedIn='1', staffID='$SID' "
        . "WHERE day=CURDATE() AND shift='$shiftNow'";
     // only want to check in if he has not been checked in yet
    if (query($cxn, $sql)) {
        if ($cxn->affected_rows==0) {
            $sql = "INSERT INTO schedule (checkedIn, staffID, day, shift) "
                . "VALUES ('1', '$SID', CURDATE(), '$shiftNow')";
            query($cxn, $sql);
            if ($cxn->affected_rows==0) {
                $message .= "DEBUG: Error during checkin. checkin=1, SID=$SID<br>\n";
            }
        }


        if ($payCredits) {
            $message .= "You are now checked in for your shift<br>\n";

            $cfts = (($shiftNow == 1) ? ($SHIFTCREDITS + 1) : $SHIFTCREDITS);
            requestCredits(
                $_SESSION['ID'], $cfts,
                "Covering: " . date("D y-m-d")
                . " shift: $shiftNow"
            );

        } // end if
    }
}

fcMessage($message);

echo "<table><tr><td width=50% valign=top>";

// remove from email list
if ($_SESSION['mem'] == 1) {
    echo "Remove email address from mailing list: <form action='index.php' method='post'>
        <input name='removeEmail' type='text' size='35' maxlength='90'>
        <input type='submit' name='submit' value='submit email'><hr>";
}


// display credit balance
echo "<h2>Hedon Account</h2>";
$balance = getCreditTotal($SID);
if (!($balance > 0)) {
    $balance = 0;
}
echo "Current Balance: $balance<p>\n";

// display pending hedon requests
displayHedonRequests($SID, 5);

// display hedon request button
echo "<b>Request Hedons</b><br>
    <form action='index.php' method='POST'>
    Amount requested: <input type='text' maxlength=4 size=4 name='hedonAmount'><br>
    Reason: <input type='text' maxlength=50 size=4 name='hedonNote'>
    <input type='submit' name='submit' value='submit request'></form><hr>";

// display credit transfer button
echo "<b>Transfer Hedons</b><br>
    <form action='index.php' method='POST'>
    Recipient: ";
selectMember('credMem', 0);
echo "<br>Amount to transfer: <input type='text' maxlength=4 size=4 
    name='credAmount'><br>
    Note: <input type='text' maxlength=50 size=4 name='credNote'>
    <input type='hidden' name='credReason' value='3'>
    <input type='submit' name='submit' value='submit transfer'></form><hr>";

// display Officer transfer
if ($_SESSION['mem'] == 1) {
    echo "<b>Officer Hedon Transfer</b><br>
        This means that you are paying out of the Great Fund!<br>
        <form action='index.php' method='POST'>
        Recipient: ";
    selectMember('credMem', 0);
    echo "<br>Amount to transfer: <input type='text' maxlength=4 size=4 
        name='credAmount'><br>
        Note: <input type='text' maxlength=50 size=4 name='credNote'>
        <input type='hidden' name='credReason' value='2'>
        <input type='submit' name='submit' value='submit transfer'></form><hr>";
}

 // Trash button
echo "You can get Hedons for small tasks around the store. If you do the 
    task, push the button. Do not abuse our honor system!<br>
    <form action='index.php' method='post'>
    <button name='trashbutton' type='submit' value='1'>I took out the trash, 
    give me a half Hedon!</button>
    <button name='bottlebutton' type='submit' value='1'>I took out the 
    bottles, give me a half Hedon!</button>
    <button name='paperbutton' type='submit' value='1'>I took out the 
    paper, give me a half Hedon!</button>
    <button name='humidifier' type='submit' value='1'>I cleared the 
    humidifier, give me a half Hedon!</button><p>
    <button name='smallthing' type='submit' value='1'>I did a little thing,
    give me 1/4 Hedon!</button>
    </form><p>\n";

// show referrals
$refcount = peopleReferred($_SESSION['ID']);
echo "<hr>You have referred $refcount people so far.\n";


echo "</td><td width=50% valign=top>";

// display shift information
if ($_SESSION['reg'] == 1) {   //If user has register privs vVv
    // display sign in button if applicable
    // this section stands alone
    if ($shiftNow > 0) {//If the store is currently open vVv
        $sched = 0;
        $sql = "SELECT staffID FROM recurringSchedule
        WHERE day='" . dayToNum(date("l")) . "' AND shift='$shiftNow'";
        $result = query($cxn, $sql);
        if ($recRow = mysqli_fetch_row($result)) {
            $sched = 1;
        }

        $sql = "SELECT staffID, checkedIn
            FROM schedule
            WHERE day=CURDATE()
            AND shift='$shiftNow'";
        $result = query($cxn, $sql);
        if ($intRow = mysqli_fetch_row($result)) {
            //If there is anyone on the schedule for today, this is not the recurring schedule. vVv
            $sched = 1; // indicates if there is already a schedule entry or if it is recurring
        }

        //If no one is scheduled, or you're on the schedule, and you're not checked in, or if it's 30 minutes past start of shift, and no one is checked in...
        if ($sched == 0 || ($intRow[1] != 1 && ($recRow[0] == $SID 
            || $intRow[0] == $SID || lateReg()))
        ) {
            echo "<form action='index.php' method='post'>
                <input type='image' src='images/checkin.png' 
                name='checkin' value='1'>
                </form>";

            if ($sched == 0) {
                echo "<hr><table border><tr><td><b>No member is "
                . "scheduled for this shift.</b></td></tr></table><hr>\n";
            } elseif (lateReg()) {
                $curMem = 0;
                if ($recRow[0] > 0) {
                    $curMem = $recRow[0];
                }
                if ($intRow[0] > 0) {
                    $curMem = $intRow[0];
                }
                echo "<hr><table border><tr><td><b>";
                echo printMemberString($curMem, 1)
                    . " is late for this shift.</b></td></tr></table><hr>\n";
            }
        } elseif ($intRow[1] == 1 && $intRow[0] == $SID) {
            echo "<table border><tr><td><b>You are currently checked in for "
                . "this shift</b></td></tr></table><hr>\n";
        } elseif ($intRow[1] == 1) {
            echo "<table border><tr><td><b> ";
            echo printMemberString($intRow[0], 1) . " is currently checked "
                . "in for this shift</b></td></tr></table><hr>\n";
        }
    } // end if shiftNow > 0

    // end of displaying check in button
}   // end if REG

// show gamer rewards info
FG_showInfoNoSale($_SESSION['ID']);

// Store Account Stuff
echo "<a name='account'>
    <h2>Store Account</h2>
    Your current Store Account Balance: "
    . money(getAccountBalance($_SESSION['ID'])) . "<br>
    Your available Store Account Balance: " 
    . money(getAvailBalance($_SESSION['ID'])) . "<br>
    Your current Future Packs: " 
    . getAccountPacks($_SESSION['ID']) . "<br>
    Your current Future Pack Value: " 
    . money(3 * getAccountPacks($_SESSION['ID'])) . "<p>
    Transfer Store Account to another Member<br>
    <i>To donate account to the store, give it to Abyss, T (#80)</i><br>
    <form action='index.php' method='post'>
    Amount to transfer: <input name='account_amount' 
    type='text' size=8 maxlength=8><br>Recipient: ";
selectMember('account_recip', 0);
echo "<br><button name='account_submit' type='sumbit' value='1'>Transfer "
    . "Account</button></form><hr>\n";

// display membership status
echo "<h2>Membership Status</h2>";

displayMembershipStatus($SID);

echo "<hr>";

// allow user to renew membership
echo "   <form action='spendcredits.php' method='post'>
    Renew Membership for <input type='text' name='renew' size=3 maxlength=3> 
    months for 15 Hedons per month.<p> <input type='submit' name='submit' 
    value='submit'></form><p>";

if ($_SESSION['reg'] == 1) {
    $sql = "SELECT * FROM recurringSchedule WHERE staffID='$SID'";
    $result = query($cxn, $sql);
    $first = true;

    $days = array (1 => "Sunday", "Monday", "Tuesday", "Wednesday", 
        "Thursday", "Friday", "Saturday");
    $shifts = array (1 => "10 AM - 2 PM", "2 PM - 6 PM", "6 PM - 10 PM");

    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($first) {
            echo "<h2>Your Recurring Shifts</h2>
                <table cellpadding=8>";
            $first = false;
        }
        echo "<tr><td>" . $days[$day] . "</td><td>Shift #$shift</td><td>(" 
            . $shifts[$shift] . ")</td></tr>";
    }
    echo "</table>";

    $sql = "SELECT *
        FROM schedule
        WHERE staffID='$SID'
        AND ADDDATE(CURDATE(), INTERVAL 14 day) > day
        AND day >= CURDATE()
        ORDER BY day";
    $result = query($cxn, $sql);
    $first = true;
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($first) {
            echo "<h2>Your upcoming special shifts</h2>";
            $first = false;
        }
        date_default_timezone_set('America/New_York');
        echo date_format(date_create($day), "D M j") . " - "
            . $shifts[$shift] . "<br>\n";
    }
    if (!$first) {
        echo "<hr>";
    }
}


echo "</td></tr></table>";
require 'footer.php';
?>
