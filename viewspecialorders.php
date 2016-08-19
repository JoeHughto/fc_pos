<?php
// viewspecialorders.php

// shows a list of special orders

   $title = 'View Special Orders';
   include('funcs.inc');
   include('header.php');
   $cxn = open_stream();
   
   if(is_array($_POST['ID']))
   {
      foreach($_POST['ID'] as $i => $name)
      {
         $sql = "UPDATE specialOrders SET dateTaken=DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE ID='$i'";
         if(query($cxn, $sql))
            echo "$name set as picked up<br>";
      }
   } // end if      
   
   
   $sql = "SELECT * FROM specialOrders WHERE dateTaken = '0000-00-00 00:00:00'";
   $result = query($cxn, $sql);

   echo "<form action='viewspecialorders.php' method='post'>
   	 <table cellpadding=5 border><tr><td>Item</td>
                    <td>Price</td>
                    <td>Customer</td>
                    <td>Phone</td>
                    <td>Date Ordered</td>
                    <td>Order ID</td>
                    <td>Completed?</td></tr>";
                    
   while($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      
      $sql = "SELECT phone1 FROM members WHERE ID='$custID'";
      $row = queryAssoc($cxn, $sql);
      $phone = formPhoneNumber($row['phone1']);
      
      $date = date_create($dateMade);

      $priceStr = (($price > 0) ? money($price) : 'request');
      if($qty > 1)
      {
         $priceStr .= " x $qty";
      }
      
      echo "<tr><td>$item</td>
                <td>$priceStr</td>
                <td>" . printMemberString($custID, 1) . "</td>
                <td>$phone</td>
                <td>" . $date->format("M j, Y") . "</td>
                <td>$ID</td>
                <td><input type='checkbox' name='ID[$ID]' value='$item'></td></tr>";
   }
   echo "</table>
         <input type='submit' name='Submit Changes' value='Submit Changes'></form>";
   
?>
