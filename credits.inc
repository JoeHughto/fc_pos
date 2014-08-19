<?php
// user number 0 is Friend Computer

// reason codes:
// 0 - payment to Friend Computer
// 1 - automatic payment for coverage
// 2 - other officer payment
// 3 - intermember transfer
// 4 - transfer caused by Admin

// number of credits for a month of working membership
$WORKINGMEMBERCREDITS = 15;

// displayCreditTransactions
// displays the last $max transactions for member $SID
// if $max is ALL, will show all
function displayCreditTransactions($SID, $max)
{
   $cxn = open_stream();

   $sql = "SELECT * FROM credits WHERE memberID='$SID' OR (senderID='$SID' AND (reason='3' OR reason ='0')) ORDER BY daytime DESC";
   $result = query($cxn, $sql);

   if($cxn->affected_rows > 0) for($i = 0; (($i < $max) || ($max == 'ALL')) && $row = mysqli_fetch_assoc($result); $i++)
   {
      if ($i == 0) echo "<b>Recent Transactions</b><br>";

      extract($row);
      echo "<hr>$daytime<br>";

      if($credits > 0)
      {
         switch($reason)
         {
            case 0: echo "$credits H paid to Friend Computer<br>for '$notes'<br>\n";
                    break;
                    
            case 1: echo "$credits H for Coverage or Service<br>
                          for '$notes'<br>\n";
                    break;
            case 2: echo "$credits H from official fund of ";
                    printMember($senderID, 1);
                    echo "<br> for '$notes'<br>\n";
                    break;
            case 3: if($memberID == $SID)
                    {
                       echo "$credits H from ";
                       printMember($senderID, 1);
                       echo "<br> for '$notes'<br>\n";
                       break;
                    }
                    else
                    {
                       echo "$credits H to ";
                       printMember($memberID, 1);
                       echo "<br> for '$notes'<br>\n";
                       break;
                    }
         }
      }
   } // end for
}

// displayHedonRequests
// displays the last $max transactions for member $SID
// if $max is ALL, will show all
function displayHedonRequests($SID, $max)
{
   $cxn = open_stream();

   $sql = "SELECT * FROM hedonReqs WHERE memberID='$SID' ORDER BY daytime DESC";
   $result = query($cxn, $sql);

   if($cxn->affected_rows > 0) for($i = 0; (($i < $max) || ($max == 'ALL')) && $row = mysqli_fetch_assoc($result); $i++)
   {
      if ($i == 0) echo "<b>Pending Requests</b><br>";

      extract($row);
      echo "$daytime<br>";
      echo "$hedons H requested for '$notes'<br>\n";
   } // end for
   echo "<hr>";
}

// displayHedonRequests
// displays the last $max transactions for member $SID
// if $max is ALL, will show all
function displayAllHedonRequests()
{
   $cxn = open_stream();

   $sql = "SELECT * FROM hedonReqs ORDER BY memberID DESC";
   $result = query($cxn, $sql);

   if($cxn->affected_rows > 0) for($i = 0; $row = mysqli_fetch_assoc($result); $i++)
   {
      if ($i == 0) echo "<b>Pending Requests</b><br>";

      extract($row);
      echo "$daytime<br>";
      echo "<input type='checkbox' name='selectedReqs[]' value='$daytime' />";
      printMember($memberID, 1);
      echo "$hedons H requested for '$notes'<br>\n";
      
      
   } // end for
   echo "<hr>";
}

// displayAllBounties
// displays all currently active bounties
function displayAllBounties()
{
   $cxn = open_stream();

   $sql = "SELECT * FROM bounties ORDER BY hedons DESC";
   $result = query($cxn, $sql);

   if($cxn->affected_rows > 0) for($i = 0; $row = mysqli_fetch_assoc($result); $i++)
   {
      if ($i == 0) echo "<b>Unclaimed Bounties</b><br>";

      extract($row);
      echo "<br>";
      echo "<input type='checkbox' name='selectedBounties[]' value='$daytime' />";
      echo "$hedons H offered for '$notes'<br>\n";
      
      
   } // end for
   echo "<hr>";
}

// displayMembershipStatus
// displays membership status as working and/or contributing with expiration dates
function displayMembershipStatus($SID)
{
   $cxn = open_stream();

   $sql = "SELECT contribExp, workingExp FROM members WHERE ID='$SID'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_assoc($result);

   $cExp = $row['contribExp'];
   $wExp = $row['workingExp'];

   $cDate = date_create($cExp);
   $wDate = date_create($wExp);

   $cMember = $cDate > date_create();
   $wMember = $wDate > date_create();

   if($wMember && $cMember)
   {
      echo "<b>You are currently a Double Member</b><br>";
      echo "Working membership through: " . $wDate->format("F jS, Y") . "<br>";
      if($cExp == '2200-01-01')
      {
         echo "Lifetime Member.</b><p>";
      }
      else if($cExp == '2300-01-01')
      {
         echo "Eternal Member.</b><p>";
      }
      else
      {
         echo "Contributing Member through</b><br>" . $cDate->format("F jS, Y") . "<p>";
      }
   }
   else if($wMember)
   {
      echo "<b>You are a Working Member through</b><br>" . $wDate->format("F jS, Y"). "<p>";
   }
   else if($cMember)
   {
      echo "<b>You are";
      if($cExp == '2200-01-01')
      {
         echo " a Lifetime Member.</b><p>";
      }
      else if($cExp == '2300-01-01')
      {
         echo " an Eternal Member.</b><p>";
      }
      else
      {
         echo " a Contributing Member through</b><br>" . $cDate->format("F jS, Y") . "<p>";
      }
   }
}

// displayMembershipStatusString
// displays membership status as working and/or contributing with expiration dates in text for email
function displayMembershipStatusString($SID)
{
   $cxn = open_stream();

   $sql = "SELECT contribExp, workingExp FROM members WHERE ID='$SID'";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_assoc($result);

   $cExp = $row['contribExp'];
   $wExp = $row['workingExp'];

   $cDate = date_create($cExp);
   $wDate = date_create($wExp);

   $cMember = $cDate > date_create();
   $wMember = $wDate > date_create();

   if($wMember && $cMember)
   {
      $text =  "You are currently a Double Member\n";
      $text .= "Working membership through: " . $wDate->format("F jS, Y") . "\n";
      if($cExp == '2200-01-01')
      {
         $text .= "You are also a Lifetime Member.\n\n";
      }
      else if($cExp == '2300-01-01')
      {
         $text .= "You are also an Eternal Member.\n\n";
      }
      else if($cExp == '2400-01-01')
      {
         $text .= "You are also a Dynasty Member.\n\n";
      }
      else
      {
         $text .= "Contributing Member through" . $cDate->format("F jS, Y") . "\n\n";
      }
   }
   else if($wMember)
   {
      $text .= "You are a Working Member through" . $wDate->format("F jS, Y"). "\n\n";
   }
   else if($cMember)
   {
      $text .= "You are";
      if($cExp == '2200-01-01')
      {
         $text .= " a Lifetime Member.\n\n";
      }
      else if($cExp == '2300-01-01')
      {
         $text .= " an Eternal Member.\n\n";
      }
      else if($cExp == '2400-01-01')
      {
         $text .= " a Dynasty Member.\n\n";
      }
      else
      {
         $text .= " a Contributing Member through" . $cDate->format("F jS, Y") . "\n\n";
      }
   }
   return ($text);
}


// getCreditTotal
// returns credit balance
// args: ID - ID of member to check
function getCreditTotal($ID)
{
   $cxn = open_stream();
   $sql = "SELECT sum(credits)
             FROM (SELECT c1.credits
                     FROM credits as c1
                    WHERE memberID='$ID'
                    UNION ALL(SELECT (0 - c2.credits)
                                FROM credits as c2
                               WHERE senderID='$ID'
                                 AND (reason='3' OR reason='0'))) AS cf";
   $result = query($cxn, $sql);
   $row = mysqli_fetch_row($result);
   return($row[0]);
}

// renewMembership
// renews membership for member #ID for $months months
// this function will NOT check credit balance
function renewMembership($ID, $months)
{
   $mounths = round($months);

   $cxn = open_stream();
   global $WORKINGMEMBERCREDITS;
   
   // determine new expiration date
   $sql = "SELECT workingExp FROM members WHERE ID='$ID'";
   $row = queryAssoc($cxn, $sql);
   extract($row);
   $date = date_create($workingExp);
   $nowdate = date_create();
   if($date < $nowdate) 
      $date = $nowdate;
   $date->modify("+$months months");
   $newExp = $date->format("Y-m-d");
   
   // set new expiration date
   $sql = "UPDATE members SET workingExp='$newExp' WHERE ID='$ID'";
   query($cxn, $sql);
   
   // spend credits
   $creditCost = $WORKINGMEMBERCREDITS * $months;
   $reason = "$months months of Membership";
   $sql = "INSERT INTO credits (daytime, memberID, credits, reason, senderID, notes)
                               VALUES
                               (NOW(), '0', '$creditCost', '0', '$ID', '$reason')";
   query($cxn, $sql);
}
   

// transferCredits
// creates a credit transaction between two members
// args: sender - member ID of giver
//       recep - member ID of recipient
//       amt - amount of transfer
//       note - reason for transfer
function transferCredits($sender, $recep, $amt, $note, $reason)
{
   $cxn = open_stream();

   // check to see if the sender has the credits

   $balance = getCreditTotal($sender);
   if($reason == 3 && $balance < $amt) return FALSE; // if there is not enough in the account

   $note = strip_tags($note);

   if($stmt = $cxn->prepare("INSERT INTO credits
                                         (daytime, memberID, credits, reason, senderID, notes)
                                         VALUES
                                         (NOW(), ?, ?, ?, ?, ?)"))
   {
      $stmt->bind_param("idiis", $recep, $amt, $reason, $sender, $note);
      $stmt->execute();
      return TRUE;
   }
   else
   {
      displayError("Error Binding Query. Hedons not transferred.");
      return FALSE;
   }
}

// requestCredits
// creates a credit request, and adds it to the queue.
// args: req - member ID of requestor
//       amt - amount requested
//       note - reason hedons are deserved
function requestCredits($memID, $amt, $note)
{
   $cxn = open_stream();

   $note = strip_tags($note);

   if($stmt = $cxn->prepare("INSERT INTO hedonReqs
                                         (daytime, memberID, hedons, notes)
                                         VALUES
                                         (NOW(), ?, ?, ?)"))
   {
      $stmt->bind_param("ids", $memID, $amt, $note);
      $stmt->execute();
      return TRUE;
   }
   else
   {
      displayError("Error Binding Query. Hedons not requested. Contact your local High Programmer.");
      return FALSE;
   }
}

function approveHedonRequest($daytime)
{
	$cxn = open_stream();
	$sql = "SELECT * FROM hedonReqs WHERE daytime='$daytime'";
	$result = query($cxn, $sql);
	$row = mysqli_fetch_row($result);
	
	if (transferCredits(0, $row[1], $row[2], $row[3], 1)){
		$sql = "DELETE FROM hedonReqs WHERE daytime='$daytime'";
		if(!query($cxn, $sql))
		{
			echo "Error: Approved hedon transaction not removed from table.";
		}
	}
}

function claimBounty($daytime)
{
	$cxn = open_stream();
	$sql = "SELECT * FROM bounties WHERE daytime='$daytime'";
	$result = query($cxn, $sql);
	$row = mysqli_fetch_row($result);
	
	if (requestCredits($_SESSION['ID'], $row[1], $row[2])){
		$sql = "DELETE FROM bounties WHERE daytime='$daytime'";
		if(!query($cxn, $sql))
		{
			echo "Error: Requested bounty not removed from table.";
		}
	}
}

function denyHedonRequest($daytime)
{
	$cxn = open_stream();
	$sql = "DELETE FROM hedonReqs WHERE daytime='$daytime'";
	if(!query($cxn, $sql))
	{
      echo "Error: Denied hedon transaction not removed from table.";
	}
}

// this class deals with spending credits to get things
// In the class is defined a pseudo-table which contains the names, credit costs, cost, and item number of associated
// items. Item number can also be * in which case it should have a specific block of code in the execution function
class creditSpending
{
   // variables containing available credit buyable items
   
   // name of buyable item to be displayed with check box and on checkout
   private $name = array(0 => "Stuff and Things",
                         1 => "Snack Card",
   	                 2 => "10% Additional Discount",
   	                 3 => "6 unsorted/4 sorted Commons",
   	                 10 => "Magic Tournaments",
   	                 11 => "Booster Draft",
   	                 12 => "(Pre)Release",
   	                 13 => "Magic League");
   
   // item number associated with buyable item. * indicates that special code is associate with this. 
   // X indicates that this is a header, not a purchasable item. Name will be displayed in bold
   // N indicates that this is a header which should start a new column. Name will be displayed in bold
   private $itemNumber = array(0 => 'X',
                               1 => 784,
                               2 => '*',
                               3 => 778,
                               10 => 'N',
                               11 => 780,
                               12 => 1193,
                               13 => 1195);

   // cost of buyable item. * indicates that price should be drawn from items table
   private $cost = array(1 => "*",
                         2 => 0,
                         3 => 0,
                         11 => "*",
                         12 => "*",
                         13 => "*");
   
   // credit cost of buyable item.
   private $creditCost = array(1 => 12,
                               2 => 50,
                               3 => 1,
                               11 => 5,
                               12 => 7,
                               13 => 12);

   // other variables
   public $creditsSpent = FALSE; // were credits spent?
   public $creditTotal = 0; // total credits to be spent
   private $checked; // array of checked off boxes
   public $creditBalance; // member's credit balance
   private $insufficientCredits = FALSE; // TRUE if member has insufficient credits
   
// initialization function
// takes data from POST. Determines if sufficient credits are available for purchases. If not, all credit purchases are purged.
// creates array called checked which is an array of which boxes are checked. True if checked.
public function creditSpending()
{
   $CSpurchases = $_POST['CSpurchases'];
   
   if(is_array($CSpurchases))
   {
   
      foreach($CSpurchases as $key => $value)
      {      
         $this->checked[$key] = TRUE;
         $this->creditTotal += $this->creditCost[$key];
      
         $this->creditsSpent = TRUE;
      }
   
      $this->creditBalance = getCreditTotal($_POST['member']);
   
      // if member does not have enough credits
      if($this->creditTotal > $this->creditBalance)
      {
         $this->checked = array(); // clear checked array
         $this->insufficientCredits = TRUE;
         $this->creditsSpent = FALSE;
      }
   }
}
      

// conclude
// concludes sale and executes all credit purchases
// returns change to totalItem Cost, or FALSE if insufficient credits
public function conclude()
{
   if(!$this->creditsSpent)
   {
      return;
   }

   $cxn = open_stream();
   $totalCost = 0;
   $TID = $_SESSION['transaction_ID'];

   if($this->insufficientCredits)
   {
      echo "<b>Insufficient Hedons for Hedon purchases</b><p>";
      $this->creditsSpent = FALSE;
      return "insuf";
   }

   echo "<table border bgcolor=WHITE><tr><td><b>Purchases with Hedons</b></td></tr><tr><td>\n";
   
   foreach($this->checked as $key => $value)
   {
      if(is_int($this->itemNumber[$key]))
      {
         if(transferCredits($_POST['member'], 0, $this->creditCost[$key], $this->name[$key], 0))
         {
            if($this->cost[$key] == '*')
            {
               $sql = "SELECT cost FROM items WHERE ID = '{$this->itemNumber[$key]}'";
               $itemCost = queryOnce($cxn, $sql);
            }
            else
            {
               $itemCost = $this->cost[$key];
            }
            $totalCost += $itemCost;
         
            $sql = "INSERT into soldItem (transactionID, itemID, cost, qty, tax)
                                  VALUES ('$TID', '{$this->itemNumber[$key]}', '$itemCost', '1', '0')";

            query($cxn, $sql);
         
            echo "{$this->creditCost[$key]} CR - {$this->name[$key]}<br>\n";
            
            if($key == 21)
            {
               global $lleagueID;
               $sql = "INSERT INTO league (leagueID, whenplayed, submitter, player, points, game)
	                           VALUES ('$lleagueID', NOW(), '{$_SESSION['ID']}', {$_POST['member']}, 0, 'NEW-HEDON')";
	       if(query($cxn, $sql))
	       {
	          echo "<table border><tr><td>" . printMemberString($member,1) . " added to L5R League</td></tr></table><p>";
	       }
	    }

            if($key == 22)
            {
               global $mleagueID;
               $sql = "INSERT INTO league (leagueID, whenplayed, submitter, player, points, game)
	                           VALUES ('$mleagueID', NOW(), '{$_SESSION['ID']}', {$_POST['member']}, 0, 'NEW-HEDON')";
	       if(query($cxn, $sql))
	       {
	          echo "<table border><tr><td>" . printMemberString($member,1) . " added to Magic League</td></tr></table><p>";
	       }
	    }
	    
	    
         }
         else
            echo "<font color=RED>Error Transferring Hedons for {$this->name[$key]}</font><br>\n";
      }
      else if($this->itemNumber[$key] == '*')
      {
         echo "{$this->creditCost[$key]} CR - {$this->name[$key]}<br>\n";
      }
   }
   echo "<br>Total Hedon Cost: {$this->creditTotal}<br>
         Remaining After Purchase: " . ($this->creditBalance - $this->creditTotal) . "</td></tr></table>";
echo "TC: $totalCost<br>";
   return($totalCost);
            
}

// discount
// returns 10 if there is a discount paid for with credits
public function discount()
{
   if($this->checked[2])
   {
      return 10;
   }
   else
      return 0;
}

// displayCheckBoxes
// displays check boxes to buy things with credits. Sends them in an array called CSpurchases indexed by special item number
public function displayCheckBoxes()
{
   $colCount = 1;
   echo "<table><tr><td valign=top>\n";
   
   foreach($this->name as $key => $toprint)
   {
      if($this->itemNumber[$key] == 'X')
         echo "<b> $toprint</b><br>\n";
      else if($this->itemNumber[$key] == 'N')
      {
         if($colCount > 3)
         {
            $colCount = 1;
            echo "</td></tr><tr><td valign=top><b> $toprint</b><br>\n";
         }
         else
         {
            $colCount++;
            echo "</td><td valign=top><b> $toprint</b><br>\n";
         }
      }
      else
         echo "<input type='checkbox' name='CSpurchases[$key]' value=1 " . (($this->checked[$key]) ? "checked" : "")
            . "> ({$this->creditCost[$key]} H) $toprint<br>\n";
   }
}  

// displayCurrent
// displays what is currently selected for Credits
public function displayCurrent()
{
   echo "<table border><tr><td><b>Purchases for Credits</b></td></tr><tr><td>\n";

   if($this->insufficientCredits)
   {
      echo "<b>Insufficient Hedons for items selected</b><br>
            Attempting to spend: {$this->creditTotal}<br>
            Hedons in Account: {$this->creditBalance}      
            </td></tr></table>\n";
      return FALSE;
   }

   echo "<b>Currently Selected For Credits</b><br>\n";
   
   foreach($this->checked as $key => $value)
   {
      echo "{$this->creditCost[$key]} CR - {$this->name[$key]}<br>";
   }
   
   echo "<br>Total Hedon Cost: {$this->creditTotal}<br>
         Remaining After Purchase: " . ($this->creditBalance - $this->creditTotal) . "</td></tr></table>";
}

} // end class
  
?>
