<?php
session_start();
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../includes/functions.php";

if (!is_logged_in() || !is_admin()) {
    redirect("../index.php");
}

$error = "";
$success = "";

// Handle Create Rotation Group
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST["name"]);
    $department_id = !empty($_POST["department_id"]) ? sanitize_input($_POST["department_id"]) : null;
    $shift_pattern_id = sanitize_input($_POST["shift_pattern_id"]);
    $start_date = sanitize_input($_POST["start_date"]);
    $description = sanitize_input($_POST["description"]);
    
    if (empty($name) || empty($shift_pattern_id) || empty($start_date)) {
        $error = "Name, shift pattern, and start date are required.";
    } else {
        try {
            // Insert rotation group
            $stmt = $pdo->prepare("
                INSERT INTO rotation_groups (name, department_id, shift_pattern_id, start_date, description, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            
            if ($stmt->execute([$name, $department_id, $shift_pattern_id, $start_date, $description])) {
                $rotation_group_id = $pdo->lastInsertId();
                $_SESSION['success'] = "Rotation group created successfully! Now add employees to the group.";
                redirect("rotation_details.php?id=" . $rotation_group_id);
            } else {
                $error = "Failed to create rotation group.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// If error, redirect back with error message
if ($error) {
    $_SESSION['error'] = $error;
    redirect("rotation_management.php");
}
?>