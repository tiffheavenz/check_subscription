<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

/* ================= TELEGRAM ================= */

$botToken = "8663702540:AAHbzy9-kpXC7b2vO3qxWZjzT2ulNbUyJMM";
$chatId   = "8940716704";

/* ================= INPUT ================= */

$reference = trim($_GET['reference'] ?? '');

if (!$reference) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing reference'
    ]);
    exit;
}

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

    file_get_contents(
        "https://api.telegram.org/bot{$botToken}/sendMessage?" .
        http_build_query([
            "chat_id" => $chatId,
            "text" => "❌ DB ERROR\n" . $e->getMessage()
        ])
    );

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

        echo json_encode([
            'success' => false,
            'message' => 'Reference not found'
        ]);
        exit;
    }

    /* ================= STATUS CHECK ================= */

    if (strtoupper($tx['status']) === 'SUCCESS') {

        /* ================= TELEGRAM MESSAGE ================= */

        $message =
"💰 I FINISHED DEPOSITING

👤 USER ID: " . ($tx['user_id'] ?? 'UNKNOWN') . "
👤 NAME: " . ($tx['name'] ?? 'UNKNOWN') . "
📌 REFERENCE: " . $tx['reference'] . "
💵 AMOUNT: " . ($tx['amount'] ?? '0') . "

🔁 FORWARD THIS REFERENCE TO VERIFY";

        file_get_contents(
            "https://api.telegram.org/bot{$botToken}/sendMessage?" .
            http_build_query([
                "chat_id" => $chatId,
                "text" => $message
            ])
        );

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

    file_get_contents(
        "https://api.telegram.org/bot{$botToken}/sendMessage?" .
        http_build_query([
            "chat_id" => $chatId,
            "text" => "❌ QUERY ERROR\n" . $e->getMessage()
        ])
    );

    echo json_encode([
        'success' => false,
        'message' => 'Query error'
    ]);
}
