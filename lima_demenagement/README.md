# LIMA Solutions ERP

**Sistema de gestão empresarial (ERP) multi-empresa** desenvolvido em PHP 8 + MySQL.  
Versão: **RC 1.0** | Ambiente alvo: Infomaniak / cPanel PHP 8

---

## Funcionalidades Principais

| Módulo | Descrição |
|---|---|
| 🏢 **CRM** | Clientes, contactos e histórico de interações |
| 📋 **Orçamentos** | Criação, envio e conversão em faturas |
| 🧾 **Faturas** | Emissão, IVA, PDF, snapshots fiscais |
| 💳 **Pagamentos** | Registo, recibos e reversão controlada |
| 📁 **Projetos** | Gestão de projetos com Kanban de tarefas |
| ⏱️ **Timesheets** | Horas por projeto, aprovação e faturação |
| 📊 **Relatórios** | BI operacional e reconciliação financeira |
| ⚙️ **Configurações** | Multi-empresa, módulos, utilizadores e permissões |

---

## Requisitos Mínimos

| Componente | Versão mínima |
|---|---|
| PHP | **8.1+** |
| MySQL / MariaDB | **8.0+ / 10.6+** |
| Apache | 2.4+ (com `mod_rewrite` e `AllowOverride All`) |
| Extensões PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` |
| HTTPS | Obrigatório em produção |

---

## Instalação Rápida

```
1. Criar base de dados lima_solutions (MySQL, charset utf8mb4)
2. Fazer upload de public_site/ → public_html/
3. Colocar private/config.php FORA do public_html
4. Editar private/config.php com as credenciais da BD
5. Importar db/schema.sql via phpMyAdmin
6. Aceder a https://dominio.com/api/v1/seed.php (inicializa e auto-apaga)
7. Login: admin@limasolutions.ch / lima2026
8. Alterar senha imediatamente
```

> 📖 Guia completo: [docs/DEPLOY.md](docs/DEPLOY.md)

---

## Estrutura de Diretórios

```
lima_demenagement/
├── private/                   ← FORA do public_html (credenciais)
│   └── config.php
├── public_site/               ← Raiz pública (→ public_html/)
│   ├── admin/                 ← Painel administrativo
│   ├── api/v1/                ← API REST interna
│   ├── modules/               ← Módulos MVC
│   ├── helpers/               ← Utilitários (Finance, PDF)
│   └── db/                    ← Schema e migrações
└── docs/                      ← Documentação completa
    ├── DEPLOY.md
    ├── ARCHITECTURE.md
    ├── DATABASE.md
    ├── SECURITY.md
    ├── BACKUP.md
    └── E2E_TEST_REPORT.md
```

---

## Documentação

| Documento | Descrição |
|---|---|
| [VISION_2030.md](VISION_2030.md) | Visão estratégica de longo prazo para a transformação digital (2030) |
| [ECOSYSTEM_ROADMAP.md](ECOSYSTEM_ROADMAP.md) | Visão estratégica e planeamento de longo prazo (Fases 1 a 5) |
| [DEPLOY.md](docs/DEPLOY.md) | Guia de deploy Infomaniak / cPanel |
| [ARCHITECTURE.md](docs/ARCHITECTURE.md) | Arquitectura, MVC, fluxo de requests |
| [DATABASE.md](docs/DATABASE.md) | Referência das 24 tabelas e relações |
| [SECURITY.md](docs/SECURITY.md) | Sessão, CSRF, headers, imutabilidade |
| [BACKUP.md](docs/BACKUP.md) | Rotinas de backup e restauração |
| [E2E_TEST_REPORT.md](docs/E2E_TEST_REPORT.md) | 29 casos de teste do fluxo completo |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de versões |

---

## Segurança

- ✅ Credenciais fora do `public_html`
- ✅ CSRF em todas as operações mutativas
- ✅ Isolamento absoluto por `company_id`
- ✅ Headers HTTP: CSP, X-Frame-Options, HSTS, SameSite=Strict
- ✅ Timesheets aprovados imutáveis (`409 Conflict`)
- ✅ Snapshots financeiros congelados na aprovação
- ✅ Erros internos nunca expostos ao utilizador

---

## Licença

Proprietário – LIMA Solutions © 2026. Todos os direitos reservados.
