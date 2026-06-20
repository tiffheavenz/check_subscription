<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

/* ================= INPUT ================= */

// You pass reference directly from ByetHost
$reference = $_GET['reference'] ?? '';

if ($reference == '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing reference'
    ]);
    exit;
}

/* ================= SUPABASE CONNECTION ================= */

try {

    $supabase = new PDO(
        "pgsql:host=aws-0-eu-west-3.pooler.supabase.com;port=5432;dbname=postgres;sslmode=require",
        "postgres.lxsddkbtbynekazmdsbh",
        "YOUR_SUPABASE_DB_PASSWORD",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]
    );

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => 'DB connection failed'
    ]);
    exit;
}

/* ================= CHECK TRANSACTION ================= */

try {

    $stmt = $supabase->prepare("
        SELECT status
        FROM transactions
        WHERE reference = ?
        LIMIT 1
    ");

    $stmt->execute([$reference]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tx) {
        echo json_encode([
            'success' => false
        ]);
        exit;
    }

    if (strtoupper($tx['status']) === 'SUCCESS') {
        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'success' => false
        ]);
    }

} catch (Throwable $e) {

    echo json_encode([
        'success' => false
    ]);
}
