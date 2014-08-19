<?php
// register.php
// This app displays a textbox for skus and specific quantity boxes for specific items.
// The textbox passes back a string of skus. The item qty boxes pass vars called qty[$ID] with a quantity number
// There is also a pulldown for member name and fields for entering guest members. The pulldown will automatically generate
// discounts

// this app does not extract POST

// The second round, it shows a list of items with quantity that can be selected (default 1)

// OUTLINE
// send bug report
// ADM set date
// use set invEventID
// Set invEventID as one in progress or new one
// POST Data
//   look for quick add of new member
//   set qty for singles
//   apply discount
//   check member info
//   convert textarea data
//   process duplicate IDs
//   process skus if any
//   special items with qty box
//   process items which may have had qty change
//      apply bulk discounts
// existing items which were not in POST
// display total
// display member account/credit balance
// complete transaction if applicable
// display form
// POST data
// member - duh
// Arrays indexed by ID: sku, ID, quantity

// VERSIONS
// 1.2 Adds ability to select member-customers and process discounts
// 1.3 Adds variable price items and magic singles. Also includes Magic bulk discount
// 1.31 Adds bug reporting
// 1.32 Adds ability to set date if you have ADM
// 1.33 Adds drafts and draft ncs and redoes singles


   include ('funcs.inc');
   include ('member.inc');
   include ('inventory.inc');
   include ('credits.inc');
   include ('giftcert.inc');
   include ('friendcomputer.inc');
   $title = 'In Store Sales';
   include ('header.php');
   $noRefresh = noRefreshCheck(); // to bypass this, uncomment next line
   $noRefresh = TRUE;
   $specOrder = new specialOrder;
   $newMember = new newMember;
   $giftCert = new giftCert;

   if(!$noRefresh && $_POST['close'] == 1)
   {
      echo "<table><tr><td bgcolor=RED><font color=WHITE><b>Notice:<br>Page refresh attempted</b></font></td></tr></table>";
      echo "<a href='register.php'>Click here for new transaction</a>";
      include('footer.php');
      die();
   }

   echo"<hr>";

   // if there is a bug report, we send it
   if(strlen($_POST['bug']) > 2)
   {
      reportBug('register.php');
   }

   if($_SESSION['reg'] != 1)
   {
      echo "You must have Register Use permissions to run the register.<p>";
      include('footer.php');
      die();
   }

   // allow the user to enter a date in get and then allow it to stick around as we update
   if(isset($_POST['date']))
   {
      $_GET['date'] = $_POST['date'];
   }
   
   if(isset($_GET['date']))
   {
      if(!checkDateNum($_GET['date']))
      {
         echo "<table border><tr><td><b>Message from Friend Computer<p>
               You have attempted to enter an invalid date of " . $_GET['date'] . ". It has been eliminated.</td></tr></table><p>";
         unset($_GET['date']);
      }
      else
      {
         $specDate = $_GET['date'];
         echo "<table border><tr><td><b>Message from Friend Computer<p>
               You have designated the date for this transaction to be " . $_GET['date'] . "</td></tr></table><p>";
      }
   }


   $cxn = open_stream();

   // if an invEvent_ID was provided, use it
   if(isset($_GET['ID']) || isset($_POST['IEID']))
   {
      $ID = (isset($_GET['ID'])) ? $_GET['ID'] : $_POST['IEID'];
      $sql = "SELECT staffID, closed, customerID FROM transaction WHERE ID='$ID'";
      $result = query($cxn, $sql);
      $row = mysqli_fetch_assoc($result);
      if($row['closed'] == 0 && $row['staffID'] == $_SESSION['ID'])
      {
         $_SESSION['transaction_ID'] = $ID;
                     $_POST['member'] = $row['customerID'];
      }
      else
      {
         if($row['closed'] != 0) echo "Transaction #$ID already closed<br>";
         if($row['staffID'] != $_SESSION['ID']) echo "Only original user may resume transaction<br>";
      }
   } // end if
   
   // check to see if there is already an active Inventory Event in progress in this session. If not, assign one
   if(!($_SESSION['transaction_ID'] > 0))
   {
      $sql = "INSERT INTO transactions
                          (staffID, closed)
                          VALUES
                          (" . $_SESSION['ID'] . ", 0)";
      if(mysqli_query($cxn, $sql))
      {
         $sql = "SELECT ID
                   FROM transactions
                  WHERE ID=(SELECT MAX(ID) FROM transactions)
                    AND staffID=" . $_SESSION['ID'];
         if($result = mysqli_query($cxn, $sql))
         {
            $row = mysqli_fetch_assoc($result);
            $_SESSION['transaction_ID'] = $row['ID'];
         }
         else
         {
            displayErrorDie("Error #3: error getting transaction_ID<br>
                          SQL query: $sql<br>
                          SQL Error: " . mysqli_error($cxn));
         } // end else
      }
      else
      {
         displayErrorDie("Error #3: error getting transaction_ID<br>
                       SQL query: $sql<br>
                       SQL Error: " . mysqli_error($cxn));
      }// end else
   }

   // $TID is just an alias
   $TID = $_SESSION['transaction_ID'];
   echo "Transaction #" . $TID . "<p>\n";
   echo "<form action='register.php' method='post'>\n";

   // check for POST data
   if((isset($_POST['submit'])) || ($_POST['close'] == 1))
   {
      // Look for QUICK ADD of NEW MEMBER
      // give 5% discount if an email address was given
      if($newMember->process()) $_POST['discount'] += 5;
   
      // Magic Singles
      if($_POST['price'][778] > 0)
      {
         $_POST['qty'][778] = 1;
      }

      $discount = $_POST['discount'];
      if(!($discount > 0) || ($discount > 40)) $discount = 0; // check to make sure discount is proper

      // check member information
      $member = $_POST['member'];
      $memDiscount = memberDiscount($member); // defined in inventory.inc
      $discount += $memDiscount;
      
      // spend credits for ten percent discount
      if($_POST['cred-ten'] == 1)
      {
         echo "50 Credit 10% discount is selected<p>";
         $discount += 10;
         if($discount > 40) $discount = 40;
      }


      // take the list from the textarea and turn it into a useful array
      if(strlen($_POST['sku']) > 0)
      {
         $skuList = explode("\n", trim($_POST['sku']));
         if(end($skuList) == '') array_pop(&$skuList); // remove a trailing empty sku
      }

      // add all the duplicate IDs to the sku list so they will be displayed
      // meaning skus for which there are multiple items
      if(is_array($_POST['splitID']))
      {
         foreach($_POST['splitID'] as $thisID)
         {
            array_push(&$skuList, $thisID);
         }
      }

      // if there are skus to be processed, we process them and display the data
      // this means if they are new we ask for all the data
      // they are already existing items, we ask for price, quantity and cost
      if(is_array($skuList))
      {
         $first = TRUE; // if this is true and affected is >0, then it will display the header

         $newItems = array();
         foreach($skuList as $s)
         {
            $s = trim($s);
            $stmt = $cxn->prepare("SELECT ID, price, description 
                                     FROM items WHERE UPC=? OR alternate1=? OR alternate2=? OR ID=? OR description LIKE ?");
            $slike = "%$s%";
            $stmt->bind_param("sssss", $s, $s, $s, $s, $slike);
            $stmt->execute();
            $stmt->bind_result($ID, $price, $description);
            $affected = $stmt->affected_rows;
            
            // if the item does not already exist, we put it in a list to report as invalid
            if($affected == 0)
            {
               $invalidID .= (isset($invalidID)) ? ', ' . $s: $s;
            }

            else if($affected == 1) // if there is exactly one match
            {
               // check to see if that item is already on the order
               // if it is, add one to qty
               $stmt->fetch();
               if(is_array($_POST['qty']) && array_key_exists($ID, $_POST['qty']))
               {
                  $thisID = $ID;
                  $_POST['qty'][$thisID]++; // if they enter it again, they might want it again

                  continue;
               }
               else
               {
                  $thisID = $ID;
                  $_POST['qty'][$thisID] = 1;
               }
               
               $price = round($price, 2);
               echo "<p>";
            }

            // multiple items can use the same sku because sometimes companies screw up like that, so we can deal with it
            // this displays each of the items with that sku and lets the user pick which one and enter the info at the same time
            else if($affected > 1)
            {
               echo "<b>You have $affected items with the same lookup of '$s'</b><br>
                     Check the ones you would like to use and enter quantity.";
               while($stmt->fetch())
               {
                  $price = round($price, 2);
                  echo "<input type='checkbox' name='splitID' value='$ID'>
                        Quantity <input type='text' name='qty[$ID]' size=4 maxlength=4>
                        <b>$ID</b>: $description<br>
                        Price \$";
                  printf("%01.2f", $price);
                  echo "<p>";
               } // end while
            } // end else if
            
            $stmt->close();
         } // end foreach
      } // end if is array skulist
      
      // Here, we look at the special items that have their own qty box
      $specqty = $_POST['specqty'];
      if(is_array($specqty))
      {
         foreach($specqty as $ID => $sq)
         {
            if($sq > 0) $_POST['qty'][$ID] += $sq;
         }
      }

      // This section looks at items which have had the chance to have qty change
      $first = TRUE;
      $qty = $_POST['qty'];
      if(is_array($qty))
      {
         // for checking to see if it already exists
         $checkID = $cxn->prepare("SELECT ID FROM soldItem WHERE transactionID=? AND itemID=?");
         $checkID->bind_param('ii', $TID, $thisID);

         // for setting up new soldItem rows
         $setSI = $cxn->prepare("INSERT INTO soldItem
                                 (transactionID, itemID, price, qty, cost, tax)
                                 VALUES
                                 ('$TID', ?, ?, ?, ?, ?)");
         $setSI->bind_param('ididd', $thisID, $p, $q, $c, $tax);
            
         // for updating in case it already exists
         $updateSI = $cxn->prepare("UPDATE soldItem
                                       SET price=?,
                                           qty=?,
                                           cost=?,
                                           tax=?
                                     WHERE itemID=?
                                       AND transactionID=?");
         $updateSI->bind_param('diddii', $p, $q, $c, $tax, $thisID, $TID);

         // for getting the info for displaying
         $displayItem = $cxn->prepare("SELECT description FROM items WHERE ID=?");
         $displayItem->bind_param('i', $thisID);
         $displayItem->bind_result($description);
            
         // to acquire price and cost
         $getPC = $cxn->prepare("SELECT price, cost, tax, department FROM items WHERE ID=?");
         $getPC->bind_param('i', $thisID);
         $getPC->bind_result($p, $c, $tax, $dept);
            
         echo"<p>";

         foreach($qty as $thisID => $q)
         {
            // if the quantity is set to 0, then the item is taken off the list
            if($q <> 0)
            {
               queryB($checkID); // SELECT ID FROM soldItem WHERE transactionID=? AND itemID=?
               $checkID->fetch();
               queryB($getPC); // SELECT price, cost, margin, tax, department FROM items WHERE ID=?
               $getPC->fetch();
               
               // apply margin cost
               if($margin > 0)
               {
                  $cost = $p * ((100 - $margin) / 100);
               }

               // check for Magic discount
               $p = round($p, 2);
               if(eregi("magic", $dept) && $p == 3.99)
               {
                  if($q == 1) $p = 3.99;
                  else if ($q >= 2 && $q <= 4) $p = 3.79;
                  else if ($q >= 5 && $q <= 9) $p = 3.49;
                  else if ($q >= 10 && $q < 36) $p = 3.29;
                  else if ($q >= 36) $p = 2.63;
               }
               
               // check for used novel discount
               if($thisID == 1148)
               {
                  if($q >= 1 && $q <= 2) $p = 2;
                  else if($q >= 3 && $q <= 9) $p = 1 && $c = .4;
                  else if($q >= 10) $p = .80 && $c = .32;
               }

               // setting p for VAR items before we save the itemSold
               if(ereg("^VAR", $dept))
               {
                  $p = (isset($_POST['price'][$thisID])) ? $_POST['price'][$thisID] : $p;
               }

               if($checkID->affected_rows == 0)
               {
                  queryB($setSI);
               }
               else
               {
                  queryB($updateSI);
               }

               // then we display it
               queryB($displayItem);
               $displayItem->fetch();

               if(ereg("^VAR", $dept))
               {
                  displayRegisterItemPrice($thisID, $qty[$thisID], $p, $description);
               }
               else
               {
                  displayRegisterItem($thisID, $qty[$thisID], $p, $description);
               }

               // set post[qty] so that it is not displayed again
               $_POST['qty'][$thisID] = $qty[$thisID];
            } // end if q<>0

            // if q is 0 we remove it
            else
            {
               $removeItem = $cxn->prepare("DELETE FROM soldItem WHERE itemID='$thisID' AND transactionID='$TID'");
               $removeItem->execute();
            }
            
         }
         $checkID->close();
         $setSI->close();
         $updateSI->close();
         $displayItem->close();
         $getPC->close();
      }  // if is array qty
   }
   
   // Now we look at all items being sold which are not represented in post.
   // anything in the post will have a qty[ID], so we don't display those
   $stmt = $cxn->prepare("SELECT i.description description,
                                 s.itemID,
                                 s.price,
                                 s.qty
                            FROM soldItem s
                            JOIN items i
                              ON s.itemID=i.ID
                           WHERE transactionID='$TID'
                        ORDER BY description");
   $stmt->bind_result($description, $thisID, $p, $qty);
   queryB($stmt);
   while($stmt->fetch())
   {
      if((($_POST['submit'] != 'submit')
       && ($_POST['close'] != 1))
     || (is_array($POST['qty']) && !array_key_exists($thisID, $_POST['qty'])))
      {
         $p = round($p, 2);
         displayRegisterItem($thisID, $qty, $p, $description);
      }
   }
   $stmt->close();

   // Lastly display snack card for credits if selected
   if($_POST['cred-snack'] == 1)
   {
      echo "<b>Ordered:</b> Snack Card for 10 Credits<p>";
   }
   
   // Finish displaying items and display total

   if(!isset($invalidID)) // if it's all good, determine total and display it
   {
      // we go through the itemSold list one by one so we can tally them up
      $price = round($price, 2);
      $cost = round($cost, 2);
      $reviewSold = $cxn->prepare("SELECT itemID, price, qty, cost, tax FROM soldItem WHERE transactionID='$TID'");
      $reviewSold->bind_result($itemID, $price, $qty, $cost, $tax);
      queryB($reviewSold);


      // initializing variables
      $totalCost = 0;
      $totalPrice = $specOrder->total + $giftCert->newCert; // this is added without applying discount and such
      $totalTax = 0;

      // make the discount a multiplier
      $discMult = ((100 - $discount) / 100);

      $itemIDs = array();
      $qtys = array();

      while($reviewSold->fetch())
      {
         // running tally
         $totalPrice += round($price * $qty * $discMult, 2);
         $totalTax += ($tax == 1) ? round($price * $qty * $discMult * $TAXRATE , 2) : 0;

         // if we are closing, we'll need a list of itemIDs so we can run through them to update quantities below
         if($_POST['close'] == 1)
         {
            // apply margin cost
            if($margin > 0)
            {
               $cost = $p * ((100 - $margin) / 100);
            }
            
            array_push(&$itemIDs, $itemID);
            array_push(&$qtys, $qty);
            $totalCost += $cost * $qty;
         }
      } // end while

      $reviewSold->close();

      // display member account/credit balance
      if($_POST['member'] > 0)
      {
         $accountLeft = getAccountBalance($_POST['member']);
         $avail = getAvailBalance($_POST['member']) + $specOrder->deliverSum();

         echo "<table border><tr><td colspan=2><b>Member Info for " . printMemberString($_POST['member'], 1) . "</b></td></tr>";
         echo "<tr><td>Account Balance: \$";
         printf("%01.2f", $accountLeft);
         echo "<br>Available Balance: ". money($avail);

         if($_POST['pay'] == 4)
         {
            echo "<br>Remaining Available Account after sale: \$";
            $after = $avail - ($totalPrice + $totalTax);
            if ($after < 0) echo "<font color='RED'>";
            else echo "<font color='BLUE'>";
            printf("%01.2f", $after);
            echo "</font>";
         }

         echo "<br>Credit Balance: " . getCreditTotal($_POST['member']);
	 
	 // Frequent Gamer Rewards
	 if(!(date_create < date_create("2008-5-1")))
	 {
	    echo "</td><td>\n";
	    FG_showInfo($_POST['member'], $totalPrice);
	 }
         
         echo "</td></tr></table>";
      }
   
      // display gift certificate info if applcable
      if($giftCert->certNum > 0)
      {
         $giftCert->displayInfo();
      }
   
      // if the complete transaction button was pressed then we update everything
      // if there were any invalid skus, we don't close yet
      // will not go through if the page was refreshed
      if($_POST['close'] == 1 && $noRefresh)
      {
         // if there is a discount we need to apply it to the item soldItems
         if($discount > 0)
         {
            $sql = "UPDATE soldItem SET price=(price * $discMult) WHERE transactionID='$TID'";
            query($cxn, $sql);
         }
      
         // DEDUCT CREDITS for credit changes
         // we do this first because it if it fails we don't want to do anything else.
         if($_POST['cred-ten'] == 1)
         {
            if(getCreditTotal($_POST['member']) >= 50)
            {
               if(transferCredits($_POST['member'], 0, 50, '10% additional discount', 0) == TRUE)
               {
                  $creditChange=TRUE;
                  echo "<p>50 Credits have been deducted for 10% discount.";
               }
               else
               {
                  echo "<p><font color=RED><b>Error deducting credits!</b>";
               } // end else
            } // end if credit total
            else
            {
               echo "<br><font color=RED><b>Insufficient Credits for 10% Discount!</b></font>";
               $_POST['cred-ten'] = 0;
               $fail = TRUE;
            }
         }

         if($_POST['cred-snack'] == 1)
         {
            if(getCreditTotal($_POST['member']) >= 10)
            {
               if(transferCredits($_POST['member'], 0, 10, 'snack card', 0) == TRUE)
               {
                  $creditChange=TRUE;
                  echo "<p>10 Credits have been deducted for a snack card.";
                  $totalCost += 5;
                  $sql = "INSERT into soldItem (transactionID, itemID, cost, qty, tax)
                                        VALUES ('$TID', '784', '5', '1', '0')";
                  query($cxn, $sql);

                  // this adds the snack card to the array which will be processed to adjust quantities.
                  $sql = "UPDATE items SET qty=qty-1 WHERE ID=784";
                  query($cxn, $sql);
               }
               else
               {
                  $fail = TRUE;
                  echo "<p><font color=RED><b>Error deducting credits for snack card!</b>";
               }
            }
            else
            {
               echo "<br><font color=RED><b>Insufficient Credits for Snack Card</b></font>";
               $_POST['cred-snack'] = 0;
               $fail = TRUE;
            }
         }
         
         if($_POST['cred-draft'] == 1)
         {
            if(getCreditTotal($_POST['member']) >= 5)
            {
               if(transferCredits($_POST['member'], 0, 5, 'snack card', 0) == TRUE)
               {
                  $creditChange=TRUE;
                  echo "<p>5 Credits have been deducted for a Draft (store keeps cards).";
                  $totalCost += 6.2;
                  $sql = "INSERT into soldItem (transactionID, itemID, cost, qty, tax)
                                        VALUES ('$TID', '780', '6.2', '1', '0')";
                  query($cxn, $sql);

                  // this adds the snack card to the array which will be processed to adjust quantities.
                  $sql = "UPDATE items SET qty=qty-1 WHERE ID=780";
                  query($cxn, $sql);
               }
               else
               {
                  $fail = TRUE;
                  echo "<p><font color=RED><b>Error deducting credits for Draft!</b>";
               }
            }
            else
            {
               echo "<br><font color=RED><b>Insufficient Credits for Draft</b></font>";
               $_POST['cred-draft'] = 0;
               $fail = TRUE;
            }
         }

         if($creditChange == TRUE)
         {
            echo "<p>Remaining Credits: " . getCreditTotal($_POST['member']);
         }


         // PAYMENTS
         // set all the payment variables
         $pay = ($specOrder->pickUp == TRUE) ? 4 : $_POST['pay'];
         $cashpay = ($_POST['cashpay'] > 0) ? $_POST['cashpay'] : 0;
         $ccpay = ($_POST['ccpay'] > 0) ? $_POST['ccpay'] : 0;
         $checkpay = ($_POST['checkpay'] > 0) ? $_POST['checkpay'] : 0;
         $accountpay = ($_POST['accountpay'] > 0) ? $_POST['accountpay'] : 0;
         $giftpay = greaterThanZero($_POST['giftpay']);
         $pay = ($pay >= 1 && $pay <=6) ? $pay : 1;
         $payment = $totalPrice + $totalTax;

         // check to make sure that split payment is correct before doing anything else
         if(!$fail && $pay == 6)
         {
            $paysum = $cashpay + $ccpay + $checkpay + $accountpay + $giftpay;
            if($paysum != $payment)
            {
               $fail = TRUE;
               Echo "<font color=RED>Payments must add up to total.</font><br>
                     Transaction Total: " . money($payment) . "<br>
                     Payment Total: " . money($paysum) . "<p>";
            }
         }


         // deal with payment on account
         if(!$fail && ($pay == 4 || ($pay == 6 && $accountpay > 0)))
         {
            // check to make sure there is a member
            if($member > 0)
            {
               $total = ($accountpay > 0) ? $accountpay : $payment;

               if ($total <= $avail)
               {
                  $sql = "INSERT INTO storeAccount (memberID, transactionID, whenAcct, amount)
                                            VALUES ('$member', '$TID', NOW(), '-$total')";
                  if(!query($cxn, $sql)) $fail = TRUE;
                  else $fail = FALSE;
               }
               else
               {
                  echo "<table border><tr><td><font color=RED>Insufficent money available in account.<br>
                        Total: $total, Avail: $avail</font></td></tr></table>\n";
                  $fail = TRUE;
               }
            }
            else
            {
               echo "<table border><tr><td><font color=RED>Member must be selected to pay with account</font></td></tr></table>\n";
               $fail = TRUE;
            }
         }
         
         // deal with payment on gift certificate
         if(!$fail && ($pay == 5 || ($pay == 6 && $giftpay > 0)))
         {
            $total = ($giftpay > 0) ? $giftpay : $payment;
            if(!$giftCert->redeem($total))
            {
               $fail = TRUE;
            }
         }

         if(!$fail && $specOrder->toProcess)
         {
            $fail = !($specOrder->process());
         }
         
         if(!$fail)
         {
            $fail = !($specOrder->deliver($payment));
         }

         if(!$fail && ($giftCert->newCert > 0))
         {
            echo "Check<p>";
            if($gcnum = $giftCert->sell())
            {
               echo "<b>Gift Certificate number $gcnum sold for \$" . money($giftCert->newCert) . "<p>\n";
            }
            else
            {
               $fail = TRUE;
               echo "<font color=RED>Error posting Gift Certificate</font>";
            }
         }

         $message = $newMember->message . $specOrder->message;
         if(strlen($message) > 0)
         {
            echo "<table border cellpadding=5><tr><td>$message</td></tr></table><p>";
         }

         if(!$fail)
         {
            // This puts the payment into the right column. This allows for split payments when that functionality is created, which is now


            switch($pay)
            {
               case 1 : $paysql = "payMethod='$pay', cash='$payment'";
                        break;
               case 2 : $paysql = "payMethod='$pay', creditcard='$payment'";
                        break;
               case 3 : $paysql = "payMethod='$pay', checkpay='$payment'";
                        break;
               case 4 : $paysql = "payMethod='$pay', account='$payment'";
                        break;
               case 5 : $paysql = "payMethod='$pay', giftCert='$payment'";
                        break;
               case 6 : $paysql = "payMethod='$pay', cash='$cashpay', creditcard='$ccpay', checkpay='$checkpay', account='$accountpay', giftCert='$giftpay'";
                        break;
            }
            


            // this will be used each time through to update the item inventory
            // this was moved inside of this if statement so that we don't change quatity until we are sure that this worked.
            $updateItem = $cxn->prepare("UPDATE items
                                            SET qty=qty - ?
                                          WHERE ID=?");
            $updateItem->bind_param('ii', $qty, $itemID);

            foreach($itemIDs as $itemID)
            {
               queryB($updateItem);
            }

            $updateItem->close();

            // this updates and closes the transaction
            $sql = (isset($specDate) && ($_SESSION['adm'] == 1)) ?
                   "UPDATE transactions
                       SET totalPrice='$totalPrice',
                           totalCost='$totalCost',
                           tax='$totalTax',
                           $paysql,
                           whensale='$specDate',
                           closed='1',
                           customerID='$member'
                     WHERE ID='$TID'" :
                   "UPDATE transactions
                       SET totalPrice='$totalPrice',
                           totalCost='$totalCost',
                           tax='$totalTax',
                           $paysql,
                           whensale=NOW(),
                           closed='1',
                           customerID='$member'
                     WHERE ID='$TID'";
            query($cxn, $sql);
      
            // display total
            $total = $totalPrice + $totalTax;

            switch($pay)
            {
               case 1 : echo "<table bgcolor=006600 border><tr><td><center>
                              <font color=WHITE><b>Cash Payment</b><br>\n";
                        break;
               case 2 : echo "<table bgcolor=660099 border><tr><td><center>
                              <font color=WHITE><b>Credit Card Payment</b><br>\n";
                        break;
               case 3 : echo "<table bgcolor=BLUE border><tr><td><center>
                              <font color=WHITE><b>Check Payment</b><br>\n";
                        break;
               case 4 : echo "<table bgcolor=BLACK border><tr><td><center>
                              <font color=WHITE><b>Worlds Apart Account Payment</b><br>
                              Remaining Balance: \$";
                        printf("%01.2f", getAccountBalance($_POST['member']));
                        echo "<br>\n";
                        break;
               case 5 : echo "<table bgcolor=ORANGE border><tr><td><center>
                              <font color=BLACK><b>Gift Certificate</b><br>
                              Gift Certificate #" . $giftCert->certNum . "<br>
                              Remaining Balance: " . money(giftCertBalance($giftCert->certNum)) . "<br>\n";
                        break;
               case 6 : echo "<table bgcolor=666666 border><tr><td><center>
                              <font color=WHITE><b>Mixed Payment</b><br>";
                        if($_POST['accountpay'] > 0)
                        {
                           echo "Remaining Balance : \$";
                           printf("%01.2f", getAccountBalance($_POST['member']));
                           echo "<br>\n";
                        }
                        break;
            }


            echo "Price: \$";
            printf("%01.2f", $totalPrice);
            echo "<br>Tax: \$";
            printf("%01.2f", $totalTax);
            echo "<p><font size=+5>Your Total</font><hr>
                  <font size=+3>\$";
            printf("%01.2f", $total);
            echo "</font></font>";
            echo ($pay == 6) ? "</td>" : "</td></tr></table><p>";

            if($pay == 6) // mixed payment
            {
               echo "<td>
                     <b>Mixed Payment</b><p>
                     <table bgcolor=WHITE cellpadding=3>";

               if($cashpay > 0)
               {
                  echo "<tr><td bgcolor=006600>
                        <font color=WHITE>Cash</td>
                        <td>" . money($cashpay) . "</td></tr>\n";
               }
               if($checkpay > 0)
               {
                  echo "<tr><td bgcolor=BLUE>
                        <font color=WHITE>Check</td>
                        <td>" . money($checkpay) . "</td></tr>\n";
               }
               if($ccpay > 0)
               {
                  echo "<tr><td bgcolor=660099>
                        <font color=WHITE>Credit Card</td>
                        <td>" . money($ccpay) . "</td></tr>\n";
               }
               if($accountpay > 0)
               {
                  echo "<tr><td bgcolor=BLACK>
                        <font color=WHITE>Account</td>
                        <td>" . money($accountpay) . "</td></tr>\n";
               }
               if($giftpay > 0)
               {
                  echo "<tr><td bgcolor=ORANGE>
                        <font color=BLACK>Gift Certificate</td>
                        <td>" . money($giftpay) . "</td></tr>\n";
               }
               echo "</table>\n";
            }

            echo "<a href='register.php'>Click here for another transaction</a><br>";
            unset($_SESSION['transaction_ID']);
            
            include('footer.php');
            exit();
         } // end if
         else // if fail is true meaning something is wrong with the transaction
         {
            echo "<p><b>Transaction not concluded due to errors</b>";
         }
      }

      if($noRefresh) // if the order is not concluded yet
      {
         echo "<table><tr><td><table>";

         if($discount >0)
         {
            echo "<tr><td><FONT COLOR='000066'>Applied Discount</font></td><td>$discount %</td></tr>\n";
         }

         echo "<tr><td>Current total Price</td><td>\$";
         printf("%01.2f", $totalPrice);
         echo "</td></tr>
               <tr><td>Current total Tax</td><td>\$";
         printf("%01.2f", $totalTax);
         echo "</td></tr>
               <tr><td>Current total Purchase</td><td>\$";
         printf("%01.2f", $totalPrice + $totalTax);
         echo "</td></tr>";
         
         // display account balance
         $member=$_POST['member'];
         $accountBal = ($member != 0) ? getAccountBalance($member) : 0;
         if($accountBal != 0)
         {
            echo "<tr><td colspan=2>";
            printMember($member, 1);
            echo " has an account balance of \$";
            printf("%0.2f", $accountBal);

            if($avail < ($totalPrice + $totalTax))
            {
               echo "<br><font color=RED>This member has insufficent account to cover this purchase!</font>";
            }
         }
         
         // show outstanding preorders, special orders, and requests
         if(checkMember($_POST['member'])) $specOrder->showOrders();

         // closing table
         echo "</td>";
         
         // info for split payments. Splits are tricky because they need to add up correctly.
         if($_POST['pay'] == 6)
         {
            echo "<td valign=top>";

            $payment = $totalPrice + $totalTax;

            $cashpay = $_POST['cashpay'];
            $ccpay = $_POST['ccpay'];
            $checkpay = $_POST['checkpay'];
            $accountpay = $_POST['accountpay'];
            $giftpay = $_POST['giftpay'];
            $paysum = $cashpay + $ccpay + $checkpay + $accountpay + $giftpay;
            if($paysum != $payment)
            {
               echo "The sum of your split payment is incorrect.<br>";
               echo ($paysum < $payment) ? "The payment is short by <font color=RED>" . money($payment - $paysum) . "</font>"
                                         : "The payment is over by <font color=GREEN>" . money($paysum - $payment) . "</font>";
               echo "<hr>";
            }
            
            echo "<center><b>Mixed Payments</b></center>
                  <table border><tr><td width=100>
                  <center>
                  Cash:<br>
                  <input type='text' name='cashpay' value='" . $_POST['cashpay'] . "' size=7 maxlength=8></center></td>
                  <td width=100><center>
                  Credit Card:<br>
                  <input type='text' name='ccpay' value='" . $_POST['ccpay'] . "' size=7 maxlength=8></center></td>
                  </td>
                  <td rowspan=2>";
            $giftCert->displaySelectForm();
            echo "Amount to Redeem: <input type='text' name='giftpay' value='" . $_POST['giftpay'] . "' size=7  maxlength=8></center></td>
                  </tr>
                  <tr><td><center>
                  Check:<br>
                  <input type='text' name='checkpay' value='" . $_POST['checkpay'] . "' size=7 maxlength=8></center></td>
                  </td><td><center>
                  Account:<br>
                  <input type='text' name='accountpay' value='" . $_POST['accountpay'] . "' size=7 maxlength=8></center></td>
                  </tr></table></td></tr>";
         } // end if

         echo "</table><p>";
      } // end else
   } // end if !isset invalid
   
   // if there are any invalid skus, we show them now
   else if(isset($invalidID))
   {
      echo "The following lookups were not found in the system:<br>$invalidID<p>";
   }
   
   $discount -= $memDiscount; // this is so the member discount is not added multiple times
   if($_POST['cred-ten'] == 1)
   {
      $discount -= 10; // ditto for credit discount
   }

   include('salesbox.inc'); // this is in an inc file so that it can be changes without reuploading register

    noRefresh(); // this goes before very submit button where there could be a problem with refreshing
    echo"<button name='submit' value='submit'>Update Order</button>
         <button name='close' value='1'>Conclude Order</button><p>";

    $newMember->displayQuickForm();

    $specOrder->displayForm();
         
    echo"<hr>If you have encounted an undesirable behaviour in this application, please describe it in the box below and press
         the submit button as usual. This will inform the High Programer and action will be taken as appropriate. This will
         not cause you to lose your data in any way, we hope.<br>
         <textarea name='bug' cols=40 rows=2></textarea>";

   if(isset($specDate))
      echo "<input type='hidden' name='date' value='$specDate'>";

   echo "</form><p>";

   $version = '1.33';
   include('footer.php');
?>
