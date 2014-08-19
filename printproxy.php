<?php
// submitquote.php
// allows any user with reg authority to add quotes

include('funcs.inc');
include('proxygen.inc');
$cardData = ProxyGen::cardSearch("watery grave");
ProxyGen::printCard($cardData, 0);

//IF POST for each object pushed, echo all current card
if ($_POST)
    var_dump($_POST);
?>