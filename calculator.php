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
 * @author    Crideke Crouch 
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
require 'header.php';

if ($_POST['submit'] == 'submit') {
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

require 'footer.php';
?>