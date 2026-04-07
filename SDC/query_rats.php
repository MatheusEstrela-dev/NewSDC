<?php
try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=dbsdc_local;charset=utf8mb4',
        'root',
        'root',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Count total rats
    $count = $pdo->query("SELECT COUNT(*) FROM rats")->fetchColumn();
    echo "Total RATs: " . $count . PHP_EOL;
    
    // Get first 5 rats
    $rats = $pdo->query("SELECT id, created_at FROM rats LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    if (count($rats) > 0) {
        echo "\nFirst 5 RAT IDs:" . PHP_EOL;
        foreach ($rats as $rat) {
            echo "ID: " . $rat['id'] . ", Created: " . $rat['created_at'] . PHP_EOL;
        }
    } else {
        echo "No rats found in database." . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $db = $app->make("db"); $rats = $db->table("rats")->select("id")->limit(3)->get(); foreach ($rats as $rat) { echo "ID: " . $rat->id . PHP_EOL; }
