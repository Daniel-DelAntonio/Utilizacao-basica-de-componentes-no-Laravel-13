# Guia de Implementação e Testes: Usuários e Exportações

Este guia orienta como implementar e testar o gerenciamento de usuários e a exportação modular de dados em qualquer projeto Laravel.

---

## 🏗️ Estrutura do Projeto

Abaixo estão os arquivos envolvidos na implementação da funcionalidade:

```text
├── config/
│   └── exports.php                         # Configurações e presets de exportação
├── database/
│   └── migrations/
│       └── 0001_0101_000000_create_users_table.php  # Migration com novos campos de usuário
├── app/
│   ├── Models/
│   │   └── User.php                        # Model de Usuário com $fillable atualizado
│   └── Http/
│       └── Controllers/
│           ├── UserController.php          # Controller do CRUD de usuários
│           └── ExportController.php        # Controller das exportações (CSV, PDF, XLSX)
├── resources/
│   └── views/
│       ├── usuario/
│       │   ├── index.blade.php             # Listagem paginada e ações
│       │   └── formUsuario.blade.php       # Formulário unificado (Criar e Editar)
│       └── exports/
│           └── generic.blade.php           # Template HTML para exportação em PDF
└── routes/
    └── web.php                             # Rotas web da aplicação
```

---

## 🛠️ Passo a Passo da Implementação

### 1. Migrações e Banco de Dados
- **Arquivo:** `database/migrations/0001_0101_000000_create_users_table.php`
- Adicione os campos `login`, `function_name` e `system_unit_id` à tabela `users`.
- Execute o comando no terminal:
  ```bash
  php artisan migrate
  ```

---

### 2. Configuração do Model
- **Arquivo:** `app/Models/User.php`
- Atualize a propriedade `$fillable` para incluir os novos campos para atribuição em massa.

---

### 3. Rotas da Aplicação
- **Arquivo:** `routes/web.php`
- Cadastre as rotas do recurso de usuários (`UserController`) e as rotas de exportação (`ExportController` para PDF, CSV e XLSX).

---

### 4. Controladores (Controllers)
- **`app/Http/Controllers/UserController.php`**
  - Implementa a listagem com filtros e paginação, além dos métodos de criação, edição, atualização e exclusão (`index`, `create`, `store`, `edit`, `update`, `destroy`).
- **`app/Http/Controllers/ExportController.php`**
  - Gerencia o processamento dos filtros, validações dos parâmetros, busca paginada/chunked e geração dos formatos `pdf`, `csv` e `xlsx`.

---

### 5. Interfaces (Views Blade)
- **`resources/views/usuario/index.blade.php`**
  - Exibe a tabela paginada de usuários, botões de ação e o menu dropdown de exportação.
- **`resources/views/usuario/formUsuario.blade.php`**
  - Formulário reaproveitável para cadastro e edição de usuários.
- **`resources/views/exports/generic.blade.php`**
  - Layout limpo e estilizado em HTML/CSS para renderização do PDF via DomPDF.

---

## 📦 Dependências Necessárias

Para habilitar a exportação nos formatos PDF e Excel (XLSX), instale os seguintes pacotes Composer:

```bash
composer require barryvdh/laravel-dompdf phpoffice/phpspreadsheet
```

---

## 🧪 Como Testar no Seu Projeto

1. **Instale as dependências**: Execute o comando do Composer listado acima.
2. **Atualize o banco de dados**: Execute `php artisan migrate`.
3. **Crie dados para teste**:
   - Utilize o formulário em `/users/create` ou crie registros via `php artisan tinker`.
4. **Valide as funcionalidades**:
   - **Listagem e CRUD:** Acesse `/users` e teste a busca, edição, exclusão e paginação.
   - **Exportação CSV:** Acesse `/export/csv?model=usuarios` para iniciar o download do arquivo CSV.
   - **Exportação PDF:** Acesse `/export/pdf?model=usuarios` para visualizar a impressão em PDF A4 (paisagem).
   - **Exportação XLSX:** Acesse `/export/xlsx?model=usuarios` para baixar a planilha formatada no Excel.