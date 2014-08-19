<?php
// submitquote.php
// allows any user with reg authority to add quotes

include('funcs.inc');
include('header.php');
include('proxygen.inc');
$cardData = ProxyGen::cardSearch("Phalanx Leader");
ProxyGen::printCard($cardData, 0);
ProxyGen::printCard($cardData, 1);
$cardData = ProxyGen::cardSearch("Fabled Hero");
ProxyGen::printCard($cardData, 0);
$cardData = ProxyGen::cardSearch("test");
ProxyGen::printCard($cardData, 0);
$cardData = ProxyGen::cardSearch("eye for an eye");
ProxyGen::printCard($cardData, 0);
$cardData = ProxyGen::cardSearch("Jace, Architect of Thought");
ProxyGen::printCard($cardData, 0);
?>