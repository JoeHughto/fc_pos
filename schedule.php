<?php
/**
 * @file schedule.php
 * @brief schedule.php displays the default schedule, as well as the actual
 *   schedule for a given time period.
 *
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - selectInputDate()
 * - checkMemberReg()
 * 
 * schedule.inc:
 * - selectSchedVolunteer()
 * - showVolunteer()
 * - selectVolunteer()
 * 
 * Possible Arguments:
 * SESSION:
 * - mem - Used to determine whether the active user has membership
 *   coordinator privs, allowing them to change the schedule.
 * 
 * POST:
 * - dis - If this variable is set to 'no', we display 'noheader.inc'
 *   instead of 'header.inc'.
 * - date - This is a submit button, which will populate with a value
 *   if there is work to be done.
 * - startyear - The values included
 * - startmonth - in the start and end
 * - startday - variables are used for
 * - endyear - identifying the desired
 * - endmonth - period for editing
 * - endday - or viewing the schedule.
 * - change[ ] - This array tells us for each day and shift, whether
 *   we need to do any work on it, or if it doesn't need to be changed.
 * - s[ ] - This array of values tells FC which of the upcoming shifts
 *   need to be amended, and what their new value should be.
 * - sched[ ] - This array of values tells FC which of the recurring shifts
 *   need to be amended, and what their new value should be.
 * 
 * GET:
 * - dis - If this variable is set to 'no', we display 'noheader.inc'
 *   instead of 'header.inc'.
 * - start - This string is the start date of the desired period. It
 *   will be set from POST, if appropriate values have been set.
 * - end - This string is the end date of the desired period. It
 *   will be set from POST, if appropriate values have been set.
 * 
 * @link http://www.worldsapartgames.org/fc/schedule.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

$title = 'Schedule';
$version = '1.8d';
$securePage = true;
require_once 'funcs.inc';
require_once 'schedule.inc';

// Check permissions and set mem to indicate if user can make changes
$mem = $_SESSION['mem'];
if ($_GET['dis'] == 'no' || $_POST['dis'] == 'no') {
    $mem = 0;
    include 'noheader.php';
} else {
    include 'header.php';
}

$cxn = open_stream();


if ($mem == 1) {
    echo "<form action='schedule.php' method='post'>\n";
}

if (isset($_POST['date'])) {
    extract($_POST);
    $_GET['start'] = $startyear . '-' . $startmonth . '-' . $startday;
    $_GET['end'] = $endyear . '-' . $endmonth . '-' . $endday;
} else {
    $startyear = substr($_GET['start'], 0, 4);
    $startmonth = substr($_GET['start'], 5, 2);
    $startday = substr($_GET ['start'], 8, 2);
    $endyear = substr($_GET['end'], 0, 4);
    $endmonth = substr($_GET['end'], 5, 2);
    $endday = substr($_GET ['end'], 8, 2);
}


echo "<form action='schedule.php' method='post'>
    <b>View Another Set of Dates</b><br>
    Start: ";
selectInputDate('startmonth', 'startday', 'startyear', 2008, date('Y', strtotime('+1 year')), $startmonth, $startday, $startyear);
echo "<br>End: ";
selectInputDate('endmonth', 'endday', 'endyear', 2008, date('Y', strtotime('+1 year')), $endmonth, $endday, $endyear);
echo "<br>";
if ($_GET['dis'] == 'no') {
    echo "<input type='hidden' name='dis' value='no'>\n";
}
echo "<input type='submit' name='date' value='submit date'><hr>\n";

// Check POST data. If there is any, confirm for each one as we go that it is a valid member with REG permissions
//   if not, print error message and do not make change
if (is_array($_POST) && $mem == 1) {
    extract($_POST);

    if (is_array($s)) {
        foreach ($s as $shift => $thiss) {
            if (is_array($thiss)) {
                foreach ($thiss as $date => $member) {
                    if (checkMemberReg($member) || ($member == -1)) {
                        if ($change[$date][$shift] == 1) {
                            $sql = "UPDATE schedule
                                SET staffID='$member',
                                approved='0'
                                WHERE day='$date'
                                AND shift='$shift'";
                            query($cxn, $sql);
                        } else {
                            $sql = "INSERT INTO schedule
                                (day, staffID, shift)
                                VALUES ('$date', '$member', '$shift')";
                            query($cxn, $sql);
                        }
                    } elseif ($member == 0) {
                        $sql = "DELETE FROM schedule WHERE day='$date' AND shift='$shift'";
                        query($cxn, $sql);
                    } else {
                        echo "<b>$member is an invalid Member ID<br>Did not assign shift $shift on $date<p>\n";
                    }

                    $member = 0; // reset $member
                }
            }
        }
    }

// take new recurring schedule changes
    if (is_array($sched)) {
        foreach ($sched as $shift => $s) {
            if (is_array($s)) {
                foreach ($s as $day => $member) {
                    if ($member == 0) {
                        $sql = "DELETE FROM recurringSchedule WHERE day='$day' AND shift='$shift'";
                        query($cxn, $sql);
                    } else {
                        if (checkMemberReg($member)) {
                            $sql = "SELECT * FROM recurringSchedule WHERE day='$day' AND shift='$shift'";
                            query($cxn, $sql);
                            if ($cxn->affected_rows == 1) {
                                $sql = "UPDATE recurringSchedule
                                    SET staffID='$member'
                                    WHERE day='$day'
                                    AND shift='$shift'";
                                query($cxn, $sql);
                            } else {
                                $sql = "INSERT INTO recurringSchedule
                                    (staffID, day, shift)
                                    VALUES ('$member', '$day', '$shift')";
                                query($cxn, $sql);
                            }
                        }
                    }
                }
            }
        }
    }
}

$sql = "SELECT * FROM recurringSchedule";
$result = query($cxn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    extract($row);
    $recSched[$day][$shift] = $staffID;
}
mysqli_free_result($result);

// link to display schedule
echo "<a href='http://www.worldsapartgames.com/fc/schedule.php?dis=no'>Show Printable Schedule</a><p>";

// Display Recurring Schedule
echo "<h2>Recurring Schedule</h2><br>
    <table border><tr><td>Shift</td>
    <td>Sunday</td>
    <td>Monday</td>
    <td>Tuesday</td>
    <td>Wednesday</td>
    <td>Thursday</td>
    <td>Friday</td>
    <td>Saturday</td></tr>";
for ($shift = 1; $shift <= 3; $shift++) {
    echo "<tr><td>Shift #$shift</td>";
    for ($day = 1; $day <= 7; $day++) {
        echo "<td>";
        if (isset($recSched[$day][$shift])) {
            if ($mem == 1) {
                selectSchedVolunteer("sched[$shift][$day]", $recSched[$day][$shift]);
            } else {
                showVolunteer($recSched[$day][$shift], 0);
            }
        } else {
            if ($mem == 1) {
                selectSchedVolunteer("sched[$shift][$day]", 0);
            } else {
                showVolunteer($recSched[$day][$shift], 0);
            }
        }
        echo "</td>\n";
    }
    echo "</tr>";
}
echo "</table>";

echo "<a name='bookmark'>\n";

// Display selected period. If MEM, display inputs to change assignments
$start = date_create((isset($_GET['start'])) ? $_GET['start'] : date("Y-m-d"));

if (!isset($_GET['end'])) {
    $end = date_create();
    date_modify($end, "+2 weeks");
} else {
    $end = date_create($_GET['end']);
}

// make strings we can send in the query
$startStr = date_format($start, "Y-m-d");
$endStr = date_format($end, "Y-m-d");

$sql = "SELECT * FROM schedule WHERE day BETWEEN '$startStr' AND '$endStr' "
    . "ORDER BY day, shift";

// initialize counters
$curShift = 1; // current shift to display

if ($result = query($cxn, $sql)) {
    // if there is an error, we don't display nothin'
    $row = mysqli_fetch_assoc($result);
    $rowDate = date_create($row['day']);
    $rowDay = $row['day'];
    $rowShift = $row['shift'];

    // this tells it that this spot already exists so it doesn't create a new one
    if ($mem == 1) {
        echo "<input type='hidden' name='change[$rowDay][$rowShift]' value='1'>\n";
    }

    echo "<h2>Non-Recurring Daily Schedule</h2>
        From $startStr to $endStr<br>
        <table border><tr><td width=150>Day</td>
        <td width=200>Shift #1<br>10 AM to 2 PM</td>
        <td width=200>Shift #2<br>2 PM to 6 PM</td>
        <td width=200>Shift #3<br>6 PM to 10 PM</td></tr>\n";

    // cycle through days
    for ($date = $start; $date <= $end; date_modify($date, "+1 day")) {
        // cycle through shifts
        echo "<tr>"; // start the row
        for ($curShift = 1; $curShift <= 3; $curShift++) {
            if ($curShift == 1) {
                echo "<tr><td>" . date_format($date, "l") . "<br>"
                    . date_format($date, "M jS") . "</td>";
            }

            echo "<td>";

            // display shift info
            // if we have one of these, it should be displayed
            // staffer is sent as default. If it's 0, selectVolunteer knows it's undefinied, otherwise it
            // notes that it is assigned. If we use this one then we need to fetch a new one
            // if the new on is empty, it will never trip this if and the program will continue printing blank boxes for filling

            $dayNum = date_format($date, "w") + 1;
            $rsn = $recSched[$dayNum][$curShift]; // this is so we can display if the staffer is the regular one

            if (($date == $rowDate) && ($curShift == $row['shift'])) {
                $staffer = $row['staffID'];

                // get the next one
                $row = mysqli_fetch_assoc($result);
                $rowDate = date_create($row['day']);
                $rowDay = $row['day'];
                $rowShift = $row['shift'];

                // this tells it that this spot already exists so it doesn't create a new one
                if ($mem == 1) {
                    echo "<input type='hidden' name='change[$rowDay][$rowShift]' value='1'>\n";
                }
            }

            if ($mem == 1) {
                $dateStr = date_format($date, "Y-m-d");
                selectVolunteer("s[$curShift][$dateStr]", $staffer, $rsn);
            } else {
                showVolunteer($staffer, $rsn);
            }

            $staffer = 0; // reset var
            echo "</td>";
        }

        echo "</tr>"; // end the row
    }
    echo "</table><p>";
}

if ($mem == 1) {
    echo "<input type='submit' name='submit changes' value='submit changes'>
            </form>";
}

require 'footer.php';
?>
