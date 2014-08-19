<?php
// giftcertreport

// shows all gift certificates

   include ('funcs.inc');
   include ('giftcert.inc');
   $title = 'Gift Certificate Report';
   include ('header.php');
   $giftCert = new giftCert;

   echo "<p>";
   showAllGiftCerts();
   
   include ('footer.php');
?>
