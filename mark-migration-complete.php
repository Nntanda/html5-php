<?php

try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=sacco_db', 'root', '');
    $pdo->exec("INSERT INTO migrations (migration, batch) VALUES ('2024_01_01_000013_add_performance_indexes_to_tables', 1)");
    echo "✅ Migration marked as complete!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
