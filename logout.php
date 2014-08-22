<?php
/**
 * Logout.php is the page that the "Logout" link in the menu brings the
 *   active user to. Upon loading, all SESSION variables are wiped, and we
 *   are presented with a login screen.
 *
 * PHP version 5.4
 *
 * LICENSE: TBD
 *
 * @category  Report_Form
 * @package   FriendComputer
 * @author    Michael Whitehouse 
 * @author    Crideke Crouch 
 * @author    Desmond Duval 
 * @copyright 2009-2014 Pioneer Valley Gaming Collective
 * @license   TBD
 * @version   GIT:$ID$
 * @link      http://www.worldsapartgames.org/fc/logout.php
 * @since     Project has existed since time immemorial.
 */

/**
 * This file includes:
 * funcs.inc:
 *   ?
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
