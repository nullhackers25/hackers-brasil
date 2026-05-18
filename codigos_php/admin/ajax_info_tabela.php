<?php
session_start();
require_once '../conexao.php';

// Verificar login
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_GET['tabela']) || empty($_GET['tabela'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tabela não especificada']);
    exit;
}

$tabela = $_GET['tabela'];

try {
    // Informações básicas da tabela
    $sql_colunas = "SELECT 
                    column_name as nome,
                    data_type as tipo,
                    character_maximum_length as tamanho,
                    is_nullable as nulo,
                    column_default as valor_padrao
                   FROM information_schema.columns 
                   WHERE table_name = :tabela 
                   ORDER BY ordinal_position";
    
    $stmt_colunas = $conn->prepare($sql_colunas);
    $stmt_colunas->bindParam(':tabela', $tabela);
    $stmt_colunas->execute();
    $colunas = $stmt_colunas->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar registros
    $sql_registros = "SELECT COUNT(*) as total FROM \"$tabela\"";
    $stmt_registros = $conn->query($sql_registros);
    $total_registros = $stmt_registros->fetchColumn();
    
    // Informações adicionais
    $sql_info = "SELECT 
                 obj_description(c.oid) as descricao
                 FROM pg_class c
                 WHERE c.relname = :tabela 
                 AND c.relkind = 'r'";
    
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bindParam(':tabela', $tabela);
    $stmt_info->execute();
    $info_adicional = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    // Chaves primárias
    $sql_pk = "SELECT 
               a.attname as coluna_pk
               FROM pg_index i
               JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
               WHERE i.indrelid = :tabela::regclass 
               AND i.indisprimary";
    
    $stmt_pk = $conn->prepare($sql_pk);
    $stmt_pk->bindParam(':tabela', $tabela);
    $stmt_pk->execute();
    $chaves_primarias = $stmt_pk->fetchAll(PDO::FETCH_COLUMN);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'tabela' => $tabela,
        'total_colunas' => count($colunas),
        'total_registros' => (int)$total_registros,
        'colunas' => $colunas,
        'descricao' => $info_adicional['descricao'] ?? null,
        'chaves_primarias' => $chaves_primarias
    ]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar informações: ' . $e->getMessage()
    ]);
}
