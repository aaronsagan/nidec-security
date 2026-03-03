<?php
session_start();
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../app/controllers/HistoryController.php';

(new HistoryController())->index();
