<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) audit('LOGOUT', 'Authentication', 'Logout');
$_SESSION = [];
session_destroy();
redirect('login.php');
