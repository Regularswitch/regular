#!/usr/bin/env bash
# Gera fix-active-plugins.php para upload temporário no servidor (staging/produção).
# Uso: ./scripts/fix-wp-active-plugins.sh > /tmp/fix-active-plugins.php
# Upload via FTP para a raiz do WordPress, acesse no browser UMA vez, apague o arquivo.

cat <<'PHP'
<?php
/**
 * Corrige active_plugins após migração traducao/ + api-etc/ → regular-cms/
 * APAGUE ESTE ARQUIVO IMEDIATAMENTE APÓS USAR.
 */

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

$secret = $_GET['key'] ?? '';
if ($secret !== 'rs-fix-' . md5(__FILE__)) {
    http_response_code(403);
    echo "Forbidden. Use: ?key=rs-fix-" . md5(__FILE__) . "\n";
    exit;
}

$wp_config = __DIR__ . '/wp-config.php';
if (!is_readable($wp_config)) {
    echo "wp-config.php não encontrado nesta pasta.\n";
    exit(1);
}

require $wp_config;

$db_host = DB_HOST;
$socket = null;
if (str_contains($db_host, ':')) {
    [$db_host, $socket] = explode(':', $db_host, 2);
}

$mysqli = $socket
    ? new mysqli($db_host, DB_USER, DB_PASSWORD, DB_NAME, 0, $socket)
    : new mysqli($db_host, DB_USER, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    echo 'Erro DB: ' . $mysqli->connect_error . "\n";
    exit(1);
}

$table = ($GLOBALS['table_prefix'] ?? 'wp_') . 'options';
$self = 'regular-cms/regular-cms.php';
$legacy = ['traducao/traducao.php', 'api-etc/api-etc.php'];

$res = $mysqli->query("SELECT option_value FROM `{$table}` WHERE option_name = 'active_plugins' LIMIT 1");
$row = $res ? $res->fetch_row() : null;

if (!$row) {
    echo "active_plugins não encontrado.\n";
    exit(1);
}

$plugins = @unserialize($row[0], ['allowed_classes' => false]);
if (!is_array($plugins)) {
    echo "active_plugins inválido.\n";
    exit(1);
}

$before = $plugins;
$plugins = array_values(array_filter(
    $plugins,
    static fn($p) => !in_array($p, $legacy, true)
));

if (!in_array($self, $plugins, true)) {
    array_unshift($plugins, $self);
}

if ($plugins === $before) {
    echo "Nada a alterar. regular-cms já está ativo.\n";
    exit(0);
}

$serialized = serialize($plugins);
$stmt = $mysqli->prepare("UPDATE `{$table}` SET option_value = ? WHERE option_name = 'active_plugins'");
$stmt->bind_param('s', $serialized);
$stmt->execute();

echo "OK — active_plugins atualizado:\n";
foreach ($plugins as $p) {
    echo "  - {$p}\n";
}
echo "\nApague este arquivo do servidor agora.\n";
PHP