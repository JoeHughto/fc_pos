<?php
// logout.php
// completely logs out user, displays login

   include('funcs.inc');
   include('header.php');

   foreach($_SESSION as $key => $value)
   {
      unset($_SESSION[$key]);
   }
   
   echo"<h1>Login</h1>
        <hr>
        <form action='logout.php' method='post'>
        Username: <input type='text' name='username'><p>
        Password: <input type='password' name='password'><p>
        <input type='submit' name='submit' value='Login'>
        </form><p>";
?>
