<?php
/**
 * @file setevents.php
 * @brief setevents.php is a page for creating and editing events, to be listed
 *   on our homepage..
 * 
 * This file includes:
 * funcs.inc:
 * - Used for the config.inc include
 * - megaStrip()
 * - safePost()
 * - checkName()
 * - checkdate()
 * - checkMember()
 * - selectInputDate()
 * - selectInputDayOfWeek()
 * - selectInputTime()
 * - selectMember()
 * - printMember()
 * 
 * Possible Arguments:
 * SESSION:
 * - eve - Used to determine whether the active user has event
 *   privs.
 * 
 * POST:
 * - submit - If this is populated, we have work to do.
 * - delete - If set to 1, we should delete the event with event ID POST['ID']
 * - name - The desired name of the event we are creating/editing.
 * - month - The month the event will take place.
 * - day - The day the event will take place.
 * - year - The year the event will take place.
 * - week - The day of the week it takes place on, if a reccuring event.
 * - type - A short string used as a category for the event.
 * - sponsor - The member ID of the member who is running the event.
 * - shour - The hour this event starts at.
 * - sminute - The minute this event starts at.
 * - ehour - The hour this event ends at.
 * - eminute - The minute this event ends at.
 * - image - If there is an image associated with the event, this is the path
 *   to it.
 * - desc - A description for the event.
 * - ID - The ID of the event we're editing.
 * 
 * GET:
 * - ID - This is the event ID of the event the user wishes to view/edit.
 * - copee - If this value is 1, we are making a new copy of the event.
 * - showall - If this is 1, we should show historic events, in addition to
 *   future and weekly events.
 * 
 * @link http://www.worldsapartgames.org/fc/setevents.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */
$securePage = true;
require_once 'funcs.inc';
require_once 'header.php';

$cxn = open_stream();
megaStrip();
$image = "default.jpg"; // set default image

$safePost = array('name', 'desc', 'month', 'day', 'year', 'hour', 'minute',
    'week', 'type', 'sponsor', 'shour', 'sminute', 'ehour', 'eminute',
    'image', 'ID', 'submit', 'delete');
//($safePost);

if ($_SESSION['eve'] != '1') {
    echo "You must have Events Priviledges to access this page";
    include 'footer.php';
    die();
}

echo "This page has been updated. By default it now only shows weekly events "
    . "and events that are coming in the future. To see past events you have to "
    . "click the link below for 'show all events'. There is also now a "
    . "link on each event for 'copy event'. Doing this will copy all the "
    . "information for that event so you can easily make a similar event, "
    . "such as copying last week's sealed deck event to make this week's "
    . "sealed deck event.<hr>\n";

// if an ID is submitted, show that data for editing
if (isset($_GET['ID'])) {
    $ID = $_GET['ID'];
    $sql = "SELECT * FROM events WHERE ID='$ID'";
    $result = query($cxn, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        extract($row);
        $date = date_create($evdate);
        $day = $date->format("j");
        $month = $date->format("n");
        $year = $date->format("Y");
        $shour = substr($start, 0, 2);
        $sminute = substr($start, 3, 2);
        $ehour = substr($end, 0, 2);
        $eminute = substr($end, 3, 2);
        if ($_GET['copee'] == 1) {
            $copy = true;
            unset($ID); // no ID, then it will make a new one
        }
    } else {
        echo "No event found for ID #$ID<p>";
        unset($ID);
    }
} elseif (isset($_POST['submit'])) {
    // Check to see if there is POST data submitted. If there is, check it for bad stuff
    // if there is GET and POST, the POST is ignored
    
    extract($_POST);

    if ($delete == 1) {
        $sql = "DELETE FROM events WHERE ID='$ID'";
        if (query($cxn, $sql)) {
            echo "Event Deleted<p>";
        }
    } else {
        if ($bad['name'] = !checkName($name)) {
            $name = '';
        }
        if (!checkdate($month, $day, $year)) {
            $month = 0;
        }
        $type = strip_tags($type);
        if (
            $bad['sponsor'] = !(checkMember($sponsor))
        ) {
            $sponsor = 0;
        }
        // if everything is good, then good
        if (!in_array(true, $bad)) {
            // set up variables
            $date = ($month > 0) ? $year . '-' . $month . '-' . $day : NULL;
            $start = $shour . ':' . $sminute;
            $end = $ehour . ':' . $eminute;
            $image = strip_tags($image);


            // if there is an ID, it's an update
            if ($ID > 0) {
                if (
                    !($stmt = $cxn->prepare(
                        "UPDATE events
                        SET name=?,
                        description=?,
                        evdate=?,
                        start=?,
                        end=?,
                        week=?,
                        type=?,
                        sponsor=?,
                        image=?
                        WHERE ID='$ID'")
                    )
                ) {
                    die($cxn->error);
                }


                $stmt->bind_param("sssssisis", $name, $desc, $date, $start, 
                    $end, $week, $type, $sponsor, $image);
                echo "UPDATE";
                if (queryB($stmt)) {
                    unset($name, $desc, $date, $start, $end, $week, $type, 
                        $sponsor, $image, $ID);
                    echo "$name UPDATED<p>";
                }
            } else {
                $stmt = $cxn->prepare(
                    "INSERT INTO events (name, description, evdate, start, end, "
                    . "week, type, sponsor, image) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param(
                    'sssssisis', $name, $desc, $date, $start, $end, $week, 
                    $type, $sponsor, $image);
                if (queryB($stmt)) {
                    unset($name, $desc, $date, $start, $end, $week, $type, 
                        $sponsor, $image, $ID);
                    echo "$name CREATED<p>";
                }
            }
        } else {
            echo "You have errors in your submission<p>";

            foreach ($bad as $what => $which) {
                if ($which) {
                    echo "Bad $what<br>\n";
                }
            }
        }
    }
}

// display edit form
echo "<hr><form action='setevents.php' method='post'>";
if ($copy) {
    echo "<b>Working from copy of existing event</b><p>";
} elseif ($ID > 0) {
    echo "<font color=RED><b>Editing EXISTING event #$ID</b><br>
    Are you sure you don't want to make a new event?</font><p>";
}
echo "Event Name: <input name='name' type='text' size=40 maxlength=250 "
    . "value='$name'><p>Select either a date or a day of the week. 
    If you select a day of the week, it will be a recurring event<br>
    Event Date: ";
selectInputDate('month', 'day', 'year', date('Y'), 
    date('Y', strtotime('+1 year')), $month, $day, $year);
echo " or Recurring Event ";
selectInputDayOfWeek('week', $week);
echo "<p>Event Start: ";
selectInputTime('shour', 'sminute', $shour, $sminute);
echo "<p>Event End: ";
selectInputTime('ehour', 'eminute', $ehour, $eminute);
echo "<p>Type: <input type='text' name='type' size=40 maxlength=250 "
    . "value='$type'><p>";
echo "Event Organizer: ";
selectMember('sponsor', $sponsor);
echo "<p>Image name (leave blank for number): <input type='text' name='image' "
    . "size=20 maxlength=20 value='$image'>";
if ($ID > 0) {
    echo "<p>Delete event <input type='checkbox' name='delete' value='1'>
        <input type='hidden' name='ID' value='$ID'><p>\n";
}
echo "Description:<br><textarea cols=50 rows=5 name='desc'>$description</textarea><p>";
echo "<input type='submit' name='submit' value='submit'> <input type='submit' "
    . "name='submit' value='dominate'><hr>\n";

echo "<a href='setevents.php?showall=1'>See all events</a><hr>\n";
// display existing events
$days = array(1 => 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 
    'Saturday', 'Sunday');

if ($_GET['showall'] != 1) {
    $datestring = "WHERE week > 0 OR evdate > DATE_ADD(NOW(), INTERVAL 1 HOUR)";
}

$sql = "SELECT * FROM events $datestring ORDER BY evdate, week, start";
$result = query($cxn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    extract($row);
    echo "<b><a href='setevents.php?ID=$ID'>($ID) $name</a></b><br>
            <a href='setevents.php?ID=$ID&copee=1'>Make a Copy of this Event</a><br>";

    if ($week == 0) {
        echo "$evdate $start to $end<br>";
    } else {
        echo "Every " . $days[$week] . " $start to $end<br>";
    }
    echo "Sponsored by: ";
    printMember($sponsor, 1);
    echo "<br>Type: $type<p>$description<hr>";
}
require_once 'footer.php';
?>