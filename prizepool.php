<?php
// prizepool.php
// For taking money and distributing prizes for Prize Pool events

include('funcs.inc');
include('header.php');

if($_SESSION['reg'] != 1)
{
   echo "You lack permission to do this.<p>";
   include('footer.php');
   die();
}

$cxn = open_stream();

// Resolving form
if($_POST['submit'] == 'submit')
{
   $event = $_POST['event'];
   $amount = $_POST['amount'];
   $player = $_POST['player'];
   $account = $_POST['account'];
   
   $avail = getAvailBalance($player);
   
   if($account == 1)
   {
      if($amount <= $avail)
      {
         accountTransact($player, -$amount, 0, "PrizePool - $event");
         echo money($amount) . " taken from Store Account for " . printMemberString($player, 1) . "<p>
              " . money(getAvailBalance($player)) . " remaining available in account<p>\n";
      }
      else
      {
         echo "<font color=RED>" . printMemberString($player, 1) . " has insufficient account for this transaction. Attempted to withdraw " . money($amount) . ", but only " . money($avail) . " available.</font><p>\n";
         $fail = TRUE;
      }
   }
      
   if($fail != TRUE)
   {
      $stmt = $cxn->prepare('INSERT INTO prizePool (amount, whenplay, event, player) VALUES (?, NOW(), ?, ?)')
            or displayErrorDie("Unable to insert cash.<p>Error: " . mysqli_error($cxn));
      $stmt->bind_param("dsi", $amount, $event, $player);
      if($stmt->execute())
         echo "\$$amount added to Prize Pool for $event<hr>";
      else
         echo "Error submitting Prize Pool<hr>";
      $stmt->close();
   }
}
else if($_POST['submit'] == 'prize')
{
   $prize = $_POST['prize'];
   $amount = -$prize;
   $winner = $_POST['winner'];
   $event = $_POST['event'] . ' - ' . $winner;
   $stmt = $cxn->prepare('INSERT INTO prizePool (amount, whenplay, event) VALUES (?, NOW(), ?)')
      or displayErrorDie("Unable to give prize.<p>Error: " . mysqli_error($cxn));
   $stmt->bind_param("ds", $amount, $event);
   if($stmt->execute())
   {
      if(accountTransact($winner, $prize, "$event PRIZE", 0))
      {
         echo "Prize given to ";
         printMember($winner, 1);
         echo ". Prize amount $prize.<hr>";
      }
      else
      {
         echo "Error inserting account<hr>";
      }
   }
   else
   {
      echo "Error assigning prize<hr>";
   }
}

// display people entered already
echo "People in event<p>";
$sql = "SELECT * FROM prizePool WHERE (amount > 0) ORDER BY ID DESC limit 20";
$result=query($cxn, $sql);
$first = TRUE;
while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   if($first)
   {
      $eventNow = $event;
      echo "Event Name: $event<p>";
      $first = FALSE;
   }
   if($event == $eventNow)
   {
      printMember($player, 1);
      echo " paid $amount<br>";
   }
}
echo "<hr>";


$sql = "SELECT amount FROM prizePool ORDER BY ID DESC LIMIT 1";
$result = query($cxn, $sql);
$row = mysqli_fetch_row($result);
$amountprev = $row[0];

if($amountprev > 0)
{
   $sql = "SELECT event FROM prizePool ORDER BY ID DESC LIMIT 1";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_row($result);
   $eventname = $row[0];
}
else
{
   unset($amountprev);
}

$sql = "SELECT SUM(amount) FROM prizePool";
$result = query($cxn, $sql);
$row = mysqli_fetch_row($result);
$amountsum = $row[0];


echo "<font size=+2>Add money to prize pool</font><p>
<form action='prizepool.php' method=POST>
Event: <input type='text' name='event' value='$eventname' size=25 maxlength=50><br>
Amount: <input type='text' name='amount' value='$amountprev' size=6 maxlength=8>
 Pay on Account? <input type='checkbox' name='account' value='1'><br>
Player: ";
selectMember('player', 0);
echo "<br><input name='submit' type='submit' value='submit'>
</form><hr>
<font size=+2>Give out prizes</font><p>
<form action='prizepool.php' method=POST>
Current Value in Prize Pool: \$$amountsum<p>
Event: <input type='text' name='event' default='$eventname' size=25 maxlength=50><br>
Amount: <input type='text' name='prize' size=6 maxlength=8><br>Prize Recipient: ";
selectMember('winner', 0);
echo "<br><input name='submit' type='submit' value='prize'></form>";

include('footer.php');