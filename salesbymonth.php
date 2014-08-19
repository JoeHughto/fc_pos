<?php
// salesbymonth.php

// shows sales by month for every month since we opened
// including sales, cost and profit

include('funcs.inc');
include('header.php');

$cxn = open_stream();

$date = date_create('2008-2-1');

echo "<h2>Sales By Month</h2>
      <table border cellpadding=5><tr><td>Month</td><td>Sales</td><td>Cost</td><td>Profit</td><td>New Customers</td></tr>\n";

while($date < date_create())
{
   $start = $date->format('Y-m-d 00:00:00');
   $end = $date->format('Y-m-');
   $end .= lastDayOfMonth($date->format('n')) . " 23:59:59";
   
   $sql = "SELECT sum(totalPrice) sales,
                  sum(totalCost) cost
             FROM transactions
            WHERE whensale > '$start'
              AND whensale < '$end'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_assoc($result);
   extract($row);
   echo "<tr><td>" . $date->format('M, y') . "</td><td>" . money($sales) . "</td><td>" . money($cost) . "</td><td>" . money(($sales - $cost)) . "</td>";
   
   $sql = "SELECT count(ID) num
             FROM members
            WHERE memberSince > '$start'
              AND memberSince < '$end'";
              
   $result = query($cxn, $sql);
   $row = mysqli_fetch_assoc($result);
   extract($row);
   echo "<td>$num</td></tr>\n";
   
   $date->modify("+1 month");
}

echo "</table>";
include('footer.php');
?>