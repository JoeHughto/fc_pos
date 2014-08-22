<?php
/**
 * Credits.inc provides an API for interacting with member's hedons.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Library
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Crideke Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   printMember()
 *   displayError()
 *   printMemberString()
 */
require_once 'funcs.inc';

// number of credits for a month of working membership
$WORKINGMEMBERCREDITS = 15;

/**
 * DisplayCreditTransactions prints a list of hedon transactions for a given
 *   member.
 * @param int        $SID is the selected member's ID.
 * @param int|string $max is the maximum records that should be returned.
 *   If this value is "ALL", it will return all records for the given member.
 * @return void
 */
function displayCreditTransactions($SID, $max='ALL')
{
    $cxn = open_stream();

    $sql = "SELECT * FROM credits WHERE memberID='$SID' OR (senderID='$SID' AND (reason='3' OR reason ='0')) ORDER BY daytime DESC";
    $result = query($cxn, $sql);

    if ($cxn->affected_rows > 0) {
        for ($i = 0;
            (($i < $max) || ($max == 'ALL')) &&
                $row = mysqli_fetch_assoc($result);
            $i++) {
            if ($i == 0) {
                echo "<b>Recent Transactions</b><br>";
            }

            extract($row);
            echo "<hr>$daytime<br>";

            if ($credits > 0) {
                switch ($reason) {
                case 0:
                    echo "$credits H paid to Friend Computer<br>for '$notes'<br>\n";
                    break;    
                case 1:
                    echo "$credits H for Coverage or Service<br>
                        for '$notes'<br>\n";
                    break;
                case 2:
                    echo "$credits H from official fund of ";
                    printMember($senderID, 1);
                    echo "<br> for '$notes'<br>\n";
                    break;
                case 3:
                    if ($memberID == $SID) {
                        echo "$credits H from ";
                        printMember($senderID, 1);
                        echo "<br> for '$notes'<br>\n";
                        break;
                    } else {
                        echo "$credits H to ";
                        printMember($memberID, 1);
                        echo "<br> for '$notes'<br>\n";
                        break;
                    }
                }
            }
        } // end for
    }
}

/**
 * DisplayHedonRequests prints a list of hedon transactions for a given
 *   member.
 * @param int        $SID is the selected member's ID.
 * @param string|int $max is the maximum records that should be returned.
 *   If this value is "ALL", it will return all records for the given member.
 * @return void
 */
function displayHedonRequests($SID, $max='ALL')
{
    $cxn = open_stream();

    $sql = "SELECT * FROM hedonReqs WHERE memberID='$SID' ORDER BY daytime DESC";
    $result = query($cxn, $sql);

    if ($cxn->affected_rows > 0) {
        for ($i = 0;
            (($i < $max) || ($max == 'ALL')) &&
                $row = mysqli_fetch_assoc($result);
            $i++) {
            if ($i == 0) {
                echo "<b>Pending Requests</b><br>";
            }

            extract($row);
            echo "$daytime<br>";
            echo "$hedons H requested for '$notes'<br>\n";
        } // end for
        echo "<hr>";
    }
}

/**
 * DisplayAllHedonRequests prints a list of all currently pending Hedon
 *   Requests, with checkboxes to allow their batch modification.
 * @return void
 */
function displayAllHedonRequests()
{
    $cxn = open_stream();

    $sql = "SELECT * FROM hedonReqs ORDER BY memberID DESC";
    $result = query($cxn, $sql);

    if ($cxn->affected_rows > 0) {
        for ($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
            if ($i == 0) {
                echo "<b>Pending Requests</b><br>";
            }

            extract($row);
            echo "$daytime<br>";
            echo "<input type='checkbox' name='selectedReqs[]' value='$daytime' />";
            printMember($memberID, 1);
            echo "$hedons H requested for '$notes'<br>\n"; 
        } // end for
        echo "<hr>";
    }
}

/**
 * DisplayAllBounties prints a list of all currently available Bounties,
 *   with checkboxes to allow their batch modification.
 * @return void
 */
function displayAllBounties()
{
    $cxn = open_stream();

    $sql = "SELECT * FROM bounties ORDER BY hedons DESC";
    $result = query($cxn, $sql);

    if ($cxn->affected_rows > 0) {
        for ($i = 0; $row = mysqli_fetch_assoc($result); $i++) {
            if ($i == 0) {
                echo "<b>Unclaimed Bounties</b><br>";
            }

            extract($row);
            echo "<br>";
            echo "<input type='checkbox' name='selectedBounties[]' value='$daytime' />";
            echo "$hedons H offered for '$notes'<br>\n";
        } // end for
        echo "<hr>";
    }
}

/**
 * DisplayMembershipStatus prints a block of text stating what a given
 *   member's membership status currently is.
 * @param int $SID is the given member's ID.
 * @return void
 */
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

    if ($wMember && $cMember) {
        echo "<b>You are currently a Double Member</b><br>";
        echo "Working membership through: " . $wDate->format("F jS, Y") . "<br>";
        if ($cExp == '2200-01-01') {
            echo "Lifetime Member.</b><p>";
        } elseif ($cExp == '2300-01-01') {
            echo "Eternal Member.</b><p>";
        } else {
            echo "Contributing Member through</b><br>" . $cDate->format("F jS, Y") . "<p>";
        }
    } elseif ($wMember) {
        echo "<b>You are a Working Member through</b><br>" . $wDate->format("F jS, Y"). "<p>";
    } elseif ($cMember) {
        echo "<b>You are";
        if ($cExp == '2200-01-01') {
            echo " a Lifetime Member.</b><p>";
        } else if ($cExp == '2300-01-01') {
            echo " an Eternal Member.</b><p>";
        } else {
            echo " a Contributing Member through</b><br>" . $cDate->format("F jS, Y") . "<p>";
        }
    }
}

/**
 * DisplayMembershipStatusString provides the same output as
 *   DisplayMembershipStatus, but returns a string representation, instead
 *   of printing directly to the page.
 * @param int $SID is the given member's ID.
 * @return string Returns a string containing all the membership status info
 *   for a given member.
 */
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

    if ($wMember && $cMember) {
        $text =  "You are currently a Double Member\n";
        $text .= "Working membership through: " . $wDate->format("F jS, Y") . "\n";
        if ($cExp == '2200-01-01') {
            $text .= "You are also a Lifetime Member.\n\n";
        } elseif ($cExp == '2300-01-01') {
            $text .= "You are also an Eternal Member.\n\n";
        } elseif ($cExp == '2400-01-01') {
            $text .= "You are also a Dynasty Member.\n\n";
        } else {
            $text .= "Contributing Member through" . $cDate->format("F jS, Y") . "\n\n";
        }
    } elseif ($wMember) {
        $text .= "You are a Working Member through" . $wDate->format("F jS, Y"). "\n\n";
    } elseif ($cMember) {
        $text .= "You are";
        if ($cExp == '2200-01-01') {
            $text .= " a Lifetime Member.\n\n";
        } elseif ($cExp == '2300-01-01') {
            $text .= " an Eternal Member.\n\n";
        } elseif ($cExp == '2400-01-01') {
            $text .= " a Dynasty Member.\n\n";
        } else {
            $text .= " a Contributing Member through" . $cDate->format("F jS, Y") . "\n\n";
        }
    }
    return ($text);
}

/**
 * GetCreditTotal takes a member's ID, and returns the number of Hedons
 *   that member has.
 * @param int $ID is the given member's ID.
 * @return float Returns a value representing the number of Hedons a
 *   given member currently has.
 */
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

/**
 * RenewMembership takes a member's ID and a number of months, and exchanges
 *   that member's hedons for months of membership at a rate of 15h/mo.
 *   THIS FUNCTION DOES NOT CHECK MEMBER'S CREDIT BALANCE.
 * @global int $WORKINGMEMBERCREDITS is a constant representing the number of
 *   Hedons a month's membership costs. Currently 15h/mo.
 * @param int $ID     is the member's ID
 * @param int $months is a number of months the member wishes to purchase.
 * @return void
 */
function renewMembership($ID, $months)
{
    $months = round($months);

    $cxn = open_stream();
    global $WORKINGMEMBERCREDITS;
   
    // determine new expiration date
    $sql = "SELECT workingExp FROM members WHERE ID='$ID'";
    $row = queryAssoc($cxn, $sql);
    extract($row);
    $date = date_create($workingExp);
    $nowdate = date_create();
    if ($date < $nowdate) {
        $date = $nowdate;
    }
    $date->modify("+$months months");
    $newExp = $date->format("Y-m-d");
   
    // set new expiration date
    $sql = "UPDATE members SET workingExp='$newExp' WHERE ID='$ID'";
    query($cxn, $sql);
   
    // spend credits
    $creditCost = $WORKINGMEMBERCREDITS * $months;
    $reason = "$months months of Membership";
    $sql = "INSERT INTO credits (daytime, memberID, credits, reason, senderID, notes)
        VALUES (NOW(), '0', '$creditCost', '0', '$ID', '$reason')";
    query($cxn, $sql);
}
   
/**
 * TransferCredits processes tranfers of Hedons between members.
 * 
 * Reason Code Definitions:
 * 0 - payment to Friend Computer
 * 1 - automatic payment for coverage
 * 2 - other officer payment
 * 3 - intermember transfer
 * 4 - transfer caused by Admin
 * 
 * @param  int    $sender is the member from whom Hedons will be taken.
 * @param  int    $recep  is the member to whom Hedons will be given.
 * @param  float  $amt    is the amount of Hedons being transferred.
 * @param  string $note   is a note that can be appended to the transfer.
 * @param  int    $reason is a Reason Code for the transfer.
 * @return boolean Returns a boolean representing whether the transfer was
 *   successful.
 */
function transferCredits($sender, $recep, $amt, $note, $reason)
{
    $cxn = open_stream();

    // check to see if the sender has the credits

    $balance = getCreditTotal($sender);
    if ($reason == 3 && $balance < $amt) {
        return false; // if there is not enough in the account
    }

    $note = strip_tags($note);

    if ($stmt = $cxn->prepare(
        "INSERT INTO credits
        (daytime, memberID, credits, reason, senderID, notes)
        VALUES (NOW(), ?, ?, ?, ?, ?)"
    )
    ) {
        $stmt->bind_param("idiis", $recep, $amt, $reason, $sender, $note);
        $stmt->execute();
        return true;
    } else {
        displayError("Error Binding Query. Hedons not transferred.");
        return false;
    }
}

/**
 * RequestCredits adds a Hedon Request to the database, to be approved or
 *   denied at a later point.
 * @param  int    $memID is the member ID of the member requesting Hedons.
 * @param  float  $amt   is the number of Hedons being requested.
 * @param  string $note  is a reason for the request.
 * @return boolean Returns a boolean representing whether the Hedon Request
 *   was successfully created.
 */
function requestCredits($memID, $amt, $note)
{
    $cxn = open_stream();

    $note = strip_tags($note);

    if ($stmt = $cxn->prepare(
        "INSERT INTO hedonReqs
        (daytime, memberID, hedons, notes)
        VALUES (NOW(), ?, ?, ?)"
    )
    ) {
        $stmt->bind_param("ids", $memID, $amt, $note);
        $stmt->execute();
        return true;
    } else {
        displayError("Error Binding Query. Hedons not requested. Contact your local High Programmer.");
        return false;
    }
}

/**
 * ApproveHedonRequest takes a datetime and approves the first Hedon Request
 *   with a matching timestamp by removing it from the table, and transferring
 *   the requisitioned hedons to the requesting member.
 * @param datetime $daytime is the datetime of the HR that is being approved.
 * @return void
 */
function approveHedonRequest($daytime)
{
    $cxn = open_stream();
    $sql = "SELECT * FROM hedonReqs WHERE daytime='$daytime'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);

    if (transferCredits(0, $row[1], $row[2], $row[3], 1)) {
        $sql = "DELETE FROM hedonReqs WHERE daytime='$daytime'";
        if (!query($cxn, $sql)) {
            echo "Error: Approved hedon transaction not removed from table.";
        }
    }
}

/**
 * ClaimBounty takes a datetime and claims the first Bounty with a
 *   matching timestamp, by deleting the bounty from the table, and
 *   submitting a Hedon Request for the Bounty.
 * @param datetime $daytime is the datetime of the Bounty that is being claimed.
 * @return void
 */
function claimBounty($daytime)
{
    $cxn = open_stream();
    $sql = "SELECT * FROM bounties WHERE daytime='$daytime'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);

    if (requestCredits($_SESSION['ID'], $row[1], $row[2])) {
        $sql = "DELETE FROM bounties WHERE daytime='$daytime'";
        if (!query($cxn, $sql)) {
            echo "Error: Requested bounty not removed from table.";
        }
    }
}

/**
 * DenyHedonRequest takes a datetime and denies the first Hedon Request
 *   with a matching timestamp by deleting it from the table.
 * @param datetime $daytime is the datetime of the HR that is being denied.
 * @return void
 */
function denyHedonRequest($daytime)
{
    $cxn = open_stream();
    $sql = "DELETE FROM hedonReqs WHERE daytime='$daytime'";
    if (!query($cxn, $sql)) {
        echo "Error: Denied hedon transaction not removed from table.";
    }
}

/**
 CreditSpending provides an interface to simplify spending Hedons on
 *   items that are Hedon purchasable.
 * 
 * Within the class, a pseudo-table is defined which contains the names,
 *   credit costs, cost, and item number of associated items. Item number
 *   can also be specified, in which case it should have a specific block of
 *   code in the execution function.
 * 
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Library
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Crideke Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */
class creditSpending
{
    // variables containing available credit buyable items
   
    // name of buyable item to be displayed with check box and on checkout
    private $name = array(
        0 => "Stuff and Things",
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
    private $itemNumber = array(
        0 => 'X',
        1 => 784,
        2 => '*',
        3 => 778,
        10 => 'N',
        11 => 780,
        12 => 1193,
        13 => 1195);

    // cost of buyable item. * indicates that price should be drawn from items table
    private $cost = array(
        1 => "*",
        2 => 0,
        3 => 0,
        11 => "*",
        12 => "*",
        13 => "*");
   
    // credit cost of buyable item.
    private $creditCost = array(
        1 => 12,
        2 => 50,
        3 => 1,
        11 => 5,
        12 => 7,
        13 => 12);

    // other variables
    public $creditsSpent = false; // were credits spent?
    public $creditTotal = 0; // total credits to be spent
    private $checked; // array of checked off boxes
    public $creditBalance; // member's credit balance
    private $insufficientCredits = false; // true if member has insufficient credits
   
    /**
     * Constructor for objects of class CreditSpending.
     * 
     * Currently takes data from POST. Determines if sufficient credits
     *   are available for purchases. If not, all credit purchases are purged.
     *   creates array called checked which is an array of which boxes are
     *   checked. checked[n] will be true if checkbox[n] is checked.
     */
    public function creditSpending()
    {
        $CSpurchases = $_POST['CSpurchases'];
   
        if (is_array($CSpurchases)) {
            foreach ($CSpurchases as $key => $value) {      
                $this->checked[$key] = true;
                $this->creditTotal += $this->creditCost[$key];

                $this->creditsSpent = true;
            }

            $this->creditBalance = getCreditTotal($_POST['member']);

            // if member does not have enough credits
            if ($this->creditTotal > $this->creditBalance) {
                $this->checked = array(); // clear checked array
                $this->insufficientCredits = true;
                $this->creditsSpent = false;
            }
        }
    }
      
    /**
     * Conclude concludes sale and executes all credit purchases.
     * @global type $mleagueID Current Magic League ID
     * @return string Returns either the total cost, or "insuf" if insufficient
     *   Hedons.
     */
    public function conclude()
    {
        if (!$this->creditsSpent) {
            return;
        }

        $cxn = open_stream();
        $totalCost = 0;
        $TID = $_SESSION['transaction_ID'];

        if ($this->insufficientCredits) {
            echo "<b>Insufficient Hedons for Hedon purchases</b><p>";
            $this->creditsSpent = false;
            return "insuf";
        }

        echo "<table border bgcolor=WHITE><tr><td><b>Purchases with Hedons</b></td></tr><tr><td>\n";

        foreach ($this->checked as $key => $value) {
            if (is_int($this->itemNumber[$key])) {
                if (transferCredits(
                    $_POST['member'], 0, $this->creditCost[$key],
                    $this->name[$key], 0
                )
                ) {
                    if ($this->cost[$key] == '*') {
                        $sql = "SELECT cost FROM items WHERE ID = '{$this->itemNumber[$key]}'";
                        $itemCost = queryOnce($cxn, $sql);
                    } else {
                        $itemCost = $this->cost[$key];
                    }
                    $totalCost += $itemCost;

                    $sql = "INSERT into soldItem (transactionID, itemID, cost, qty, tax)
                        VALUES ('$TID', '{$this->itemNumber[$key]}', '$itemCost', '1', '0')";

                    query($cxn, $sql);

                    echo "{$this->creditCost[$key]} CR - {$this->name[$key]}<br>\n";

                    if ($key == 22) {
                        global $mleagueID;
                        $sql = "INSERT INTO league (leagueID, whenplayed, submitter, player, points, game)
                            VALUES ('$mleagueID', NOW(), '{$_SESSION['ID']}', {$_POST['member']}, 0, 'NEW-HEDON')";
                        if (query($cxn, $sql)) {
                            echo "<table border><tr><td>" . printMemberString($member, 1) . " added to Magic League</td></tr></table><p>";
                        }
                    }


                } else {
                    echo "<font color=RED>Error Transferring Hedons for {$this->name[$key]}</font><br>\n";
                }
            } elseif ($this->itemNumber[$key] == '*') {
                echo "{$this->creditCost[$key]} CR - {$this->name[$key]}<br>\n";
            }
        }
        echo "<br>Total Hedon Cost: {$this->creditTotal}<br>
            Remaining After Purchase: " . ($this->creditBalance - $this->creditTotal) . "</td></tr></table>";
        echo "TC: $totalCost<br>";
        return($totalCost);
    }

    /**
     * Discount reduces the total cost of the order by 10% if the 50h discount
     *   checkbox has been checked.
     * @return int Returns 10 if user has bought Hedon Discount, 0 otherwise.
     */
    public function discount()
    {
        if ($this->checked[2]) {
            return 10;
        } else {
            return 0;
        }
    }
    
    /**
     * DisplayCheckBoxes prints all Hedon Purchasable items to the register
     *   page, as defined in the data structures in class CreditSpending.
     * @return void
     */
    public function displayCheckBoxes()
    {
        $colCount = 1;
        echo "<table><tr><td valign=top>\n";

        foreach ($this->name as $key => $toprint) {
            if ($this->itemNumber[$key] == 'X') {
                echo "<b> $toprint</b><br>\n";
            } elseif ($this->itemNumber[$key] == 'N') {
                if ($colCount > 3) {
                    $colCount = 1;
                    echo "</td></tr><tr><td valign=top><b> $toprint</b><br>\n";
                } else {
                    $colCount++;
                    echo "</td><td valign=top><b> $toprint</b><br>\n";
                }
            } else {
                echo "<input type='checkbox' name='CSpurchases[$key]' value=1 " . (($this->checked[$key]) ? "checked" : "")
                .   "> ({$this->creditCost[$key]} H) $toprint<br>\n";
            }
        }
    }  

    // displayCurrent
    // displays what is currently selected for Credits
    /**
     * DisplayCurrent prints a box containing all currently selected
     *   Hedon Purchases to the page it's called from.
     * @return boolean Returns false if insufficient Hedons, true otherwise.
     */
    public function displayCurrent()
    {
        echo "<table border><tr><td><b>Purchases for Hedons</b></td></tr><tr><td>\n";

        if ($this->insufficientCredits) {
            echo "<b>Insufficient Hedons for items selected</b><br>
                Attempting to spend: {$this->creditTotal}<br>
                Hedons in Account: {$this->creditBalance}      
                </td></tr></table>\n";
            return false;
        }

        echo "<b>Currently Selected For Hedons</b><br>\n";

        foreach ($this->checked as $key => $value) {
            echo "{$this->creditCost[$key]} CR - {$this->name[$key]}<br>";
        }

        echo "<br>Total Hedon Cost: {$this->creditTotal}<br>
            Remaining After Purchase: " . ($this->creditBalance - $this->creditTotal) . "</td></tr></table>";
    }
} // end class  
?>
