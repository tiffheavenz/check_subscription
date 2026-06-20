<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

/* ================= INPUT ================= */

$reference = $_GET['reference'] ?? '';

if (!$reference) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing reference'
    ]);
    exit;
}

/* ================= SUPABASE CONNECTION (MATCH YOUR WORKING ONE) ================= */

try {

    $pdo = new PDO(
        "pgsql:host=aws-0-eu-west-3.pooler.supabase.com;port=5432;dbname=postgres",
        "postgres.lxsddkbtbynekazmdsbh",
        "@Shjeeee2024",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]
    );

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'debug' => $e->getMessage()
    ]);
    exit;
}

/* ================= CHECK TRANSACTION ================= */

try {

    $stmt = $pdo->prepare("
        SELECT status
        FROM transactions
        WHERE reference = ?
        LIMIT 1
    ");

    $stmt->execute([$reference]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tx) {
        echo json_encode([
            'success' => false,
            'message' => 'Reference not found'
        ]);
        exit;
    }

    if (strtoupper($tx['status']) === 'SUCCESS') {

        echo json_encode([
            'success' => true,
            'message' => 'Payment successful'
        ]);

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'Payment not successful'
        ]);
    }

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => 'Query error',
        'debug' => $e->getMessage()
    ]);
}
