<?php include('funcs.inc')?>
<HTML>
<HEAD>
<TITLE>Check Database</TITLE>
</HEAD>
<BODY>
<H1>Repair Database</H1><HR>
<?php
// checktransactions.php

// Checks transactions againsts the soldItem rows

$cxn = open_stream();
$sql = "SELECT ID, totalPrice FROM transactions";
$transres = query($cxn, $sql);
while($row = mysqli_fetch_assoc($transres))
{
   extract($row);
   $sql = "SELECT SUM(price * qty) Tprice FROM soldItem WHERE transactionID='$ID'";
   
   $Tprice = queryAssoc($cxn, $sql);
   extract($Tprice);
   if($totalPrice != $Tprice)
   {
      echo "ERROR on TID: $ID, Transaction Price: $totalPrice, Item Price: $Tprice<p>";
   }
   $error += $totalPrice - $Tprice;
   if(($totalPrice - $Tprice) > .02) echo "<font color=RED>BIG ERROR</font><p>\n";
}
echo "ERRORSUM: $error";
?>
</BODY>
</HTML>
             
                  
