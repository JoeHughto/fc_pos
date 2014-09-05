<?php
/**
 * @file logout.php
 * @brief logout.php is the page that the "Logout" link in the menu brings the
 *   active user to. Upon loading, all SESSION variables are wiped, and we
 *   are presented with a login screen.
 * 
 * This file includes:
 * funcs.inc:
 * - ?
 * 
 * @link http://www.worldsapartgames.org/fc/logout.php @endlink
 * 
 * @author    Michael Whitehouse 
 * @author    Creidieki Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @version   1.8d
 * @since     Project has existed since time immemorial.
 */

require_once 'funcs.inc';
require_once 'header.php';

foreach ($_SESSION as $key => $value) {
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
