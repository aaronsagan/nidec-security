<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/PrintReportByNoController.php';

$controller = new PrintReportByNoController();
$controller->redirect();
