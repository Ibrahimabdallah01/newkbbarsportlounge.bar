<?php

require_once __DIR__ . "/../config/config.php";

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}

function create_admin($name, $email, $password) {
    global $pdo;
    $hashed_password = hash_password($password);
    $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
    return $stmt->execute([$name, $email, $hashed_password]);
}

function authenticate_admin($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && verify_password($password, $admin["password"])) {
        $_SESSION["user_id"] = $admin["id"];
        $_SESSION["user_name"] = $admin["name"];
        $_SESSION["user_role"] = "admin";
        return true;
    }
    return false;
}

function authenticate_employee($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->execute([$email]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($employee && verify_password($password, $employee["password"])) {
        $_SESSION["user_id"] = $employee["id"];
        $_SESSION["user_name"] = $employee["name"];
        $_SESSION["user_role"] = "employee";
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    setcookie(session_name(), 
    '', 
    time() - 42000, 
    '/');
}

?>

