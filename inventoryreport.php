<?php
// inventoryreport.php

// Displays an inventory Report

// GET
// noblank = 1 - do not show 0 qty items
// sort = column to sort by (department, manufacturer, price, cost, inv, UPC, alternate1, alternate2, qty, description, tax)

   $title = 'Inventory Report';
   include ('funcs.inc');
   include ('header.php');
   
   $cxn = open_stream();
   
   $allowcount = ($_SESSION['inv'] == 1);
   
   // change inventory
   if($_POST['change'] == 1)
   {
   $_GET['boardcard'] = $_POST['boardcard'];
   
   echo "<hr><font size=+2>Adjusting Quantities</font><br>\n";
   
   $sql = "INSERT INTO invEvent (type, staffID, invDate, closed) VALUE (1, " . $_SESSION['ID'] . ", NOW(), 1)";
   if(!query($cxn, $sql))
      displayErrorDie("Unable to set invEvent");
   $ied = $cxn->insert_id;
   echo "Inventory Event ID: $ied<br>";
   
      extract($_POST);
      foreach($qty as $ID => $newqty)
      {
         // if a number is put in
         if(is_numeric($newqty))
         {
            echo "Item #$ID<br>";
            
            // find old quantity
            $sql = "SELECT description, qty, price, cost FROM items WHERE ID='$ID'";
            $result = query($cxn, $sql);
            $row = mysqli_fetch_assoc($result);
            $oldqty = $row['qty'];
            $qtychange = $newqty - $oldqty;
            $cost = $row['cost'];
            $price = $row['price'];
            $desc = $row['description'];
   
            // only do the thing if there is actually a change
            if($qtychange != 0)
            {
               // create itemChange
               $sql = "INSERT INTO itemChange (itemID, invEventID, qty, cost, price)
                             VALUE ($ID, $ied, $qtychange, $cost, $price)";
               if(query($cxn, $sql))
               {
                  echo "$desc Item Change Created<br>";
               }
               else
               {
                  displayErrorDie("Error Submitting itemChange for $desc ($ID)");
               }
            
               // change item quantity
               $sql = "UPDATE items SET qty=$newqty WHERE ID=$ID";
               if(query($cxn, $sql))
               {
                  echo "$desc Qty changed $qtychange to $newqty<br>";
               }
               else
               {
                  displayErrorDie("Error Submitting qty for $desc ($ID)");
               }
            }
         }
      }
   echo "<hr>";
   }
   
   $validColumns = array('ID', 'department', 'manufacturer', 'price', 'cost', 'inv', 'UPC', 'alternate1', 'alternate2', 'qty', 'description', 'tax');
   if(isset($_GET['sort']))
   {
      $sort = (in_array($_GET['sort'], $validColumns)) ? 'ORDER BY ' . $_GET['sort'] : '';
   }
   
   if($_GET['noblank'] == 1)
   {
      $noblank = "WHERE qty > 0";
      if($_GET['boardcard'] == 1)
      {
         $boardcard = "AND (department LIKE 'Board Games' OR department LIKE 'Card Games')";
      }
   }
   else if($_GET['boardcard'] == 1)
   {
      $boardcard = "WHERE (department LIKE 'Board Games' OR department LIKE 'Card Games')";
   }
   
   $sql = "SELECT * FROM items $noblank $boardcard $sort";
   
   $result = query($cxn, $sql);

   echo "<form action='inventoryreport.php' method='get'>
         <select name='sort'>\n";
   foreach($validColumns as $vc)
   {
      echo "<option value='$vc'>$vc</option>\n";
   }
   
   $isbc = ($_GET['boardcard'] == 1) ? " checked" : "";
   
   echo "</select><br>
         <input type='checkbox' name='noblank' value=1> Do not show 0 quantity items<br>
         <input type='checkbox' name='boardcard' value=1 $isbc> Board/Card Games Only?<br>
         <input type='submit' name='submit' value='sort'></form><p>";

   
   if($allowcount)
      echo "<form action='inventoryreport.php' method='post'>\n";
   echo "<table cellpadding=3 border><tr>
         <td>ID</td>
         <td width=100>Name</td>
         <td>Price</td>
         <td>Cost</td>
         <td>Qty</td>
         <td>Tax</td>
         <td>Inv</td>
         <td>UPC<br>Alt1<br>Alt2</td>
         <td>Department<br>Manufacturer</td>\n";
   if($allowcount)
      echo "<td>Current Qty</td>\n";
   echo "</tr>\n";

   while($row = mysqli_fetch_assoc($result))
   {
      extract ($row);
      
      echo "<tr>
            <td><a name='$ID'>$ID</td>
            <td><a href='edititem.php?ID=$ID'>$description</a></td>
            <td>" . money($price) . "</td>
            <td>" . money($cost) . "</td>
            <td>$qty</td>
            <td>" . (($tax == 1) ? "YES" : "NO") . "</td>
            <td>" . (($inv == 1) ? "YES" : "NO") . "</td>
            <td>$UPC<br>$alternate1<br>$alternate2</td>
            <td>$department<br>$manufacturer</td>\n";
      if($allowcount)
         echo "<td><input name='qty[$ID]' size=5 maxlength=5></td>\n";
      echo "</tr>";
         
   }
	
   echo "</table><br>";
   if($allowcount)
   {
      if($_GET['boardcard'] == 1)
      {
         echo "<input name='boardcard' value='1' type='hidden'>";
      }
      echo "<input name='change' value='1' type='hidden'>
            <input name='submit' value='submit' type='submit'></form>
            <br>";
   }
   
   include('footer.php');


?>
