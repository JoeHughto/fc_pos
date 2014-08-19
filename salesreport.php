<?php
// salesreport.php
// This application produces a report of sales within certain perameters.
// Over time, more GET arguments will be created

// GET
// start - start date of report in form YYYY-MM-DD
// end - end date of report
// sort - 1 - name (default), 2 - dept, 3 - manu
// staff - selling staff member

   include('funcs.inc');
   include('salesreport.inc');
   $title='Sales Report';
   include('header.php');

   unset($start, $end);

   if(isset($_POST['date']))
   {
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
   
   include("footer.php");
?>