<p align="center">
  <img src="public/images/logo/corujinha-horizontal-300dpi.png" width="380" alt="Logo Corujinha - Prepara Avalia+" />
</p>

<p align="center">
  <strong>Plataforma educacional para realização de avaliações, simulados escolares e acompanhamento de aprendizagem em tempo real.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.3+" />
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4" />
  <img src="https://img.shields.io/badge/License-AGPL_v3-blue?style=flat-square" alt="Licença GNU AGPLv3" />
</p>

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/book-open.svg" width="20" height="20" align="absmiddle" alt="" /> Sobre a Aplicação

O **Corujinha** é uma aplicação web desenvolvida para apoiar professores e estudantes na aplicação de provas, simulados e atividades diagnósticas em sala de aula, com foco pedagógico e acompanhamento em tempo real.

A plataforma oferece:
- **Banco de Questões Estruturado:** suporte a questões de múltipla escolha e resposta direta, textos e imagens de apoio, fórmulas matemáticas (KaTeX), descritores/habilidades pedagógicas, áreas de ensino, anos/séries, temporizador por questão e destaque guiado de comandos e pistas no enunciado.
- **Modos de Aplicação:** modo **Corujinha** (com suporte à **Revisão Coletiva**, permitindo que o estudante revise e altere um número configurável de questões antes da entrega definitiva) e modo **Corujão** (aplicação direta sem revisão coletiva).
- **Tempo Real e Controle de Sala:** acompanhamento ao vivo das tentativas via WebSockets (Laravel Reverb), autorização de dispositivos, registro de saídas de aba e condução de devolutiva pedagógica.
- **Relatórios e Colaboração:** compartilhamento e clonagem de questões, pastas e avaliações entre professores, além de exportação e envio por e-mail de relatórios em PDF e planilhas.

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/layers.svg" width="20" height="20" align="absmiddle" alt="" /> Stack Utilizada

| Camada | Tecnologias |
| :--- | :--- |
| **Backend** | PHP `^8.3`, Laravel `^13.17`, Laravel Reverb `^1.0` |
| **Frontend** | Blade Templates, Tailwind CSS `^4.0`, Vite `^8.0`, Laravel Echo `^2.6` |
| **Editor & Conteúdo** | TipTap `^3.33` (editor rico), KaTeX `^0.19` (fórmulas matemáticas), DOMPurify `^3.4` |
| **Ícones** | Lucide Icons (`mallardduck/blade-lucide-icons`) |
| **Banco de Dados** | MariaDB / MySQL (com suporte a criptografia TDE em tabelas sensíveis) |
| **Relatórios & Mídia** | `barryvdh/laravel-dompdf`, `phpoffice/phpspreadsheet`, `bacon/bacon-qr-code` |
| **Segurança & Auth** | WebAuthn / Passkeys (`webauthn-polyfills`), TOTP (2FA), políticas NIST SP 800-63B-4 |
| **Testes & Qualidade** | Pest `^5.3`, Laravel Pint `^1.27`, Laravel Pail `^1.2` |

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/check-circle-2.svg" width="20" height="20" align="absmiddle" alt="" /> Requisitos

Antes de iniciar, certifique-se de ter instalado em sua máquina:

- **PHP** `>= 8.3` (com extensões `bcmath`, `ctype`, `fileinfo`, `gd`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`)
- **Composer**
- **Node.js** (`>= 20`) e **npm**
- **MariaDB** ou **MySQL** (recomendado; SQLite pode ser utilizado para testes automatizados)

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/users.svg" width="20" height="20" align="absmiddle" alt="" /> Casos de Uso

### <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/shield-check.svg" width="16" height="16" align="absmiddle" alt="" /> Super-Administrador
- Cadastrar e gerenciar professores por meio de convites enviados por e-mail (reenvio e cancelamento de convites pendentes).
- Prestar suporte de segurança a contas de professores (como redefinição/desabilitação de TOTP).

### <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/graduation-cap.svg" width="16" height="16" align="absmiddle" alt="" /> Professor
- **Banco de Perguntas e Pastas:** criar, editar, organizar em pastas, compartilhar e clonar questões com outros professores.
- **Avaliações:** montar provas manualmente ou via seleção automática de questões, consultar estatísticas de desempenho e gerar relatórios (PDF/planilha) para download ou envio por e-mail.
- **Salas de Aula:** abrir salas com código de acesso, definir janela de horário, ativar/desativar a sala, escolher o modo de jogo (Corujinha ou Corujão) e regras de Revisão Coletiva, autorizar dispositivos dos estudantes, acompanhar tentativas em tempo real, corrigir respostas diretas e conduzir a devolutiva.

### <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/door-open.svg" width="16" height="16" align="absmiddle" alt="" /> Estudante
- Acessar a sala informando apenas o código fornecido pelo professor (sem necessidade de cadastro prévio) ou retomar uma tentativa em andamento com seu código de aluno.
- Aguardar autorização do dispositivo em tempo real quando habilitada pelo professor.
- Responder às questões (com suporte a marcação de comandos/pistas e visualização de fórmulas), participar da Revisão Coletiva (quando ativa) e conferir seu resultado.

### <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/lock.svg" width="16" height="16" align="absmiddle" alt="" /> Segurança e Conta (Usuários Autenticados)
- Autenticação com senha, Passkeys (WebAuthn) e verificação em duas etapas (TOTP).
- Gerenciamento de perfil, foto de avatar e alteração de e-mail com confirmação dupla (*double-opt-in*).

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/terminal.svg" width="20" height="20" align="absmiddle" alt="" /> Como Configurar e Executar Localmente

### 1. Clonar o repositório
```bash
git clone https://github.com/henrique-1/Corujinha.git
cd Corujinha
```

### 2. Instalar dependências
```bash
composer install
npm install
```

### 3. Configurar variáveis de ambiente
Copie o arquivo de exemplo e gere a chave da aplicação:
```bash
cp .env.example .env
php artisan key:generate
```

Edite o arquivo `.env` com as credenciais do seu banco MariaDB/MySQL:
```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Corujinha
DB_USERNAME=root
DB_PASSWORD=
```

Configure também as credenciais do **Laravel Reverb** (WebSockets) no `.env` executando:
```bash
php artisan reverb:install
```
*(Ou preencha manualmente `REVERB_APP_ID`, `REVERB_APP_KEY` e `REVERB_APP_SECRET` no `.env`.)*

### 4. Preparar o banco de dados e assets
Crie o banco de dados `Corujinha` no seu servidor MariaDB/MySQL e execute as migrations, o link simbólico de armazenamento e o build inicial do frontend:
```bash
php artisan migrate
php artisan storage:link
npm run build
```

### 5. Criar o primeiro Super-Administrador
```bash
php artisan app:create-super-admin
```

### 6. Executar a aplicação em desenvolvimento
O script `dev` do Composer inicia simultaneamente o servidor web (`localhost:8000`), o worker de filas, o monitor de logs (`pail`), o servidor WebSocket (`reverb`) e o Vite:
```bash
composer run dev
```

Acesse a aplicação em: [http://localhost:8000](http://localhost:8000)

### 7. Executar os testes
```bash
composer run test
```

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/award.svg" width="20" height="20" align="absmiddle" alt="" /> Créditos

Créditos especiais à **professora Flavia Maria de Oliveira**, que idealizou o conceito original deste projeto.

---

## <img src="https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/scale.svg" width="20" height="20" align="absmiddle" alt="" /> Licença

Este projeto é um software livre distribuído sob os termos da licença **GNU Affero General Public License v3.0 (AGPLv3)**. Consulte o arquivo [LICENSE](LICENSE) para obter o texto integral da licença.
