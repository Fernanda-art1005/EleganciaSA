<?php
/**
 * Autoloader simples para o padrão PSR-4 conforme exigido pelo relatório.
 */
spl_autoload_register(function ($class) {
    // Prefixo do namespace do projeto
    $prefix = 'App\\';

    // Diretório base para o prefixo do namespace
    $base_dir = __DIR__ . '/../app/';

    // A classe usa o prefixo?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Pega o nome relativo da classe
    $relative_class = substr($class, $len);

    // Substitui o prefixo do namespace pelo diretório base, substitui separadores de namespace
    // por separadores de diretório no nome relativo da classe, e adiciona .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Se o arquivo existir, carrega-o
    if (file_exists($file)) {
        require $file;
    }
});
