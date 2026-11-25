<?php

declare(strict_types=1);

/*
 * Arquivo carregado no topo de todas as páginas.
 * Inicia a sessão, carrega as funções e abre a conexão com o banco.
 */

// Em desenvolvimento os erros aparecem na tela; em produção (Coolify) só vão para o log.
$modoDesenvolvimento = (getenv('APP_ENV') ?: 'development') !== 'production';

if ($modoDesenvolvimento) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

/** Mostra uma página de erro legível em vez de uma tela branca. */
function mostrarErroFatal(string $detalhe, bool $mostrarDetalhe): void
{
    error_log('SolarControl: ' . $detalhe);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $texto = $mostrarDetalhe
        ? htmlspecialchars($detalhe, ENT_QUOTES, 'UTF-8')
        : 'Ocorreu um erro interno. Tente novamente em instantes.';

    echo '<div style="font-family:sans-serif;max-width:720px;margin:40px auto;padding:20px;'
        . 'border:1px solid #c0392b;border-radius:8px;color:#222;background:#fff">'
        . '<h2 style="margin-top:0;color:#c0392b">Não foi possível carregar o SolarControl</h2>'
        . '<p style="white-space:pre-wrap">' . $texto . '</p></div>';
}

// Exceções não tratadas (ex.: banco fora do ar) viram mensagem na tela
set_exception_handler(static function (Throwable $e) use ($modoDesenvolvimento): void {
    $detalhe = get_class($e) . ': ' . $e->getMessage()
        . "\nArquivo: " . $e->getFile() . ' (linha ' . $e->getLine() . ')';

    if ($e instanceof PDOException) {
        $detalhe .= "\n\nVerifique se o MySQL está rodando e se DB_HOST, DB_PORT, DB_DATABASE, "
            . 'DB_USERNAME e DB_PASSWORD estão corretos.';
    }

    mostrarErroFatal($detalhe, $modoDesenvolvimento);
});

// Erros fatais (função repetida, arquivo com erro de sintaxe...) também
register_shutdown_function(static function () use ($modoDesenvolvimento): void {
    $erro = error_get_last();

    if ($erro && in_array($erro['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        // Com display_errors ligado o PHP já mostrou o erro
        if (!$modoDesenvolvimento) {
            mostrarErroFatal($erro['message'] . ' em ' . $erro['file'] . ':' . $erro['line'], false);
        }
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Confere se todos os arquivos foram copiados para o lugar certo
$arquivosNecessarios = [
    'banco/database.php',
    'banco/crud.php',
    'banco/consultas.php',
    'sessao.php',
];

foreach ($arquivosNecessarios as $arquivo) {
    if (!is_file(__DIR__ . '/' . $arquivo)) {
        mostrarErroFatal(
            "Arquivo não encontrado: app/{$arquivo}\n\n"
            . 'Copie esse arquivo do ZIP para a pasta indicada'
            . ' (e, no Docker, rode "docker compose up -d --build").',
            true
        );
        exit;
    }

    require_once __DIR__ . '/' . $arquivo;
}

$pdo = database();
