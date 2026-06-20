<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

/* ================= TELEGRAM ================= */

$botToken = "8663702540:AAHbzy9-kpXC7b2vO3qxWZjzT2ulNbUyJMM";
$chatId   = "8940716704";

/* ================= HELPER: SEND TO TELEGRAM ================= */

function tg($text) {
    global $botToken, $chatId;

    file_get_contents(
        "https://api.telegram.org/bot{$botToken}/sendMessage?" .
        http_build_query([
            "chat_id" => $chatId,
            "text" => $text
        ])
    );
}

/* ================= INPUT ================= */

$reference = trim($_GET['reference'] ?? '');

if (!$reference) {

    tg("❌ REQUEST FAILED\nMissing reference");

    echo json_encode([
        'success' => false,
        'message' => 'Missing reference'
    ]);
    exit;
}

/* ================= LOG REQUEST ================= */

tg("📡 NEW CHECK REQUEST\nReference: {$reference}");

/* ================= DATABASE ================= */

try {

    $pdo = new PDO(
        "pgsql:host=aws-0-eu-west-3.pooler.supabase.com;port=5432;dbname=postgres",
        "postgres.lxsddkbtbynekazmdsbh",
        "@Shjeeee2024",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );

} catch (PDOException $e) {

    tg("❌ DATABASE CONNECTION FAILED\n" . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit;
}

/* ================= FETCH TRANSACTION ================= */

try {

    $stmt = $pdo->prepare("
        SELECT *
        FROM transactions
        WHERE reference = ?
        LIMIT 1
    ");

    $stmt->execute([$reference]);
    $tx = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tx) {

        tg("⚠️ REFERENCE NOT FOUND\n{$reference}");

        echo json_encode([
            'success' => false,
            'message' => 'Reference not found'
        ]);
        exit;
    }

    /* ================= LOG TRANSACTION ================= */

    tg(
        "📊 TRANSACTION FOUND\n\n" .
        "User ID: " . ($tx['user_id'] ?? 'UNKNOWN') . "\n" .
        "Name: " . ($tx['name'] ?? 'UNKNOWN') . "\n" .
        "Reference: " . $tx['reference'] . "\n" .
        "Amount: " . ($tx['amount'] ?? '0') . "\n" .
        "Status: " . $tx['status']
    );

    /* ================= CHECK STATUS ================= */

    if (strtoupper($tx['status']) === 'SUCCESS') {

        tg("✅ PAYMENT SUCCESS CONFIRMED\n{$reference}");

        echo json_encode([
            'success' => true,
            'message' => 'Payment successful'
        ]);

    } else {

        tg("⏳ PAYMENT STILL PROCESSING\n{$reference}");

        echo json_encode([
            'success' => false,
            'message' => 'Payment not successful'
        ]);
    }

} catch (Throwable $e) {

    tg("❌ QUERY ERROR\n" . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Query error'
    ]);
}
