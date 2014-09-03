<?php
/**
 * @file checkedinreport.php
 * @brief checkedinreport.php is used to provide a view on the state of staffing
 *   over a given period.
 * 
 * This file includes:<br>
 * funcs.inc:<br>
 * &nbsp;&nbsp;Used for the config.inc include<br>
 * &nbsp;&nbsp;checkDateNum()<br>
 * &nbsp;&nbsp;selectInputDate()<br>
 * &nbsp;&nbsp;printMemberString()<br>
 * <br>
 * Possible Arguments:<br>
 * GET:<br>
 * &nbsp;&nbsp;startmonth - The month the report should start during.<br>
 * &nbsp;&nbsp; startday - The day the report should start on.<br>
 * &nbsp;&nbsp;startyear - The year the report should start during.<br>
 * &nbsp;&nbsp;start - Composite report start date, potentially built at runtime
 *   by combining its components.<br>
 * &nbsp;&nbsp;endmonth - The month the report should end during.<br>
 * &nbsp;&nbsp;endday - The day the report should end on.<br>
 * &nbsp;&nbsp;endyear - The year the report should end during.<br>
 * &nbsp;&nbsp;end - Composite report end date, potentially built at runtime
 *   by combining its components.<br>
 * 
 * @link http://www.worldsapartgames.org/fc/checkedinreport.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Checked In Report";
$version = "1.8d";
require_once 'funcs.inc';
require_once 'header.php';

$cxn = open_stream();

if (isset($_GET['startmonth'])) {
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
while ($row = mysqli_fetch_assoc($result)) {
    extract($row);
   
    while (($nextshift != $shift) && ($showdate != date_create($day))) {
        $dow = $showdate->format("w") + 1;
        $sql = "SELECT staffID FROM recurringSchedule WHERE day='$dow' and shift='$nextshift'";
        $member = queryOnce($cxn, $sql);

        echo "<tr><td>" . $showdate->format("D, M j, Y") . "</td><td>$nextshift</td><td><i>" . printMemberString($member, 1) . "</i></td><td bgcolor=RED>no</td></tr>\n";
        $nextshift++;
        if ($nextshift > 3) {
            echo "<tr><td colspan=4 bgcolor=BLACK> </td></tr>";
            $nextshift = 1;
            $showdate->modify("+1 day");
        }
    }      
   
    $showdate = date_create($day);
    echo "<tr><td>" . $showdate->format("D, M j, Y") . "</td><td>$shift</td><td>" . printMemberString($staffID, 1) . "</td><td " .(($checkedIn == 1) ? "bgcolor=GREEN>yes" : "bgcolor=RED>no") . "</td></tr>\n";
   
    $nextshift = $shift + 1;
    if ($nextshift > 3) {
        echo "<tr><td colspan=4 bgcolor=BLACK> </td></tr>";
        $nextshift = 1;
        $showdate->modify("+1 day");
    }
}
echo "</table>";
require_once 'footer.php';
?>