<?php
// mandepinv.php

// this application allows the addition of new manufacturers and departments
// it also shows the first 10 items with no manufacturere or department

   $securePage = TRUE;
   include('funcs.inc');
   include('header.php');
   include('inventory.inc');
   $cxn = open_stream();
   
   if(!$_SESSION['inv'])
   {
      die ("You are not cool enough to be here");
   }
   


   // review post data
   if(isset($_POST['submit']))
   {
      extract($_POST);
      if($submit == 'mandep') foreach($manufacturer as $thisID => $manu)
      {
         if((checkName($manu) && checkName($department[$thisID]))
         && (isset($manu)     && isset($department[$thisID])))
         {
            $diffman = FALSE;
            $diffdep = FALSE;
            $sql = "SELECT description, manufacturer, department FROM items WHERE ID='$thisID'";
            $result = query($cxn, $sql);
            $row = mysqli_fetch_assoc($result);
            $description = $row['description'];
            $sql = "UPDATE items SET ";
            if($manu != $row['manufacturer'])
            {
               $diffman = TRUE;
               $sql .= "manufacturer = '" . $manu . "'";
            }
            if($department[$thisID] != $row['department'])
            {
               $diffdep = TRUE;
               if($diffman) $sql .= ',';
               $sql .= "department = '" . $department[$thisID] . "'";
            }
            $sql .= " WHERE ID='$thisID'";

            if($diffman || $diffdep)
            {
               if(query($cxn, $sql))
               {
                  if($diffman) echo "$description Manufacturer changed to " . $manu . "<br>\n";
                  if($diffdep) echo "$description Department changed to " . $department[$thisID] . "<br>\n";
               }
            }
         } // end if - checking new manufacturers
      } // end if submit = mandep
      else if($submit == dept)
      {
         foreach($newdept as $dept)
         {
            if(checkName($dept) && (strlen($dept) > 1))
            {
               $stmt = $cxn->prepare("INSERT INTO departments (name) VALUES (?)");
               $stmt->bind_param('s', $dept);
               if($stmt->execute()) echo "$dept added to departments<br>";
               $stmt->close();
            }
         }
      }
      else if($submit == manu)
      {
         foreach($newman as $man)
         {
            if(checkName($man) && (strlen($man) > 1))
            {
               $stmt = $cxn->prepare("INSERT INTO manufacturers (name) VALUES (?)");
               $stmt->bind_param('s', $man);
               if($stmt->execute()) echo "$man added to manufacturers<br>";
               $stmt->close();
            }
         }
      }
   }
   else
   {
      echo "<font color=RED>WARNING: This is a very crude interface which was written quickly. If you are not sure about anything, wait
             until you can find Michael to ask. Otherwise you can really f*\${ things up!</font><hr>";
   }
   echo "<hr>";
      

   // blank dept/manu items
   $sql = "SELECT * FROM items WHERE manufacturer='' AND department=''";
   $result = query($cxn, $sql);
   echo "<b>Items that need definition</b><p>
         <form action='mandepinv.php' method='post'>";
   $count = 0;
   while($row = mysqli_fetch_assoc($result))
   {
      if($count++ > 10) break;
      extract($row);
      
      echo "$ID - $description - Dept: ";
      displayDepartmentList($ID, $dep);
      echo " Manu: ";
      displayManufacturerList($ID, $man);
      echo "<p>\n";
   }
   echo "<input type='submit' name='submit' value='mandep'></form><hr>\n";


   // departments
   // first show existing ones
   $sql = "SELECT name FROM departments ORDER BY name";
   $result = query($cxn, $sql);

   echo "<b>Current Departments</b><p>
         Notice that there is sort of a system of hierarchy to these departments. Some day it will be more formalized. At the moment
         it's just a matter of dashes. Please try to use the hierarchy.<p>
         VAR are items which have variable prices<br>
         CONS are consignment items<p>";
   while($row = mysqli_fetch_assoc($result))
   {
      echo $row['name'] . "<br>";
   }
   
   echo "<form action='mandepinv.php' method='post'><br>
         New Departments:<br>
         <input name='newdept[0]' type='text'><br>
         <input name='newdept[1]' type='text'><br>
         <input name='newdept[2]' type='text'><br>
         <input name='newdept[3]' type='text'><br>
         <input name='newdept[4]' type='text'><br>
         <input type='submit' name='submit' value='dept'></form><hr>";
         
   // manufacturers
   // first show existing ones
   $sql = "SELECT name FROM manufacturers ORDER BY name";
   $result = query($cxn, $sql);

   echo "<b>Current manufacturers</b><p>";
   while($row = mysqli_fetch_assoc($result))
   {
      echo $row['name'] . "<br>";
   }

   echo "<form action='mandepinv.php' method='post'><br>
         New Manufacturers:<br>
         <input name='newman[0]' type='text'><br>
         <input name='newman[1]' type='text'><br>
         <input name='newman[2]' type='text'><br>
         <input name='newman[3]' type='text'><br>
         <input name='newman[4]' type='text'><br>
         <input type='submit' name='submit' value='manu'></form><hr>";
         
?>
