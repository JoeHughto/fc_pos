<?php include('funcs.inc')?>
<HTML>
<HEAD>
<TITLE>Repair Database</TITLE>
</HEAD>
<BODY>
<H1>Repair Database</H1><HR>
<?php
// repairdatabase.php

// Checks all soldItem rows and checks for corresponding transactions
// if not transactions or if transaction inaccurate, fixes transactions

$cxn = open_stream();

$sql = "SELECT * FROM soldItem ORDER BY transactionID";
$result = query($cxn, $sql);

while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   $sql = "SELECT * FROM transactions WHERE ID='$transactionID'";
   query($cxn, $sql);
   if($cxn->affected_rows == 0)
   {
      echo "TID: $transactionID not found<br>";
      $sql = "DELETE FROM soldItem WHERE transactionID='$transactionID'";
      $r2 = query($cxn, $sql);
      echo "Deleted soldItem #$ID<p>";
/*      $Tprice = 0;
      $Tcost = 0;
      $Ttaxable = 0;
      while($row = mysqli_fetch_assoc($r2))
      {
         extract($row);
         $Tprice += $price;
         $Tcost += ($cost * $qty);
         $Ttaxable += ($tax == 1) ? $price : 0;
         
         echo "New item: TID: $transactionID Item #$itemID (Price: $price, Cost: $cost)<br>";
      }
      $Tprice = round($Tprice,2);
      $Tcost = round($Tcost,2);
      $Ttax = round(($Ttaxable * .05),2);
      $cash = $Tprice;

      $sql = "SELECT whensale FROM transactions WHERE ID=MAX" . ($transactionID - 1) . "'";
      $row = queryAssoc($cxn, $sql);
      $whensale = $row['whensale'];

      $sql = "INSERT INTO transactions 
              (ID, staffID, totalPrice, totalCost, tax, payMethod, cash, whensale)
              VALUES
              ('$transactionID', '0', '$Tprice', '$Tcost', '$Ttax', '1', '$cash', '$whensale')";
      if(query($cxn, $sql))
      {
         echo "<b>Added</b> TID: $transactionID, Price: $Tprice, Cost: $Tcost, Tax: $Ttax, When: $whensale<p>";
      }*/
   }
}
?>
</BODY>
</HTML>
    

      
                     