<?php
// inputmember.php
// This application is for entering and editing member information
// with GET ID displays a member for editing
// with POST sub>0, attepts to submit data, sub==1 for new, sub==2 for edit

// Versions
// 1.1 Emails passwords when they are changes
/**
 * InputMember.php is a page for adding new members to the database, or
 *   editing existing members' accounts.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  MemberInfo_Mutator
 * @package   FriendComputer
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/inputmember.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   Used for the config.inc include
 *   displayErrorDie()
 *   checkName()
 *   check_email_address()
 *   extractNums()
 *   displayError()
 *   selectInputDate()
 */
$title = 'Input Member Information';
$version = "1.8d";
$securePage = true;
require_once 'funcs.inc';
require_once'header.php';

/**
 * Possible Arguments:
 * SESSION:
 *   mem - Used to determine whether the current user has membership
 *     privledges.
 * POST:
 *   ID - This variable will only be filled if GET['ID'] is set, it is a
 *     hidden value equal to GET['ID'].
 *   sub - An integer value telling us what we'll be doing.
 *     0 - Nothing
 *     1 - Add New Member
 *     2 - Edit Existing Member
 *   login - Login is the value the member's username will be set to.
 *   newpwd1 - If both newpwds are set to the same value, the member's
 *   newpwd2 - password will be changed to that value.
 *   fname - This is the value the member's first name will be set to.
 *   lname - This is the value the member's last name will be set to.
 *   street - This is the value the member's street address will be set to.
 *   city - This is the value the member's city will be set to.
 *   state - This is the value the member's state will be set to.
 *   ZIP - This is the value the member's zip code will be set to.
 *   email - This is the value the member's email will be set to.
 *   phone1 - This is the value the member's phone number will be set to.
 *   phone2 - This is the value the member's alternate phone number will be set to.
 *   DOBMonth - The three DOB variables are combined into a composite date,
 *   DOBDay -   and then that date is set as them member's date of birth.
 *   DOBYear -  (This one too)
 *   taxexempt - This is the value the member's tax exempt number will be set to.
 *   registerUse - This value represents whether the member should have register privs.
 *   inventoryUse - This value represents whether the member should have inventory privs.
 *   memberUse - This value represents whether the member should have membership privs.
 *   eventUse - This value represents whether the member should have event privs.
 *   adminUse - This value represents whether the member should have admin privs.
 *   optOut - This value represents whether the member has opted out of scavenger hunt.
 *   working - If this is checked, the member will be given 1 month working membership.
 *   contributing[] - If this is set, contributing member status will be added.
 *   submit - This will have a value when clicked, but we check sub for that.
 * GET:
 *   ID - If ID is set, the page will set 'sub's value to 2, and pull the
 *     given member's info.
 */

if ($_SESSION['mem'] != 1) {
    echo "You must have Member Admin Privilidges to input and edit member information.<p>";
    include 'footer.php';
    exit();
}

if (!$cxn = open_stream()) {
    displayErrorDie(
        "Error #7: Unable to open datastream<br>SQL Error: " 
        . mysqli_connect_error()
    );
} else {
    if ($_POST['sub'] > 0) {
        // if there is data to process
        // check data
        extract($_POST);
        if (strlen($newpwd1) > 0) {
            if ($bad['password match'] = ($newpwd1 != $newpwd2)) {
                echo "New password must be entered the same twice.<br>";
            } else {
                $password = $newpwd1;
            }
            $bad['PASSWORD'] = !(strlen($password) > 0);
        }

        if ($bad['login'] = !checkName($login)) {
            $login = '';
        } else {
            $login = mysqli_real_escape_string($cxn, $login);
        }
        
        if ($bad['fname'] = !checkName($fname)) {
            $fname = '';
        } else {
            $fname = mysqli_real_escape_string($cxn, $fname);
        }
        if ($bad['lname'] = !checkName($lname)) {
            $lname = '';
        } else {
            $lname = mysqli_real_escape_string($cxn, $lname);
        }
        $street = mysqli_real_escape_string($cxn, $street);
        if ($bad['city'] = !checkName($city)) {
            $city = '';
        } else {
            $city = mysqli_real_escape_string($cxn, $city);
        }
        $state = strtoupper($state);
        if ($bad['state'] = (!ereg("^[A-Z]{0,2}$", $state))) {
            $state='';
        }
        if ($bad['ZIP'] 
            = (!ereg("^([0-9]{0,5}-?[0-9]{0,4})|([A-Z0-9]{3} [A-Z0-9]{3})$", $ZIP))
        ) {
            $ZIP='';
        }
        if (!check_email_address($email)) {
            $email = '';
        }
        $phone1 = extractNums($phone1);
        $phone2 = extractNums($phone2);
        $status = (int)$status;
        if (!checkDate($DOBMonth, $DOBDay, $DOBYear)) {
            $DOBDay = 0;
            $DOBMonth = 0;
            $DOBYear = 0;
        } else {
            $DOB=$DOBYear . '-' . $DOBMonth . '-' . $DOBDay;
        }
        $taxexempt = (strlen($taxexempt) > 6) ? extractNums($taxexempt) : "";
        if ($registerUse != 1) {
            $registerUse = 0;
        }
        if ($inventoryUse != 1) {
            $inventoryUse = 0;
        }
        if ($memberUse != 1) {
            $memberUse = 0;
        }
        if ($eventUse != 1) {
            $eventUse = 0;
        }
        if ($adminUse != 1) {
            $adminUse = 0;
        }

        $opt = ($optout == 1) ? ",optout = '1'" : "";

        // working membership
        if (isset($working)) {
            $date = date_create();
            $date->modify("+1 month");
            $workingExp = $date->format("Y-m-d");
        }

        // contributing membership
        $date = date_create();
        switch($contributing)
        {
        case 0 :
            $contribExp = '0000-00-00';
            break;
        case 1 :
            date_modify($date, "+1 month");
            $contribExp = date_format($date, "Y-m-d");
            break;
        case 10 :
            date_modify($date, "+1 year");
            $contribExp = date_format($date, "Y-m-d");
            break;
        case 20 :
            $contribExp = "2200-01-01";
            break;
        case 21 :
            $contribExp = "2300-01-01";
            break;
        default :
            $contribExp = '0000-00-00';
        }

        // if its all good, ram it in there
        if (!in_array(true, $bad)) {
            if ($sub == 1) {
                // if there is a new member to add
                // we only do the password if they are new. 
                // Don't want to reset the password for every edit
                $origpass = $password;
                $password = hash('sha256', $password);

                $sql = "INSERT INTO members
                    (login, password, fname, lname, street, city, state, 
                    ZIP, email, phone1, phone2, DOB, registerUse, 
                    inventoryUse, memberUse, eventUse, adminUse, 
                    memberSince, workingExp, contribExp, taxexempt)
                    VALUES
                    ('$login', '$password', '$fname', '$lname', '$street', "
                        . "'$city', '$state', '$ZIP', '$email', '$phone1', "
                        . "'$phone2', '$DOB', '$registerUse', '$inventoryUse', "
                        . "'$adminUse', '$memberUse', 'eventUse', NOW(), "
                        . "'$workingExp', '$contribExp', '$taxexempt')";
                if (!mysqli_query($cxn, $sql)) {
                    displayError(
                        "Error #8: Query Error<br>Query: "
                        . "$sql<br>SQL Error: " . mysqli_error($cxn)
                    );
                } else {
                    echo "New member $fname $lname added to database successfully!<hr>";
                    $message
                        = "Welcome, friend $fname
                        This email is to inform you that you have just been 
                        entered into the Worlds Apart Member Database.
                        You may now log into Friend Computer to administer 
                        your Credit account, sell stuff when you are on duty, 
                        and all the rest of that good stuff.
                        Your username is: $login
                        Your password: $origpass
                        The URL for the system is http://www.worldsapartgames.com/fc
                        Sincerely,
                        Friend Computer";

                    mail(
                        "$email", 
                        "Welcome New Worlds Apart Member", $message,
                        "From: friendcomputer@worldsapartgames.com"
                    );
                    unset(
                        $login, $fname, $lname, $street, $city, $state, 
                        $ZIP, $email, $phone1, $phone2, $status, $DOB, 
                        $registerUse, $inventoryUse, $adminUse, $ID, 
                        $taxexempt
                    );
                }
            } elseif ($sub == 2) {
                // if it is an edit
                if (strlen($password) > 0) {
                     // password is checked above and only set if valid
                    // this will be added only if there is a password change
                    $pwdsql = ",password='" . hash('sha256', $password) . "'";
                }
                $sql = "UPDATE members
                    SET login='$login',
                    fname='$fname',
                    lname='$lname',
                    street='$street',
                    city='$city',
                    state='$state',
                    ZIP='$ZIP',
                    email='$email',
                    phone1='$phone1',
                    phone2='$phone2',
                    DOB='$DOB',
                    registerUse='$registerUse',
                    inventoryUse='$inventoryUse',
                    adminUse='$adminUse',
                    memberUse='$memberUse',
                    eventUse='$eventUse',
                    taxexempt='$taxexempt'
                    $pwdsql
                    $opt
                    WHERE ID='$ID'";
                if (!mysqli_query($cxn, $sql)) {
                    displayError(
                        "Error #8: Query Error<br>Query: "
                        . "$sql<br>SQL Error: " . mysqli_error($cxn)
                    );
                } else {
                    echo "Information for $fname $lname updated in database "
                        . "successfully!<hr>";

                    // if password was changed, notify member
                    if (strlen($password) > 0) {
                        $message
                            = "Welcome, friend $fname,
                            Your member information has been updated in 
                            Friend Computer, and your password has been updated
                            Below is your new log in information:
                            Your username is: $login
                            Your password: $password
                            The URL for the system is http://www.worldsapartgames.com/fc
                            Sincerely,
                            Friend Computer";
                        mail(
                            "$email", 
                            "Welcome New Worlds Apart Member", $message, 
                            "From: friendcomputer@worldsapartgames.com"
                        );
                    }

                    unset($login, $fname, $lname, $street, $city, $state, 
                            $ZIP, $email, $phone1, $phone2, $status, 
                            $DOB, $registerUse, $inventoryUse, $adminUse, 
                            $memberUse, $DOBDay, $DOBMonth, $DOBYear, 
                            $taxexempt);
                }
            } // end if
        } else {
            echo "There are errors in your submission<p>";
            foreach ($bad as $key => $value) {
                if ($value) {
                    echo "Invalid $key<br>";
                }
            }
            echo "Please make necessary corrections and try again.<hr>";
        }
    } elseif (($ID = $_GET['ID']) > 0) {
        // if an ID is submitted for editing
        echo "ID: $ID";
        $sql = "SELECT * FROM members WHERE ID='$ID'";
        $result = query($cxn, $sql);
        $row = mysqli_fetch_assoc($result);
        extract($row);
        $DOBYear = intval(substr($DOB, 0, 4));
        $DOBMonth = intval(substr($DOB, 5, 2));
        $DOBDay = intval(substr($DOB, 8, 2));

    } // end else if GET[ID]
}

// display form
echo "Enter Member Information<p>
    <form action='inputmember.php' method='post'>";
if ($ID>0) {
    // if it's an edit we set sub to 2 so that it is processed as an edit
    echo "<input type='hidden' name='ID' value='$ID'>
        <input type='hidden' name='sub' value=2>\n";
} else {
    // otherwise we set sub as 1 which means that it's a new entry
    echo "<input type='hidden' name='sub' value=1>\n";
}
echo "<table>
    <tr><td>Login:</td><td><input type='text' name='login' 
    value='$login' size=25 maxlength=25></td></tr>";
echo "<tr><td>New Password:</td><td><input type='password' 
    name='newpwd1' size=25 maxlength=100></td></tr>
    <tr><td>New Password:</td><td><input type='password' 
    name='newpwd2' size=25 maxlength=100></td></tr>
    <tr><td>First Name:</td><td><input type='text' name='fname' 
    value='$fname' size=25 maxlength=25></td></tr>
    <tr><td>Last Name:</td><td><input type='text' name='lname' 
    value='$lname' size=25 maxlength=25></td></tr>
    <tr><td>Street Address:</td><td><input type='text' 
    name='street' value='$street' size=50 maxlength=50></td></tr>
    <tr><td>City:</td><td><input type='text' name='city' 
    value='$city' size=25 maxlength=25></td></tr>
    <tr><td>State:</td><td><input type='text' name='state' 
    value='$state' size=2 maxlength=2></td></tr>
    <tr><td>ZIP:</td><td><input type='text' name='ZIP' value='$ZIP'
    size=12 maxlength=12></td></tr>
    <tr><td>Email:</td><td><input type='text' name='email' 
    value='$email' size=50 maxlength=255</td></tr>
    <tr><td>Phone:</td><td><input type='text' name='phone1' 
    value='$phone1' size=15 maxlength=20></td></tr>
    <tr><td>Alt Phone:</td><td><input type='text' name='phone2' 
    value='$phone2' size=15 maxlength=20></td></tr>
    <tr><td>Date of Birth:</td><td>";
selectInputDate('DOBMonth', 'DOBDay', 'DOBYear', 1910, 2004, $DOBMonth, $DOBDay, $DOBYear);
echo "</td></tr>
    <tr><td>Tax exempt number (for organizations only):</td><td>
    <input type='text' name='taxexempt' value='$taxexempt' size=15 maxlength=15>
    </td></tr>
    <tr><td colspan=4>Permissions. Click none for regular members.</td></tr>
    <tr><td colspan=2>Register Use?<input type='checkbox' name='registerUse' value=1 ";
if ($registerUse==1) {
    echo "checked";
}
echo ">\nInventory Use?<input type='checkbox' name='inventoryUse' value=1 ";
if ($inventoryUse==1) {
    echo "checked";
}
echo ">\nMember Use?<input type='checkbox' name='memberUse' value=1 ";
if ($memberUse==1) {
    echo "checked";
}
echo ">\nEvent Use?<input type='checkbox' name='eventUse' value=1 ";
if ($eventUse==1) {
    echo "checked";
}
echo ">\nAdmin Use?<input type='checkbox' name='adminUse' value=1 ";
if ($adminUse==1) {
    echo "checked";
}
echo "></td></tr>";
if ($ID > 0) {
    echo "<tr><td>Opt Out of Scavenger Hunt List?<input type='checkbox' name='optout' value=1 ";
    if ($optout==1) {
        echo "checked";
    }
    echo "></td></tr>";
}

// for new users, we can enter their member type
if (!($ID > 0)) {
    echo "<tr><td colspan=2><input type='checkbox' name='working' value=1> Currently Working Member (Good for one month)</td></tr>
        <tr><td>Contributing Membership Level</td><td>
        <select name='contributing[$ID]'>
        <option></option>
        <option value=0>Guest/Working Member</option>
        <option value=1>One Month</option>
        <option value=10>One Year</option>
        <option value=20>Lifetime</option>
        <option value=21>Eternal</option>
        </select></td></tr>";
}
echo "</table>
    <input type='submit' name='submit' value='submit'>\n";
require 'footer.php';
?>
