<?php
/**
 * @file member.inc
 * @brief member.inc is a library containing most membership and frequent gamer
 *   functions.
 * 
 * This file includes:
 * Nothing!
 * 
 * @link http://www.worldsapartgames.org/fc/index.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$FGDISCOUNT = array(0 => 0, 1 => 1, 5 => 1, 25, 50, 75, 100, 150, 200, 250, 
    300, 400, 500, 600, 700, 800, 900, 1000, 2000);

/**
 * FG_discount calculates and returns the discount for a given sales total.
 * @param float $sales is the sales total to be tested.
 * @retval int Returns an integer discount percentage between 5 and 21.
 */
function FG_discount($sales)
{
    if ($sales > 2000) {
        return 21;
    } elseif ($sales > 1000) {
        return 20;
    } elseif ($sales > 900) {
        return 19;
    } elseif ($sales > 800) {
        return 18;
    } elseif ($sales > 700) {
        return 17;    
    } elseif ($sales > 600) {
        return 16;        
    } elseif ($sales > 500) {
        return 15;    
    } elseif ($sales > 400) {
        return 14;   
    } elseif ($sales > 300) {
        return 13;   
    } elseif ($sales > 250) {
        return 12;   
    } elseif ($sales > 200) {
        return 11;  
    } elseif ($sales > 150) {
        return 10;
    } elseif ($sales > 100) {
        return 9;    
    } elseif ($sales > 75) {
        return 8;    
    } elseif ($sales > 50) {
        return 7;   
    } elseif ($sales > 25) {
        return 6;   
    } elseif ($sales > 1) {
        return 5;   
    }
    return 0;
}

/**
 * FG_discountNow takes a member ID, and calculates that member's discount,
 *   based on their last month's sales total.
 * @param int $member is the chosen member's ID.
 * @retval int Returns an integer discount percentage between 5 and 21.
 */
function FG_discountNow($member)
{
    return(FG_discount(memberSalesLastMonth($member)));
}

/**
 * FG_showInfo prints a Member Info box, including all FG information
 *   easily provided, and taking into account the current sale.
 * @global array $FGDISCOUNT This is the array of discount values, used to
 *   calculate distance to the next level.
 * @param int   $member   This is the selected member's ID.
 * @param float $thissale This is the total of the current sale.
 */
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
        To next level: " . money($tonext);
}

/**
 * FG_showInfoNoSale prints Member Info, including all FG information
 *   easily provided, but ignoring any potential current sales.
 * @global array $FGDISCOUNT This is the array of discount values, used to
 *   calculate distance to the next level.
 * @param int $member This is the selected member's ID.
 */
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
        To next level: " . money($tonext);
}

/**
 * giveReferralCredit checks a member's account to see who, if anyone, referred
 *   them, and awards that person store credit as appropriate from the
 *   given sale.
 * @param int   $customerID is the member ID of the member making a purchase.
 * @param float $totalPrice is the total price of the curretn sale.
 * @param int   $TID        is the transaction ID of the sale for which there is referral
 *   credit being awarded.
 * @retval string Returns a descriptive string if a member is awarded some 
 *   referral credit, and false if there is no credit awarded, or if there is 
 *   no referrer to award credit to.
 */
function giveReferralCredit($customerID, $totalPrice, $TID)
{
    $cxn = open_stream();

    $sql = "SELECT refBy, dt FROM howFound WHERE memberID='$customerID'";
    $result = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($refBy > 0) {
            $date = date_create($dt); //The date they became a member
            $earliestDate = date_create(); //Today
            $memberStatus = checkMember($refBy);
            if ($memberStatus >= 2) {
                //If the referrer is a member, working or higher
                $earliestDate->modify("-1 month");
                //Subtract a month from today
            } else {
                $earliestDate->modify("-1 month");
            }
            if ($earliestDate < $date) {
                $amount = $totalPrice * .05;
                $amount = round($amount, 2);
                $note = "REF $customerID";
                accountTransact($refBy, $amount, $TID, $note);
                $returnString = printMemberString($refBy, 1) . " given " 
                    . money($amount) . " in credit for referral bonus.";
                return ($returnString);
            } else {
                return false;
            }
        }
    }
    return false;
}   

/**
 * memberSalesLastMonth takes a member ID, and returns that member's total
 *   sales last month.
 * @param int $member is the chosen member's ID.
 * @retval float Returns the sum of the chosen member's sales for last month.
 */
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
        FROM transactions WHERE customerID='$member' "
        . "AND whensale > '$start' AND whensale < '$end'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return($row[0]);
}

/**
 * memberSalesThisMonth takes a member ID, and returns that member's total
 *   sales so far this month.
 * @param int $member is the chosen member's ID.
 * @retval float Returns the sum of the chosen member's sales for this month.
 */
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

function allMemberSalesLastMonth()
{
    $cxn = open_stream();
    $sql = "SELECT customerID, (SUM(totalPrice)) FROM transactions 
          where customerID is not NULL and 
                customerID <> 0 and 
                whensale between DATE_FORMAT(NOW() - INTERVAL 1 MONTH ,'%Y-%m-01') AND LAST_DAY(NOW() - INTERVAL 1 MONTH) 
          group by customerID";
    $result = query($cxn,$sql);
    return $result;
}

function allMemberSalesThisMonth()
{
    $cxn = open_stream();
    $sql = "SELECT customerID, (SUM(totalPrice)) FROM transactions 
          where customerID is not NULL and 
                customerID <> 0 and 
                whensale between DATE_FORMAT(NOW() ,'%Y-%m-01') AND NOW() 
          group by customerID";
    $result = query($cxn,$sql);
    return $result;
}

/**
 * peopleReferred takes a member's ID, and returns the number of other
 *   members who were referred by them.
 * @param int $ID is the chosen member's ID.
 * @retval int Returns the number of members referred by the chosen member.
 */
function peopleReferred($ID)
{
    $cxn = open_stream();
    $sql = "SELECT COUNT(refBy) FROM howFound WHERE refBy='$ID'";
    $count = queryOnce($cxn, $sql);
    return($count);
}

/**
 * taxExempt takes a member's ID, and returns either that member's
 *   tax exempt number, or false if no tax exempt number exists.
 * @param int $ID is the chosen member's ID.
 * @retval string Returns the chosen member's tax exempt number, or false
 *   if none exists.
 */
function taxExempt($ID)
{
    $cxn = open_stream();

    $sql = "SELECT taxexempt FROM members WHERE ID='$ID' "
        . "AND taxexempt IS NOT NULL AND taxexempt != 'NULL'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return((($row[0] > 0) || (strlen($row[0]) > 0)) ? $row[0] : false);
}

/**
 * newMember is a class containing functions relavent to signing up
 *   new members.
 * 
 * @link http://www.worldsapartgames.org/fc/index.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @since     Project has existed since time immemorial.
 */
class newMember
{
    var $fname;
    var $lname;
    var $phone;
    var $email;
    var $findus;
    var $member;
    var $message; // message for FC

    /**
     * Constructor for class newMember.
     */
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

    /**
     * displayQuickForm either prints the quick add form, if there is no member
     *   currently set, or does nothing, if a member has been set.
     */
    function displayQuickForm()
    {
        $cxn = open_stream();
        $member = $this->member = $_POST['member'];
        if (!$this->member > 0) {
            // this will occur if there was data but no member was selected
            // it should appear blank in this case.
            if ($this->fname == 'exist!') {
                unset($this->fname, $this->lname, $this->phone, $this->email);
            }

            $this->phone = formPhoneNumber($this->phone);
            echo "<table border cellpadding=5><tr><td>";
            echo "<b>Quick Add Customer<b><br>
                First Name: <input type='text' name='Mfname' value='" 
                . $this->fname . "' size=20 maxlength=50>
                Last Name: <input type='text' name='Mlname' value='" 
                . $this->lname . "' size=20 maxlength=50><br>
                Phone Number: <input type='text' name='Mphone' value='" 
                . $this->phone . "' size=12 maxlength=20>
                Email Address: <input type='text' name='Memail' value='" 
                . $this->email . "' size=40 maxlength=150><br>
                How Did You Find Us? <input type='text' name='Mhowfound' value='" 
                . $this->howfound . "' size=75 maxlength=255><br>
                Referred By Someone? ";
            selectMember("Mref", 0);
            echo "<hr>
                <font size=-1>Notice how it says 'tax exempt number' there? 
                If this doesn't mean anything to you, leave it blank. 
                If you have a tax exempt organization before you, 
                put in the parent organization at first name and 
                sub organization as last name. For example, 
                for Excalibur at Hampshire enter 'Hampshire' as first name 
                and 'Excalibur' as last name.</font><br>
                Tax Exempt Number: <input type='text' name='Mtaxexempt' 
                value='" . $this->taxexempt . "' size=15 maxlength=15><br>";
            echo "</td></tr></table><p>";
        }

    }

    /**
     * process takes whatever information is currently stored in the object,
     *   and attempts to add a new member with it. If the member already exists,
     *   it will attempt to update the existing member.
     * @retval boolean Returns true if successful, and false if creating/updating
     *   fails.
     */
    function process()
    {
        $cxn = open_stream();

        if ($this->fname != 'exist!') {
            // if it is supposedly new
            $exists = false;

            $sql = "SELECT ID FROM members WHERE email='" . $this->email . "'";
            $row = queryAssoc($cxn, $sql);

            // if no email address
            if ($this->email == '' && $this->phone == '') {
                return(0);
            }

            if ($cxn->affected_rows > 0) {
                $this->message .= $this->email . " found in database.<br>";
                $this->member = $row['ID'];
                $exists = true;
            } else {
                $sql = "SELECT ID FROM members WHERE fname='" . $this->fname . "' AND lname='" . $this->lname . "'";
                $row = queryAssoc($cxn, $sql);
                if ($cxn->affected_rows > 0) {
                    $this->message .= $this->fname . " " 
                        . $this->lname . " found in database.<br>";
                    $this->member = $row['ID'];
                    $exists = true;
                }
            }

            $this->fname = stripslashes($this->fname);
            $this->lname = stripslashes($this->lname);
            $this->taxexempt = (strlen($this->taxexempt) > 6) ? $this->taxexempt : "NULL";

            if ($this->email == '' && $_POST['member'] == 0 
                && strlen($this->fname) > 0 
                && strlen($this->lname) > 0 && !$exists
            ) {
                $stmt = $cxn->prepare(
                    "INSERT INTO members 
                    (login, password, fname, lname, phone1, email, 
                    memberSince, taxexempt)
                    VALUES (?,?,?,?,?,?, NOW(),?)"
                );
                $login = strtolower(substr($this->fname, 0, 1) . $this->lname);
                $password = hash('sha256', '!@#$%^&*()1');
                $stmt->bind_param('sssssss', $login, $password, $this->fname, $this->lname, $this->phone, $this->email, $this->taxexempt);
                if ($stmt->execute()) {
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
                return (false);
            } elseif (!$exists) {
                // if it is still considered new
                $stmt = $cxn->prepare(
                    "INSERT INTO members 
                    (login, password, fname, lname, phone1, email, 
                    memberSince, taxexempt)
                    VALUES (?,?,?,?,?,?, NOW(), ?)"
                );
                $login = strtolower(substr($this->fname, 0, 1) . $this->lname);
                $rawpass = substr($this->fname, 2, 2) . substr($this->email, 4, 2) . $_SESSION['lastActive'];
                $password = hash('sha256', $rawpass);
                $stmt->bind_param('sssssss', $login, $password, $this->fname, $this->lname, $this->phone, $this->email, $this->taxexempt);
                if ($stmt->execute()) {
                    $date = date_create();
                    $date->modify("+1 month");
                    $datestr = date_format($date, "l, F jS, Y");

                    // add the howfound if it was included
                    if ((strlen($this->howfound) > 0) OR ($this->refBy > 0)) {
                        $sql = "SELECT ID FROM members WHERE login='$login'";
                        $row = queryAssoc($cxn, $sql);
                        $memberID = $row['ID'];
                        if ($this->refBy > 0) {
                            $this->howfound .= " REFBY: " . printMemberString($this->refBy, 1);
                            $stmt = $cxn->prepare("INSERT INTO howFound (memberID, dt, howfound, refBy) VALUES (?, NOW(), ?, ?)");
                            $stmt->bind_param('dsd', $memberID, $this->howfound, $this->refBy);
                            $this->message .= "New customer referred by " . printMemberString($this->refBy, 1) . "<br>\n";
                        } else {
                            $stmt = $cxn->prepare("INSERT INTO howFound (memberID, dt, howfound) VALUES (?, NOW(), ?)");
                            $stmt->bind_param('ds', $memberID, $this->howfound);
                            $this->message .= "New customer not referred by existing member.<br>\n";
                        }

                        if ($stmt->execute()) {
                            $this->message .= "<br>Method of finding submitted successfully<br>";
                        } else {
                            displayError("<font color=RED>Error submitting method of finding<br>" . $cxn->error);
                        }
                    }

                    $couponmessage = "Dear " . $this->fname . " " . $this->lname . "
                        On behalf of the Pioneer Valley Gamer Collective, I, 
                        Friend Computer, would like to thank you for 
                        registering with Worlds Apart Games. This now means 
                        that you are enrolled in the Frequent Gamer Rewards 
                        Program. The more you buy one month, the higher your 
                        discount the next month. If you are one of the top 
                        three purchasers for the month, you get cash rewards! 
                        Visit http://www.worldsapartgames.org/fgrp.php for 
                        more details. Want to know where you are in the 
                        standings? Just drop by the store and ask."

                        . (($this->refBy > 0) ? "You were referred by " 
                        . printMemberString($this->Mref, 5) . " who will get "
                        . "a little token of our appreciation for bringing "
                        . "your into our community in the form of store "
                        . "account. " : "") 
                        ."Know some other fun people that would like Worlds 
                        Apart? Send them in, and if they list you as the 
                        person who referred them, then you will get a 
                        percentage of what they buy put into your store 
                        account. Ask at the store for more details.

                        By being in our database, you are able to log into
                        your Friend Computer account. This lets you spend your 
                        Hedons, transfer account to other people, and do some 
                        other things. Your info is as follows:
                        http://www.worldsapartgames.org/fc
                        Login: $login
                        Password: $rawpass

                        As you were hopefully told, you will now be getting our 
                        newsletter which is full of great information on 
                        Worlds Apart events and new releases.

                        As additional thanks for signing up, we are sending 
                        you this coupon good for 10% off any purchase. 
                        Just print out this email and bring it in " .
                        "before $datestr for your discount. We look forward 
                        to seeing you soon.

                        Sincerely
                        Friend Computer
                        Robot Overlord of Worlds Apart Games\n";

                    $header = "from: friendcomputer@worldsapartgames.org
                        Reply-To: newsletter@pvgaming.org
                        Precedence: bulk
                        X-Mailer: PHP/" . phpversion();


                    mail(
                        $this->email, 
                        "Thanks for Signing Up With Worlds Apart", 
                        $couponmessage, $header
                    );
                } else {
                    displayError("Error submitting new member");
                }

                $stmt->close();
                $this->message .= $this->fname . " " . $this->lname . " added to list.<br>\n";

                if (transferCredits(
                    0, $_SESSION['ID'], 1, ('For email for ' . $this->lname), 1
                )
                ) {
                    $this->message .= "Credits awarded.<br>\n";
                }

                // Check for people listing themselves as referrer
                if ($this->refBy == $_SESSION['ID']) {
                    $srmessage 
                        = "A volunteer just submitted a customer as referred 
                        by themselves. Make a point in checking with them
                        to make sure they aren't gaming the system.

                        Volunteer: " . printMemberString($_SESSION['ID'], 1) 
                        . "RefBy: " . printMemberString($this->refBy, 1) 
                        . "New Customer: {$this->fname} {$this->lname}";

                    if (mail(
                        "gm@pvgaming.org", "FC Self Referral", $srmessage, $header
                    )
                    ) {
                        $this->message .= "<p>You entered yourself as referrer. "
                            . "An email was sent to the High Programmer "
                            . "as a matter of policy. We may be in touch to "
                            . "confirm the referral was processed correctly. "
                            . "Thanks for all your hard work!<br>\n";
                    } else {
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
                if ($this->email != '') {
                    return true;
                } else {
                    return false;
                }
            }
        } elseif ($exists) {
            $this->message .= "<b>" . $this->fname . " " . $this->lname 
                . " is already in the database and cannot have any rice pilaf</b><p>";
            echo $this->message;
            return false;
        }

        // if it is an update or if it was supposed to be new but actually exists, still update info
        if ($_POST['oldmem'] == $this->member) {
            // only do this if the member was not changed
            $stmt = $cxn->prepare("UPDATE members SET phone1=?, email=? WHERE ID=?");
            $stmt->bind_param('ssi', $this->phone, $this->email, $this->member);
            $stmt->execute();
            $stmt->close();
            $_POST['member'] = ($_POST['member'] == 0) ? $this->member : $_POST['member'];
        }
        echo $this->message;
        return false;
    }
}
?>