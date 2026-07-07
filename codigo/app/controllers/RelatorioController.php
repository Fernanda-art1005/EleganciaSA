<?php
namespace App\Controllers;

use App\Models\Auditoria;

class RelatorioController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->base_path . '/login');
            exit();
        }

        if (!$_SESSION['perms']['relatorios']) {
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado aos Relatórios / Auditoria.'));
            exit();
        }
    }

    public function index(): void {
        $logs = Auditoria::getAll();
        $this->render('relatorio/index', [
            'logs' => $logs
        ]);
    }

    // Exportação de dados de relatórios para CSV (conforme critério de aceitação, página 21)
    public function export(): void {
        $logs = Auditoria::getAll();

        // Configura cabeçalhos HTTP para download do arquivo CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=auditoria_elegancia_premium_' . date('Ymd_His') . '.csv');

        // Abre fluxo de saída em PHP
        $output = fopen('php://output', 'w');

        // Adiciona UTF-8 BOM para garantir exibição correta de caracteres acentuados no Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Escreve cabeçalhos das colunas (RF-RE-002)
        fputcsv($output, [
            'Data/Hora',
            'Tipo de Ação',
            'Descrição',
            'Usuário Operador',
            'Valor Monetário',
            'Status'
        ], ';');

        // Escreve cada registro no arquivo
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['data_hora'],
                $log['tipo_acao'],
                $log['descricao'],
                $log['usuario'],
                $log['valor'],
                $log['status']
            ], ';');
        }

        fclose($output);
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . '/views/layout/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layout/footer.php';
    }
}
