<?php
// inputpreorder.php
// allows user to enter and edit preorders
// user must have INV permission to do so

// GET
// ID - ID of a preorder, which allows editing

// POST
// ID, price, description, department, manufacturer, orderDay, orderMonth, orderYear, releaseDay, releaseMonth, releaseYear, etc
// if ID is set, treats as edit, otherwise makes new one

$title='Input Preorder';
include('funcs.inc');
include('inventory.inc');
include('header.php');
$cxn = open_stream();

if($_SESSION['inv'] != 1)
{
   echo "<font color=RED>You must have Inventory Permission to use this application</font><p>";
   include('footer.php');
   die();
}

if(isset($_POST['price']))
{
   extract($_POST);
   $description = strip_tags($description);
   $department = strip_tags($department[0]);
   $manufacturer = strip_tags($manufacturer[0]);
   $orderDate = $orderYear . '-' . $orderMonth . '-' . $orderDay;
   $releaseDate = $releaseYear . '-' . $releaseMonth . '-' . $releaseDay;
   
   if($ID > 0)
   {
      $stmt = $cxn->prepare("UPDATE preorders
        	  	        SET description=?,
        	  	            price=?,
        	  	            department=?,
        	  	            manufacturer=?,
        	  	            tax=?,
        	  	            orderDate=?,
        	  	            releaseDate=?
        	  	      WHERE ID='$ID'");
   }
   else
   {
      $stmt = $cxn->prepare("INSERT INTO preorders (description, price, department, manufacturer, tax, orderDate, releaseDate)
      				            VALUES (?,?,?,?,?,?,?)");
   }
   $stmt->bind_param("sdssiss", $description, $price, $department, $manufacturer, $tax, $orderDate, $releaseDate);
   if($stmt->execute())
   {
      if($ID > 0)
      {
         echo "Preorder for $description Edited Successfully<p>";         
      }
      else
      {
         echo "Preorder for $description Created Succesfully<p>";
      }
      echo "Department: $department, Manufacturer: $manufacturer<br>
            Price: " . money($price) . " Taxable: " . (($tax == 1) ? "Yes" : "No") .
           "<br>Release Date: $releaseDate<br>
           Order Deadline: $orderDate<hr>";
      unset($description, $price, $department, $manufacturer, $tax, $orderDate, $releaseDate, $ID); // clear vars
      unset($releaseMonth, $releaseDay, $releaseYear, $orderMonth, $orderDay, $orderYear);
   }
   else
   {
      echo "Unable to submit data. Error: " . $stmt->error;
   }
}
      

if($_GET['ID'] > 0)
{
   extract($_GET);
   $sql = "SELECT * FROM preorders WHERE ID='$ID'";
   if($result = query($cxn, $sql))
   {
      $row = mysqli_fetch_assoc($result);
      extract($row);
   }
   else
   {
      echo "<font color=RED>Error Loading Preorder #$ID<p>";
   }
   $date = date_create($releaseDate);
   $releaseDay = $date->format("j");
   $releaseMonth = $date->format("n");
   $releaseYear = $date->format("Y");
   $date = date_create($orderDate);
   $orderDay = $date->format("j");
   $orderMonth = $date->format("n");
   $orderYear = $date->format("Y");
   
}

echo "<p><b>Enter Preorder Item</b><form action='inputpreorder.php' method='post'>
<table><tr>
<td>Description</td><td><input type='text' name='description' value='$description' size=30 maxlength=50></td></tr>
<tr><td>Department:</td><td>";
displayDepartmentList(0, $department);
echo "</td></tr><tr><Td>Manufacturer</td><td>";
displayManufacturerList(0, $manufacturer);

echo "</td></tr>";
echo "
<tr><td>Price:</td><td>\$<input type='text' name='price' value='$price' size=8 maxlength=8></td></tr>
<tr><td>Taxable?</td><td><input type='checkbox' name='tax' value='1' " . ((($tax == 0) && isset($tax)) ? "" : "checked") . "></td></tr>";

echo "<tr><td>Release Date:</td><td>";
selectInputDate('releaseMonth','releaseDay','releaseYear', 2008, 2015, $releaseMonth, $releaseDay, $releaseYear);
echo "</td></tr><tr><td>Order Deadline:</td><td>";
selectInputDate('orderMonth','orderDay','orderYear', 2008, 2015, $orderMonth, $orderDay, $orderYear);
echo "</td></tr></table><br>\n";
if($ID > 0)
{
   echo "<input type='hidden' name='ID' value='$ID'>\n";
}
echo "<input type='submit' name='dominate' value='dominate'><input type='submit' name='submit' value='submit'><p>\n";
 
include('footer.php');
?>