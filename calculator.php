<?php
/**
 * @file calculator.php
 * @brief calculator.php is a page that calculates percentage discounts as needed.
 * 
 * This file includes:
 * Nothing!
 * 
 * Possible Arguments:
 * POST:
 * - submit - When this variable = 'Submit', the button has been pressed, so
 *   we should attend to the data, and ship some store credit.
 * - base - This is the original price of the item/order that we're trying
 *   to discount.
 * - target - This is the price we want to discount the item/order to.
 * 
 * @link http://www.worldsapartgames.org/fc/calculator.php @endlink
 *
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = "Calculator";
$version = "1.8d";
require_once 'header.php';

if ($_POST['submit'] == 'Submit') {
    $base = $_POST['base'];
    $target = $_POST['target'];
    $discount = ($base - $target) / $base;
    $discount *= 100;
    echo "Not Counting Tax Discount: $discount<p>";
   
    $base *= 1.0625;
    $discount = ($base - $target) / $base;
    $discount *= 100;
    echo "With Tax Discount: $discount<hr>";
}

echo "<b>Calculator</b><p>
<form action='calculator.php' method='post'>
Base Price: <input name='base' type='text' size=8 maxlength=8><br>
Target Price: <input name='target' type='text' size=8 maxlength=8><br>
<input type=submit name='submit' value='submit'></form>";

require_once 'footer.php';
?>