<?php
// auth_check.php
// Session guard for protected portal pages using AuthController

require_once __DIR__ . '/controllers/AuthController.php';

$currentUser = AuthController::requireAuth();
