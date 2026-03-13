<?php
require_once "../backend/config.php";
require_once "../backend/auth.php";

logout();
redirect("/login.php");
?>
