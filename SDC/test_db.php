<?php
$host = '34.39.254.123';
$db   = 'sdc';
$user = 'sdc';
$pass = '}~>0dPy0rv"~1NQL';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset;port=3306";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Check if the process exists and what ID it has
    $stmt = $pdo->prepare("SELECT id FROM dec_entrada_processos WHERE n_protocolo_fide = ?");
    $stmt->execute(['MG-F-3148301-13214-20251217']);
    $processo = $stmt->fetch();
    $pid = $processo['id'];
    
    // Get ALL raw data without dic.tipo filtering
    $query = "
        SELECT 
            ed.id as ed_id,
            ed.municipio_id,
            dc.titulo as categoria,
            di.titulo as item,
            dic.titulo as campo,
            dic.tipo as dic_tipo,
            ed.valor,
            ed.deleted_at
        FROM dec_entrada_categoria_desastres AS ecd
        JOIN dec_entrada_desastres AS ed ON ecd.id = ed.entrada_categoria_desastre_id
        JOIN dec_desastre_item_campos AS dic ON ed.item_campo_id = dic.id
        LEFT JOIN dec_desastre_items AS di ON dic.desastre_item_id = di.id
        JOIN dec_desastre_categorias AS dc ON ecd.categoria_id = dc.id
        WHERE ecd.entrada_processo_id = ?
          AND dic.tipo IN ('number', 'currency')
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$pid]);
    $raw = $stmt->fetchAll();
    
    echo "--- RAW DESASTRE NUMBERS/CURRENCY ---\n";
    foreach ($raw as $r) {
        echo sprintf("MunID: %s | %s -> %s (tipo: %s) = %s\n", 
            $r['municipio_id'] ?? 'NULL',
            $r['categoria'], 
            $r['campo'], 
            $r['dic_tipo'], 
            $r['valor']
        );
    }
    
} catch (\PDOException $e) {
    echo $e->getMessage();
}
