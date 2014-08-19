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

$cxn = open_stream();
if($_POST['league'] > 0)
   $_GET['league'] = $_POST['league'];
   
if($_POST['submit'] == 'magic')
{
   $winner = $_POST['winner'];
   $loser = $_POST['loser'];
   $league = $_POST['league'];
   if($winner > 0 && $loser > 0 && $league > 0)
   {
      $sql = "INSERT INTO league (whenplayed, leagueID, submitter, player, points, game)
                          VALUES (CURDATE(), '$league', '{$_SESSION['ID']}', '$winner', '2', '$loser')";
      if(query($cxn, $sql))
         echo printMemberString($winner, 1) . " entered as winner<p>";
      else
         die("Error entering winner");

      $sql = "INSERT INTO league (whenplayed, leagueID, submitter, player, points, game)
                          VALUES (CURDATE(), '$league', '{$_SESSION['ID']}', '$loser', '1', '$winner')";
      if(query($cxn, $sql))
         echo printMemberString($loser, 1) . "entered as loser<p>";
      else
         die("Error entering loser");
   }
   else
   {
      echo "Error with Winner, Loser, or League ID<p>";
   }
}
else if($_POST['submit'] == 'board')
{
   $players = $_POST['players'];
   $points = $_POST['points'];
   $league = $_POST['league'];
   
   foreach($players as $key => $player)
   {
      $point = $points[$key];
      
      if($player > 0 && $point > 0)
      {
         $stmt = $cxn->prepare("INSERT INTO league(whenplayed, leagueID, submitter, player, points, game)
                                            VALUE (CURDATE(), ?, ?, ?, ?, ?)");
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
       
if($_GET['league'] < 1000 && $_GET['league'] > 0) // Magic
{
   $league = $_GET['league'];
   echo "Current Results<p>\n";
   
   $sql = "SELECT * FROM league where leagueID='$league' ORDER BY player";
   $result = query($cxn, $sql);
   while($row = mysqli_fetch_assoc($result))
   {
      $thereAreResults = TRUE;
      extract($row);
      $scores[$player] += $points;
      if($points == 2) $wins[$player]++;
      if($points == 1) $loses[$player]++;
   }
   if($thereAreResults)
   {
      arsort($scores);
      echo "<table><tr><td>Player</td><td>Points</td><td>Wins</td><td>Losses</td></tr>";
      foreach($scores as $key => $value)
      {
         echo "<tr><td>" . printMemberString($key, 1) . "</td><td>$value</td><td>{$wins[$key]}</td><td>{$loses[$key]}</td></tr>\n";
      }
      echo "</table><hr>";
   }
   
   echo "<hr><form action='league.php' method='post'>
         Winner: ";
   selectMember('winner', 0);
   echo "<br>Loser: ";
   selectMember('loser', 0);
   echo "<br><input type='hidden' name='league' value='{$_GET['league']}'>
         <input name='submit' value='magic' type='submit'></form><br>";
}
else if($_GET['league'] > 1000) // board game league
{
   $league = $_GET['league'];
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
      arsort($scores);
      echo "<table><tr><td>Player</td><td>Points</td></tr>";
      foreach($scores as $key => $value)
      {
         echo "<tr><td>" . printMemberString($key, 1) . "</td><td>$value</td></tr>\n";
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
   echo "<input type='hidden' name='league' value='{$_GET['league']}'>
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