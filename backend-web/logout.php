<?php
// logout.php
// Clears session using AuthController and redirects to login

require_once __DIR__ . '/php/controllers/AuthController.php';

AuthController::logout();
header("Location: login.php");
exit;
