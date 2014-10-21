<?php
/**
 * @file config.inc
 * @brief config.inc provides an interface between FriendComputer and our MySql
 *   database. It contains secret information that should not be shared.
 *
 * This file includes:
 * @todo This file uses functions from funcs.inc, but doesn't include it.
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

/**
 * open_stream() connects the webapp with our database, so we can
 *   access or modify records from it.
 * @retval boolean|object
 */
function open_stream()
{
    $dbname="db_name";
    $host="host_name";
    $user="db_user";
    $pwd="db_password";

    $access = new mysqli($host, $user, $pwd, $dbname);

    if ($err = mysqli_connect_errno()) {
        die ("Couldn't connect to server." . $err);
    } else {
        return $access;
    }
    return false;
}

$cxn = open_stream();

// Magic League Info
$mleagueID = 44;
$sql = "SELECT * FROM league where leagueID='$mleagueID' ORDER BY player";
$result = query($cxn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $thereAreResults = true;
    $points = $row['points'];
    $player = $row['player'];
    $win = $row['win'];
    $scores[$player] += $points;
    if ($win == 1) {
        $wins[$player]++;
    } elseif ($points > 0) {
        $loses[$player]++;
    }
}
if ($thereAreResults) {
    arsort($scores);
    $mleague = "<table><tr><td>Player</td><td>Points</td><td>Wins</td><td>Losses</td></tr>";
    $count = 0;
    foreach ($scores as $key => $value) {
        $count++;
        $mleague .= "<tr><td>" . printMemberString($key, 1) . "</td><td>$value</td><td>{$wins[$key]}</td><td>{$loses[$key]}</td></tr>\n";
        if ($count > 2) {
            break;
        }
    }
    $mleague .= "</table>";
}

unset($scores, $wins, $win, $loses, $count, $key, $value, $player, $result, $cxn, $sql, $thereAreResults);

$topinfo = "</HEAD>
<BODY LINK='333366' ALINK='333366' VLINK='333366'>
<TABLE><TR><TD><IMG SRC='../data/shield.png' ALT='Worlds Apart Shield' WIDTH=150></TD>
<TD>
<TABLE><TR><TD valign=top>
<a href='league.php?league=$mleagueID'>Magic League<BR>Quick Link</a></B>
<TD>
New League Runs 7-20-14 -> 8-16-14<br>
</TD></TR></TABLE><BR>" .
(($securePage == true) ?
"This is a high security clearance page.<br>
For your protection, Friend Computer will log out after 17 minutes and 23 seconds of inactivity." :
"") . 
"</TD></TR></TABLE>
<TABLE><TR><TD WIDTH=150 ROWSPAN=2 VALIGN=TOP>";

?>
