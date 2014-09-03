<?php
/**
 * @file friendcomputer.inc
 * @brief friendcomputer.inc provides an API for displaying an information box.
 * 
 * This file includes:<br>
 * funcs.inc:<br>
 * &nbsp;&nbsp;determineCurrentShift()<br>
 * 
 * @link http://www.worldsapartgames.org/fc/index.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

require_once 'funcs.inc';

/**
 * fcMessage displays a message from Friend Computer.
 * @param string $message is the message to be printed.
 */
function fcMessage($message)
{
    $SID = $_SESSION['ID'];

    // get info to display current time
    date_default_timezone_set('America/New_York');
    $date = date("l, F jS, o g:i A");
    $shiftNow = determineCurrentShift();

    echo "<table border width=100%><tr><td><h2>Messages from Friend Computer</h2>";
    echo "Greetings Citizen #$SID<br>
        Current Worlds Apart Time: $date ";
    if ($shiftNow > 0) {
        echo " -- Shift: $shiftNow<p>";
    } else {
        echo "<br>Worlds Apart is currently officially closed";
    }
   
    if ($message != '') {
        echo "<p>" . $message;
    }
 
    echo "</td></tr></table>";
}
?>
