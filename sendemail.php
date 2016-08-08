<?php
/**
 * @file sendemail.php
 * @brief sendemail.php is used to send emails to a select portion, or all of our
 *   members in bulk.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - checkAlphaNumSpace()
 * - printMemberString()
 * - money()
 * 
 * member.inc:
 * - memberSalesThisMonth()
 * - memberSalesLastMonth()
 * - FG_discount()
 * 
 * credits.inc:
 * - displayMembershipStatusString()
 * 
 * Possible Arguments:
 * SESSION:
 * - mem - Used to confirm the active user has membership privs. Either these or
 *   admin privs are required.
 * - adm - Used to confirm the active user has admin privs. Either these or
 *   membership privs are required.
 * - ID - Used to identify the active user, to sign the email with the user's name..
 * 
 * POST:
 * - to - This is the variable result of radio buttons used to select which
 *   group of members we're emailing.
 * - all - This checkbox is used to provide confirmation when attempting to
 *   email the entire list. If not checked, webapp will refuse to bulk mail everyone.
 * - subject - The subject of the email being sent out.
 * - body - The body of the email, as you want it to appear.
 * - submit - Submit button, will have a value if the form was submitted.
 * 
 * @link http://www.worldsapartgames.org/fc/inventoryreport.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @author    Kiernan Gulick-Sherrill 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = 'Send Email';
$version = '1.8d';
require_once 'funcs.inc';
require_once 'member.inc';
require_once 'credits.inc';
require_once 'header.php';

$cxn = open_stream();

if ($_SESSION['mem'] != 1 && $_SESSION['adm'] != 1) {
    echo "<font size+2>You do not have permission to use this application.</font>";
    include 'footer.php';
    exit();
}
if (isset($_POST['body'])) {
    extract($_POST);
    if (!checkAlphaNumSpace($subject)) {
        echo "<font size+2>Subject must be alphanumeric!</font>";
        include 'footer.php';
        exit();
    }
    switch ($to) {
    case 'first' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'300' AND ID>'0'";
        break;
    case 'second' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'600' AND ID>'299'";
        break;
    case 'third' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'900' AND ID>'599'";
        break;
    case 'fourth' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'1200' AND ID>'899'";
        break;
    case 'fifth' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'1500' AND ID>'1199'";
        break;
    case 'sixth' : 
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'1800' AND ID>'1499'";
        break;
    case 'seventh' : 
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'2100' AND ID>'1799'";
        break;
    case 'eighth' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'2400' AND ID>'2099'";
        break;
    case 'ninth' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'2700' AND ID>'2399'";
        break;
    case 'tenth' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'3000' AND ID>'2699'";
        break;
    case 'all' : 
        $sql = "SELECT ID, email, fname, lname FROM members WHERE optout IS NULL";
        break;
    case 'reg' :
        $sql = "SELECT ID, email, fname, lname FROM members WHERE registerUse='1'";
        break;
    case 'inv' : 
        $sql = "SELECT ID, email, fname, lname FROM members WHERE inventoryUse='1'";
        break;
    }

    $result = query($cxn, $sql);
    $count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $count++;
        extract($row);
        $array[$count]['member'] = $ID;
        $array[$count]['e'] = $email;
        $array[$count]['f'] = $fname;
        $array[$count]['l'] = $lname;
    }

    $body = str_replace('\\', '', $body);
    $body = "
        $body

        Sincerely,
        General Manager
        " . printMemberString($_SESSION['ID'], 5);

    $count = 0;
    foreach ($array as $info) {
        extract($info);
        $sales = memberSalesThisMonth($member);
        $lastmonth = memberSalesLastMonth($member);
        $disc = FG_discount($lastmonth);
        $newdisc = FG_discount($sales);
        $tonext = $FGDISCOUNT[$newdisc + 1] - $thissale - $sales;

        $fgblock = displayMembershipStatusString($member) . "\nYour frequent gamer discount for this month is $disc%, so you will get this discount off of all of your purchases this month.\n";
        if ($sales > 0) {
            $fgblock .= "This month, you have spent " . money($sales) . " at Worlds Apart which gives you a discount for next month of $newdisc%, but you only need to spend " . money($tonext) . " to get to the next discount level for next month.\n";
        }
        if (strlen($f) > 0 && strlen($l) > 0) {
            $message = "Dear $f $l,\n" . $fgblock . $body;
        } elseif (strlen($f) > 0) {
            $message = "Dear $f,\n" . $fgblock . "\n\n" . $body;
        } else {
            $message = "Dear Community Member,\n" . $fgblock . "\n\n" . $body;
        }

        $message .= "\n\nThis message has been sent to you because you are in the Worlds Apart Database. If you have recieved this message in error, please email gm@pvgaming.org to be removed from the list.";
        if ($to = 'all') {
            $message .= "\n\nThe government says we have to say this part:
                This is a commercial message from Worlds Apart which you could call an advertisement.
                Worlds Apart Games
                48 North Pleasant Street B2
                Amherst, MA  01002";
        }
        $header = "from: WorldsApart@pvgaming.org
            Reply-To: newsletter@pvgaming.org
            Precedence: bulk
            X-Mailer: PHP/" . phpversion();

        echo "Sending to $f $l at $e - Result:";

        if (mail($e, $subject, $message, $header)) {
            $count++;
            echo "Success - $count<br>";
        } else {
            echo "Failure<br>";
        }
    }
} else {
    echo "<form action='sendemail.php' method='post'>
        Send to:<br>
        <input type='radio' name='to' value='reg'> Register Folks<br>
        <input type='radio' name='to' value='inv'> Inventory Folks<p>
        <input type='radio' name='to' value='all'> Full Mailing List<p>
        <input type='radio' name='to' value='first'> First <p>
        <input type='radio' name='to' value='second'> Second <p>
        <input type='radio' name='to' value='third'> Third <p>
        <input type='radio' name='to' value='fourth'> Fourth <p>
        <input type='radio' name='to' value='fifth'> Fifth<p>
        <input type='radio' name='to' value='sixth'> Sixth <p>
        <input type='radio' name='to' value='seventh'> Seventh <p>
        <input type='radio' name='to' value='eighth'> Eighth <p>
        <input type='radio' name='to' value='ninth'> Ninth <p>
        <input type='radio' name='to' value='ninth'> Tenth <p>
        <input type='checkbox' name='all' value='1'> Yes, I really want to email the whole list<p>
        Subject: <input type='text' name='subject' size=60 maxlength=60><p>
        <textarea cols=60 rows=30 name='body'></textarea><br>
        <input type='submit' name='submit' value='submit'><p>";
}
require 'footer.php';
?>