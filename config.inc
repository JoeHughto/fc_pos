<?php
// config.inc
// contains specific info for either ops or fc files

// FC VERSION

// takes a database name and a SQL query, returns raw SQL object
function open_stream()
{
  $dbname="worldsap_fc";
  $host="localhost";
  $user="worldsap_fc";
  $pwd="37FUHupr";

  $access = new mysqli($host, $user, $pwd, $dbname);

  if($err = mysqli_connect_errno())
     die ("Couldn't connect to server." . $err);
  else
     return $access;
}

$cxn = open_stream();

/*
// Board Game Info
$bgleagueID = 1002;
$sql = "SELECT * FROM league WHERE leagueID='$bgleagueID' AND player != 3 ORDER BY whenplayed, game, points DESC, player";
$result = query($cxn, $sql);
while($row = mysqli_fetch_assoc($result))
{
   $thereAreResults = TRUE;
   $points = $row['points'];
   $player = $row['player'];
   $scores[$player] += $points;
}
if($thereAreResults)
{
   arsort($scores);
   $bgleague = "<table><tr><td>Player</td><td>Points</td></tr>";
   $count = 0;
   foreach($scores as $key => $value)
   {
      $count++;
      $bgleague .= "<tr><td>" . printMemberString($key, 1) . "</td><td>$value</td></tr>\n";
      if($count > 2) break;
   }
   $bgleague .= "</table>";
}

unset($scores, $thereAreResults);
*/

// Magic League Info
$mleagueID = 44;
$sql = "SELECT * FROM league where leagueID='$mleagueID' ORDER BY player";
$result = query($cxn, $sql);
while($row = mysqli_fetch_assoc($result))
{
   $thereAreResults = TRUE;
   $points = $row['points'];
   $player = $row['player'];
   $win = $row['win'];
   $scores[$player] += $points;
   if($win == 1) $wins[$player]++;
   else if($points > 0) $loses[$player]++;
}
if($thereAreResults)
{
   arsort($scores);
   $mleague = "<table><tr><td>Player</td><td>Points</td><td>Wins</td><td>Losses</td></tr>";
   $count = 0;
   foreach($scores as $key => $value)
   {
      $count++;
      $mleague .= "<tr><td>" . printMemberString($key, 1) . "</td><td>$value</td><td>{$wins[$key]}</td><td>{$loses[$key]}</td></tr>\n";
      if($count > 2) break;
   }
   $mleague .= "</table>";
}

unset($scores, $wins, $win, $loses, $count, $key, $value, $player, $result, $cxn, $sql, $thereAreResults);


// L5R League Info
/*
$lleagueID = 101;
$sql = "SELECT * FROM league where leagueID='$lleagueID' ORDER BY player";
$result = query($cxn, $sql);
while($row = mysqli_fetch_assoc($result))
{
   $thereAreResults = TRUE;
   $points = $row['points'];
   $player = $row['player'];
   $win = $row['win'];
   $scores[$player] += $points;
   if($win == 1) $wins[$player]++;
   if($win == 0 && $game > 0) $loses[$player]++;}

if($thereAreResults)
{
   arsort($scores);
   $lleague = "<table><tr><td>Player</td><td>Points</td><td>Wins</td><td>Losses</td></tr>";
   $count = 0;
   foreach($scores as $key => $value)
   {
      $count++;
      $lleague .= "<tr><td>" . printMemberString($key, 1) . "</td><td>$value</td><td>{$wins[$key]}</td><td>{$loses[$key]}</td></tr>\n";
      if($count > 2) break;
   }
   $lleague .= "</table>";
}

unset($scores, $wins, $win, $loses, $count, $key, $value, $player, $result, $cxn, $sql, $thereAreResults);
*/

$topinfo = "</HEAD>
<BODY LINK='333366' ALINK='333366' VLINK='333366'>
<TABLE><TR><TD><IMG SRC='../data/shield.png' ALT='Worlds Apart Shield' WIDTH=150></TD>
<TD>
<TABLE><TR><TD valign=top>
<a href='league.php?league=$mleagueID'>Magic League<BR>Quick Link</a></B>
<TD>
New League Runs 7-20-14 -> 8-16-14<br>
</TD></TR></TABLE><BR>" .
(($securePage == TRUE) ?
"This is a high security clearance page.<br>
For your protection, Friend Computer will log out after 17 minutes and 23 seconds of inactivity." :
"") . 
"</TD></TR></TABLE>
<TABLE><TR><TD WIDTH=150 ROWSPAN=2 VALIGN=TOP>";

?>
