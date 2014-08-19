<?php
// sendemail
// sends email to a list of people from the member table

// POST options
// to:
// sh = sends to all members minus optouts - requires ADM
// all = sends to all members - requires ADM
// reg = sends to all reg - requires MEM
// inv = sends to all inv - requires MEM

// body = body of email to send
// subject = subject of email

   include('funcs.inc');
   $title = 'Email Sender';
   include('header.php');
   include('member.inc');
   include('credits.inc');
   $cxn = open_stream();
   
   if($_SESSION['reg'] != 1)
   {
      echo "<font size=+2>You do not even have permission to know this application exists.
            Please report to nearest termination booth.</font>";
      include('footer.php');
      exit();
   }
   if($_SESSION['mem'] != 1)
   {
      echo "<font size+2>You do not have permission to use this application.</font>";
      include('footer.php');
      exit();
   }
   
   if(isset($_POST['body']))
   {
      extract($_POST);
      if(!checkAlphaNumSpace($subject))
      {
         echo "<font size+2>Subject must be alphanumeric!</font>";
         include('footer.php');
         exit();
      }


      switch($to)
      {
	 case 'first'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'300' AND ID>'0'";
                      }
                      break;
	 case 'second'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'600' AND ID>'299'";
                      }
                      break;
	 case 'third'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'900' AND ID>'599'";
                      }
                      break;
	 case 'fourth'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'1200' AND ID>'899'";
                      }
                      break;
	 case 'fifth'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'1500' AND ID>'1199'";
                      }
                      break;
	 case 'sixth'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'1800' AND ID>'1499'";
                      }
                      break;
	 case 'seventh'  : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the Scavenger Hunt list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE ID<'2100' AND ID>'1799'";
                      }
                      break;



         case 'all' : if($_SESSION['adm'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the general list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE optout IS NULL";
                      }
                      break;
         case 'reg' : if($_SESSION['mem'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the general list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE registerUse='1'";
                      }
                      break;
         case 'inv' : if($_SESSION['mem'] != 1)
                      {
                         echo "<font size=+2>You do not have permission to send to the general list</font>";
                         include('footer.php');
                         exit();
                      }
                      else
                      {
                         $sql = "SELECT ID, email, fname, lname FROM members WHERE inventoryUse='1'";
                      }
                      break;
      }
   
      $result = query($cxn, $sql);
      $count = 0;
      while($row = mysqli_fetch_assoc($result))
      {
         $count++;
         extract($row);
         $array[$count]['member'] = $ID;
         $array[$count]['e'] = $email;
         $array[$count]['f'] = $fname;
         $array[$count]['l'] = $lname;
      }

      $body = str_replace('\\', '', $body);
      $body = "
$body

Sincerely,
General Manager
" . printMemberString($_SESSION['ID'], 5);

$count = 0;   
      foreach($array as $info)
      {
         extract($info);
         $sales = memberSalesThisMonth($member);
         $lastmonth = memberSalesLastMonth($member);
         $disc = FG_discount($lastmonth);
         $newdisc = FG_discount($sales);
         $tonext = $FGDISCOUNT[$newdisc + 1] - $thissale - $sales;

         $fgblock = displayMembershipStatusString($member) . "\nYour frequent gamer discount for this month is $disc%, so you will get this discount off of all of your purchases this month.\n";
if($sales > 0)
   $fgblock .= "This month, you have spent " . money($sales) . " at Worlds Apart which gives you a discount for next month of $newdisc%, but you only need to spend " . money	($tonext) . " to get to the next discount level for next month.\n";

         if(strlen($f) > 0 && strlen($l) > 0) $message = "Dear $f $l,\n" . $fgblock . $body;
         else if(strlen($f) > 0) $message = "Dear $f,\n" . $fgblock . "\n\n" . $body;
         else $message = "Dear Community Member,\n" . $fgblock . "\n\n" . $body;
         
         $message .= "\n\nThis message has been sent to you because you are in the Worlds Apart Database. If you have recieved this message in error, please email gm@pvgaming.org to be removed from the list.";
         if($to = 'all')
            $message .= "\n\nThe government says we have to say this part:
This is a commercial message from Worlds Apart which you could call an advertisement.
Worlds Apart Games
48 North Pleasant Street B2
Amherst, MA  01002";
      
         $header = "from: WorldsApart@pvgaming.org
Reply-To: newsletter@pvgaming.org
Precedence: bulk
X-Mailer: PHP/" . phpversion();

         echo "Sending to $f $l at $e - Result:";
      
         if(mail ($e, $subject, $message, $header))
         {
	    $count++;
            echo "Success - $count<br>";
         }
         else
         {
            echo "Failure<br>";
         }
      }
   }
   else // if no post
   {
      echo "<form action='sendemail.php' method='post'>
            Send to:<br>
            <input type='radio' name='to' value='reg'> Register Folks<br>
            <input type='radio' name='to' value='inv'> Inventory Folks<p>
            <input type='radio' name='to' value='all'> Full Mailing List<p>
            <input type='radio' name='to' value='first'> First <p>
            <input type='radio' name='to' value='second'> Second <p>
            <input type='radio' name='to' value='third'> Third <p>
            <input type='radio' name='to' value='fourth'> Fourth <p>
            <input type='radio' name='to' value='fifth'> Fifth<p>
            <input type='radio' name='to' value='sixth'> Sixth <p>
            <input type='radio' name='to' value='seventh'> Seventh <p>
            <input type='checkbox' name='all' value='1'> Yes, I really want to email the whole list<p>
            Subject: <input type='text' name='subject' size=60 maxlength=60><p>
            <textarea cols=60 rows=30 name='body'></textarea><br>
            <input type='submit' name='submit' value='submit'><p>";
   }
   
   include('footer.php');
?>
