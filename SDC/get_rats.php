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
    
    // Get first 3 rats
    $rats = $pdo->query("SELECT id, created_at FROM rats ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    echo "First 3 RATs:" . PHP_EOL;
    
    foreach ($rats as $rat) {
        echo $rat['id'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
?>
