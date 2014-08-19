<?php
// edititem.php

// shows an item described by GET['ID']

include('funcs.inc');
include('inventory.inc');
include('header.php');
$cxn = open_stream();

if($_SESSION['inv'] != 1)
{
   echo "You must have Inventory Permissions to recieve and invoice. If you believe you have recieved this in error, please
         contact the General Manager or Quartermaster.<p>";
   include('footer.php');
   exit();
}

// Check for POST and Make changes
if($_POST['submit'] == 'submit')
{
   extract($_POST);
   $stmt = $cxn->prepare("UPDATE items 
                             SET price = '$price',
                                 salePrice = '$salePrice',
                                 cost = '$cost',
                                 department = '$department',
                                 manufacturer = '$manufacturer',
                                 UPC = '$UPC',
                                 alternate1 = '$alternate1',
                                 alternate2 = '$alternate2',
                                 inv = '$inv',
                                 tax = '$tax'
                           WHERE ID='$ID';");
   if($stmt->execute())
   {
      echo "Changes Made";
      include('footer.php');
      exit();
   }
   else
   {
      echo "Error: " . $cxn->error . "<p>";
      include('footer.php');
      exit();
   }     
}

// Display Form
if($_GET['ID'] > 0)
{
   $ID = $_GET['ID'];
   $sql = "SELECT * FROM items WHERE ID='$ID'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_assoc($result);
   extract($row);
   echo "Edit Data For $description, Item #$ID<p>
         <form action='edititem.php' method='post'>
         <input type='hidden' name='ID' value='$ID'>
         Price: <input name='price' value='$price' type='text' size=8 maxsize=8><br>
         Sale Price: <input name='salePrice' value='$salePrice' type='text' size=8 maxsize=8> (Leave blank if none)<br>
         Cost: <input name='cost' value='$cost' type='text' size=8 maxsize=8> (don't change cost unless you need to because it has some minor accounting side effects)<br> 
         Department: ";
   displayDepartmentListScalar($department);
   echo "<br>Manufacturer: ";
   displayManufacturerListScalar($manufacturer);
   echo "<br>UPC: <input type='text' name='UPC' size=35 maxlength=35 value='$UPC'>
         <br>Alt1: <input type='text' name='alternate1' size=35 maxlength=35 value='$alternate1'>
         <br>Alt2: <input type='text' name='alternate2' size=35 maxlength=35 value='$alternate2'>";
   echo "<br>Taxable Item? <input type='checkbox' name='tax' value='1'";
   if($tax) echo " checked";

   echo "><input type='submit' name='submit' value='submit'></form>";
}

include('footer.php');
?>
