<?php
// medals.inc
// functions specific to medals

// displayMedals
// displays all the medals for member
// shows $width medals per line
function displayMedals($member, $width)
{
   if(!($member > 0))
   {
      dispError("Invalid Member for displayMedals");
      return FALSE;
   }
   
   $cxn = open_stream();
   $sql = "SELECT m.ID medalID,
   	          m.name medalName,
                  m.value medalValue,
                  mg.memberID memberID,
                  mg.notes notes
             FROM medalsGiven mg
             JOIN medals m
               ON mg.medalID = m.ID
            WHERE memberID = '$member'
         ORDER BY m.value,
                  m.ID";
      
   $result = query($cxn, $sql);
   if($cxn->affected_rows > 0)
   {
      if($member == $_SESSION['ID'])
         echo "<b>Your Medals</b><br>\n";
      
      $count = 0;
      while($row = mysqli_fetch_assoc($result))
      {
         extract($row);
         $count++;
         echo "<img src='../medals/$medalID.jpg' alt='$medalName (Value=$medalValue) $notes'>\n";
         if($count > $width)
         {
            $count = 0;
            echo "<br>\n";
         }
      }
   }
}

// medalPermission
// returns true is the member has permission to give that medal
// returns false if not permission and echoes why
function medalPermission($member, $medal)
{
   $cxn = open_stream();
   $sql = "SELECT month FROM medalPermissions WHERE member='$member' AND medal='$medal'";
   $result = query($cxn, $sql);
   if($cxn->rows_affected > 0)
   {
      $row = mysqli_fetch_assoc($result);
      extract($row);
      if($month != date('n'))
      {
         return(TRUE);
      }
      else
      {
         echo "<font color='RED'>You do not have permission to give this medal until $nextDate</font><p>\n";
         return(FALSE);
      }
   }
   else
   {
      echo "<font color='RED'>You do not have permission to give this medal</font><p>\n";
      return(FALSE);
   }
}

// selectMedalsToGive
// gives a select input of medals which the current user is allowed to give
// selname - name for the form
function selectMedalsToGive($selname)
{
   $cxn = open_stream();
   
   if($_SESSION['adm'] == 1)
   {
      $admin = 1;
   }
   else
   {
      $cxn = open_stream();
      $sql = "SELECT month, medal FROM medalPermissions WHERE member='" . $_SESSION['ID'] . "'";
      $result = query($cxn, $sql);
   
      $medals = array();
   
      while($row = mysqli_fetch_assoc($result))
      {
         extract($row);
         if($month != date('n'))
         {
            $array_push($medals, $medal);
         }
      }
   }
   
    
   if($admin == 1)
   {
      $sql = "SELECT name, ID FROM medals ORDER BY class, ID";
      $result = query($cxn, $sql);

      echo "Select Medal:<br>\n<select name='$selname'>\n";
      while($row = mysqli_fetch_assoc($result))
      {
         extract($row);
         echo "<option value='$ID'>$name</option>\n";
      }
      echo "</select><br>\n";
      return TRUE;
   }
   else if(count($medals) > 0)
   {
      echo "Select Medal:<br>\n<select name='$selname'>\n";
      foreach($medals as $m)
      {
         $sql = "SELECT name FROM medals WHERE ID='$m'";
         $row = queryAssoc($cxn, $sql);
         $name = $row[0];
         echo "<option value='$m'>$name</option>\n";
      }
      echo "</select><br>\n";
      return TRUE;
   }
   else
   {
      echo "You do not have permission to give medals<p>\n";
      return FALSE;
   }
}
   
?>