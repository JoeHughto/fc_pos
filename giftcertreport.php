<?php
// giftcertreport

// shows all gift certificates

require_once 'funcs.inc';
require_once 'giftcert.inc';
$title = 'Gift Certificate Report';
require 'header.php';
$giftCert = new giftCert;

echo "<p>";
showAllGiftCerts();

require 'footer.php';
?>
