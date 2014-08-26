<?php
/**
 * Calculator.php is a page that calculates percentage discounts as needed.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Transfer_Form
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/calculator.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * Nothing!
 */
$title = "Calculator";
require_once 'header.php';

/**
 * Possible Arguments:
 * POST:
 *   submit - When this variable = 'Submit', the button has been pressed, so
 *     we should attend to the data, and ship some store credit.
 *   base - This is the original price of the item/order that we're trying
 *     to discount.
 *   target - This is the price we want to discount the item/order to.
 */
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