<?php
/**
 * @file bounties.php
 * @brief bounties.php is a page for posting and claiming Hedon Bounties
 *
 * This file includes:<br>
 * funcs.inc:<br>
 * &nbsp;&nbsp;Used for the config.inc include<br>
 * &nbsp;&nbsp;displayError()<br>
 * credits.inc:<br>
 * &nbsp;&nbsp;displayAllBounties()<br>
 * &nbsp;&nbsp;claimBounty()<br>
 * <br>
 * Possible Arguments:<br>
 * POST:<br>
 * &nbsp;&nbsp;submit - Indicates a button was pushed, and which one.<br>
 * 
 * @link http://www.worldsapartgames.org/fc/bounties.php @endlink
 *
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Unclaimed Bounties";
$version = "1.8d";
require_once 'funcs.inc' ;
require_once 'credits.inc';

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
