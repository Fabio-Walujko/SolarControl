<?php

/*
 * Funções de sessão e permissão.
 * O ID do usuário fica salvo na sessão no login e é usado em todas as
 * consultas para que cada pessoa veja apenas os próprios dados.
 */

/** Escapa texto antes de mostrar no HTML. */
if (!function_exists('h')) {
    function h($texto): string
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    }
}

/** Nome exibido para cada valor da coluna usuarios.TIPO */
const TIPOS_USUARIO = [
    'cliente' => 'Cliente',
    'funcionario' => 'Funcionário',
    'adm' => 'Administrador',
];

/** Salva o usuário na sessão logo após o login. */
function iniciarSessaoUsuario(array $usuario): void
{
    // Gera um novo ID de sessão para evitar sequestro de sessão
    session_regenerate_id(true);

    $_SESSION['usuario_logado'] = true;
    $_SESSION['ID_USUARIO'] = (int) $usuario['ID_USUARIO'];
    $_SESSION['nome'] = (string) $usuario['nome'];
    $_SESSION['email'] = (string) $usuario['email'];
    $_SESSION['TIPO'] = (string) ($usuario['TIPO'] ?: 'cliente');
}

/** Apaga os dados da sessão (usado no "Sair" e quando a conta não existe mais). */
function encerrarSessaoUsuario(): void
{
    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Devolve o usuário logado, buscando os dados atualizados no banco.
 * Retorna null se ninguém estiver logado.
 */
function buscarUsuarioDaSessao(PDO $pdo): ?array
{
    if (($_SESSION['usuario_logado'] ?? false) !== true) {
        return null;
    }

    $usuario = false;
    $id = (int) ($_SESSION['ID_USUARIO'] ?? 0);

    if ($id > 0) {
        $usuario = buscarUsuario($pdo, $id);
    } elseif (!empty($_SESSION['email'])) {
        // Sessões abertas antes desta versão só tinham o e-mail
        $usuario = buscarUsuarioPorEmail($pdo, (string) $_SESSION['email']);
    }

    if (!$usuario) {
        return null;
    }

    // Mantém a sessão igual ao banco (ex.: nome alterado nas configurações)
    $_SESSION['ID_USUARIO'] = (int) $usuario['ID_USUARIO'];
    $_SESSION['nome'] = (string) $usuario['nome'];
    $_SESSION['email'] = (string) $usuario['email'];
    $_SESSION['TIPO'] = (string) ($usuario['TIPO'] ?: 'cliente');

    return $usuario;
}

/** Usa no topo das páginas protegidas: manda para o login se não houver usuário. */
function exigirLogin(PDO $pdo): array
{
    $usuario = buscarUsuarioDaSessao($pdo);

    if ($usuario === null) {
        encerrarSessaoUsuario();
        header('Location: login.php');
        exit;
    }

    // Não reutilizar páginas privadas em caches compartilhados ou de outra conta.
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');

    return $usuario;
}

/** Identifica a equipe para recebimento e resposta de alertas manuais. */
function ehEquipe(array $usuario): bool
{
    return in_array($usuario['TIPO'] ?? 'cliente', ['funcionario', 'adm'], true);
}

/** Sistemas e indicadores são privados da conta, independentemente do cargo. */
function escopoUsuario(array $usuario): int
{
    $id = (int) ($usuario['ID_USUARIO'] ?? 0);

    if ($id <= 0) {
        throw new InvalidArgumentException('Usuário inválido para consultar sistemas.');
    }

    return $id;
}

function nomeTipoUsuario(?string $tipo): string
{
    return TIPOS_USUARIO[$tipo ?: 'cliente'] ?? 'Cliente';
}

/** Primeira letra do nome (funciona com acentos, sem precisar de mbstring). */
function inicialNome(string $nome): string
{
    return preg_match('/\S/u', $nome, $letra) ? strtoupper($letra[0]) : '?';
}
