<?php
// preorderreport.php
// shows a report of all preorders whose release date has not yet passed

$title = 'Preorder Report';
include('funcs.inc');
include('header.php');
$cxn = open_stream();

$sql = "SELECT * FROM preorders WHERE releaseDate >= CURDATE() ORDER BY releaseDate";
$result = query($cxn, $sql);

echo "<p><b>Preorders that have not released yet</b>
      <table border><tr><td>Item</td><td>Price</td><td>Manufacturer/Department</td><td>Order Deadline</td><td>Release Date</td></tr>\n";

while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   echo "<tr><td><a href='inputpreorder.php?ID=$ID'>$description</td>
         <td>" . money($price) . "</td>
         <td>$manufacturer<br>$department</td>
         <td>$orderDate</td>
         <td>$releaseDate</td></tr>\n";
}
echo "</table><p>\n";
include('footer.php');
?>