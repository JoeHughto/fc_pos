<?php
// spendcredits.php
// This is the app with which member spend credits, including renewing membership.

// POST
// renew - for renewing membership
//   values: any integer - renew membership for that many months. If insufficent credits, renews as many as possible
//                         if no previous wExp, then starts from today. If wExp, renews from then unless wExp is more than
//                         60 days previous

// Versions
// 1.0 Allows members to renew membership

   include('funcs.inc');
   include('credits.inc');
   include('friendcomputer.inc');
   include('header.php');
   $cxn = open_stream();
   
   $SID = $_SESSION['ID'];

   // determine current number of credits
   $credits = getCreditTotal($SID);

   if(isset($_POST))
   {
      extract($_POST);

      // renew working membership
      if($renew > 0)
      {
         // reduce number of months if necessary
         $creditCost = $renew * $WORKINGMEMBERCREDITS;
         if($creditCost > $credits)
         {
            $renew = $credits / $WORKINGMEMBERCREDITS;
            $renew = floor($renew);
            $reduction = TRUE; // a flag so we know it was changed
         }
         
         if($renew > 0)
         {
            renewMembership($SID, $renew);
            if($reduction == TRUE)
            {
               $message .= "Due to lack of credits, Friend Computer has reduced the number of months you will renew for.<br>";
            }
            
            if($renew == 1)
               $message .= "Membership renewed for one month.<p>";
            else
               $message .= "Membership renewed for $renew months.<p>";
         }
         else
         {
            $message .= "You lack sufficient credits to renew membership.<p>";
         }
      } // end renewing membership
   } // end using POST
   
   fcMessage($message);

   $credits = getCreditTotal($SID);
      
   echo "You currently have $credits Credits in your account<p>";
   displayMembershipStatus($SID);
   echo "<font size=+1>Spend Credits with Friend Computer</font><br>
         <form action='spendcredits.php' method='post'>
         Renew Membership for <input type='text' name='renew' size=3 maxlength=3> months for 15 Credits per month.<p>
         <input type='submit' name='submit' value='submit'>
         <input type='submit' name='submit' value='dominate'></form><p>";
   $version = '1.0';
   include('footer.php');
?>
