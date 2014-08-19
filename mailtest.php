<?php
// gmail testing program

$message = "This is a test message";
$subject = "Test Message";
$to = "metageekcast@gmail.com";
$header = "from: newsletter@pvgaming.org
Reply-To: newsletter@pvgaming.org
Precedence: bulk
X-Mailer: PHP/" . phpversion();

if(mail($to, $subject, $message, $header))
   echo "It is done";
else
   echo "It is FAIL!";

?>

