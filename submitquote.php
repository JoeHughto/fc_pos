<?php
// submitquote.php
// allows any user with reg authority to add quotes

include('funcs.inc');
include('header.php');

$cxn = open_stream();

if($_SESSION['reg'] != 1)
{
   echo "You need Register Permission to add quotes.<p>";
   include('footer.php');
   die();
}

if(strlen($_POST['quote']) > 5)
{
   $quote = strip_tags($_POST['quote']);
   $quote = ereg_replace("\n", "<br>", $quote);
   $stmt = $cxn->prepare("INSERT INTO quotes (quote, author) VALUES (?,?)");
   $stmt->bind_param("sd", $quote, $_SESSION['ID']);
   if($stmt->execute())
   {
      echo "Quote Submitted<br>$quote<hr>";
   }
   else
   {
      echo "Error submitting quote<hr>";
   }
}

echo "<h1>Submit Quote</h1><br>
These quotes will go on the main page, and we keep track of who entered them, so don't be stupid.<br>
<form action='submitquote.php' method='post'>
Quote:<br>
<textarea rows=5 cols=40 name='quote'></textarea><br>
<input name='submit' value='submit' type='submit'>
</form><hr>";

echo "<h2>Quotes in database</h2><br>";
$sql = "SELECT quote FROM quotes";
$result = query($cxn, $sql);
while($row = mysqli_fetch_assoc($result))
{
   extract($row);
   echo "$quote<hr>";
}

include('footer.php');
?>