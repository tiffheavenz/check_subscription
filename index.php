<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

/* ================= INPUT ================= */

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing reference'
    ]);
    exit;
}

/* ================= SUPABASE CONNECTION ================= */

try {

    $host = "aws-0-eu-west-3.pooler.supabase.com";
    $port = "5432";
    $db   = "postgres";

    $user = "postgres.lxsddkbtbynekazmdsbh";
    $pass = "YOUR_SUPABASE_DB_PASSWORD";

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

    $supabase = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);

} catch (Throwable $e) {

    error_log("Supabase connection failed: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
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

    /* ================= NOT FOUND ================= */

    if (!$tx) {
        echo json_encode([
            'success' => false,
            'message' => 'Reference not found'
        ]);
        exit;
    }

    /* ================= CHECK STATUS ================= */

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

    error_log("Transaction check failed: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Transaction check error'
    ]);
}
