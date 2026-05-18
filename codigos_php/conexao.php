<?php
require_once 'config.php';

try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
        DB_HOST,
        DB_PORT,
        DB_NAME
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // PDO::ATTR_PERSISTENT      => true, // opcional: descomente se quiser conexão persistente
    ];

    $conn = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Ajusta timezone da sessão no banco (Postgres)
    $conn->exec("SET TIME ZONE 'America/Sao_Paulo'");

} catch (PDOException $e) {
    // Log do erro (arquivo de log do servidor) — não exponha em produção
    error_log("DB Connection error: " . $e->getMessage());

    // Mensagem genérica para o usuário / frontend
    http_response_code(500);
    die("Erro de conexão com o banco de dados. Contate o administrador.");
}
?>
