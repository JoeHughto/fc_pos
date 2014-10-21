<?php
/**
 * @file salesreport.php
 * @brief salesreport.php provides an interface for checking the report on sales,
 *   broken down by day and by hour.
 *
 * This file includes:
 * funcs.inc:
 * - selectInputDate()
 * - checkDateNum()
 * - dateString()
 * 
 * salesreport.inc:
 * - displayList()
 * - displayTableByDay()
 * - displayTableByHour()
 * 
 * Possible Arguments:
 * POST:
 * - startmonth - The start and end
 * - startday - date variables are
 * - startyear - used to select a
 * - endmonth - range of dates, and
 * - endday - are posted by a custom
 * - endyear - select box.
 * - order - This integer is used to choose an order for the data
 *   to be formatted in. This value is set by a <select> box.
 * - staff - This is an optional integer for restricting returned
 *   data to a single staff member.
 * - cust - This is an optional integer for restricting returned
 *   data to a single customer.
 * 
 * GET:
 * - start - This is a datestring used to allow links to specific
 *   ranges of dates, this being the start date. If the date selects are
 *   posted, they will overwrite any values inserted via url.
 * - end - This is a datestring used to allow links to specific
 *   ranges of dates, this being the end date. If the date selects are
 *   posted, they will overwrite any values inserted via url.
 * 
 * @link http://www.worldsapartgames.org/fc/salesreport.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = 'Sales Report';
require_once 'funcs.inc';
require_once 'salesreport.inc';
require_once 'header.php';

unset($start, $end);

if (isset($_POST['date'])) {
    extract($_POST);
    $_GET['start'] = $startyear . '-' . $startmonth . '-' . $startday;
    $_GET['end'] = $endyear . '-' . $endmonth . '-' . $endday;
}
$sdate = date_create($_GET['start']);
$edate = date_create($_GET['end']);

echo "<form action='salesreport.php' method='post'>
    <b>View Another Set of Dates</b><br>
    Start: ";
selectInputDate('startmonth', 'startday', 'startyear', 2008, date('Y', strtotime('+1 year')), $sdate->format("n"), $sdate->format("j"), $sdate->format("Y"));
echo "<br>End: ";
selectInputDate('endmonth', 'endday', 'endyear', 2008, date('Y', strtotime('+1 year')), $edate->format("n"), $edate->format("j"), $edate->format("Y"));
echo "<br>";
echo "Sort By:<br>
    <select name='order'>
    <option value=1>Name</option>
    <option value=2>Department</option>
    <option value=3>Manufacturer</option>
    <option value=4>Cost</option>
    </select><br>";
echo "<input type='submit' name='date' value='submit date'><hr>\n";

extract($_GET);

$start = (checkDateNum($start)) ? $start : dateString("-2 weeks");
$end = (checkDateNum($end)) ? $end : date('Y-m-d');

echo "<h2>Displaying Report for $start to $end<p></h2>";

displayList($start, $end, $order, $staff, $cust);

echo "<hr><h2>Sales Table</h2>\n";
displayTableByDay($start, $end);
echo "<hr><h2>Sales by Hour</h2>\n";
displayTableByHour($start, $end);

require "footer.php";
?>