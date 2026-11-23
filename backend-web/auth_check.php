<?php
// auth_check.php
// Session guard for protected portal pages using AuthController

require_once __DIR__ . '/php/controllers/AuthController.php';

$currentUser = AuthController::requireAuth();
