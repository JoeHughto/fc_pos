<?php
// checkedinreport

// displays a report of who has checked in

// GET
// start - starting date range
// end - ending date range

include('funcs.inc');
include('header.php');

$cxn = open_stream();

if(isset($_GET['startmonth']))
{
   $_GET['start'] = $_GET['startyear'] . '-' . $_GET['startmonth'] . '-' . $_GET['startday'];
   $_GET['end'] = $_GET['endyear'] . '-' . $_GET['endmonth'] . '-' . $_GET['endday'];
}

$start = (checkDateNum($_GET['start'])) ? $_GET['start'] : dateString("-2 weeks");
$end = (checkDateNum($_GET['end'])) ? $_GET['end'] : date("Y-m-d");

$sdate = date_create($start);
$edate = date_create($end);

echo "<h1>Checked In Report</h1><hr>
      From $start to $end<hr>";
      
echo "<form action='checkedinreport.php' method='get'>
   Start: ";
   selectInputDate('startmonth', 'startday', 'startyear', 2008, date('Y', strtotime('+1 year')), $sdate->format("n"), $sdate->format("j"), $sdate->format("Y"));
echo "<br>End: ";
   selectInputDate('endmonth', 'endday', 'endyear', 2008, date('Y', strtotime('+1 year')), $edate->format("n"), $edate->format("j"), $edate->format("Y"));
echo "<br>
   <input type='submit' name='submit' value='submit'><hr>
   
   Note that <i>italicized</i> names are from the current default database and may not be accurate if the the recurring schedule has been changed since the date you are looking at.<p>";



echo "<table border><tr><td>Date</td><td>Shift</td><td>Staff Member</td><td>Checked In?</td></tr>\n";
$sql = "SELECT * FROM schedule WHERE day >= '$start' AND day <= '$end' ORDER BY day, shift";
$result = query($cxn, $sql);
$showdate = date_create("$start");
while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   
   while(($nextshift != $shift) && ($showdate != date_create($day)))
   {
      $dow = $showdate->format("w") + 1;
      $sql = "SELECT staffID FROM recurringSchedule WHERE day='$dow' and shift='$nextshift'";
      $member = queryOnce($cxn, $sql);

      echo "<tr><td>" . $showdate->format("D, M j, Y") . "</td><td>$nextshift</td><td><i>" . printMemberString($member, 1) . "</i></td><td bgcolor=RED>no</td></tr>\n";
      $nextshift++;
      if($nextshift > 3)
      {
         echo "<tr><td colspan=4 bgcolor=BLACK> </td></tr>";
         $nextshift = 1;
         $showdate->modify("+1 day");
      }
   }      
   
   $showdate = date_create($day);
   echo "<tr><td>" . $showdate->format("D, M j, Y") . "</td><td>$shift</td><td>" . printMemberString($staffID, 1) . "</td><td " .(($checkedIn == 1) ? "bgcolor=GREEN>yes" : "bgcolor=RED>no") . "</td></tr>\n";
   
   $nextshift = $shift + 1;
   if($nextshift > 3)
   {
      echo "<tr><td colspan=4 bgcolor=BLACK> </td></tr>";
      $nextshift = 1;
      $showdate->modify("+1 day");
   }
}
echo "</table>";
include('footer.php');
?>