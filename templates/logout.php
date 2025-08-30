<?php
session_start();
session_unset();
session_destroy();
header('Location: before_login.php');
exit;
?>
