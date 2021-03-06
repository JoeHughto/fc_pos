<?php
/**
 * @file hedonrequests.php
 * @brief hedonrequests.php is a page for admins to approve/deny any pending
 *   Hedon Requests.
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * 
 * credits.inc:
 * - approveHedonRequest()
 * - denyHedonRequest()
 * - displayAllHedonRequests()
 * 
 * Possible Arguments:
 * SESSION:
 * - mem - Used to determine whether the current user has membership
 *   coordinator privledges.
 * 
 * POST:
 * - submit - When this variable is filled, we need to do work. The three
 *   values this variable can have are 'Add Packs', 'Remove Packs',
 *   and 'Convert Packs'.
 * - selectedReqs[ ] - This is an array of all checkboxes which were checked
 *   when the submit button was pressed.
 * 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @link      http://www.worldsapartgames.org/fc/hedonrequests.php
 * @since     Project has existed since time immemorial.
 */

$title = "Pending Hedon Requests";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'credits.inc';

if (isset($_POST['submit'])) {
    extract($_POST);

    if ($_POST['submit'] == 'submit approve') {
        for ($i = 0; $i < count($selectedReqs); $i++) {
            approveHedonRequest($selectedReqs[$i]);
            sleep(2);
        }
    }
    if ($_POST['submit'] == 'submit deny') {
        for ($i = 0; $i < count($selectedReqs); $i++) {
             denyHedonRequest($selectedReqs[$i]);
        }
    }
}
require 'header.php';

$cxn = open_stream();
$message .= '';

echo "<hr>";

if ($_SESSION['mem'] != 1) {
    die("You must be an officer to use this application");
}

echo "<form action='hedonrequests.php' method='post'>";

displayAllHedonRequests();

echo "<button name='submit' value='submit approve'>Approve</button>
    <button name='submit' value='submit deny'>Deny</button><p></form>";

require 'footer.php';
?>
