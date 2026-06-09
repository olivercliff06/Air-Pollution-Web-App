<?php
require_once __DIR__ . '/config.php';

logout_user();

$redirect = $_GET['redirect'] ?? 'index.php';
header('Location: ' . $redirect);
exit;


