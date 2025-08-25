<?php
if (session_status()===PHP_SESSION_NONE) session_start();
function require_admin(){ if(empty($_SESSION['uid'])){ header('Location:admin/login.php'); exit; } }
?>