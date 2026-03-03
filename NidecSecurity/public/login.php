<?php
// Start session at the very beginning
session_start();
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';

(new AuthController())->login();
