<?php
// viewreceipts.php

// By default: shows a list of all receipts in reverse chronological order
// Provides links to reverse a receipt, view a receipt, or, if it is not closed, enter receipt

// GET: start - start date
//      end - end date
//      first - first receipt ID
//      last - last receipt ID
//      staff - show only receipts sold by this staff member
//      customer - show only receipts sold to this customer

//      reverse - ID of receipt to reverse
//      unreverse - ID of receipt to unreverse

//      view - ID of receipt to view

   $title = 'View Receipts';
   include('funcs.inc');
   include('inventory.inc');
   include('credits.inc');
   include('header.php');
   $cxn = open_stream();
   
   // Let user select spiffy specific dates
   if(!isset($_GET['start']))
   {
      $_GET['start'] = date('Y-m-d');
   }
   $sdate = date_create($_GET['start']);
   $edate = date_create($_GET['end']);
   echo "<form action='viewreceipts.php' method='get'>
         <p><b>View Another Set of Dates</b><br>
         Start: ";
   selectInputDate('startmonth', 'startday', 'startyear', 2008, date('Y', strtotime('+1 year')), $_GET['startmonth'], $_GET['startday'], $_GET['startyear']);
   echo "<br>End: ";
   selectInputDate('endmonth', 'endday', 'endyear', 2008, date('Y', strtotime('+1 year')), $edate->format("n"), $edate->format("j"), $edate->format("Y"));
   echo "<p>
         Sales for a Customer: ";
   selectMember('customer', 0);
   echo "<br>
         Sales by Staff Member: ";
   selectRegMember('staff', 0);
   echo "<br>
         <input type='submit' name='submit' value='submit'>
         <input type='submit' name='dominate' value='dominate'><hr>\n";
   

   // Show specific receipt if appropriate
   if(isset($_GET['view']))
   {
      displayReceipt($_GET['view']);
   }
   echo "<hr>\n";
   
   // show new receipts
   $view = new viewreceipt;
   $view->showlist();

?>