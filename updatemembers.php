<?php
// updateMembers
// produces a list of all members who are not active members and offers pull downs to make them members

// takes an array of working[] and contrib[]
// working is 1 or 0
// contrib is
// 0 - guest
// 1 - one month
// 10 - one year
// 20 - lifetime
// 21 - eternal

   $securePage = TRUE;
   include('funcs.inc');
   include('header.php');
   $cxn = open_stream();
   
   if($_SESSION['mem'] != 1)
   {
      echo "You must have Member Admin priviledges to use this page!<br>";
      include('footer.php');
      die();
   }
   
   echo "This is a utility page to make up for a lack in the input members page. It's quick, dirty, and ugly.<p>
         2200-01-01 is a lifetime member<br>
         2300-01-01 is an eternal member<hr>";

   if($_POST['dominate'] == 'dominate')
   {
      extract($_POST);
      foreach($working as $num => $work)
      {
         // working membership
         $date = date_create();
         $date->modify("+1 month");
         $workingExp = $date->format("Y-m-d");
         $sql = "UPDATE members
                    SET workingExp='$workingExp'
                  WHERE ID='$num'";
         if(query($cxn, $sql)) echo "Set ID:$num to workingExp: $workingExp<p>";
      }

      foreach($contributing as $num => $cont)
      {
         // contributing membership
         $date = date_create();
         switch($cont)
         {
            case 0 : $contribExp = '';
                     break;
            case 1 : date_modify($date, "+1 month");
                     $contribExp = date_format($date, "Y-m-d");
                     break;
            case 10 : date_modify($date, "+1 year");
                     $contribExp = date_format($date, "Y-m-d");
                     break;
            case 20 : $contribExp = "2200-01-01";
                     break;
            case 21 : $contribExp = "2300-01-01";
                     break;
            default : $contribExp = '';
         }
         
         if($contribExp != '')
         {
            $sql = "UPDATE members
                       SET contribExp='$contribExp'
                     WHERE ID='$num'";
            if(query($cxn, $sql)) echo "Set ID:$num to contribExp: $contribExp<p>";
         }
      }
   }
   
   $sql = "SELECT *
             FROM members
            WHERE (contribExp < NOW()
               OR workingExp < NOW())
               AND registerUse=1";
   $result = query($cxn, $sql);
   echo "<form action='updatemembers.php' method='POST'>
         <table border><tr><td>Name</td><td>Working Exp</td><td>Contrib Exp</td><td>Make Working Member</td><td>Contributing Status</td></tr>";
   while ($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      echo "<tr><td>$lname, $fname</td><td>$workingExp</td><td>$contribExp</td>
            <td><input type='checkbox' name='working[$ID]' value=1></td>
            <td><select name='contributing[$ID]'>
            <option></option>
            <option value=0>Guest/Working Member</option>
            <option value=1>One Month</option>
            <option value=10>One Year</option>
            <option value=20>Lifetime</option>
            <option value=21>Eternal</option>
            </select></td></tr>";
   }
   echo "</table><input type='submit' name='dominate' value='dominate'></form>";
   include ('footer.php');
?>