<?php
// calculator.php

include('funcs.inc');
include('header.php');

if($_POST['submit'] == 'submit')
{
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

include('footer.php');
?>