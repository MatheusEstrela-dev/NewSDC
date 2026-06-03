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
    
    // Get all rats
    $rats = $pdo->query("SELECT id, created_at FROM rats ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "Total RATs: " . count($rats) . PHP_EOL . PHP_EOL;
    
    foreach ($rats as $idx => $rat) {
        echo ($idx + 1) . ". ID: " . $rat['id'] . " | Created: " . $rat['created_at'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
