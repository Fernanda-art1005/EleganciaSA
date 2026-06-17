# Sistema de Gestão de Ferramentaria (SGF)

Este projeto foi organizado e configurado de acordo com as especificações do **Relatório Técnico da AuraTech Development**.

## Estrutura do Projeto
- `app/`: Contém o núcleo da aplicação (MVC).
  - `config/`: Configurações de banco de dados.
  - `controllers/`: Lógica de controle.
  - `models/`: Regras de negócio e acesso a dados.
  - `views/`: Interfaces visuais.
- `public/`: Único diretório que deve ser exposto pelo servidor web.
- `vendor/`: Contém o autoloader PSR-4.
- `DOCS/`: Documentação técnica e scripts SQL.

## Como rodar no VS Code

1. **Pré-requisitos**:
   - Ter o PHP instalado (versão 8.x recomendada).
   - Ter o MySQL instalado.
   - Extensão recomendada no VS Code: **PHP Intelephense**.

2. **Configuração do Banco de Dados**:
   - Execute o script em `DOCS/setup_banco.sql` no seu servidor MySQL.
   - Ajuste as credenciais em `app/config/Database.php` se necessário.

3. **Iniciando o Servidor Local**:
   - Abra o terminal no VS Code.
   - Navegue até a pasta `public/`: `cd public`
   - Inicie o servidor embutido do PHP: `php -S localhost:8000`
   - Acesse no navegador: `http://localhost:8000`

## Observações Técnicas
- O sistema utiliza o padrão **PSR-4** para carregamento automático de classes.
- O diretório `public/` contém o **Front Controller** (`index.php`), que centraliza todas as requisições.
- O código original enviado no ZIP (React/Vite) foi preservado na pasta `original_files/` para referência, mas a estrutura principal foi reconstruída em PHP conforme o relatório técnico exigiu.
