<?php
/**
 * @file visibleitem.php
 * @brief visibleitem.php is a page allowing people with inventory privileges to hide/unhide items in the register.
 *
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - printMemberString()
 *
 * member.inc:
 * - taxExempt()
 * - memberSalesThisMonth()
 * - memberSalesLastMonth()
 * - FG_discount()
 * - FG_discountNow()
 *
 * @link http://www.worldsapartgames.org/fc/visibleitem.php @endlink
 *
 * @author    JoeHughto
 * @copyright 2016 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     New as of August 2016
 */
$securePage = true;
include('funcs.inc');
include('header.php');
include('inventory.inc');
$cxn = open_stream();

if($_SESSION['inv'] != 1) {
    echo "You must have Inventory priviledges to use this page!<br>";
    include('footer.php');
    die();
}
echo "This is a utility page for hiding items from the register.  It is not pretty.  For now, you can only hide items.  If you need to unhide an item, please contact Joe.<hr>";

echo "<form action='visibleitem.php' method='POST'>";
if($_POST['submit'] == 'submit' && is_array($_POST['ID']))
   {
       foreach($_POST['ID'] as $i => $name)
       {
//           echo "$i<br>";
           $sql = "UPDATE items SET visible=0 WHERE ID='$i'";
           if(query($cxn, $sql))
               echo "$name set as hidden<br>";
       }
   } // end if      
if($_POST['search'] == 'search' && strlen($_POST['sku']) > 0) {
    $skuList = explode("\n", trim($_POST['sku']));
    if (end($skuList) == '') {
        array_pop($skuList);
    } // remove a trailing empty sku
    
    foreach ($skuList as $s) {
        $s = trim($s);
        $s = $cxn->real_escape_string($s);
        $sql = "SELECT description, department, manufacturer, price, visible, ID FROM items WHERE (UPC='$s' OR alternate1='$s' "
            . "OR alternate2='$s' OR ID='$s' OR description LIKE '%$s%') "
            . "ORDER BY description";
        $result = query($cxn, $sql);
        $affected = mysqli_affected_rows($cxn);

        // if the item does not already exist, we put it in a list to report as invalid
        if ($affected == 0) {
            $invalidID .= (isset($invalidID)) ? ', ' . $s: $s;
        } elseif (($some = mysqli_affected_rows($cxn)) > 0) {
            // multiple items can use the same sku because sometimes
            // companies screw up like that, so we can deal with it
            // this displays each of the items with that sku and lets the
            // user pick which one and enter the info at the same time
            echo "<table border><tr><td><b>Title</b></td><td><b>Department</b></td><td><b>Manufacturer</b></td><td><b>Price</b></td><td><b>Visible</b></td><td><b>Hide?</b></td></tr>";
            while ($row = mysqli_fetch_row($result)) {
                $visible=$row[4]?"Yes":"No";
                echo "<tr><td>$row[0]</td><td>$row[1]</td><td>$row[2]</td><td>$row[3]</td><td>$visible</td><td><input type='checkbox' name='ID[$row[5]]' ";
                if ($visible == 'No') echo "checked='checked' ";
                echo "value='$row[0]'>";
                echo"</td></tr>";
//                $onSale = salePrice($salePrice);
//                echo "Quantity <input type='text' name='qty[$ID]' size=4 maxlength=4> <b>$ID</b>: $description<br>" . "
//                        Price \$";
//                printf("%01.2f", $price);
//                echo "<p>";
            }
            echo "<button name='submit' value='submit'>Submit</button>";
        }
    }
    if (isset($invalidID)) {
        // if there are any invalid skus, we show them now
        echo "The following lookups were not found in the system:<br>$invalidID<p>";
        echo "Enter skus in this box: <br>";
        echo "<textarea cols=20 rows=7 name='sku'></textarea><br>";
        echo "<button name='search' value='search'>Search</button>";
    }
} else {
    echo "Enter skus in this box: <br>";
    echo "<textarea cols=20 rows=7 name='sku'></textarea><br>";
    echo "<button name='search' value='search'>Search</button>";
}

echo "</form>";
?>