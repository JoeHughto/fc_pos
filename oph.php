<?php
// oph.php
// Other People's Hedons

// This allows users with admin authority to transfer another user's Hedons to another user

include('funcs.inc');
include('header.php');
include('credits.inc');

if($_SESSION['adm'] != 1)
{
   echo "<h1>You are not admin. OPH NOT YOURS!</h1>";
   include('footer.php');
   die();
}

echo "<h1>Transfer Other People's Hedons</h1>
This allows you to transfer a user's Hedons to another user. When you use this, it will send an email to Michael telling him about it, so you should have a good reason for it.
<p>
<form action='oph.php' method='post'>
Take Hedons from: ";
selectMember('from', 0);
echo "<br>And give them to: ";
selectMember('to', 0);
echo "<br>Hedons: <input name='qty' size=3 maxlength=3><br>
      Reason: <input name='reason' size=20 maxlength=40><br>
      <input name='submit' type='submit' value='submit'><hr>\n";
      
if($_POST['to'] > 0)
{
   $to = intval($_POST['to']);
   $from = intval($_POST['from']);
   $qty = intval($_POST['qty']);
   $reason = $_POST['reason'];
   
   if($to == $_SESSION['ID'])
   {
      echo "<h1>You cannot transfer to yourself!!!</h1>";
      include('footer.php');
      die();
   }
   
   if(getCreditTotal($from) >= $qty)
   {
      if(transferCredits($from, $to, $qty, "txed by member {$_SESSION['ID']} - $reason", 4))
      {
         echo "$qty Hedons transferred from " . printMemberString($from, 1) . " to " . printMemberString($to, 1) . " for reason: $reason<p>";
         mail("gm@pvgaming.org", "Hedons Transferred", "$qty Hedons transferred from " . printMemberString($from, 1) . " to " . printMemberString($to, 1) . " for reason: $reason");
      }
      else
      {
         echo "Error transferring Credits<p>";
      }
   }
   else
   {
      echo printMemberString($from, 1) . " does not have $qty Hedons. Current balance: " . getCreditTotal($from) . "<p>";
   }
}
   
   
include('footer.php');
?>