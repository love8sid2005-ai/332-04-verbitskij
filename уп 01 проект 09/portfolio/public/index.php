<?php
require_once "../backend/config.php";

if (checkAuth()) {
    redirect("/portfolio.php");
} else {
    redirect("/login.php");
}
?>
