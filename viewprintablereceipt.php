<?php
//viewprintablereceipt.php
//shows a printable receipt with nothing else

include('funcs.inc');
include('inventory.inc');
?>

<HTML>
<HEAD>
<TITLE>View Receipt</TITLE>
</HEAD>
<BODY>
<img src="../data/header.jpg"><p>

<?php
displayReceipt($_GET['ID']);
?>
</BODY>
</HTML>