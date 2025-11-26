<?php

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

function is_admin() {
    return isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin";
}

function is_employee() {
    return isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "employee";
}

?>

