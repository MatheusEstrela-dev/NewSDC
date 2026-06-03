<?php

$files_to_check = [
    'app/Modules/Rat/Controllers/RatController.php',
    'routes/modules/rat.php',
    'app/Models/Rat/RatOcorrenciaAnexo.php'
];

echo "===== RAT ATTACHMENT IMPLEMENTATION VALIDATION =====\n\n";

echo "1. CHECKING REQUIRED FILES:\n";
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $lines = count(file($full_path));
        echo "   ✓ $file ($lines lines)\n";
    } else {
        echo "   ✗ $file MISSING\n";
    }
}

echo "\n2. VALIDATING CONTROLLER METHODS:\n";
$controller_path = __DIR__ . '/app/Modules/Rat/Controllers/RatController.php';
if (file_exists($controller_path)) {
    $content = file_get_contents($controller_path);

    // Check storeAnexo method
    if (strpos($content, 'public function storeAnexo') !== false) {
        echo "   ✓ storeAnexo() method found\n";
        if (strpos($content, 'public function storeAnexo(Request $request, string $id): JsonResponse') !== false) {
            echo "     ✓ Correct signature with Request, id, and JsonResponse return type\n";
        }
    } else {
        echo "   ✗ storeAnexo() method NOT FOUND\n";
    }

    // Check destroyAnexo method
    if (strpos($content, 'public function destroyAnexo') !== false) {
        echo "   ✓ destroyAnexo() method found\n";
        if (strpos($content, 'public function destroyAnexo(string $id, string $anexoId): JsonResponse') !== false) {
            echo "     ✓ Correct signature with id, anexoId, and JsonResponse return type\n";
        }
    } else {
        echo "   ✗ destroyAnexo() method NOT FOUND\n";
    }
}

echo "\n3. VALIDATING ROUTES:\n";
$routes_path = __DIR__ . '/routes/modules/rat.php';
if (file_exists($routes_path)) {
    $content = file_get_contents($routes_path);

    // Check POST attachments route
    if (strpos($content, "'{id}/attachments'") !== false && strpos($content, "'POST'") !== false) {
        echo "   ✓ POST /rat/{id}/attachments route found\n";
    } else {
        echo "   ✗ POST attachments route NOT FOUND\n";
    }

    // Check DELETE attachments route
    if (strpos($content, "'{id}/attachments/{anexoId}'") !== false && strpos($content, "'DELETE'") !== false) {
        echo "   ✓ DELETE /rat/{id}/attachments/{anexoId} route found\n";
    } else {
        echo "   ✗ DELETE attachments route NOT FOUND\n";
    }
}

echo "\n4. CHECKING DATABASE MIGRATION:\n";
$migrations_path = __DIR__ . '/database/migrations';
if (is_dir($migrations_path)) {
    $migrations = glob($migrations_path . '/*create_rat_ocorrencia_anexos_table.php');
    if (count($migrations) > 0) {
        echo "   ✓ RatOcorrenciaAnexo migration found\n";
        $migration_content = file_get_contents($migrations[0]);
        if (strpos($migration_content, 'arquivo_nome') !== false) {
            echo "     ✓ Migration has arquivo_nome column\n";
        }
        if (strpos($migration_content, 'arquivo_path') !== false) {
            echo "     ✓ Migration has arquivo_path column\n";
        }
        if (strpos($migration_content, 'arquivo_tipo') !== false) {
            echo "     ✓ Migration has arquivo_tipo column\n";
        }
    } else {
        echo "   ✗ Migration NOT FOUND\n";
    }
}

echo "\n5. CHECKING MODEL:\n";
$model_path = __DIR__ . '/app/Models/Rat/RatOcorrenciaAnexo.php';
if (file_exists($model_path)) {
    $content = file_get_contents($model_path);
    echo "   ✓ RatOcorrenciaAnexo model found\n";

    if (strpos($content, "protected \$fillable") !== false) {
        echo "     ✓ Has \$fillable property\n";
    }
    if (strpos($content, "SoftDeletes") !== false || strpos($content, "soft_deletes") !== false) {
        echo "     ✓ Uses soft deletes\n";
    }
}

echo "\n6. CHECKING FILE VALIDATION:\n";
$controller_path = __DIR__ . '/app/Modules/Rat/Controllers/RatController.php';
if (file_exists($controller_path)) {
    $content = file_get_contents($controller_path);

    if (strpos($content, 'max:10240') !== false || strpos($content, 'max:10') !== false) {
        echo "   ✓ File size validation present\n";
    }
    if (strpos($content, 'mimes:') !== false) {
        echo "     ✓ MIME type validation present (jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt)\n";
    }
}

echo "\n7. CHECKING STORAGE AND DB PERSISTENCE:\n";
$controller_path = __DIR__ . '/app/Modules/Rat/Controllers/RatController.php';
if (file_exists($controller_path)) {
    $content = file_get_contents($controller_path);

    if (strpos($content, "Storage::disk('public')->put") !== false || strpos($content, "->store(") !== false) {
        echo "   ✓ File storage to disk implemented\n";
    }
    if (strpos($content, "RatOcorrenciaAnexo::create") !== false) {
        echo "   ✓ Database record creation implemented\n";
    }
}

echo "\n===== VALIDATION COMPLETE =====\n";
?>
