<?php
// member.inc

// functions relevant to member operations and Frequent Gamer Program Info

$FGDISCOUNT = array(0 => 0, 1 => 1, 5 => 1, 25, 50, 75, 100, 150, 200, 250, 300, 350, 400, 450, 500, 550, 600, 700, 1000);

// FG_discount
// args: sales
// returns the discount for the given sales amount
function FG_discount($sales)
{
   if($sales > 1000) return 21;
   if($sales > 700) return 20;
   if($sales > 600) return 19;
   if($sales > 550) return 18;
   if($sales > 500) return 17;
   if($sales > 450) return 16;
   if($sales > 400) return 15;
   if($sales > 350) return 14;
   if($sales > 300) return 13;
   if($sales > 250) return 12;
   if($sales > 200) return 11;
   if($sales > 150) return 10;
   if($sales > 100) return 9;
   if($sales > 75) return 8;
   if($sales > 50) return 7;
   if($sales > 25) return 6;
   if($sales > 1) return 5;
   return 0;
}

// FG_discountNow
// returns the discount that $member has this month based on last month's sales
function FG_discountNow($member)
{
   return(FG_discount(memberSalesLastMonth($member)));
}

// FG_showInfo
// echoes the information about this member's current sales regarding the Frequent Gamer Program
function FG_showInfo($member, $thissale)
{
   global $FGDISCOUNT;

   $sales = memberSalesThisMonth($member);
   $lastmonth = memberSalesLastMonth($member);
   $disc = FG_discount($lastmonth);
   $newdisc = FG_discount($sales + $thissale);
   $tonext = $FGDISCOUNT[$newdisc + 1] - $sales - $thissale;
   
   echo "<b>Frequent Gamer Info</b><br>
         Sales this month: " . money($sales) . "<br>
         Sales after current sale: " . money($thissale) . "<br>
         Discount after current sale: $newdisc%<br>
         To next level: " . money($tonext); // . "<hr>
         //Sales last month: " . money($lastmonth) . "<br>
         //Current Discount: $disc%";
         
         // commented sections to be turned on July 1, 2008
}

// FG_showInfoNoSale
// echoes the information about this member's current sales. Like above but without current sales info
function FG_showInfoNoSale($member)
{
   global $FGDISCOUNT;

   $sales = memberSalesThisMonth($member);
   $lastmonth = memberSalesLastMonth($member);
   $disc = FG_discount($lastmonth);
   $newdisc = FG_discount($sales);
   $tonext = $FGDISCOUNT[$newdisc + 1] - $thissale - $sales;

   
   echo "<h2>Frequent Gamer Info</h2>
         Sales this month: " . money($sales) . "<br>
         Next month's discount: $newdisc%<br>
         To next level: " . money($tonext); // . "<hr>\n";
}
   
// giveBAFMedal
// args: ID - memberID
// if member does not have Bring a Friend Medal, gives them one
// returns message
function giveBAFMedal($ID)
{
   $BAF = 20;

   $cxn = open_stream();
   $sql = "SELECT * FROM medalsGiven WHERE medalID=$BAF AND memberID='$ID'";
   $result = query($cxn, $sql);
   if($cxn->affected_rows == 0)
   {
      $stmt = $cxn->prepare("INSERT INTO medalsGiven (whoGave, medalID, memberID, whenGiven, notes)
                                              VALUES (?, $BAF, ?, NOW(), 'For referral')");
      $stmt->bind_param("dd", $_SESSION['ID'], $ID);
      if($stmt->execute())
      {
         return("Bring a Friend Medal Given<p>");
      }
      else
      {
         return("Error giving Bring a Friend Medal<p>");
      }
   }
   else
   {
      $refcount = peopleReferred($ID);
      if($refcount == 10)
      {
         $stmt = $cxn->prepare("INSERT INTO medalsGiven (whoGave, medalID, memberID, whenGiven, notes)
                                                 VALUES (?, 35, ?, NOW(), 'For referral')");
         $stmt->bind_param("dd", $_SESSION['ID'], $ID);
         if($stmt->execute())
         {
            return("Bring 5 Friends Medal Given<p>");
         }
         else
         {
            return("Error giving Bring 5 Friends Medal<p>");
         }
      }
      else if($refcount == 5)
      {
         $stmt = $cxn->prepare("INSERT INTO medalsGiven (whoGave, medalID, memberID, whenGiven, notes)
                                                 VALUES (?, 34, ?, NOW(), 'For referral')");
         $stmt->bind_param("dd", $_SESSION['ID'], $ID);
         if($stmt->execute())
         {
            return("Bring 5 Friends Medal Given<p>");
         }
         else
         {
            return("Error giving Bring 5 Friends Medal<p>");
         }
      }
      else
         return("Referrer already has Bring a Friend Medal and cannot get another.<p>");
   }
}
         

// giveReferralCredit
// args: customerID
//       totalPrice
// returns: string to print
function giveReferralCredit($customerID, $totalPrice, $TID)
{
   $cxn = open_stream();
   
   $sql = "SELECT refBy, dt FROM howFound WHERE memberID='$customerID'";
   $result = query($cxn, $sql);
   if($row = mysqli_fetch_assoc($result))
   {
      extract($row);
      if($refBy > 0)
      {
         $date = date_create($dt); //The date they became a member
         $earliestDate = date_create(); //Today
         $memberStatus = checkMember($refBy);
         if($memberStatus >= 2) //If the referrer is a member, working or higher
            $earliestDate->modify("-1 month"); //Subtract a month from today
         else
            $earliestDate->modify("-1 month");
         if($earliestDate < $date)
         {
            $amount = $totalPrice * .05;
            $amount = round($amount, 2);
            $note = "REF $customerID";
            accountTransact($refBy, $amount, $TID, $note);
            $returnString = printMemberString($refBy, 1) . " given " . money($amount) . " in credit for referral bonus.";
            return ($returnString);
         }
         else
            return FALSE;
      }
   }
   return FALSE;
}   

// memberSalesLastMonth
// returns member's sales last month
function memberSalesLastMonth($member)
{
   $cxn = open_stream();
   
   $date = date_create();
   $string = $date->format("Y-n-1 00:00");
   $date = date_create($string);
   $date->modify("-1 day");
   $month = $date->format("n");
   $start = $date->format("Y-n-1 00:00");
   $end = $date->format("Y-n-") . lastDayOfMonth($month) . " 23:59";

   $sql = "SELECT (SUM(totalPrice))
           FROM transactions WHERE customerID='$member' AND whensale > '$start' AND whensale < '$end'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_row($result);
   return($row[0]);
}

// memberSalesThisMonth
// returns member's sales this month
function memberSalesThisMonth($member)
{
   $cxn = open_stream();
   
   $date = date("Y-n-1 00:00");

   $sql = "SELECT (SUM(totalPrice)) 
           FROM transactions WHERE customerID='$member' AND whensale > '$date'";
           
   $result = query($cxn, $sql);
   $row = mysqli_fetch_row($result);
   return($row[0]);
}

// peopleReferred
// args: ID
// returns number of people that this member has referred
function peopleReferred($ID)
{
   $cxn = open_stream();
   $sql = "SELECT COUNT(refBy) FROM howFound WHERE refBy='$ID'";
   $count = queryOnce($cxn, $sql);
   return($count);
}

// taxExempt
// args: member ID
// returns member's tax exempt number or FALSE
function TaxExempt($ID)
{
   $cxn = open_stream();
   
   $sql = "SELECT taxexempt FROM members WHERE ID='$ID' AND taxexempt IS NOT NULL AND taxexempt != 'NULL'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_row($result);
   return((($row[0] > 0) || (strlen($row[0]) > 0)) ? $row[0] : FALSE);
}
   


class newMember
{
   var $fname;
   var $lname;
   var $phone;
   var $email;
   var $findus;
   var $member;
   var $message; // message for FC

   function newMember()
   {
      $this->fname = strip_tags($_POST['Mfname']);
      $this->lname = strip_tags($_POST['Mlname']);
      $this->phone = extractNums($_POST['Mphone']);
      $this->email = (check_email_address($_POST['Memail'])) ? ($_POST['Memail']) : '';
      $this->howfound = strip_tags($_POST['Mhowfound']);
      $this->refBy = ($_POST['Mref'] > 0) ? $_POST['Mref'] : 0;
      
      $this->member = $_POST['member'];
   }

   // displayQuickForm
   // if member is 0, displays fname, lname, phone, email. Sets login to lnamef and password to changeme
   // if member is set, allows phone and email to be edited
   function displayQuickForm()
   {
      $cxn = open_stream();
      $member = $this->member = $_POST['member'];
      if(!$this->member > 0)
      {
         // this will occur if there was data but no member was selected
         // it should appear blank in this case.
         if($this->fname == 'exist!')
         {
            unset($this->fname, $this->lname, $this->phone, $this->email);
         }

         $this->phone = formPhoneNumber($this->phone);
         echo "<table border cellpadding=5><tr><td>";
         echo "<b>Quick Add Customer<b><br>
               First Name: <input type='text' name='Mfname' value='" . $this->fname . "' size=20 maxlength=50>
               Last Name: <input type='text' name='Mlname' value='" . $this->lname . "' size=20 maxlength=50><br>
               Phone Number: <input type='text' name='Mphone' value='" . $this->phone . "' size=12 maxlength=20>
               Email Address: <input type='text' name='Memail' value='" . $this->email . "' size=40 maxlength=150><br>
               How Did You Find Us? <input type='text' name='Mhowfound' value='" . $this->howfound . "' size=75 maxlength=255><br>
               Referred By Someone? ";
         selectMember("Mref", 0);
         echo "<hr>
               <font size=-1>Notice how it says 'tax exempt number' there? If this doesn't mean anything to you, leave it blank. If you have a tax exempt organization before you, put in the parent organization at first name and sub organization as last name. For example, for Excalibur at Hampshire enter 'Hampshire' as first name and 'Excalibur' as last name.</font><br>
               Tax Exempt Number: <input type='text' name='Mtaxexempt' value='" . $this->taxexempt . "' size=15 maxlength=15><br>";
         echo "</td></tr></table><p>";
      }

   } // end displayQuickForm
   
   // process
   // processes either adding new member or updating information
   // returns true if email address was added, false if not
   function process()
   {
      $cxn = open_stream();

      if($this->fname != 'exist!') // if it is supposedly new
      {
         $exists = FALSE;

         $sql = "SELECT ID FROM members WHERE email='" . $this->email . "'";
         $row = queryAssoc($cxn, $sql);
         
         // if no email address
         if($this->email == '' && $this->phone == '')
         {
            return(0);
         }
         
         if($cxn->affected_rows > 0)
         {
            $this->message .= $this->email . " found in database.<br>";
            $this->member = $row['ID'];
            $exists = TRUE;
         }
         else
         {
            $sql = "SELECT ID FROM members WHERE fname='" . $this->fname . "' AND lname='" . $this->lname . "'";
            $row = queryAssoc($cxn, $sql);
            if($cxn->affected_rows > 0)
            {
               $this->message .= $this->fname . " " . $this->lname . " found in database.<br>";
               $this->member = $row['ID'];
               $exists = TRUE;
            }
         } // end else
         
         $this->fname = stripslashes($this->fname);
         $this->lname = stripslashes($this->lname);
         $this->taxexempt = (strlen($this->taxexempt) > 6) ? $this->taxexempt : "NULL";

         if($this->email == '' && $_POST['member'] == 0 && strlen($this->fname) > 0 && strlen($this->lname) > 0 && !$exists)
         {
	    $stmt = $cxn->prepare("INSERT INTO members (login, password, fname, lname, phone1, email, memberSince, taxexempt)
                                                VALUES (?,?,?,?,?,?, NOW(),?)");
            $login = strtolower(substr($this->fname, 0, 1) . $this->lname);
            $password = hash('sha256','!@#$%^&*()1');
            $stmt->bind_param('sssssss', $login, $password, $this->fname, $this->lname, $this->phone, $this->email, $this->taxexempt);
            if($stmt->execute())
            {
               $date = date_create();
               $date->modify("+1 month");
               $datestr = date_format($date, "l, F jS, Y");
            }
            $stmt->close();
            $this->message .= $this->fname . " " . $this->lname . " added to list.<br>\n";
            
            // set new active member
            $sql = "SELECT MAX(ID) FROM members";;
            $result = query($cxn, $sql);
            $row = mysqli_fetch_row($result);
            $this->member = $row[0];
            $_POST['member'] = $this->member;
            
            echo $this->message;
            return (FALSE);
         }
         else if(!$exists) // if it is still considered new
         {
            $stmt = $cxn->prepare("INSERT INTO members (login, password, fname, lname, phone1, email, memberSince, taxexempt)
                                                VALUES (?,?,?,?,?,?, NOW(), ?)");
            $login = strtolower(substr($this->fname, 0, 1) . $this->lname);
            $rawpass = substr($this->fname, 2, 2) . substr($this->email, 4, 2) . $_SESSION['lastActive'];
            $password = hash('sha256',$rawpass);
            $stmt->bind_param('sssssss', $login, $password, $this->fname, $this->lname, $this->phone, $this->email, $this->taxexempt);
            if($stmt->execute())
            {
               $date = date_create();
               $date->modify("+1 month");
               $datestr = date_format($date, "l, F jS, Y");

               // add the howfound if it was included
               if((strlen($this->howfound) > 0) OR ($this->refBy > 0))
               {
                  $sql = "SELECT ID FROM members WHERE login='$login'";
                  $row = queryAssoc($cxn, $sql);
                  $memberID = $row['ID'];
                  if($this->refBy > 0)
                  {
                     $this->howfound .= " REFBY: " . printMemberString($this->refBy, 1);
                     $stmt = $cxn->prepare("INSERT INTO howFound (memberID, dt, howfound, refBy) VALUES (?, NOW(), ?, ?)");
                     $stmt->bind_param('dsd', $memberID, $this->howfound, $this->refBy);
                     $this->message .= "New customer referred by " . printMemberString($this->refBy, 1) . "<br>\n";
                  }
                  else
                  {
                     $stmt = $cxn->prepare("INSERT INTO howFound (memberID, dt, howfound) VALUES (?, NOW(), ?)");
                     $stmt->bind_param('ds', $memberID, $this->howfound);
                     $this->message .= "New customer not referred by existing member.<br>\n";
                  }

                  if($stmt->execute())
                  {
                     $this->message .= "<br>Method of finding submitted successfully<br>";
                  }
                  else
                  {
                     displayError("<font color=RED>Error submitting method of finding<br>" . $cxn->error);
                  }
                  $this->message .= giveBAFMedal($this->refBy);
               }

               $couponmessage = "Dear " . $this->fname . " " . $this->lname . "
               
On behalf of the Pioneer Valley Gamer Collective, I, Friend Computer, would like to thank you for registering with Worlds Apart Games. This now means that you are enrolled in the Frequent Gamer Rewards Program. The more you buy one month, the higher your discount the next month. If you are one of the top three purchasers for the month, you get cash rewards! Visit http://www.worldsapartgames.org/fgrp.php for more details. Want to know where you are in the standings? Just drop by the store and ask.

" . (($this->refBy > 0) ? "You were referred by " . printMemberString($this->Mref, 5) . " who will get a little token of our appreciation for bringing your into our community in the form of store account. " : "") 
."Know some other fun people that would like Worlds Apart? Send them in, and if they list you as the person who referred them, then you will get a percentage of what they buy put into your store account. Ask at the store for more details.

By being in our database, you are able to log into your Friend Computer account. This lets you spend your Hedons, transfer account to other people, and do some other things. Your info is as follows:
http://www.worldsapartgames.org/fc
Login: $login
Password: $rawpass

As you were hopefully told, you will now be getting our newsletter which is full of great information on Worlds Apart events and new releases.

As additional thanks for signing up, we are sending you this coupon good for 10% off any purchase. Just print out this email and bring it in " .
"before $datestr for your discount. We look forward to seeing you soon.

Sincerely
Friend Computer
Robot Overlord of Worlds Apart Games\n";

         $header = "from: friendcomputer@worldsapartgames.org
Reply-To: newsletter@pvgaming.org
Precedence: bulk
X-Mailer: PHP/" . phpversion();


               mail($this->email, "Thanks for Signing Up With Worlds Apart", $couponmessage, $header);
            }
            else
            {
               displayError("Error submitting new member");
            }
            
            $stmt->close();
            $this->message .= $this->fname . " " . $this->lname . " added to list.<br>\n";
            
            if(transferCredits(0, $_SESSION['ID'], 1, ('For email for ' . $this->lname), 1))
               $this->message .= "Credits awarded.<br>\n";

            // Check for people listing themselves as referrer
            if($this->refBy == $_SESSION['ID'])
            {
               $srmessage = 
"A reg monkey just submitted a customer as referred by themselves. This may be legit, or you may need to shank them. Only you can know.

Reg Monkey: " . printMemberString($_SESSION['ID'], 1) . "
RefBy: " . printMemberString($this->refBy, 1) . "
New Customer: {$this->fname} {$this->lname}";

               if(mail("gm@pvgaming.org", "FC Self Referral", $srmessage, $header))
               {
                  $this->message .= "<p>You entered yourself as referrer. An email was sent to the High Programmer. If you legitimately brought this person in, you have nothing to worry about and will have our gratitude. Hopefully you are not trying to claim credit for someone you did not bring in. That would make Baby Cthulhu cry.<br>\n";
               }
               else
               {
                  displayError("Error sending email");
               }
            }
            
            // set new active member
            $sql = "SELECT MAX(ID) FROM members";;
            $result = query($cxn, $sql);
            $row = mysqli_fetch_row($result);
            $this->member = $row[0];
            $_POST['member'] = $this->member;
            
            echo $this->message;
            if($this->email != '') return TRUE;
            else return FALSE;
         }
      }
      else if($exists)
      {
         $this->message .= "<b>" . $this->fname . " " . $this->lname . " is already in the database and cannot have any rice pilaf</b><p>";
         echo $this->message;
         return FALSE;
      }

      // if it is an update or if it was supposed to be new but actually exists, still update info
      if($_POST['oldmem'] == $this->member) // only do this if the member was not changed
      {
         $stmt = $cxn->prepare("UPDATE members SET phone1=?, email=? WHERE ID=?");
         $stmt->bind_param('ssi', $this->phone, $this->email, $this->member);
         $stmt->execute();
         $stmt->close();
         $_POST['member'] = ($_POST['member'] == 0) ? $this->member : $_POST['member'];
      }
      
      echo $this->message;
      return FALSE;
   } // end process
} // end class
?>