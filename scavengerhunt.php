<?php
// scavengerhunt.php
// This application allows a user with REG permission to mark tokens as turned in

// GET
// show = 1 - show a list of who has what tokens, ordered by name

$title = "Scavenger Hunt Utilities";
include("funcs.inc");
include("header.php");

$cxn = open_stream();


if($_POST['dominate'] == 'dominate')
{
   extract($_POST);
   
   if($token > 0 && $customer > 0)
   {
      $sql = "SELECT custID FROM huntTokens WHERE token='$token'";
      query($cxn, $sql);
      if($cxn->affected_rows > 0) $first = 0;
      else $first = 1;
      
      $sql = "INSERT INTO huntTokens (whenSub, staffID, custID, token, first)
                              VALUES (NOW(), " . $_SESSION['ID'] . ", $customer, $token, $first)";
      query($cxn, $sql);
   }
   else
   {
      echo "<font color=RED>Invalid customer ID or token ID</font><p>";
   }
}

echo "<font size=+3>Submit Token</font><br>
<form action='scavengerhunt.php' method='post'>
Customer: ";
selectMember("customer", 0);
echo "<p>
Token: <select name='token'>
<option value=1>W</option>
<option value=2>O</option>
<option value=3>R</option>
<option value=4>L</option>
<option value=5>D</option>
<option value=6>S</option>
<option value=7>A</option>
<option value=8>P</option>
<option value=9>T</option>
</select><p>
<input name='dominate' value='dominate' type='submit'></form><p>";
//if($_GET['show'] == 1)
//{
   $sql = "SELECT m.fname fname,
   	          m.lname lname,
   	          t.custID ID,
   	          t.token token,
   	          t.first first
   	     FROM huntTokens t
   	     JOIN members m
   	       ON t.custID = m.ID
   	 ORDER BY lname,
   	 	  fname,
   	 	  token";
   $result = query($cxn, $sql);
  
   echo "<font size=+3>Tokens Turned In</font><br>
         <table border><tr><td>Name</td>
                    <td width=20>W</td>
                    <td width=20>O</td>
                    <td width=20>R</td>
                    <td width=20>L</td>
                    <td width=20>D</td>
                    <td width=20>S</td>
                    <td width=20>A</td>
                    <td width=20>P</td>
                    <td width=20>T</td></tr>\n";
   
   while($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      
      // when it is a new person
      if($oldID != $ID)
      {
         echo "</tr><tr><td>";
         printMember($ID, 1);
         echo "</td>";
         
         $oldID = $ID;
         $curToken = 1;
      }
      
      // create blank spaces      
      while($curToken < $token)
      {
         echo "<td></td>";
         $curToken++;
      }
      
      $curToken++;
      if($first == 1)
         echo "<td bgcolor=RED></td>";   
      else
         echo "<td bgcolor=BLUE></td>";
   }
   echo "</tr></table><p>\n";
//}

include("footer.php");
?>