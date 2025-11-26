<?php
session_start();
require_once __DIR__ . "/includes/functions.php";
require_once __DIR__ . "/includes/auth.php";

logout();
redirect("index.php");
?>

