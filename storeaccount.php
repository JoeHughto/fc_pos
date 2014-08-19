<?php
// storeaccount.php
// allows user to add money to an account

// when money is put into an account for prepayment, the transactionID is 0
// when money is put in for some other reason, the transactionID is -1. This can only be done by "off" in future versions
// until "off" is added, it will require "mem"

// this means that when the computer counts how much money should be in the register, it should take the total of sales plus
// account changes with TID 0

// POSTs as scalar or array
// amount - amount that is to be added to account
// item - items which are being prepaid for

// GET
// qty - how many items is it set up for (default 5)

// member(scalar) - member whose account is being altered

   $title = "Give Account (no cash)";
   include('funcs.inc');
   include('friendcomputer.inc');
   include('header.php');
   $cxn = open_stream();
   $message = '';

   if($_SESSION['eve'] != 1 && $_SESSION['adm'] != 1)
   {
      die("You must have Admin or Event Permission to use this application");
   }

   if(isset($_POST['submit']) && $_POST['member'] > 0)
   {
      extract($_POST);

      $message .= "Submitting Orders for Member #$member<br>";
      
      if(!is_array($amount)) $amount[0] = $amount;
      if(!is_array($item)) $item[0] = $item;

      // submit into account
      $a = array_sum($amount);
      if($a > 0)
      {
         foreach($item as $name)
         {
            $notes .= (strlen($name) > 1) ? $name . ',' : '';
         }

         $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, NOW(), ?, ?)");
         $stmt->bind_param("dds", $member, $a, $notes);
         if($stmt->execute())
         {
            $message .= $notes . " for $a put in Member's account<br>";
            $stmt->close();
            $accountFail = FALSE;
         }
         else
         {
            $message .= "Failed to submit account for ". $notes . "<br>";
            $stmt->close();
            $accountFail = TRUE;
         }
      } // end if a > 0

      
      // submit special order/requests
      if($accountFail != TRUE)
      {
         foreach($item as $num => $it)
         {
            if($item[$num] == '') continue;
            // make sure that $a is legit but allow it to be 0 also
            $a = ($amount[$num] > 0) ? $amount[$num] : 0;
         
            if($a >= 0)
            {

               $stmt = $cxn->prepare("INSERT INTO specialOrders (custID, dateMade, item, price) VALUES (?, NOW(), ?, ?)");
               $stmt->bind_param("dsd", $member, $item[$num], $a);
               if($stmt->execute())
               {
                  $message .= ($a > 0) ? "Special order for " . $item[$num] . " submitted<br>"
                                       : "Request for " .$item[$num] . "submitted<br>";
               }
               else
               {
                  $message .= "Failed to submit " . $item[$num] . "<br>";
               }
            } // if $a >= 0
         } // foreach item
      } // if account fail
   }
   else if(isset($_POST['dominate']))
   {
      extract($_POST);
      $stmt = $cxn->prepare("INSERT INTO storeAccount (memberID, whenAcct, amount, notes) VALUES (?, NOW(), ?, ?)");
      $stmt->bind_param("dds", $member, $other, $notes);
      if($stmt->execute())
      {
         $notes = strip_tags($notes);
         $message .= "Account increased by \$$other for #$member<br>
                      Reason: $notes";
      }
      else
      {
         $message .= "Failed to submit account increase for $member<br>";
      }
      $stmt->close();
   } // else if dominate
   else if(($_POST['submit'] == 'submit') && !($member > 0))
   {
      $message .= "Unable to submit order for no member<br>";
   }

   fcMessage($message);

/*   echo "Special Orders<p>
         Select Customer Name:";
   echo "<form action='storeaccount.php' method='post'>\n";
   selectMember('member', '');
   echo "<p>
         Enter the price prepaid for the item. Enter 0 if it is a request, not a special order.<br>
         If a price is included, it is assumed that cash was taken at this time.<p>\n";

   $qty = (isset($_GET['qty'])) ? $_GET['qty'] : 5;
   
   for($i = 1; $i <= $qty; $i++)
   {
      echo "Item: <input type='text' name='item[$i]' size=40 maxlength=100>
            \$<input type='text' name='amount[$i]' size=8 maxlength=8><p>\n";
   }
   
   echo "<input type='submit' name='submit' value='submit'></form><form action='storeaccount.php' method='post'><p>\n";
*/   
   if($_SESSION['eve'] == 1 || $_SESSION['adm'] == 1)
   {
      echo "<form action='storeaccount.php' method='post'>\n";
      echo "<b>Giving account for other reasons</b><br>";
      selectMember('member', '');
      echo "\$<input type='text' name='other'><br>
            Reason: <input type='text' name='notes'><br>
            <input type='submit' name='dominate' value='dominate'></form><p>\n";
   }
   
   include('footer.php');
?>
