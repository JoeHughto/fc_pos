<?php
/**
 * @file periodic.php
 * @brief periodic.php is a page used to perform all the once a month tasks in a batch.
 *
 * Currently, it gives $15, $10, and $5 credits to the highest raking frequent
 *   gamers, grants officers their monthly 15 hedons & $20 store credit, and
 *   gives Michael $50 store credit.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - printMemberString()
 * - accountTransact()
 * - displayError()
 * - printMemberString()
 * 
 * member.inc:
 * - taxExempt()
 * - memberSalesLastMonth()
 * - getMemberEmail()
 * 
 * credits.inc:
 * - transferCredits()
 * 
 * Possible Arguments:
 * SESSION:
 * - adm - Used to determine whether the active user has admin
 *   privs.
 * - ID - The ID of the active user, required for appending to
 *   some queries.
 * 
 * POST:
 * - monthly - If monthly is set to 1, we do some work, 
 *   running the monthly script.
 * 
 * @link http://www.worldsapartgames.org/fc/periodic.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Monthly Upkeep";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'member.inc';
require_once 'credits.inc';
require_once 'header.php';

$officers = array(5, 39, 417, 17, 1044);
if ($_SESSION['adm'] != 1) {
    echo "Restricted Page.";
    include 'footer.php';
    die();
}
// check for post command,  if so do monthly update
if ($_POST['monthly'] == 1) {
    $cxn = open_stream();

    $date = date_create();
    $date->modify("-1 month");
    $monthyear = $date->format('F Y');
    $firstFGNote = "#1 Customer $monthyear";
    $sql = "SELECT notes FROM storeAccount WHERE notes='$firstFGNote'";
    $result = query($cxn, $sql);
    if (1) {
        // determine FG winners
        $sql = "SELECT MAX(ID) FROM members";
        $result = query($cxn, $sql);
        $row = mysqli_fetch_row($result);
        $count = $row[0];

        for ($member = 1; $member <= $count; $member++) {
            $sales = (!taxExempt($member)) ? memberSalesLastMonth($member) : 0;

            $namea[$member] = $memberInfo['name'][$member] = printMemberString($member, 5);
            $salesa[$member] = $memberInfo['sales'][$member] = $sales;
            $memnum[$member] = $member;
        }
        array_multisort($salesa, SORT_DESC, $namea, SORT_DESC, $memnum, SORT_DESC);
        $count = 1;
        foreach ($memnum as $key => $value) {
            $note = "#" . $count . " Customer $monthyear";
            accountTransact($value, (20 - ($count * 5)), 0, $note);
            echo "$note for {$namea[$key]}<br>";

            $email = getMemberEmail($value);
            $body = "Congratulations!
                You were one of the top spending Frequent Gamers at Worlds Apart Games
                last month.

                As a token of our appreciation we have applied a credit of " 
                . (20 - ($count * 5)) .  " to
                your store account.
                This money can be spent same as cash on any games, 
                events, snacks, dice, etc.

                To track your standings in the Frequent Gamer Discount Program at any
                time, you can log in to Friend Computer and see your standings.
                (http://worldsapartgames.org/fc/fgreport.php)

                Have a great month!
                Kiernan Gulick-Sherrill
                Worlds Apart Games
                www.worldsapartgames.org

                Follow us on Facebook!
                www.facebook.com/worldsapartgames";

            if (mail($email, "Congratulations from Worlds Apart Games!", $body)) {
                echo "Message sent to " . printMemberString($value, 1) . "<br>";
            } else {
                echo "Member email should be $email";
                displayError("Error: Unable to send email to member, do they lack an email address?");
            }
            $count++;
            if ($count >= 4) {
                break;
            }
        }
        // Give credits to officers and give $20
        foreach ($officers as $who) {
            if (transferCredits(0, $who, 15, "$monthyear Officer", 1)) {
                echo "Credits Given to ";
                printMember($who, 1);
                echo "<br>\n";
            }
            if (accountTransact($who, 20, 0, "$monthyear Officer Account")) {
                echo "$20 account Given to ";
                printMember($who, 1);
                echo "<br>\n";
            }
        }
        // Give Michael monies
        accountTransact(1, 50, 0, "$monthyear Monthly Repay");
        echo "Michael paid<br>";
    } else {
        echo "This month's monthly updates have already been performed! '$firstFGNote' found.<br>";
    }
    include 'footer.php';
} else {
    echo "<form action='periodic.php' method='post'>
        <input type='checkbox' name='monthly' value='1'> Monthly Updates<br>
        <input type='submit' name='submit' value='submit'></form>";
    include 'footer.php';
}
?>