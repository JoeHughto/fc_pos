<?php
// schedule.php
// Displays the schedule for a given time period to any member
// Also displays default schedule
// 'Mem' users can change who is assigned to a shift. They can set it as a one time thing or a recurring schedule

// GET:
// start - a starting date to display
// end - an ending date to display

// POST:
// S1, S2, S3 - array of member numbers with keys of dates
// change - array of date,shift which is 1 if this is a change and 0 if the shift is currently empty

// VERSION
// 1.1 added ability to set shift as blank even if there is a recurring staff member
// 1.11 added dis=no to produce block format

   $securePage = true;
   include('funcs.inc');

// Check permissions and set mem to indicate if user can make changes
   $mem = $_SESSION['mem'];
   if($_GET['dis'] == 'no' || $_POST['dis'] == 'no')
   {
      $mem = 0;
      include('noheader.php');
   }
   else
   {
      include('header.php');
   }
   
   include('schedule.inc');

   $cxn = open_stream();


   if($mem == 1)
   {
      echo "<form action='schedule.php' method='post'>\n";
   }

   if(isset($_POST['date']))
   {
      extract($_POST);
      $_GET['start'] = $startyear . '-' . $startmonth . '-' . $startday;
      $_GET['end'] = $endyear . '-' . $endmonth . '-' . $endday;
   }
   else
   {
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
   if ($_GET['dis'] == 'no') echo "<input type='hidden' name='dis' value='no'>\n";
   echo "<input type='submit' name='date' value='submit date'><hr>\n";

// Check POST data. If there is any, confirm for each one as we go that it is a valid member with REG permissions
//   if not, print error message and do not make change
   if(is_array($_POST) && $mem == 1)
   {
      extract($_POST);


      if(is_array($s)) foreach($s as $shift => $thiss)
      {
         if(is_array($thiss))
         {
            foreach ($thiss as $date => $member)
            {
               if(checkMemberReg($member) || ($member == -1))
               {
                  if($change[$date][$shift] == 1)
                  {
                     $sql = "UPDATE schedule
                                SET staffID='$member',
                                    approved='0'
                              WHERE day='$date'
                                AND shift='$shift'";
                     query($cxn, $sql);
                  }
                  else
                  {
                     $sql = "INSERT INTO schedule
                                         (day, staffID, shift)
                                  VALUES ('$date', '$member', '$shift')";
                     query($cxn, $sql);
                  } // end else
               } // end if checkMemberReg
               else if($member == 0)
               {
                  $sql = "DELETE FROM schedule WHERE day='$date' AND shift='$shift'";
                  query($cxn, $sql);
               }
               else
               {
                  echo "<b>$member is an invalid Member ID<br>Did not assign shift $shift on $date<p>\n";
               }
               
               $member = 0; // reset $member
            } // end foreach
         } // end if
      } // end foreach

      // take new recurring schedule changes
      if(is_array($sched)) foreach($sched as $shift => $s)
      {
         if(is_array($s))
         {
            foreach($s as $day => $member)
            {
               if($member == 0)
               {
                  $sql = "DELETE FROM recurringSchedule WHERE day='$day' AND shift='$shift'";
                  query($cxn, $sql);
               }
               else
               {
                  if(checkMemberReg($member))
                  {
                     $sql = "SELECT * FROM recurringSchedule WHERE day='$day' AND shift='$shift'";
                     query($cxn, $sql);
                     if($cxn->affected_rows == 1)
                     {
                        $sql = "UPDATE recurringSchedule
                                   SET staffID='$member'
                                 WHERE day='$day'
                                   AND shift='$shift'";
                        query($cxn, $sql);
                     }
                     else
                     {
                        $sql = "INSERT INTO recurringSchedule
                                            (staffID, day, shift)
                                     VALUES ('$member', '$day', '$shift')";
                        query($cxn, $sql);
                     } // end else
                  } // end if
               } // end else, member != 0
            } // end foreach
         } // end if is array
      } //end foreach
   } // end if POST isset


// fetch recurring schedule
   $sql = "SELECT * FROM recurringSchedule";
   $result = query($cxn, $sql);
   while($row = mysqli_fetch_assoc($result))
   {
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
   for($shift = 1; $shift <= 3; $shift++)
   {
      echo "<tr><td>Shift #$shift</td>";
      for($day = 1; $day <= 7; $day++)
      {
         echo "<td>";
         if(isset($recSched[$day][$shift]))
         {
            if($mem == 1)
            {
               selectSchedMonkey("sched[$shift][$day]", $recSched[$day][$shift]);
            }
            else
            {
               showRegMonkey($recSched[$day][$shift], 0);
            }
         } // if isset recSched
         else
         {
            if($mem == 1)
            {
               selectSchedMonkey("sched[$shift][$day]", 0);
            }
            else
            {
               showRegMonkey($recSched[$day][$shift], 0);
            }
         } // end else
         echo "</td>\n";
      } // end for shift
      echo "</tr>";
   } // end for day
   echo "</table>";

   echo "<a name='bookmark'>\n";

// Display selected period. If MEM, display inputs to change assignments
   $start = date_create((isset($_GET['start'])) ? $_GET['start'] : date ("Y-m-d"));
   
   if(!isset($_GET['end']))
   {
      $end = date_create();
      date_modify($end, "+2 weeks");
   }
   else
   {
      $end = date_create($_GET['end']);
   }

   // make strings we can send in the query
   $startStr = date_format($start, "Y-m-d");
   $endStr = date_format($end, "Y-m-d");

   $sql = "SELECT * FROM schedule WHERE day BETWEEN '$startStr' AND '$endStr' ORDER BY day, shift";

   // initialize counters
   $curShift = 1; // current shift to display

   if($result = query($cxn, $sql)) // if there is an error, we don't display nothin'
   {
      $row = mysqli_fetch_assoc($result);
      $rowDate = date_create($row['day']);
      $rowDay = $row['day'];
      $rowShift = $row['shift'];

      // this tells it that this spot already exists so it doesn't create a new one
      if($mem == 1) echo "<input type='hidden' name='change[$rowDay][$rowShift]' value='1'>\n";

      echo "<h2>Non-Recurring Daily Schedule</h2>
            From $startStr to $endStr<br>
            <table border><tr><td width=150>Day</td>
            <td width=200>Shift #1<br>10 AM to 2 PM</td>
            <td width=200>Shift #2<br>2 PM to 6 PM</td>
            <td width=200>Shift #3<br>6 PM to 10 PM</td></tr>\n";

      // cycle through days
      for($date = $start; $date <= $end; date_modify($date, "+1 day"))
      {
         // cycle through shifts
         echo "<tr>"; // start the row
         for($curShift = 1; $curShift <= 3; $curShift++)
         {
            if($curShift == 1) // starting a new row, print left labels
            {
               echo "<tr><td>" . date_format($date, "l") . "<br>" . date_format($date, "M jS") . "</td>";
            }

            echo "<td>";

            // display shift info

            // if we have one of these, it should be displayed
            // staffer is sent as default. If it's 0, selectRegMonkey knows it's undefinied, otherwise it
            // notes that it is assigned. If we use this one then we need to fetch a new one
            // if the new on is empty, it will never trip this if and the program will continue printing blank boxes for filling

            $dayNum = date_format($date, "w") + 1;
            $rsn = $recSched[$dayNum][$curShift]; // this is so we can display if the staffer is the regular one

            if (($date == $rowDate)
            && ($curShift == $row['shift']))
            {
               $staffer = $row['staffID'];

               // get the next one
               $row = mysqli_fetch_assoc($result);
               $rowDate = date_create($row['day']);
               $rowDay = $row['day'];
               $rowShift = $row['shift'];
               
               // this tells it that this spot already exists so it doesn't create a new one
               if($mem == 1) echo "<input type='hidden' name='change[$rowDay][$rowShift]' value='1'>\n";
            }
            
            if($mem == 1)
            {
               $dateStr = date_format($date, "Y-m-d");
               selectRegMonkey("s[$curShift][$dateStr]", $staffer, $rsn);
            }
            else
            {
               showRegMonkey($staffer, $rsn);
            }
            
            $staffer = 0; // reset var
            echo "</td>";
         } // end for cycling through shifts

         echo "</tr>"; // end the row
      } // end for cycling through days
   echo "</table><p>";
   } // end if
   
   if ($mem==1)
   {
      echo "<input type='submit' name='submit changes' value='submit changes'>
            </form>";
   }
   
   $version = '1.1';
   include('footer.php');
?>
