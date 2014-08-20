<?php
/**
 * Bounties.php is a page for posting and claiming Hedon Bounties
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Request_Form
 * @package   FriendComputer
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/bounties.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   displayError()
 * credits.inc:
 *   displayAllBounties()
 *   claimBounty()
 */
$title = "Unclaimed Bounties";
require_once 'funcs.inc' ;
require_once 'credits.inc';

/**
 * Possible Arguments:
 * POST:
 *   submit - Indicates a button was pushed, and which one.
 */
if (isset($_POST['submit'])) {
    extract($_POST);

    if ($_POST['submit'] == 'submit bounty') {
        $cxn = open_stream();

        $note = strip_tags($bountyNote);

        if (
            $stmt = $cxn->prepare(
                "INSERT INTO bounties
                (daytime, hedons, notes)
                VALUES
                (NOW(), ?, ?)"
            )
        ) {
            $stmt->bind_param("ds", $bountyAmount, $note);
            $stmt->execute();
        } else {
            displayError("Error Binding Query. Bounty not created. Contact your local High Programmer.");
        }
    }
    if ($_POST['submit'] == 'submit claim') {
        for ($i=0; $i<count($selectedBounties); $i++) {
            claimBounty($selectedBounties[$i]);
            sleep(1);
        }
    }
}
require_once 'header.php';

$cxn = open_stream();
$message .= '';

echo"<hr>";

if ($_SESSION['mem'] == 1) {
    echo "<b>Create Bounty</b><br>
        <form action='bounties.php' method='POST'>
        Amount offered: <input type='text' maxlength=4 size=4 name='bountyAmount'><br>
        Description: <input type='text' maxlength=50 size=12 name='bountyNote'>
        <input type='submit' name='submit' value='submit bounty'></form><hr>";
}

    echo "<form action='bounties.php' method='post'>";

    displayAllBounties();

    echo "<button name='submit' value='submit claim'>Claim</button><p></form>";

require_once 'footer.php';
?>
