<?php
/**
 * SGF — Autoloader PSR-4 nativo
 * Carrega automaticamente as classes do namespace App\ a partir de /app/
 */

spl_autoload_register(function (string $classe): void {
    // Converte namespace para caminho de arquivo
    // Ex: App\Controllers\EmprestimoController → /app/controllers/EmprestimoController.php
    $prefixo   = 'App\\';
    $base_dir  = dirname(__DIR__) . '/app/';

    if (strncmp($prefixo, $classe, strlen($prefixo)) !== 0) {
        return; // Classe não pertence a este namespace
    }

    $classe_relativa = substr($classe, strlen($prefixo));
    $arquivo = $base_dir . strtolower(str_replace('\\', '/', $classe_relativa)) . '.php';

    // Tenta o caminho com capitalização exata caso strtolower não encontre
    if (!file_exists($arquivo)) {
        $arquivo = $base_dir . str_replace('\\', '/', $classe_relativa) . '.php';
    }

    if (file_exists($arquivo)) {
        require $arquivo;
    }
});
