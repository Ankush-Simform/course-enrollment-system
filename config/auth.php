<?php
session_start();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /public/login.php");
        exit;
    }
}

function requireRole($role_id) {
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != $role_id) {
        header("Location: /public/login.php");
        exit;
    }
}