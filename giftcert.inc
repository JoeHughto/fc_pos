<?php
// giftcerts.inc

// returns the balance of gift cert ID or FALSE if there is no gift cert of that ID
function giftCertBalance($ID)
{
   $cxn = open_stream();
   
   $sql = "SELECT SUM(amount) FROM giftCerts WHERE giftCertID='$ID'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_row($result);
   return($row[0]);
}

// giftCertToAccount
// converts a gift certificate to account
function giftCertToAccount($giftCert, $member)
{
   $cxn = open_stream();
   
   $balance = giftCertBalance($giftCert);
   $unbalance = -$balance;
   if($balance > 0)
   {
      $stmt = $cxn->prepare("INSERT INTO giftCerts (giftCertID, transactionID, whenAcct, amount)
                                                     VALUES (?, ?, NOW(), ?)");
      $amount = -$amount;
      $stmt->bind_param("iid", $giftCert, $_SESSION['transaction_ID'], $unbalance);
      if($stmt->execute())
      {
         accountTransact($member, $balance, $_SESSION['transaction_ID'], "Redeem gift cert #$giftCert");
         return TRUE;
      }
      else
      {
         displayError("Failed to create Gift Certificate transaction. Error #1");
         return FALSE;
      }
   }
   else
   {
      return FALSE;
   }
}
            

// shows a list of all gift certificates including who sold them to whom and when
function showAllGiftCerts()
{
   $giftCert = new giftCert;
   
   for($giftCert->certNum = 1; $giftCert->displayInfo(); $giftCert->certNum++);
}

// this class sets up structures to send through a POST form various variables which start with GC
class giftCert
{
   var $name;
   var $newCert;
   var $notes;
   
   var $spentCert;
   var $certNum;

   
   function giftCert()
   {
      $this->name      = (checkName($_POST['GCname'])) ? $_POST['GCname'] : '';
      $this->newCert   = greaterThanZero($_POST['GCnewCert']);
      $this->certNum   = greaterThanZero($_POST['GCcertNum']);
      $this->notes     = (checkName($_POST['GCnotes'])) ? $_POST['GCnotes'] : '';
   }
   
   // displays the form which will send the proper post info, designed for use in register.php
   function displayForm()
   {
      echo "<b>Purchase Gift Certificate</b><br>
            Recipient: <input type='text' name='GCname' value='" . $this->name . "' size=20 maxlength=40><br>
            Value: \$<input type='text' name='GCnewCert' value='" . moneyND($this->newCert) . "' size=7 maxlength=7><br>";
   }

   // displays the information for this gift certificate
   function displayInfo()
   {
      $cxn = open_stream();

      $balance = giftCertBalance($this->certNum);
      if($balance)
      {
         $sql = "SELECT * FROM giftCerts WHERE giftCertID='" . $this->certNum . "'";
         if($result = query($cxn, $sql))
         {
            $row = mysqli_fetch_assoc($result); // the first one is done differently because it is where it is set up
            extract($row);
            echo "<b>Gift Certificate #" . $giftCertID . " details</b><br>
                  Recipient: $name<br>
                  Starting Value: " . money($amount) . "<br>";

            // now we show any transactions if any
            $first = TRUE;
            while($row = mysqli_fetch_assoc($result))
            {
               if($first)
               {
                  echo "Previous Gift Certificate Uses:<br>
                        <table border><tr><td>Date</td><td>Amount Spent</td></tr>";
                  $first=FALSE;
               }

               extract($row);
               $date = date_create("$whenAcct");
               echo "<tr><td>" . $date->format("F jS, Y, g:i A") . "</td><td>" . money(-$amount) . "</td></tr>";
            } // end while
            if(!$first) echo "</table><br>";

            echo "Remaining Balance: " . money($balance) . "<p>";
         } // end if
         else
         {
            displayError("Unable to retrieve Gift Certificate info<br>
                          SQL: $sql<br>
                          SQL Error" . $cxn->error);
            return FALSE;
         }
      } // end if balance
      else
      {
         echo "<font color=RED>There is no gift certificate #" . $this->certNum . "<p>";
         return FALSE;
      }
   } // end function
   
   // displays the form which will allow the user to select a gift certificate number
   function displaySelectForm()
   {
      echo "Redeem Gift Certificate #: <input type='text' name='GCcertNum' size=3 maxlength=3 value='" . $this->certNum . "'><br>";
   }

   // deducts the appropriate value from the gift certificate
   function redeem($amount)
   {
      $cxn = open_stream();
      $balance = giftCertBalance($this->certNum);
      if($amount <= $balance)
      {
         $stmt = $cxn->prepare("INSERT INTO giftCerts (giftCertID, transactionID, whenAcct, amount)
                                               VALUES (?, ?, NOW(), ?)");
         $amount = -$amount;
         $stmt->bind_param("iid", $this->certNum, $_SESSION['transaction_ID'], $amount);
         if($stmt->execute())
         {
            return TRUE;
         }
         else
         {
            displayError("Failed to create Gift Certificate transaction. Error #2<br>
                          GiftCertID: {$this->certNum}, Transaction number: {$_SESSION['transaction_ID']}, amount: $amount<br>
                          {$stmt->error}<p>");
            return FALSE;
         }
      }
      else if($balance) // if the gift cert exists but there is not enough in the account
      {
         echo "<font color=RED>Insufficient value in gift certificate.</font><br>
               Gift Certificate #" . $this->certNum . " value is ". money($balance) ."<br>
               You tried to spend " . money($amount) . "<p>";
         return FALSE;
      }
      else if(!$balance)
      {
         echo "<font color='RED'>No gift certificate #" . $this->certNum . "</font><p>";
         return FALSE;
      }
   } // end redeem

   // this creates a gift certificate. This should be called after payment is confirmed
   function sell()
   {
      $cxn = open_stream();
      
      $sql = "SELECT MAX(giftCertID) FROM giftCerts";
      $result = query($cxn, $sql);
      $row = mysqli_fetch_row($result);
      $max = $row[0] + 1;

      $stmt = $cxn->prepare("INSERT INTO giftCerts (name, giftCertID, transactionID, whenAcct, amount)
                                            VALUES (?, ?, ?, NOW(), ?)");
      $stmt->bind_param("siid", $this->name, $max, $_SESSION['transaction_ID'], $this->newCert);
      if($stmt->execute())
      {
         return($max);
      }
      else
      {
         echo "<font color='RED'>Error: $stmt->error</font><p>";;
         return (FALSE);
      }
   } // end function
}
?>
