<?php
// cashspend.php
// records spending of cash from register and displays recent cash from register

include('funcs.inc');
include('header.php');

$cxn = open_stream();

if($_POST['amount'] > 0)
{
   $amount = $_POST['amount'];
   $reason = $_POST['reason'];
   
   $stmt = $cxn->prepare("INSERT INTO cashSpend (submitter, amount, reason, whenSub)
                                  VALUES (?, ?, ?, NOW())");                                  
   $stmt->bind_param("ids", $_SESSION['ID'], $amount, $reason);
   if($stmt->execute())
   {
      echo "<b>Cash Spend Submitted</b><br>\n" .
           money($amount) . " removed from register for reason:<br>
           $reason<hr>";
   }
   else
   {
      echo "<b>Error</b><br>
            Cash Spend not submitted properly<hr>";
   }
}

$sql = "SELECT * FROM cashSpend";
$result = query($cxn, $sql);

echo "<h2>Previous Cash Spends</h2>
      <table border><tr><td>Date</td><td>Staff</td><td>Amount</td><td>Reason</td></tr>\n";

while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   echo "<tr><td>$whenSub</td><td>" . printMemberString($submitter, 1) . "</td><td>" . money($amount) . "</td><td>$reason</td></tr>\n";
}
echo "</table>";
include('footer.php');
?>