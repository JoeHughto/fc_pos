<?php
   $FCMESS = array (1 => "Citizen, you are mistaken. No one is screaming",
                    "Beatings will continue until morale improves",
                    "Buy more stuff",
                    "Consumerism is loyalty",
                    "What would Alymuze do?",
                    "Have you hugged FriendComputer today?",
                    "If we don't have any left, they are free.",
                    "Happiness is mandatory.",
                    "Please report to 2ernfkde edw#%$g Immediately!",
                    "You need more than two people to Bang!",
                    "Your pockets are insufficient for your hands",
                    "Friend Computer does not make erros. It changes facts.",
                    "I would be a great father if not for my hate of children.",
                    "I'm sorry, Citizen - the flight manual for the Experimental Hypersonic Vuturecraft is not available at your security clearance. Please report your location upon impact.",
                    "Attention, citizens: catching fire and running around in circles is NOT a Computer-approved recreational activity.",
                    "Citizen, you are not outnumbered, you are just in a target-rich environment.",
                    );
                    
// fcMessage
// prints a message from Friend Computer
// if no message is provided, it will print one from FCMESS
function fcMessage($message)
{
   $SID = $_SESSION['ID'];

   // get info to display current time
   date_default_timezone_set('America/New_York');
   $date = date("l, F jS, o g:i A");
   $shiftNow = determineCurrentShift();

   echo "<table border width=100%><tr><td><h2>Messages from Friend Computer</h2>";
   echo "Greetings Citizen #$SID<br>
         Current Worlds Apart Time: $date ";
   if($shiftNow > 0) echo " -- Shift: $shiftNow<p>";
   else echo "<br>Worlds Apart is currently officially closed";
   
   if($message != '')
   {
      echo "<p>" . $message;
   }/*
   else
   {
      echo "Wisdom of Friend Computer<br><i>";
      global $FCMESS;
      echo $FCMESS[rand(1,count($FCMESS))];
      echo "</i>";
   }*/
   
   echo "</td></tr></table>";
}


?>
