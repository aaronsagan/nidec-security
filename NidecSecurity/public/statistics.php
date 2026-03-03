<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/StatisticsController.php';

(new StatisticsController())->index();
