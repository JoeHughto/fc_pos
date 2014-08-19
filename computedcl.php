<?php
// computedcl.php
// computes the DCL ratings of all players based  on previous league results
// Resets all DCLs which are reviewed to 1600

include('funcs.inc');

function getDCL($member)
{
   $cxn = open_stream();
   $sql = "SELECT DCL FROm members WHERE ID=$member";
   return (queryOnce($cxn, $sql));
}

$cxn = open_stream();

$sql = "SELECT * FROM league WHERE leagueID = 4 AND win=1";
$result = query($cxn, $sql);

$DCL = array();
$p = array(1 => 0.0);

while($row = mysqli_fetch_assoc($result))
{
print_r($row);
   extract($row);
   $winnerID = $player;
   $loserID = $game;
echo "WID: $winnerID, LID: $loserID, WDCL: {$DCL[$winnerID]},  LDCL: {$DCL[$loserID]}<p>";   
   if(!array_key_exists($winnerID, $DCL)) $DCL[$winnerID] = 1600;
   if(!array_key_exists($loserID, $DCL)) $DCL[$loserID] = 1600;
   
echo "10to: " . (pow(10,(($DCL[$loserID] - $DCL[$winnerID]) / 400)) )
    ." diff: " . ($DCL[$loserID] - $DCL[$winnerID])
    ." diff/400:" . (($DCL[$loserID] - $DCL[$winnerID]) / 400) . "<br>";
   
   $newWinnerScore = $DCL[$winnerID] + 40 * (1 - (1 / (1 + pow(10, (($DCL[$loserID] - $DCL[$winnerID]) / 400)))));
   $newLoserScore = $DCL[$loserID] + 40 * (0 - (1 / (1 + pow(10, (($DCL[$winnerID] - $DCL[$loserID]) / 400)))));

//   Kiernan System
//   $newLoserPoints = 100 / ( 1+ pow(10, ($DCL[$loserID] - $DCL[$winnerID]) / 400));
//   $newWinnerPoints = 100 / ( 1+ pow(10, ($DCL[$winnerID] - $DCL[$loserID]) / 400));
//   $newLoserPoints /= 4;

//   Dan System
   $newWinnerPoints = ($DCL[$loserID] - 1000) / 10;
   $newLoserPoints = ($DCL[$winnerID] - 1000) / 40;

//   Dannan System
//   $newLoserPoints = 100 / ( 1+ pow(10, ($DCL[$loserID] - $DCL[$winnerID]) / 400));
//   $newWinnerPoints = 100 / ( 1+ pow(10, ($DCL[$winnerID] - $DCL[$loserID]) / 400));
//   $newLoserPoints /= 4;
//   if($newWinnerPoints < 50) $newWinnerPoints = 50;


   $p[$winnerID] = $p[$winnerID] + $newWinnerPoints;
   $p[$loserID] = $p[$loserID] + $newLoserPoints;
   
   echo "Game# $ID:<br>Winner: " . printMemberString($winnerID, 1) . ", Old DCL: {$DCL[$winnerID]}; New DCL: $newWinnerScore; Change of " . ($newWinnerScore - $DCL[$winnerID]) . "/ Score: $newWinnerPoints Total: {$p[$winnerID]}" .
   "<br>Loser: " . printMemberString($loserID, 1) . ", Old DCL: {$DCL[$loserID]}; New DCL: $newLoserScore; Change of " . 
   ($newLoserScore - $DCL[$loserID]) . "/ Score: $newLoserPoints Total: {$p[$loserID]}<hr>";
   
   echo 
   
   $DCL[$winnerID] = $newWinnerScore;
   $DCL[$loserID] = $newLoserScore;
}

// display DCLs
echo "<h2>Computed Scores</h2>";
asort($DCL);
foreach($DCL as $key => $value)
{
   echo printMemberString($key,1) . " $value<br>";
}

echo "<h2>Theoretical League</h2><table><tr><td>#</td><td>Name</td><td>Points</td></tr>";
$count = 1;
arsort($p);
foreach($p as $key => $value)
{
   echo "<tr><td>" . $count++ . "</td><td>" . printMemberString($key,1) . "</td><td>$value</td></tr>";
}
echo "</table>";
?>