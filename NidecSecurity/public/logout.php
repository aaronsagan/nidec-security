<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/LogoutController.php';

(new LogoutController())->index();
