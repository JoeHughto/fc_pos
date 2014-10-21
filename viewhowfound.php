<?php
// viewhowfound.php
// A report of how people found the store in reverse date order

$title = "How People Found Out About Us";
include('funcs.inc');
include('header.php');

$cxn = open_stream();

$sql = "SELECT m.fname fname,
               m.lname lname,
               m.ID ID,
               h.dt date,
               h.howfound howfound
          FROM members m
          JOIN howFound h
            ON h.memberID = m.ID
      ORDER BY h.dt desc";
$result = query($cxn, $sql);

echo "<table border cellpadding=3><tr><td>Name</td><td>Date</td><td>How'd ey find us?</td><td>Sales</td></tr>\n";

while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   echo "<tr><td>$fname $lname</td><td>$date</td><td>$howfound</td>";
   
   $sql = "SELECT SUM(totalPrice) FROM transactions WHERE customerID='$ID'";
   $rzlt = query($cxn, $sql);
   $r2 = mysqli_fetch_row($rzlt);
   $sales = $r2[0];
   echo "<td>" . money($sales) . "</td></tr>\n";
}
echo "</table>";

include('footer.php');
?>