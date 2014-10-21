<?php
// upgradetransactions.php

// This application is a one time application to convert from the old system of using a payment type column to
// using four different columns for payments

   include ('funcs.inc');
   
   $cxn = open_stream();
   
   $sql = "SELECT ID, payMethod, totalPrice, tax FROM transactions";
   $result = query($cxn, $sql);
   
   $paymentTypes = array(1 => 'cash', 'creditcard', 'checkpay', 'account');

   while ($row = mysqli_fetch_assoc($result))
   {
      extract($row);

      if($payMethod == 0) continue;
      $paymentType = $paymentTypes[$payMethod];
      $value = round($totalPrice + $tax, 2);

      $sql = "UPDATE transactions SET $paymentType = '$value' WHERE ID = '$ID'";
      if(query($cxn, $sql))
      {
         echo "Successfully updated ID# $ID<br>";
      }
   }
?>
