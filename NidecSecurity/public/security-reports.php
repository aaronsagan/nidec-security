<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/SecurityReportsController.php';

(new SecurityReportsController())->index();
