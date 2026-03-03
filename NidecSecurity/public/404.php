<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/NotFoundController.php';

$controller = new NotFoundController();
$controller->index();
