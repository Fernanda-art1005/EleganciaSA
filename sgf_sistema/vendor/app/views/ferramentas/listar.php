<?php /** @var array $ferramentas */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>SGF — Catálogo de Ferramentas</title>
    <link rel="stylesheet" href="/css/estilo.css">
</head>
<body>
    <header class="topbar">
        <h1>Catálogo de Ferramentas</h1>
        <a href="/" class="btn-voltar">← Dashboard</a>
    </header>

    <main>
        <form method="GET" action="/ferramentas" class="filtros">
            <input type="text" name="termo" placeholder="Buscar por nome…" value="<?= htmlspecialchars($_GET['termo'] ?? '') ?>">
            <select name="status">
                <option value="">Todos os status</option>
                <option value="DISPONIVEL" <?= ($_GET['status'] ?? '') === 'DISPONIVEL' ? 'selected' : '' ?>>Disponível</option>
                <option value="EMPRESTADA" <?= ($_GET['status'] ?? '') === 'EMPRESTADA' ? 'selected' : '' ?>>Emprestada</option>
                <option value="MANUTENCAO" <?= ($_GET['status'] ?? '') === 'MANUTENCAO' ? 'selected' : '' ?>>Manutenção</option>
            </select>
            <button type="submit" class="btn-primario">Filtrar</button>
        </form>

        <table class="tabela-ferramentas">
            <thead>
                <tr>
                    <th>Tag / Código</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Localização</th>
                    <th>Status</th>
                    <th>Ciclos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ferramentas as $f): ?>
                <tr>
                    <td><?= htmlspecialchars($f['codigo_tag']) ?></td>
                    <td><?= htmlspecialchars($f['nome']) ?></td>
                    <td><?= htmlspecialchars($f['categoria']) ?></td>
                    <td><?= htmlspecialchars($f['localizacao']) ?></td>
                    <td><span class="badge badge-<?= strtolower($f['status_atual']) ?>"><?= htmlspecialchars($f['status_atual']) ?></span></td>
                    <td><?= htmlspecialchars($f['ciclos_atuais']) ?> / <?= htmlspecialchars($f['vida_util_ciclos']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
