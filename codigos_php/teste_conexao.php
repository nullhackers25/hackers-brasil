<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Testando conexão...<br>";

try {
    require_once 'conexao.php';
    echo "✅ Conexão com banco: OK!<br>";
    
    // Testa uma query simples
    $stmt = $conn->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Query teste: OK!<br>";
    
    echo "🎉 Tudo funcionando!";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
