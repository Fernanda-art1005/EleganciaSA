<?php /** @var array $kpis */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGF — Dashboard Gerencial</title>
    <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
    <header class="topbar">
        <h1>SGF — Sistema de Gestão de Ferramentaria</h1>
    </header>

    <main class="dashboard-grid">
        <div class="card card-verde">
            <span class="card-label">Taxa de Disponibilidade</span>
            <span class="card-disponibilidade card-valor"><?= htmlspecialchars($kpis['taxa_disponibilidade']) ?>%</span>
            <span class="card-meta">Meta: &gt; 92,0%</span>
        </div>

        <div class="card card-azul">
            <span class="card-label">Ativos Alocados</span>
            <span class="card-alocados card-valor"><?= htmlspecialchars($kpis['ativos_alocados']) ?></span>
            <span class="card-meta">Em operação agora</span>
        </div>

        <div class="card <?= $kpis['ativos_atrasados'] > 0 ? 'card-vermelho' : 'card-cinza' ?>">
            <span class="card-label">Atrasos Críticos</span>
            <span class="card-atrasos card-valor"><?= htmlspecialchars($kpis['ativos_atrasados']) ?></span>
            <span class="card-meta">Meta: 0 (Zero)</span>
        </div>
    </main>

    <script src="/js/dashboard.js"></script>
</body>
</html>
