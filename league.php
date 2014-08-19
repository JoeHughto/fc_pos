<?php
// league.php

// League tracking application.
// This application lets you add people to a league, and submit a winner and a loser

// Leagues numbered up to 1000 are Magic Leagues
// Leagues numbered over 1000 are Board Game Leagues

// This uses the league table:
// ID
// leagueID - league number
// whenplayed - date that game is submitted
// submitter - memberID of member that submitted data
// player - player memberID
// points - points awarded in this game
// game - game played if applicable

// GET
// league - league #, if none provided will give list of leagues

include('funcs.inc');
include('header.php');

// functions

// getMagicDCL
// args: memberID - ID of member to get
// returns: DCL rating for type 1 or false if none found
function getMagicDCL($memberID, $type)
{
   $cxn = open_stream();
   $sql = "SELECT rating FROM leagueRatings WHERE memberID='$memberID' AND type='$type'";
   return(queryOnce($cxn, $sql));
}

// initMagicDCL
// args: memberID - ID of member to set up
// creates entry for memberID at 1600
function initMagicDCL($memberID, $type)
{
   $cxn = open_stream();
   $sql = "INSERT INTO leagueRatings (memberID, rating, type) VALUES ('$memberID', '1600', '$type')";
   query($cxn, $sql);
}

// lowestDCL
// args: leagueID
// returns: lowest DCL in league
function lowestDCL($leagueID)
{
   $cxn = open_stream();
   $sql = "SELECT MIN(rating)
             FROM leagueRatings r
       INNER JOIN league l
               ON l.player = r.memberID";
   $lowest = queryOnce($cxn, $sql);
   return ($lowest);
}

// setMagicDCL
// args: memberID - ID of member to set for
//       newDCL - DCL to set to
// sets new DCL for league type 1
function setMagicDCL($memberID, $newDCL, $type)
{
   $cxn = open_stream();
   $sql = "UPDATE leagueRatings SET rating='$newDCL' WHERE memberID='$memberID' AND type='$type'";
   return(query($cxn, $sql));
}

// config data
// 1 - Flashback style Magic League
// 2 - DCL style Magic League
// 3 - L5R Glory League
// 10 - Board Game League
$leagueTypes = array(1 => 1,
                    2 => 1,
                    3 => 1,
                    4 => 1,
                    5 => 2,
                    6 => 2,
                    'magic' => 2,
                    101 => 3,
                    1000 => 10,
                    1001 => 10,
                    1002 => 10,
                    'board' => 10);

$cxn = open_stream();

if($_GET['playerID'] > 0 && $_GET['league'] > 0)
{
   $playerID = $_GET['playerID'];
   $league = $_GET['league'];
   $sql = "SELECT * FROM league WHERE player='$playerID' AND leagueID = '$league' ORDER BY whenplayed";
   $result = query($cxn, $sql);
   
   echo "<H1>Results for " . printMemberString($playerID, 1) . "</h1>
         <table><tr><td>When</td><td>Opponent</td><td>Win?</td><td>Points</td></tr>";
   while ($row = mysqli_fetch_assoc($result))
   {
      echo "<tr><td>{$row['whenplayed']}</td><td>" . printMemberString($row['game'], 1) . "</td><td " . (($row['win'] == 1) ? "bgcolor=BLUE><font color=WHITE>Yes</a>" : "bgcolor=RED><font color=WHITE>No</a>") . "</td><td>{$row['points']}</td></tr>";
   }
   echo "</tr></table><hr>";
}

if($_POST['league'] > 0)
   $_GET['league'] = $_POST['league'];
   $league = $_GET['league'];

if(isset($_GET['NewPlayer'])) $newPlayer = TRUE;

if (array_key_exists($league, $leagueTypes)) $leagueType = $leagueTypes[$league];
else if ($league < 100) $leagueType = $leagueTypes['magic'];
else if ($league < 1000) $leagueType = $leagueTypes['L5R'];
else $leagueType = $leagueTypes['board'];

$DCLType = ($leagueType == 2) ? 1 : 2;


if($_POST['BOMB'] == 'bomb')
{
   echo "Setting up the Bomb!<p>";
   
   $sqlid = $_POST['sqlid'];
   $oldDCL = $_POST['oldDCL'];
   
   $sql = "DELETE FROM league WHERE ID='{$sqlid[1]}' OR ID='{$sqlid[2]}'";
   if(query($cxn, $sql))
   {
      echo "Match deleted<br>";
   }
   
   foreach($oldDCL as $key => $value)
   {
      $sql = "UPDATE leagueRatings SET rating='$value' WHERE memberID='$key' AND type=$DCLType";
      if(query($cxn, $sql))
         echo "DCL for " . printMemberString($key, 1) . " reverted to $value<br>\n";
   }
unset($_POST);
echo "<P>BOOM!";
include('footer.php');
die();
}

  
echo "League Type: $leagueType<p>";

echo "<form action='league.php' method='get'>
      <input type='hidden' name='league' value='$league'>";
if($leagueType != 3) echo "<button name='NewPlayer' value='1'>NEW PLAYER MODE</button></form><hr>";
else echo "</form>For L5R Leagues, new members are added by making them pay in Register.php<hr>";


if($leagueType == 1 && isset($_POST['submit']))
{
   $winner = $_POST['winner'];
   $loser = $_POST['loser'];
   

   // Winner Points
   $sql = "SELECT SUM(points) FROM league WHERE player='$winner' AND leagueID='$league'";
   $winnerPoints = queryOnce($cxn, $sql);
   
   // Loser Points
   $sql = "SELECT SUM(points) FROM league WHERE player='$loser' AND leagueID='$league'";
   $loserPoints = queryOnce($cxn, $sql);
   
   $diff = $winnerPoints - $loserPoints;
   if($diff > 0) // winner has more points
   {
      $pointsForWinner = 5;
      $pointsForLoser = round((.05 * $diff) + 1.4);
   }
   else // loser has more points or same
   {
      $diff = -$diff;
      $pointsForWinner = round((.2 * $diff) + 5.4);
      $pointsForLoser = 1;
   }
   
   if($winner > 0 && $loser > 0 && $league > 0)
   {
      $sql = "INSERT INTO league (whenplayed, leagueID, submitter, player, points, game, win)
                          VALUES (NOW(), '$league', '{$_SESSION['ID']}', '$winner', '$pointsForWinner', '$loser', '1')";
      if(query($cxn, $sql))
         echo printMemberString($winner, 1) . " entered as winner with $pointsForWinner Points<p>";
      else
         die("Error entering winner");

      $sql = "INSERT INTO league (whenplayed, leagueID, submitter, player, points, game, win)
                          VALUES (NOW(), '$league', '{$_SESSION['ID']}', '$loser', '$pointsForLoser', '$winner', '0')";
      if(query($cxn, $sql))
         echo printMemberString($loser, 1) . "entered as loser with $pointsForLoser Points<p>";
      else
         die("Error entering loser");
   }
   else
   {
      echo "Error with Winner, Loser, or League ID<p>";
   }
}
else if(($leagueType == 2 || $leagueType == 3) && isset($_POST['submit']) && $_POST['winner'] > 0 && $_POST['loser'] > 0)
{
   $winner = $_POST['winner'];
   $loser = $_POST['loser'];
   
   $winnerDCL = getMagicDCL($winner, $DCLType);
   $loserDCL = getMagicDCL($loser, $DCLType);
   
   if(!$winnerDCL)
   {
      initMagicDCL($winner, $DCLType);
      $winnerDCL = 1600;
   }
   if(!$loserDCL)
   {
      initMagicDCL($loser, $DCLType);
      $loserDCL = 1600;
   }
   
   // Award Points
   $newWinnerPoints = round((($loserDCL - 1100) / 100), 2);
   $newWinnerPoints = ($newWinnerPoints > 2) ? $newWinnerPoints : 2;
   //$newLoserPoints = round((($winnerDCL - 1100) / 400), 2);
   $newLoserPoints = 1;
   
   // Put into Database
   if($winner > 0 && $loser > 0 && $league > 0)
   {
      $sql = "INSERT INTO league (whenplayed, leagueID, submitter, player, points, game, win)
                          VALUES (NOW(), '$league', '{$_SESSION['ID']}', '$winner', '$newWinnerPoints', '$loser', '1')";
      if(query($cxn, $sql))
      {
         echo printMemberString($winner, 1) . " entered as winner with $newWinnerPoints Points<p>";
         $winnerSQLID = $cxn->insert_id;
      }
      else
         die("Error entering winner");

      $sql = "INSERT INTO league (whenplayed, leagueID, submitter, player, points, game, win)
                          VALUES (NOW(), '$league', '{$_SESSION['ID']}', '$loser', '$newLoserPoints', '$winner', '0')";
      if(query($cxn, $sql))
      {
         echo printMemberString($loser, 1) . " entered as loser with $newLoserPoints Points<p>";
         $loserSQLID = $cxn->insert_id;
      }
      else
         die("Error entering loser");
   }
   else
   {
      echo "Error with Winner, Loser, or League ID<p>";
   }
   
   // Update DCL
   $newWinnerRating = $winnerDCL + 40 * (1 - (1 / (1 + pow(10, (($loserDCL - $winnerDCL) / 400)))));
   $newLoserRating = $loserDCL + 40 * (0 - (1 / (1 + pow(10, (($winnerDCL - $loserDCL) / 400)))));
   
   if(setMagicDCL($winner, $newWinnerRating, $DCLType))
      echo printMemberString($winner, 1) . " new DCL rating: $newWinnerRating<br>";
   else
      displayError("Error submitting new winner DCL");

   if(setMagicDCL($loser, $newLoserRating, $DCLType))
      echo printMemberString($loser, 1) . " new DCL rating: $newLoserRating<br>";
   else
      displayError("Error submitting new loser DCL");
      
   // set up the bomb
   echo "<form action='league.php' method='post'>
         <input name='sqlid[1]' value='$winnerSQLID' type='hidden'>
         <input name='sqlid[2]' value='$loserSQLID' type='hidden'>
         <input name='oldDCL[$winner]' value='$winnerDCL' type='hidden'>
         <input name='oldDCL[$loser]' value='$loserDCL' type='hidden'>
         <input name='league' value='$league' type='hidden'>
         <input name='BOMB' value='bomb' type='hidden'>
         Did you screw up the most recent entry? Click this button to blow it up.<p>
         <input type=submit value='Set Up the Bomb' name='submit'></form><hr>\n";
}
else if($leagueType == 10 && isset($_POST['submit']))
{
   $players = $_POST['players'];
   $points = $_POST['points'];
   
   foreach($players as $key => $player)
   {
      $point = $points[$key];
      
      if($player > 0 && $point > 0)
      {
         $stmt = $cxn->prepare("INSERT INTO league(whenplayed, leagueID, submitter, player, points, game)
                                            VALUE (NOW(), ?, ?, ?, ?, ?)");
         $stmt->bind_param("dddds", $league, $_SESSION['ID'], $player, $point, $_POST['game']);
         if($stmt->execute())
         {
            echo printMemberString($player, 1) . " submitted for $point points<p>";
         }
         else
         {
            echo "Error submitting info for " . printMemberString($player, 1) . "<br>
                  Error: {$stmt->error}<p>";
         }
      }
   }
}
       
if($league < 1000 && $league > 0) // Magic or L5R
{
   echo "<b>Current Results</b><br>\n";
   
   $sql = "SELECT * FROM league where leagueID='$league' ORDER BY player";
   $result = query($cxn, $sql);
   while($row = mysqli_fetch_assoc($result))
   {
      $thereAreResults = TRUE;
      extract($row);
      $scores[$player] += $points;
      if($win == 1) $wins[$player]++;
      if($win == 0 && ($points > 0)) $loses[$player]++;
   }
   if($thereAreResults)
   {
      $place = 1;
      
      arsort($scores);
      echo "<table><tr><td>Place</td><td>Player</td><td>Points</td><td>Wins</td><td>Losses</td><td>Points to<br>Promos</td><td>DCL</td></tr>";
      foreach($scores as $key => $value)
      {
         $DCL = getMagicDCL($key, $DCLType);
         $pointstopromos = ($wins[$key] * 2) + $loses[$key];
         echo "<tr><td>" . $place++ . "</td><td><a href='league.php?playerID=$key&league=$league'>" . printMemberString($key, 1) . "</a></td><td>$value</td><td>{$wins[$key]}</td><td>{$loses[$key]}</td><td>$pointstopromos</td><td>$DCL</td></tr>\n";
      }
      echo "</table><hr>";
   }
   
   if($newPlayer == TRUE)
   {
      echo "<hr><b>Notice: You are entering a game in NEW PLAYER mode. This means that you may enter any player in the FC database.    Be very careful to enter the right name.</b><p>
            <form action='league.php' method='post'>
            Winner: ";
      selectMember('winner', 0, $league);
      echo "<br>Loser: ";
      selectMember('loser', 0, $league);
      echo "<br><input type='hidden' name='league' value='{$_GET['league']}'>
            <input name='submit' value='magic' type='submit'></form><br>";
   }
   else
   {   
      echo "<hr><form action='league.php' method='post'>
            Winner: ";
      selectLeagueMember('winner', 0, $league);
      echo "<br>Loser: ";
      selectLeagueMember('loser', 0, $league);
      echo "<br><input type='hidden' name='league' value='{$_GET['league']}'>
            <input name='submit' value='magic' type='submit'></form><br>";
   }   
}
else if($league > 1000) // board game league
{
   echo "Current Results<p>\n";
   
   $sql = "SELECT * FROM league where leagueID='$league' ORDER BY whenplayed, game, points DESC, player";
   $result = query($cxn, $sql);
   while($row = mysqli_fetch_assoc($result))
   {
      $thereAreResults = TRUE;
      extract($row);
      $scores[$player] += $points;
      
      // create string which will show results of individual games
      if($lastgame != $game)
      {
         $lastgame = $game;
         $gamestring .= "<hr><b>$game</b><br>
                         Submitted by: " . printMemberString($submitter, 1) . "<p>\n";
      }
      $gamestring .= "$points points - " . printMemberString($player, 1) . "<br>\n";
   }
   if($thereAreResults)
   {
      $place = 1;
      
      arsort($scores);
      echo "<table><tr><td>Place</td><td>Player</td><td>Points</td></tr>";
      foreach($scores as $key => $value)
      {
         echo "<tr><td>" . $place++ . "</td><td>" . printMemberString($key, 1) . "</td><td>$value</td></tr>\n";
      }
      echo "</table><hr>";
   }
   
   echo "<form action='league.php' method='post'>
         Game: <input name='game' type='text' size=40 maxlength=40><p>\n";
   for($i = 1; $i <= 6; $i++)
   {
      echo "Player: ";
      selectMember("players[$i]", 0);
      echo " Points: <input name='points[$i]' type='text' size=3 maxlength=3><p>\n";
   }
   echo "<input type='hidden' name='league' value='$league'>
         <input type='submit' name='submit' value='board'></form><br>";
         
   echo "<h1>Individual Game Results</h1>
         $gamestring";
}
else
{
   echo "Error, league not selected<p>";
}

include('footer.php');
?>