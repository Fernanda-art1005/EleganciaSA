# SGF — Sistema de Gestão de Ferramentaria
**Cliente:** NexaForge Industrial Solutions  
**Desenvolvido por:** AuraTech Development  
**Versão:** 6.7 | **Data:** 08/06/2026

---

## Estrutura do Projeto

```
sgf_sistema/
├── app/
│   ├── config/
│   │   └── Database.php          # Provedor de conexão PDO (Singleton)
│   ├── controllers/
│   │   ├── DashboardController.php
│   │   └── EmprestimoController.php
│   ├── models/
│   │   ├── Ferramenta.php
│   │   └── Emprestimo.php
│   └── views/
│       ├── dashboard/
│       │   └── index.php
│       └── ferramentas/
│           └── listar.php
├── public/                       # ÚNICO diretório exposto ao servidor web
│   ├── css/
│   │   └── estilo.css
│   ├── js/
│   │   └── dashboard.js
│   ├── .htaccess                 # Reescrita de URL amigável (Apache)
│   └── index.php                 # Front Controller — ponto único de entrada
├── vendor/
│   └── autoload.php              # Autoloader PSR-4 nativo
├── DOCS/
│   ├── schema.sql                # Script de criação do banco MySQL
│   └── RELATORIO_TECNICO_v6.7.pdf
├── app_elegancia.py              # Servidor local Python (Elegância Premium)
└── README.md
```

## Como Iniciar

### Backend PHP (SGF)
1. Configure um servidor Apache/Nginx apontando a raiz para `public/`
2. Execute o script `DOCS/schema.sql` no MySQL para criar o banco
3. Ajuste as credenciais em `app/config/Database.php`
4. Acesse `http://seu-servidor/`

### Servidor Python (Elegância Premium)
```bash
python app_elegancia.py
# Acesse: http://localhost:8000
```

## Tecnologias
- **Backend:** PHP 8.x (OOP, PDO, PSR-4, MVC, SOLID)
- **Frontend:** HTML5, CSS3, JavaScript ES6
- **Banco de Dados:** MySQL 8.x (ACID, índices compostos)
- **Servidor local alternativo:** Python 3 (app_elegancia.py)
