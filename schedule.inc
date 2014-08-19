<?php
// selectRegMonkey
// args - $name is the name for the select name, $default is the default member ID
// displays a select of reg monkeys
// if there is a default set, it indicates that the shift is assigned, and otherwise indicates it is open
function selectRegMonkey($name, $default, $rsn)
{
   $sql = "SELECT ID, fname, lname FROM members WHERE registerUse='1' ORDER BY lname, fname, ID";
   $cxn = open_stream();
   $result = query($cxn, $sql);
   
   if(($default == 0 && $rsn == 0) || ($default == -1))
   {
      echo "<font color=RED>Open Shift</font><br>";
   }
   else
   {
      echo "<font color=GREEN>Assigned Shift</font><br>";
   }

   echo "<select name='$name'>";
   echo "<option value='0'>Default</option>
         <option value='-1'";
   
   if($default == -1)
   {
      echo "selected";
   }
   echo ">OPEN SHIFT</option>\n";
   
   if($rsn == 0) echo "- NONE SET";
   echo "</option>\n";
   while ($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      if($ID == $default)
      {
         echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
      }
      else echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
      if($ID == $rsn)
      {
         $afname = $fname;
         $alname = $lname;
      }
   }
   echo "</select>";
   
   if(isset($afname))
      echo "<br>Default: $alname, $afname<br>";

   mysqli_free_result($result);
   mysqli_close($cxn);
}

// selectSchedMonkey
// args - $name is the name for the select name, $default is the default member ID
// displays a select of reg monkeys
// if there is a default set, it indicates that the shift is assigned, and otherwise indicates it is open
function selectSchedMonkey($name, $default)
{
   $sql = "SELECT ID, fname, lname FROM members WHERE registerUse='1' ORDER BY lname, fname, ID";
   $cxn = open_stream();
   $result = query($cxn, $sql);

   if($default == 0)
   {
      echo "<b>Open Shift</b><br>";
   }
   else
   {
      echo "<font color=GREEN>Assigned Shift</font><br>";
   }


   echo "<select name='$name'>";
   echo "<option value='0'></option>\n";
   while ($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      $fname = substr($fname, 0, 1);
      if($ID == $default)
      {
         echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
      }
      else echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
   }
   echo "</select>";

   mysqli_free_result($result);
   mysqli_close($cxn);
}


// showRegMonkey
// shows Register monkey with assigned ID. If ID is 0, says unassigned
function showRegMonkey($ID, $rsn)
{
   if($ID > 0 || $rsn > 0)
   {
      $cxn = open_stream();
      $ID = ($ID > 0) ? $ID : $rsn;
      $sql = "SELECT fname, lname, ID FROM members WHERE ID=$ID";
      $result = query($cxn, $sql);
      if($row = mysqli_fetch_assoc($result))
      {
         extract($row);
         if($ID == $_SESSION['ID']) echo "<font color='000066'>";
         echo "$lname, $fname (#$ID)";
         if($ID == $_SESSION['ID']) echo "</font>";
         return('your mom');
      }
   }

   // if it makes it here, there is no one in this shift
   echo "<table><tr><td bgcolor=BLACK><font color=WHITE><center>OPEN SHIFT</center></td></tr></table>";
}
      

?>
