<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_data'])) {
    echo json_encode($_SESSION['user_data']);
} else {
    echo json_encode(['error' => 'User not logged in']);
}
