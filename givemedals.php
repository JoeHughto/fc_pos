<?php
// givemedals.php
// Allows anyone with adm permissions to give any medal
// Allows adm permission to give specific permissions to members, and taketh away
// Allows people with specific permissions to give specific medals

// POST
// member - member number of medal recipient
// medal - medal number being given
// note - note for medal

// perMember - member to give/take permissions to
// perMedal - medal to give/take permissions for
// perDate - if medal is a once a month medal, this is 1

$secureSite = TRUE;
include('funcs.inc');
include('medals.inc');
include('header.php');

$cxn = open_stream();

// if a medal has been granted
if($_POST['member'] > 0 && $_POST['medal'] > 0)
{
   extract($_POST);
   if(($_SESSION['adm'] == 1) || (medalPermission($_SESSION['ID'], $medal)))
   {
      $stmt = $cxn->prepare("INSERT INTO medalsGiven (medalID, memberID, whenGiven, notes, whoGave)
                                  VALUES (?, ?, NOW(), ?, ?)");
      $stmt->bind_param("ddsd", $medal, $member, $note, $_SESSION['ID']);
      if($stmt->execute())
      {
         echo "Successfully gave this medal<br>
               <img src='../medals/$medal.jpg'><br>
               to ";
         printMember($member, 1);
         echo ".<hr>\n";
         
         // check to see if the permission is a once a month permission
         $sql = "SELECT month FROM medalPermissions WHERE member='$member' AND medal='$medal'";
         $row = queryAssoc($cxn, $sql);
         if($row[0] != 0)
         {
            $sql = "UPDATE medalPermissions SET month='" . date('n') . "' WHERE member='" . $_SESSION['ID'] . "' AND medal='$medal'";
            if(query($cxn, $sql))
            {
               echo "This medal can only be given once a month by you. Be advised.<p>\n";
            }
         }
      }
      else
      {
         echo "<font color='RED'>Error submitting medal.<br>Error: " . $stmt->error . "<hr>\n";
      }
   }
}

echo "<form action='givemedals.php' method='POST'>\n";
if(selectMedalsToGive('medal'))
{
   echo "<br>Member to give medal to:<br>\n";
   selectMember('member', 0);
   echo "<br>Notes: <input name='note' type='text' size=50 maxlength=50>";
   echo "<br><input type='submit' name='submit' value='submit'><p>";
}
echo "</form>\n";
include('footer.php');
?>