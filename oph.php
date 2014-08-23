<?php
/**
 * OPH.php allows for the manipulation of hedons from arbitrary accounts.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Inventory_Mutator
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Crideke Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/oph.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   selectMember()
 *   printMemberString()
 * credits.inc:
 *   getCreditTotal()
 *   transferCredits()
 */
$title = "Other People's Hedons";
require_once 'funcs.inc';
require_once 'credits.inc';
require_once 'header.php';

/**
 * Possible Arguments:
 * SESSION:
 *   adm - Used to determine whether the active user has admin
 *     privs.
 *   ID - The ID of the active user, required as this app incurs an
 *     automatic email to the GM for review.
 * POST:
 *   to - The member ID of the member receiving the appropriated hedons.
 *   from - The member ID of the member whose hedons are being appropriated.
 *   qty - The number of hedons being appropriated.
 *   reason - An explanation for why the transfer is happening. This is
 *     for review purposes.
 */

if ($_SESSION['adm'] != 1) {
    echo "<h1>Restricted Page.</h1>";
    include 'footer.php';
    die();
}

echo "<h1>Transfer Other People's Hedons</h1>
This allows you to transfer a user's Hedons to another user. When you use 
this, it will send an email to the General Manager.
<p>
<form action='oph.php' method='post'>
Take Hedons from: ";
selectMember('from', 0);
echo "<br>And give them to: ";
selectMember('to', 0);
echo "<br>Hedons: <input name='qty' size=3 maxlength=3><br>
    Reason: <input name='reason' size=20 maxlength=40><br>
    <input name='submit' type='submit' value='submit'><hr>\n";
      
if ($_POST['to'] > 0) {
    $to = intval($_POST['to']);
    $from = intval($_POST['from']);
    $qty = intval($_POST['qty']);
    $reason = $_POST['reason'];

    if ($to == $_SESSION['ID']) {
        echo "<h1>You cannot transfer to yourself!!!</h1>";
        include 'footer.php';
        die();
    }

    if (getCreditTotal($from) >= $qty) {
        if (transferCredits($from, $to, $qty, "txed by member {$_SESSION['ID']} - $reason", 4)) {
            echo "$qty Hedons transferred from " . printMemberString($from, 1) 
                . " to " . printMemberString($to, 1) 
                . " for reason: $reason<p>";
            mail(
                "gm@pvgaming.org", "Hedons Transferred", "$qty Hedons "
                . "transferred from " . printMemberString($from, 1) . " to " 
                . printMemberString($to, 1) . " for reason: $reason"
            );
        } else {
            echo "Error transferring Credits<p>";
        }
    } else {
        echo printMemberString($from, 1) . " does not have $qty Hedons. "
            . "Current balance: " . getCreditTotal($from) . "<p>";
    }
}
require 'footer.php';
?>