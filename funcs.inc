<?php
/**
 * @file funcs.inc is the main function library of FriendComputer
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @package   FriendComputer\Library\Core
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/index.php
 * @since     Project has existed since time immemorial.
 */

session_start();

require_once 'config.inc'; // include specific info for specific database

if (!$_SESSION['loggedin']) {
    if (login($_POST['username'], $_POST['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['lastActive'] = mktime();
    } else {
        displayLogin();
        include 'footer.php';
        die();
    }
}

// lastActive is stored in Unix timestamp
$elapsed = mktime() - $_SESSION['lastActive'];

$_SESSION['lastActive'] = mktime();

$ADMINEMAIL = "gm@pvgaming.org";

$IMAGEROOT = "http://www.worldsapartgames.org/productimages/";

$SHIFTCREDITS = 4; // credits for covering one standard shift

/**
 * MegaStrip is used to strip slashes from GET, POST, and COOKIE.
 */
function megaStrip()
{
    $_POST = (
        (function_exists("get_magic_quotes_gpc") &&  get_magic_quotes_gpc()) ||
        (
            ini_get('magic_quotes_sybase') &&
            (strtolower(ini_get('magic_quotes_sybase'))!="off")
        )
    ) ? stripslashes_deep($_POST) : $_POST;
}

/**
 * Stripslashes_deep is used by megaStrip() to remove slashes from arrays
 *   cleanly.
 * @param mixed $value is a string, or array of strings to be stripped of
 *   slashes.
 * @retval mixed Returns a string or array of strings without slashes.
 */
function stripslashes_deep($value)
{
    $value = is_array($value) ?
        array_map('stripslashes_deep', $value) :
        stripslashes($value);
    return $value;
}

/**
 * AccountTransact is used to adjust store credit ledgers as needed.
 * @param int    $member  is the selected member's ID
 * @param float  $amount  is the amount by which the member's store credit
 *   will be adjusted, up if positive, or down if negative.
 * @param int    $transID is the ID of the register transaction leading to this
 *   ledger record.
 * @param string $reason  is the explanation attached to the added record, 
 *   for admin review purposes.
 * @retval boolean Returns true if the record is added to the storeAccount
 *   table, false if the record fails to be added.
 */
function accountTransact($member, $amount, $transID, $reason)
{
    $cxn = open_stream();
   
    if (!($member > 0)) {
        diplayError("Invalid member for AccountTransact");
        return false;
    }
   
    $stmt = $cxn->prepare(
        "INSERT INTO storeAccount (memberID, transactionID,
        whenAcct, amount, notes) VALUES (?, ?, NOW(), ?, ?)"
    );
    $stmt->bind_param("iids", $member, $transID, $amount, $reason);
    if ($stmt->execute()) {
        return(true);
    } else {
        return(false);
    }
}

/**
 * AdjustPacksOnAcct is used to add transactions to the future pack ledger.
 *   given the relavent information, it can add or remove packs from
 *   a given account, requiring an explanation to do so.
 * @param int    $member is the selected member's ID
 * @param float  $qty    is the number of packs being added (or subtracted, if
 *   the quantity is negative.
 * @param string $note   is an explanation for why the packs are being added
 *   or subtracted. This should always be filled.
 * @retval boolean Returns true if packs are successfully adjusted,
 *   false otherwise.
 */
function adjustPacksOnAcct($member, $qty, $note="")
{
    $cxn = open_stream();

    if (!($member > 0)) {
        diplayError("Invalid member for AccountTransact");
        return false;
    }

    $sql = "INSERT INTO futurepacks(memberID, timestamp, qty, notes)"
        . " VALUES($member, NOW(), $qty, '$note')";
    if ($result = query($cxn, $sql)) {
        if ($qty > 0) {
            echo "$qty packs have been added to member's account.<br>";
        } else {
            echo ($qty * -1) . " packs have been removed from member's account.<br>";
        }
        return true;
    } else {
        echo "Error adding packs.<br>";
        return false;
    }
}

/**
 * CashCountInFourHours is used to check whether a new cash count needs
 *   doing.
 * @retval boolean Returns true when a cash count has been done in the last
 *   four hours, and false when there hasn't been one.
 */
function cashCountInFourHours()
{
    $last = cashCountTime();
    $lastDate = date_create($last);
    $fourDate = date_create();
    $fourDate->modify("-4 hours");
    if ($lastDate > $fourDate) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * CashCountTime checks when the last cash count was done, and returns
 *   the time as an integer.
 * @retval integer Returns an integer representing the time of the last cash
 *   count.
 */
function cashCountTime()
{
    $cxn = open_stream();
    $sql = "SELECT countTime FROM cashCounts ORDER BY countTime DESC";
    $last = queryOnce($cxn, $sql);
    return($last);
}

/**
 * CheckAlphaNum takes a string as input and returns true if it only contains
 *   alphanumeric characters. Otherwise returns false.
 * @param string $string is the string being tested.
 * @retval boolean Returns true if all conditions are satisfied.
 */
function checkAlphaNum($string)
{
    return eregi("^[a-z0-9]*$", $string);
}

/**
 * CheckAlphaNum takes a string as input and returns true if it only contains
 *   alphanumeric characters and space. Otherwise returns false.
 * @param string $string is the string being tested.
 * @retval boolean Returns true if all conditions are satisfied.
 */
function checkAlphaNumSpace($string)
{
    return eregi("^[a-z0-9 ]*$", $string);
}

/**
 * CheckDateNum takes a string as input and returns true if it contains
 *   a date in the form "YYYY-MM-DD". Otherwise returns false.
 * @param string $string is the string to be checked for a date.
 * @retval boolean Returns true if there is a date in $string, false
 *   otherwise.
 */
function checkDateNum($string)
{
    return ereg("^[0-9]{1,4}\-[0-9]{1,2}\-[0-9]{1,2}$", $string);
}

/**
 * Check_email_address takes a string as input and returns true if it contains
 *   a legal email address. Otherwise returns false.
 * @param string $email is the email address to be checked.
 * @retval boolean Returns true or false, depending on the state of the address.
 */
function check_email_address($email)
{
    // First, we check that there's one @ symbol, and that the lengths are right
    if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) {
        // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
        return false;
    }

    // Split it into sections to make life easier
    $email_array = explode("@", $email);
    $local_array = explode(".", $email_array[0]);
    for ($i = 0; $i < sizeof($local_array); $i++) {
        if (!ereg(
            "^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?"
            . "^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i]
        )
        ) {
            return false;
        }
    }
    // Check if domain is IP. If not, it should be valid domain name
    if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) {
        $domain_array = explode(".", $email_array[1]);
        if (sizeof($domain_array) < 2) {
            return false; // Not enough parts to domain
        }
        for ($i = 0; $i < sizeof($domain_array); $i++) {
            if (!ereg(
                "^(([A-Za-z0-9][A-Za-z0-9-]{0,61}"
                . "[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i]
            )
            ) {
                return false;
            }
        }
    }
    return true;
}

/**
 * CheckMember takes an integer member number and returns the membership status
 *   of that particular member.
 * 
 * Membership Codes:
 * 0  - Not a Known Member
 * 1  - Guest Member
 * 2  - Working Member
 * 3  - Contributing Member
 * 10 - Double Member
 * 
 * @param int $memberNum is the selected member's ID.
 * @retval int Returns an integer membership code.
 */
function checkMember($memberNum)
{
    $cxn = open_stream();
    $sql = "SELECT contribExp, workingExp FROM members WHERE ID='$memberNum' AND contribExp IS NOT NULL AND workingExp IS NOT NULL";
    $result = query($cxn, $sql);
    if ($cxn->affected_rows == 0) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    extract($row);

    $cDate = date_create($contribExp);
    $wDate = date_create($workingExp);
    if ($cDate >= date_create()) {
        $c = true;
    }
    if ($wDate >= date_create()) {
        $w = true;
    }
   
    if ($w && !$c) {
        return(2); // working
    } elseif ($c && !$w) {
        return(3); // contributing
    } elseif ($c && $w) {
        return(10); // double
    } else {
        return(1); // guest
    }
}

/**
 * CheckMemberReg takes an integer member ID and returns a boolean
 *   indicating whether the selected member is a Register Volunteer.
 * @param int $memberNum is the selected member's ID.
 * @retval int Returns 1 if member is a register volunteer, 0 otherwise.
 */
function checkMemberReg($memberNum)
{
    $cxn = open_stream();
    $sql = "SELECT registerUse FROM members WHERE ID='$memberNum'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return ($row[0]);
}

/**
 * CheckName takes a string and returns a boolean indicating whether the string
 *   fits a pattern that matches a name. Doesn't alter $name.
 * @param string $name is the name to be tested.
 * @retval boolean Returns true if given string meets criterea for names.
 */
function checkName($name)
{
    return ereg("^([A-Za-z0-9/\,\.\'\& ]|-)*$", $name);
}

/**
 * ConvertPacksToStoreCredit allows members to convert their Future Packs
 *   to store credit at a rate of 3$/pack.
 * @param int   $member is the ID of the selected member.
 * @param float $qty    is the quantity of packs being converted.
 * @retval boolean Returns true if successful, and false otherwise.
 */
function convertPacksToStoreCredit($member, $qty)
{
    $cxn = open_stream();

    if (!($member > 0)) {
        diplayError("Invalid member for AccountTransact");
        return false;
    }

    $sql = "SELECT SUM(qty) FROM futurepacks WHERE memberID=$member";
    $result = query($cxn, $sql);
    if ($row = mysqli_fetch_row($result)) {
        if ($row[0] >= $qty) {
            $value = 3.00 * $qty;
            echo "Total Qty trading in: $qty<br>";
            echo "Total value trading in: $value<br>";
            if (accountTransact(
                $member, $value, 0,
                "Converting packs to store credit."
            )
            ) {
                $qty *= -1;
                if (adjustPacksOnAcct(
                    $member, $qty,
                    "Converting packs to store credit."
                )
                ) {
                    echo "Conversion complete!<br>";
                } else {
                    echo "Error converting packs.<br>";
                }
            }
        }
    } else {
        //Member was not a legal selection. What Do?
    }
}

/**
 * DateDiff takes a delimiter and two dates, and returns the difference
 *   between them in days.
 * @param string $dformat   is a delimiter used to separate the date strings
 *   into their components. Dates must be in the form "YY-MM-DD".
 * @param string $beginDate is the starting date for the comparison.
 * @param string $endDate   is the ending date for the comparison.
 * @retval int Returns the difference in days between $beginDate and $endDate.
 */
function dateDiff($dformat, $beginDate, $endDate)
{
    $date_parts1=explode($dformat, $beginDate);
    $date_parts2=explode($dformat, $endDate);
    $start_date=gregoriantojd($date_parts1[1], $date_parts1[2], $date_parts1[0]);
    $end_date=gregoriantojd($date_parts2[1], $date_parts2[2], $date_parts2[0]);
    return $end_date - $start_date;
}

/**
 * DateSame checks to see if two dates are the same, returning true if
 *   they're the same, and false otherwise.
 * @param string $date1 is the starting date for the comparison.
 * @param string $date2 is the ending date for the comparison.
 * @retval boolean Returns true if $date1 == $date2, and false otherwise.
 */
function dateSame($date1, $date2)
{
    if (isstring($date1)) {
        $date1 = date_create($date1);
    }
    if (isstring($date2)) {
        $date2 = date_create($date2);
    }
   
    $a1 = date_parse($date1);
    $a2 = date_parse($date2);
   
    return(
        ($a1['year'] == $a2['year']) &&
        ($a1['month'] == $a2['month']) &&
        ($a1['day'] == $a2['day']));
}

/**
 * DateSplit allows users to chop a given datestring into its component parts.
 * @param string $datestr is a string holding a date in the form "YYYY-MM-DD".
 * @retval array Returns an array in the form $newdate[] = [0, 1, 2]=>[Y, M, D]
 */
function dateSplit($datestr)
{
    $newdate[0] = substr($datestr, 0, 4);
    $newdate[1] = substr($datestr, 5, 2);
    $newdate[2] = substr($datestr, 8, 2);
    return $newdate;
}

/**
 * DateString takes a string modification, and applies it to the current
 *   date, returning the modified date.
 * @param string $mod is a string modification string.
 * @retval date Returns the current date, modified by $mod.
 */
function dateString($mod)
{
    $date = date_create();
    $date->modify($mod);
    return date_format($date, "Y-m-d");
}

/**
 * DateStringVar takes a datestring and converts it to a date, returning it.
 * @param string $when is the selected date.
 * @retval date Returns a date object representing $when.
 */
function dateStringVar($when)
{
    $date = date_create($when);
    return $date->format("Y-m-d");
}

/**
 * DayToNum takes a string representing a day of the week, and returns
 *   a consistent and corresponding integer.
 * @param string $day is a string containing the name of a daykof the week.
 * @retval int Returns an integer representation for the day of the week.
 */
function dayToNum($day)
{
    switch($day) {
    case "Sunday":
        return 1;
        break;
    case "Monday":
        return 2;
        break;
    case "Tuesday":
        return 3;
        break;
    case "Wednesday":
        return 4;
        break;
    case "Thursday":
        return 5;
        break;
    case "Friday":
        return 6;
        break;
    case "Saturday":
        return 7;
        break;
    }
}

/**
 * DetermineCurrentShift calculates the current time, and checks to see what
 *   shift, if any, it currently is at the game store.
 * @retval int Returns an integer of the current shift number, or 0 if the
 *   store is closed.
 */
function determineCurrentShift()
{
    date_default_timezone_set('America/New_York');
    $time = localtime();
    $hour = $time[2];
   
    if ($hour >= 10 && $hour < 14) {
        return 1;
    } elseif ($hour >= 14 && $hour < 18) {
        return 2;
    } elseif ($hour >= 18 && $hour < 22) {
        return 3;
    } else {
        return 0;
    }
}

/**
 * DisplayAccount lists all account transactions for a given member.
 * @param int $max reduces the number of records displayed to whatever
 *   value is stored in it. All transactions will be loaded if $max is set
 *   to 0.
 * @param int $ID  is the selected member's ID
 */
function displayAccount($max, $ID)
{
    $cxn = open_stream();
    $sql = "SELECT * FROM storeAccount WHERE memberID='$ID' ORDER BY whenAcct DESC";
    $result = query($cxn, $sql);
    $count = 0;
  
    echo "<table border cellpading=3><tr><td>Date</td><td>transaction ID</td><td>note</td><td>amount</td></tr>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        $tstring = ($transactionID > 0) ? "<a href='viewreceipts.php?view=$transactionID'>$transactionID</a>" : "N/A";
        echo "<tr><td>$whenAcct</td><td>$tstring</td><td>$notes</td><td>" . money($amount) . "</td></tr>\n";
        $count++;
        if ($max > 0 && $count > $max) {
            break;
        }
    }
    echo "</table>";
}

/**
 * DisplayRefs displays all referrals for a given member, since a given date.
 * @param date $start is the earliest date that will be checked for referrals.
 *   All referrals before this date will be ignored.
 * @param int  $ID    is the selected member's ID
 * @retval boolean Returns true if the referrals are successfully displayed,
 *   and false otherwise.
 */
function displayRefs($start, $ID)
{
    $cxn = open_stream();
    $sql = "SELECT * FROM storeAccount WHERE memberID='$ID' AND notes LIKE 'REF%' AND whenAcct >= $start";
    $result = query($cxn, $sql);
   
    $refs = false;
    while ($row = mysqli_fetch_assoc($result)) {
        $refs = true;
        extract($row);
        $refID = substr($notes, 4, 10);
        $refID = intval($refID);
        $refBucks[$refID] += $amount;
        $lastPurchase[$refID] = $whenAcct;
    }
   
    if ($refs == false) {
        echo "<table border cellpadding=3><tr><td>No Referral bonuses since $start</td></tr></table>";
        return (false);
    } else {
        foreach ($refBucks as $key => $value) {
            $name[$key] = printMemberString($key, 1);
        }
        ksort($name);
   
        // display stuff
        echo "Since $start<br>
        <table border cellpadding=3><tr><td>Referred Customer</td><td>Reward</td><td>Last Purchase</td></tr>\n";
        foreach ($name as $key => $value) {
            echo "<tr><td>$value</td><td>" . money($refBucks[$key]) . "</td><td>{$lastPurchase[$refID]}</td></tr>\n";
        }
        echo "</table>\n";
        $sum = array_sum($refBucks);
        echo "Total Referral Rewards: " . money($sum) . "<br>";
    }
    return (true);
}

/**
 * DisplayError allows the application to print error messages to the page.
 * @param string $message is the error message to be printed.
 */
function displayError($message)
{
    $WEBMASTER = "webmaster@pvgaming.org";

    echo"<font color=RED>$message<br>
        Please contact the webmaster about this problem at <a href='mailto:$WEBMASTER'>$WEBMASTER</a></font><p>";
}

/**
 * DisplayErrorDie allows the application to print error messages to the page,
 *   after which it promptly prints the footer and dies.
 * @param string $message is the error message to be printed.
 */
function displayErrorDie($message)
{
    $WEBMASTER = "webmaster@pvgaming.org";

    echo"<font color=RED>$message<br>
        Please contact the webmaster about this problem at <a href='mailto:$WEBMASTER'>$WEBMASTER</a></font><p>";
    include 'footer.php';
    die();
}

/**
 * DisplayLogin prints a login form to whatever page it's called from.
 */
function displayLogin()
{
    echo"<h1>Login</h1>
        <hr>
        <form action='" . $_SERVER['SCRIPT_NAME'] . "' method='post'>
        Username: <input type='text' name='username'><p>
        Password: <input type='password' name='password'><p>
        <input type='submit' name='submit' value='Login'>
        </form><a href='forgotpassword.php'>Forgot your password?</a><p>";
}

/**
 * DisplayFCMessage builds a brief message from FriendComputer, and prints
 *   it to the screen
 * @param string $message is the message to be printed.
 */
function displayFCMessage($message)
{
    echo "<table border cellpadding=3><tr><td><font size=+3>Message From Friend Computer</font><p>
        $message</td></tr></table><p>\n";
}

/**
 * EndKey takes an array, and returns the key of the last element in the array.
 * @param array $array is the selected array.
 * @retval mixed Returns the key of the last element in the array.
 */
function endKey($array)
{
    end($array);
    return key($array);
}

/**
 * ExtractNums takes any string, and returns a second string consisting of
 *   only the numbers from the input string.
 * @param string $input is the string to be modified.
 * @retval string Returns a string containing all the numbers from $input,
 *   respectively.
 */
function extractNums($input)
{
    $nums = '';

    for ($i=0;$i<strlen($input);$i++) {
        $s = substr($input, $i, 1);
        if (ereg("^[0-9]$", $s)) {
            $nums .= $s;
        }
    }
    return ($nums);
}

/**
 * FormPhoneNumber
 * @param string $raw is the string of raw numbers to be modified.
 * @retval string Returns an American-formatted phone number.
 */
function formPhoneNumber($raw)
{
    $raw = strval($raw);

    if (strlen($raw) == 11) {
        return ('(' . substr($raw, 1, 3) . ') ' . substr($raw, 4, 3)
            . '-' . substr($raw, 7, 4));
    }
    if (strlen($raw) == 10) {
        return ('(' . substr($raw, 0, 3) . ') ' . substr($raw, 3, 3)
            . '-' . substr($raw, 6, 4));
    }
    if (strlen($raw) == 7) {
        return (substr($raw, 0, 3) . '-' . substr($raw, 3, 4));
    } else {
        return ($raw);
    }
}

/**
 * GreaterThanZero takes a number and returns either that number or 0,
 *   whichever is larger.
 * @param float $num is the number to be tested.
 * @retval float Returns either 0 or $num, whichever is larger.
 */
function greaterThanZero($num)
{
    return(($num > 0) ? $num : 0);
}

/**
 * NoRefresh prints a hidden form element which can be used to update
 *   SESSION in noRefreshCheck
 */
function noRefresh()
{
    $next = $_SESSION['page'] + 1;
   
    echo "\n<input type='hidden' name='page' value='$next'>\n";
}

/**
 * NoRefreshCheck checks to see if the POST variable 'page' is the same
 *   as a hidden value. If they are the same, it indicates the page has
 *   been refreshed. If they are different, The page has not been refreshed.
 * @retval boolean Returns true if page has not been refreshed. Returns false
 *   if the page has been refreshed.
 */
function noRefreshCheck()
{
    // if it is not set, then we are not checking it
    if (!isset($_POST['page'])) {
        $_SESSION['page'] == 0;
        return true;
    }
   
    // if they are the same, it indicates a refresh
    if ($_POST['page'] == $_SESSION['page']) {
        return false;
    } else {
        $_SESSION['page'] = $_POST['page'];
        return true;
    }
}

/**
 * GetAccountBalance takes a member ID and returns that member's store credit
 *   balance.
 * @param int $member is the selected member's ID
 * @retval float Returns the total account balance for the given member. 
 */
function getAccountBalance($member)
{
    $cxn = open_stream();
   
    $sql = "SELECT SUM(amount) FROM storeAccount WHERE memberID='$member'";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return (($row[0] > 0) ? $row[0] : 0);
}

/**
 * GetAccountPacks takes a member ID and returns that member's future pack
 *   balance.
 * @param int $member is the selected member's ID
 * @retval float Returns the total future packs for the given member. 
 */
function getAccountPacks($member)
{
    $cxn = open_stream();
   
    $sql = "SELECT SUM(qty) FROM futurepacks WHERE memberID=$member";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    return (($row[0] > 0) ? $row[0] : 0);
}

/**
 * GetAvailBalance takes a member ID and returns that member's available
 *   store credit balance.
 * @param int $member is the selected member's ID
 * @retval float Returns the available account balance for the given member. 
 */
function getAvailBalance($member)
{
    $cxn = open_stream();

    $total = getAccountBalance($member);

    // special orders
    $sql = "SELECT SUM(price) FROM specialOrders WHERE custID='$member' "
        . "AND dateTaken='0000-00-00 00:00:00' AND qty IS NULL";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    $encumb = $row[0];
    // preorders
    $sql = "SELECT SUM(price * qty) FROM specialOrders WHERE custID='$member' "
        . "AND dateTaken='0000-00-00 00:00:00' AND qty IS NOT NULL";
    $result = query($cxn, $sql);
    $row = mysqli_fetch_row($result);
    $encumb += $row[0];
   
    return ($total - $encumb);
}

/**
 * GetMemberEmail takes a member ID and returns that member's email address.
 * @param int $member is the selected member's ID
 * @retval string Returns the email address for the given member. 
 */
function getMemberEmail($member)
{
    $cxn = open_stream();
   
    $sql = "SELECT email FROM members WHERE ID=$member";
    $result = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['email'];
    }
    return "null";
}

/**
 * LastDayOfMonth takes a selected month as an integer, and returns the
 *   number of days in that month, ignoring leapyears.
 * @param int $month is the integer representation of the selected month.
 * @retval int Returns the number of days in given month.
 */
function lastDayOfMonth($month)
{
    switch($month)
    {
    case 1:
    case 3:
    case 5:
    case 7:
    case 8:
    case 10:
    case 12:
        return (31);
    case 4:
    case 6:
    case 9:
    case 11 :
        return (30);
    case 2 :
        return (28);
    default :
        return (false);
    }
}

/**
 * LateReg is a boolean check to determine whether the current shift has been
 *   in effect for at least 30 minutes.
 * @retval boolean Returns true if more than 30 minutes have passed since
 *   the start of the current shift, and false otherwise.
 */
function lateReg()
{
    date_default_timezone_set('America/New_York');
    $time = localtime();
    $hour = $time[2];
    $min = $time[1];
   
    if ($hour > 10 && $hour < 14) {
        return true;
    }
    if ($hour > 14 && $hour < 18) {
        return true;
    }
    if ($hour > 18 && $hour < 22) {
        return true;   
    }
    if ($hour == 10 && $min >= 30) {
        return true;
    }
    if ($hour == 14 && $min >= 30) {
        return true;
    }
    if ($hour == 18 && $min >= 30) {
        return true;
    }
    return false;
}

/**
 * Login takes a username and password, and compares them to the database,
 *   returning a true or false indicating whether the password was accepted.
 * @param string $username is the username being tested.
 * @param string $password is the password being tested.
 * @retval boolean Returns true if the combination is accepted, false if they
 *   are rejected.
 */
function login($username, $password)
{
    if (strlen($username) == 0) {
        return false;
    }

    $stream = open_stream();
    // if there is no password, cannot log in - no accounts w/o passwords
    if (strlen($password) == 0) {
        echo "Login Failed: No Password<br>";
        return false;
    }

    $pwdhash = hash('sha256', $password);
    $query = "SELECT * FROM members WHERE login='$username' AND password='$pwdhash'";

    if ($result = query($stream, $query)) {
        if (($row = mysqli_fetch_assoc($result)) && ($row['status'] != -1)) {
            $_SESSION['ID'] = $row['ID'];
            $_SESSION['inv'] = $row['inventoryUse'];
            $_SESSION['reg'] = $row['registerUse'];
            $_SESSION['mem'] = $row['memberUse'];
            $_SESSION['adm'] = $row['adminUse'];
            $_SESSION['eve'] = $row['eventUse'];
            mysqli_free_result($result);
            mysqli_close($stream);
            unset($_POST['username']); // to make sure it does not trip up safePost
            unset($_POST['password']);
            echo "Login successful";
            return true;
        } else {
            echo "Login Failed.";
            mysqli_free_result($result);
            mysqli_close($stream);
            return false;
        }
    }
}

/**
 * Logout deletes all active SESSION variables and prints the login UI.
 */
function logout()
{
    foreach ($_SESSION as $key => $value) {
        unset($_SESSION[$key]);
    }

    echo "<h1>Login</h1>
        <hr>
        <form action='index.php' method='post'>
        Username: <input type='text' name='username'><p>
        Password: <input type='password' name='password'><p>
        <input type='submit' name='submit' value='Login'>
        </form><p>";
    include 'footer.php';
    exit();
}

/**
 * LogoutTimeout is used when a member times out of FriendComputer, in
 *   which case we save all the lost variables in hidden inputs so they
 *   can be recovered if need be.
 */
function logoutTimeout()
{
    foreach ($_SESSION as $key => $value) {
        unset($_SESSION[$key]);
    }

    echo "<h1>Login</h1>
        <hr>
        <form action='" . $_SERVER["REQUEST_URI"] . "' method='post'>";
    if (is_array($_POST)) {
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k2 => $v2) {
                    echo "<input type='hidden' name='$key" . "[$k2]' value='$v2'>\n";
                }
            } else {
                echo "<input type='hidden' name='$key' value='$value'>\n";
            }
        }
    }
        
    echo "Username: <input type='text' name='username'><p>
        Password: <input type='password' name='password'><p>
        <input type='submit' name='submit' value='Login'>
        </form><p>";
    include 'footer.php';
    exit();
}

/**
 * Money formats a given float and returns a string representing that float
 *   as a monetary amount.
 * @param float $float is the value to be formatted.
 * @retval string Returns a string representation of the float as a monetary
 *   sum.
 */
function money($float)
{
    return "\$" . sprintf("%01.2f", $float);
}

/**
 * MoneyND formats a given float and returns a string representing that float
 *   as a monetary amount, but without the prefacing dollar sign.
 * @param float $float is the value to be formatted.
 * @retval string Returns a string representation of the float as a monetary
 *   sum.
 */
function moneyND($float)
{
    return sprintf("%01.2f", $float);
}

/**
 * PrintMember takes a member ID, and a formatting code, and prints the
 *   member's name, formatted as indicated by the formatting code.
 * 
 * Formatting Codes:
 * 1: fname lname
 * 2: lname, fname
 * 3: fname
 * 4: lname, F
 * 
 * @param int $num   is the selected member's ID
 * @param int $order is the formatting code, determining how to format
 *   the returned value.
 * @retval boolean Returns true if the name is successfully printed, and false
 *   otherwise.
 */
function printMember($num, $order)
{
    $cxn = open_stream();
    $sql = "SELECT fname, lname FROM members WHERE ID='$num'";
    $result = query($cxn, $sql);
    if (!$row = mysqli_fetch_assoc($result)) {
        return false;
    }
    extract($row);
    switch($order)
    {
    case 1: echo "$fname $lname ($num)";
        break;
    case 2: echo "$lname, $fname ($num)";
        break;
    case 3: echo "$fname";
        break;
    case 4: $fname = substr($fname, 0, 1);
        echo "$lname, $fname ($num)";
        break;
    }
    return true;
}

/**
 * PrintMemberString takes a member ID, and a formatting code, and returns the
 *   member's name, as a string, formatted as indicated by the formatting code.
 * 
 * Formatting Codes:
 * 1: fname lname
 * 2: lname, fname
 * 3: fname
 * 4: lname, F
 * 
 * @param int $num   is the selected member's ID
 * @param int $order is the formatting code, determining how to format
 *   the returned value.
 * @retval string Returns the selected member's name formatted as set by $order.
 */
function printMemberString($num, $order)
{
    $cxn = open_stream();
    $sql = "SELECT fname, lname FROM members WHERE ID='$num'";
    $result = query($cxn, $sql);
    if (!$row = mysqli_fetch_assoc($result)) {
        return false;
    }
    extract($row);
    switch($order)
    {
    case 1: 
        return "$fname $lname ($num)";
        break;
    case 2: 
        return "$lname, $fname ($num)";
        break;
    case 3: 
        return "$fname";
        break;
    case 4: 
        $fname = substr($fname, 0, 1);
        return "$lname, $fname ($num)";
        break;
    case 5: 
        return "$fname $lname";
        break;
    }
    return "Invalid Display Type Requested";
}

/**
 * Query is a handle for database queries. It takes an SQL connection
 *   and an SQL query, and returns all records at that connection which
 *   satisfy the stipulations of $sql.
 * @param mixed  $cxn is the connection to the database.
 * @param string $sql is the query to be passed to the database.
 * @retval array Returns an array of all the records matching the query.
 */
function query($cxn, $sql)
{
    if (!$result = mysqli_query($cxn, $sql)) {
        displayError(
            "Query Error!<br>Query: $sql<br>SQL Error: "
            . mysqli_error($cxn)
        );
        return (false);
    } else {
        return ($result);
    }
}

/**
 * QueryAssoc is a handle for database queries. It takes an SQL connection
 *   and an SQL query, and returns all records at that connection which
 *   satisfy the stipulations of $sql as an associative array.
 * @param mixed  $cxn is the connection to the database.
 * @param string $sql is the query to be passed to the database.
 * @retval array Returns an associative array of all the records
 *   matching the query.
 */
function queryAssoc($cxn, $sql)
{
    if ($result = query($cxn, $sql)) {
        $row = mysqli_fetch_assoc($result);
        return($row);
    } else {
        return false;
    }
}

/**
 * QueryOnce is a handle for database queries. It takes an SQL connection
 *   and an SQL query, and returns the first element of the first record
 *   at that connection which satisfies the stipulations of $sql.
 * @param mixed  $cxn is the connection to the database.
 * @param string $sql is the query to be passed to the database.
 * @retval mixed Returns the value of the first element of the first record
 *   matching the given query.
 */
function queryOnce($cxn, $sql)
{
    if ($result = query($cxn, $sql)) {
        $row = mysqli_fetch_row($result);
        return($row[0]);
    } else {
        return false;
    }
}
   
/**
 * QueryB is a handle for SQL queries that uses a bound statement, as
 * opposed to a static statement for the query, and a connection.
 * @param mixed $stmt is a bound statement, including both a connection and
 *   a query.
 * @retval mixed Returns the bound statement, after storing the results of
 *   the query inside it.
 */
function queryB($stmt)
{
    if (!$stmt->execute()) {
        displayError("Query Error!<br>Query: $sql<br>SQL Error: " . $stmt->error);
        return (false);
    } else {
        $stmt->store_result();
        return ($stmt);
    }
}

/**
 * ReportBug sends an email to the High Programmer, with as much
 *   information as possible so as to diagnose what happened.
 * @param string $page is the name of the page the function is being called
 *   from.
 */
function reportBug($page)
{
    $bug = $_POST['bug'];
    $post = var_export($_POST, true);
    $session = var_export($_SESSION, true);
    $body = "A Bug Report was sent from $page.
        Date: " . date('l dS \of F Y h:i:s A') . "
        POST data: $post

        SESSION data: $session\n";

    if (mail('webmaster@pvgaming.org', "FC/$page Bug Report", $body)) {
        echo "<table border><tr><td><b>Message From Friend Computer</b><br>
            The following bug report has been sent to the High Programmers<br>
            $bug</td></tr></table>\n";
    } else {
        displayError("Error: Unable to send bug report. Pretty ironic, isn't it?");
    }
}

/**
 * SafeGet takes an array of strings, and unsets any initialized
 *   GET variables that are not listed in the $approved array.
 * @param array $approved is the array of approved variables.
 */
function safeGet($approved)
{
    $errors = false;

    foreach ($_GET as $key => $value) {
        if (!in_array($key, $approved)) {
            unset($_GET[$key]);
            echo ($errors) ? "<font color=BLUE>Invalid POST entry: '$key'" : ", '$key'";
            $errors = true;
        }
    }

    if ($errors) {
        echo "</font><br>\n";
        displayErrorDie("Invalid GET entries.");
    }
}

/**
 * SafeGet takes an array of strings, and unsets any initialized
 *   POST variables that are not listed in the $approved array.
 * @param array $approved is the array of approved variables.
 */
function safePost($approved)
{
    $errors = false;
   
    foreach ($_POST as $key => $value) {
        if (!in_array($key, $approved)) {
            unset($_POST[$key]);
            echo (!$errors) ? "<font color=BLUE>Invalid POST entry: '$key'" : ", '$key'";
            $errors = true;         
        }
    }
   
    if ($errors) {
        echo "</font><br>\n";
        displayErrorDie("Invalid POST entries.");
    }
}

/**
 * SelectDepartment prints a box that allows the user to select a
 *   department from our full list of departments. The <select> name
 *   is set to "department[$id]" and the returned value is set to the
 *   name of the selected department.
 * @param int    $id      is an integer that will determine what POST variable
 *   this <select> box will be added to. All selectDepartment() calls
 *   print a <select> with a name of "department[]", but the key of any
 *   given stored value can be set at will. Printed <select> box will
 *   be named "department[$id]".
 * @param string $default is the name of the department you want to be
 *   displayed as the default value.
 */
function selectDepartment($id, $default)
{
    $sql = "SELECT name FROM departments";
    $cxn = open_stream();
    $result = mysqli_query($cxn, $sql);
    echo "<select name='department[$id]'>";
    while ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        if ($name == $default) {
            echo "<option value='$name' selected>$name</option>\n";
        } else {
            echo "<option value='$name'>$name</option>\n";
        }
    }
    echo "</select>";
    mysqli_close($cxn);
    mysqli_free_result($result);
}

/**
 * SelectInputDate takes some defining variables, and prints a set of
 *   three <select> boxes that can be easily used to select a date.
 * @param string $monName   is the name of the printed month <select> box.
 * @param string $dayName   is the name of the printed day <select> box.
 * @param string $yearName  is the name of the printed year <select> box.
 * @param int    $startYear is the earliest year offered by the
 *   year <select> box.
 * @param int    $endYear   is the latest year offered by the year <select> box.
 * @param int    $defMon    is the default integer value for the month <select> box.
 * @param int    $defDay    is the default integer value for the day <select> box.
 * @param int    $defYear   is the default integer value for the year <select> box.
 */
function selectInputDate($monName, $dayName, $yearName, $startYear,
    $endYear, $defMon, $defDay, $defYear
) {
    $month = array('', 'January', 'February', 'March', 'April',
        'May', 'June', 'July', 'August', 'September', 'October',
        'November', 'December');

    echo "<select name='$monName'>\n";
    foreach ($month as $num => $monName) {
        if ($defMon == $num) {
            echo "<option value=$num selected>$monName</option>\n";
        } else {
            echo "<option value=$num>$monName</option>\n";
        }
    }
    echo"</select>\n
        <select name='$dayName'>\n
        <option value=0></option>\n";

    for ($i=1;$i<=31;$i++) {
        if ($defDay == $i) {
            echo "<option value=$i selected>$i</option>\n";
        } else {
            echo "<option value=$i>$i</option>\n";
        }
    }

    echo "</select>
        <select name='$yearName'>\n
        <option value=0></option>\n";
    for ($i=$startYear; $i<=$endYear; $i++) {
        if ($defYear == $i) {
            echo "<option value=$i selected>$i</option>\n";
        } else {
            echo "<option value=$i>$i</option>\n";
        }
    }
    echo "</select>\n";
}

/**
 * SelectInputDayOfWeek prints a <select> box containing all the days of
 *   the week, it's returned variables will be in numeric form, as defined in
 *   funcs.dayToNum()
 * @param string $name    is the name of the printed <select> box.
 * @param int    $default is the day of the week that should be pre-selected.
 */
function selectInputDayOfWeek($name, $default)
{
    $days = array (1=>'Monday', 'Tuesday', 'Wednesday', 'Thursday',
        'Friday', 'Saturday', 'Sunday');
    echo "<select name='$name'>
        <option value='0'></option>\n";

    for ($i = 1; $i <= 7; $i++) {
        if ($i == $default) {
            echo "<option value='$i' selected>" . $days[$i] . "</option>\n";
        } else {
            echo "<option value='$i'>" . $days[$i] . "</option>\n";
        }
    }
    echo "</select>\n";
}
      
/**
 * SelectInputTime takes names and default values, and prints a set of
 *   <select> boxes used for selecting a time.
 * @param string $hourName   is the name of the hour <select> box.
 * @param string $minuteName is the name of the minute <select> box.
 * @param int    $defHour    is the default value for the hour <select> box.
 * @param int    $defMinute  is the default value for the minute <select> box.
 */
function selectInputTime($hourName, $minuteName, $defHour, $defMinute)
{
    echo "<select name='$hourName'>\n";
    for ($i = 0; $i < 24; $i++) {
        if ($defHour == $i) {
            echo "<option value=$i selected>$i</option>\n";
        } else {
            echo "<option value=$i>$i</option>\n";
        }
    }
    echo "</select> : <select name='$minuteName'>\n";
    for ($i = 0; $i < 60; $i += 5) {
        if ($defMinute == $i) {
            echo "<option value=$i selected>";
            printf("%02d", $i);
            echo "</option>\n";
        } else {
            echo "<option value=$i>";
            printf("%02d", $i);
            echo "</option>\n";
        }
    }
    echo "</select>";
}

/**
 * SelectManufacturer prints a box that allows the user to select a
 *   manufacturer from our full list of manufacturers. The <select> name
 *   is set to "manufacturer[$id]" and the returned value is set to the
 *   name of the selected manufacturer.
 * @param int    $id      is an integer that will determine what POST variable
 *   this <select> box will be added to. All selectManufacturer() calls
 *   print a <select> with a name of "manufacturer[]", but the key of any
 *   given stored value can be set at will. Printed <select> box will
 *   be named "manufacturer[$id]".
 * @param string $default is the name of the manufacturer you want to be
 *   displayed as the default value.
 */
function selectManufacturer($id, $default)
{
    $sql = "SELECT name FROM manufacturers";
    $cxn = open_stream();
    $result = mysqli_query($cxn, $sql);
    echo "<select name='manufacturer[$id]'>";
    while ($row = mysqli_fetch_assoc($result)) {
        $name = $row['name'];
        if ($name == $default) {
            echo "<option value='$name' selected>$name</option>\n";
        } else {
            echo "<option value='$name'>$name</option>\n";
        }
    }
    echo "</select>";
    mysqli_close($cxn);
    mysqli_free_result($result);
}

/**
 * SelectLeagueMember prints a <select> box containing only the names of
 *   store members who are registered for a given league.
 * @param string $name    is the name of the <select> box.
 * @param int    $default is the member ID of a selected default value.
 * @param int    $league  is the integer ID of the selected magic league.
 */
function selectLeagueMember($name, $default, $league)
{
    $sql = "SELECT m.ID, m.fname, m.lname 
        FROM members m
        JOIN (SELECT * 
        FROM league  
        WHERE leagueID='$league') as l
        ON m.ID = l.player
        ORDER BY m.lname, m.fname, m.ID";
    $cxn = open_stream();
    $result = query($cxn, $sql);

    echo "<select name='$name'>";
    echo "<option value='0'></option>\n";
   
    $lastlast = 'this is not a name that could come up';
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        if ($lastlast == $lname) {
            continue;
        }
        $lastlast = $lname;
      
        $fname = substr($fname, 0, 1);
        if ($ID == $default) {
            echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
        } else {
            echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
        }
    }
    echo "</select>";

    mysqli_free_result($result);
    mysqli_close($cxn);
}

/**
 * SelectMember prints a <select> box containing the names of
 *   all store members.
 * @param string $name    is the name of the <select> box.
 * @param int    $default is the member ID of a selected default value.
 */
function selectMember($name, $default)
{
    $sql = "SELECT ID, fname, lname FROM members ORDER BY lname, fname, ID";
    $cxn = open_stream();
    $result = query($cxn, $sql);

    echo "<select name='$name'>";
    echo "<option value='0'></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        $fname = substr($fname, 0, 4);
        if ($ID == $default) {
            echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
        } else {
            echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
        }
    }
    echo "</select>";

    mysqli_free_result($result);
    mysqli_close($cxn);
}

/**
 * SelectRegMember prints a <select> box containing the names of
 *   all store register volunteers.
 * @param string $name    is the name of the <select> box.
 * @param int    $default is the member ID of a selected default value.
 */
function selectRegMember($name, $default)
{
    $sql = "SELECT ID, fname, lname FROM members WHERE registerUse='1' ORDER BY lname, fname, ID";
    $cxn = open_stream();
    $result = query($cxn, $sql);

    echo "<select name='$name'>";
    echo "<option value='0'></option>\n";
    while ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        $fname = substr($fname, 0, 1);
        if ($ID == $default) {
            echo "<option value='$ID' selected>$lname, $fname (#$ID)</option>\n";
        } else {
            echo "<option value='$ID'>$lname, $fname (#$ID)</option>\n";
        }
    }
    echo "</select>";

    mysqli_free_result($result);
    mysqli_close($cxn);
}
?>
