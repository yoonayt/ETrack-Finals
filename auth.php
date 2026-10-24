<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function redirectIfLoggedIn(): void
{
    if (!empty($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}
