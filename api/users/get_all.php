<?php

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-CredentialsL true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Akses ditolak! Anda belum login."
    ]);
    exit;
}

if ($_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Akses ditolak! Hanya Super Admin yang dapat melihat daftar user."
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, name, username, role, photo, is_admin FROM users ORDER BY name ASC");
        $users = $stmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "data" => $users
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Terjadi kesalahan server internal."
        ]);
    }
    exit;
}

?>