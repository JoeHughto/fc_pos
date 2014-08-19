<?php
// preorders.inc
// functions for preorders

// displayPreorders
// displays a list of preorders in a POST form which refers back to $action
// if $action==0 then it does not produce a form
function displayPreorders($action)
{
   $cxn = open_stream();
   $sql = "SELECT * FROM preorders WHERE itemID IS NULL AND releaseDate > NOW() ORDER BY releaseDate, orderDate"; // get all preorders that have not come in yet
   $result = query($cxn, $sql);

   $out = '';
   
   $out = "<table border cellpadding=3><tr>";
   if($action != 0) $out .= "<td></td>";
   $out .= "<td><b>Release Date</b></td><td><b>Order Deadline</b></td>
           <td><b>Product Name</b></td><td><b>Price</b></td><td><b>Disc.<br>Price</b></td></tr>\n";
   while($row = mysqli_fetch_assoc($result))
   {
      extract ($row);
      
      $rDate = date_create($releaseDate);
      $rDateOut = $rDate->format("M j, Y");
      $oDate = date_create($orderDate);
      $oDateOut = $oDate->format("M j, Y");
      $orderPrice = round(($price * .85 * 1.05), 2);
      
      if($action != 0)
      {
         if($oDate >= date_create())
         {
            $out .= "<tr><td>
<form target='paypal' action='https://www.paypal.com/cgi-bin/webscr' method='post'>
<input type='image' src='http://www.worldsapartgames.org/data/addtocart.png' border='0' name='submit' alt='PayPal - The safer, easier way to pay online!'>
<img alt='' border='0' src='https://www.paypal.com/en_US/i/scr/pixel.gif' width='1' height='1'>
<input type='hidden' name='add' value='1'>
<input type='hidden' name='cmd' value='_cart'>
<input type='hidden' name='business' value='online@pvgaming.org'>
<input type='hidden' name='item_name' value='$description'>
<input type='hidden' name='item_number' value='$ID'>
<input type='hidden' name='amount' value='$orderPrice'>
<input type='hidden' name='shipping' value='0.00'>
<input type='hidden' name='no_shipping' value='1'>
<input type='hidden' name='return' value='http://www.worldsapartgames.org/thankyou.php'>
<input type='hidden' name='logo_custom' value='http://www.worldsapartgames.org/data/addtocart.png'>
<input type='hidden' name='no_note' value='1'>
<input type='hidden' name='currency_code' value='USD'>
<input type='hidden' name='tax' value='0.00'>
<input type='hidden' name='lc' value='US'>
<input type='hidden' name='bn' value='PP-ShopCartBF'>
</form></td>";
         }
         else
         {
            $out .= "<tr><td></td>";
         }
      }
      else $out .= "<tr>";
      
      $out .= "<td>$rDateOut</td><td>$oDateOut</td><td>$description</td><td>\$" . sprintf("%01.2f", $price)  . "</td><td>\$" . sprintf("%01.2f", $orderPrice) . "</td></tr>\n";
   }
   
   $out .= "</table>\n";
   if($action != 0) $out .= "
<form target='paypal' action='https://www.paypal.com/cgi-bin/webscr' method='post'>
<input type='hidden' name='cmd' value='_cart'>
<input type='hidden' name='business' value='online@pvgaming.org'>
<input type='image' src='data/conclude.png' border='0' name='submit' alt='PayPal - The safer, easier way to pay online!'>
<input type='hidden' name='display' value='1'>
</form>\n";
   $out .= "</form>";
   
   return($out);
}