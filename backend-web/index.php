<?php
// index.php
// [SPEAKER 3 PRESENTATION POINT: Back-Office Web Dashboard Controller]
// Authenticates active session state and routes the supervisor to the client-rendered index.html view.

require_once __DIR__ . '/auth_check.php';

// Direct the browser to the pure HTML5 presentation dashboard
header("Location: index.html");
exit;
