<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/AssignedReportsController.php';

(new AssignedReportsController())->index();
