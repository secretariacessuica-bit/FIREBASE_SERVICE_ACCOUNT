<?php
// LIMA Solutions ERP - Client Portal User Guide (Guia do Utilizador)
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// Fetch client details
$stmtClient = $pdo->prepare("SELECT name FROM clients WHERE id = :id AND company_id = :company_id LIMIT 1");
$stmtClient->execute(['id' => $clientId, 'company_id' => $companyId]);
$client = $stmtClient->fetch();

// Unread messages for the sidebar badge
$stmtMsg = $pdo->prepare("SELECT COUNT(*) FROM client_messages WHERE client_id = :client_id AND company_id = :company_id AND sender_type = 'staff' AND read_at IS NULL");
$stmtMsg->execute(['client_id' => $clientId, 'company_id' => $companyId]);
$unreadMessages = (int)$stmtMsg->fetchColumn();

// Company Info
$stmtComp = $pdo->prepare("SELECT name, main_color FROM companies WHERE id = :id LIMIT 1");
$stmtComp->execute(['id' => $companyId]);
$company = $stmtComp->fetch();
$companyName = $company['name'] ?? 'LIMA Solutions';
$mainColor = $company['main_color'] ?? '#007a87';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guia do Utilizador - <?php echo htmlspecialchars($companyName); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: <?php echo $mainColor; ?>;
            --primary-light: #e6f2f3;
            --bg-light: #f8fafc;
            --white: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --border-gray: #e2e8f0;
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar navigation */
        .sidebar {
            width: 260px;
            background-color: #0f172a;
            color: var(--white);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: var(--transition);
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar-brand i {
            font-size: 24px;
            color: var(--primary);
        }

        .sidebar-brand h2 {
            font-size: 18px;
            font-weight: 700;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 10px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
        }

        .sidebar-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .sidebar-item a:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.05);
        }

        .sidebar-item.active a {
            color: var(--white);
            background-color: var(--primary);
        }

        .sidebar-item .badge {
            margin-left: auto;
            background-color: #ef4444;
            color: var(--white);
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 20px;
            font-weight: 700;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
        }

        /* Main Content area */
        .main-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .navbar {
            background-color: var(--white);
            border-bottom: 1px solid var(--border-gray);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background-color: var(--primary-light);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 14px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-info span {
            font-size: 14px;
            font-weight: 600;
        }

        .user-info small {
            font-size: 11px;
            color: var(--text-light);
        }

        /* Content spacing */
        .content-container {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        .panel-card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        /* Guide Layout */
        .guide-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .guide-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .guide-header p {
            color: var(--text-light);
            font-size: 15px;
        }

        .guide-section {
            margin-bottom: 40px;
            border-bottom: 1px solid var(--border-gray);
            padding-bottom: 30px;
        }

        .guide-section:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-dark);
        }

        .section-title i {
            color: var(--primary);
        }

        .guide-content {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
        }

        .guide-content p {
            margin-bottom: 12px;
        }

        .guide-content ul {
            list-style: none;
            margin-bottom: 16px;
            padding-left: 4px;
        }

        .guide-content li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 8px;
        }

        .guide-content li::before {
            content: "•";
            color: var(--primary);
            font-weight: bold;
            font-size: 18px;
            position: absolute;
            left: 4px;
            top: -2px;
        }

        .badge-info {
            background-color: var(--primary-light);
            color: var(--primary);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }

        /* Accordion for FAQs */
        .faq-item {
            border: 1px solid var(--border-gray);
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .faq-question {
            background-color: var(--bg-light);
            padding: 14px 20px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
            font-size: 14px;
        }

        .faq-answer {
            padding: 16px 20px;
            border-top: 1px solid var(--border-gray);
            font-size: 14px;
            color: #475569;
            line-height: 1.5;
            display: none;
        }

        .faq-item.open .faq-answer {
            display: block;
        }

        .faq-item.open .faq-question i {
            transform: rotate(180deg);
        }

        .faq-question i {
            transition: transform 0.2s ease;
            font-size: 12px;
            color: var(--text-light);
        }

        .guide-footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: var(--text-light);
            border-top: 1px solid var(--border-gray);
            padding-top: 20px;
        }
    </style>
</head>
<body>

    <!-- Left Sidebar Menu -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-cube"></i>
            <h2><?php echo htmlspecialchars($companyName); ?></h2>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Tableau de bord / Início</a>
            </li>
            <li class="sidebar-item">
                <a href="quotes.php"><i class="fa-solid fa-file-signature"></i> Mes Devis / Devis</a>
            </li>
            <li class="sidebar-item">
                <a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Mes Factures / Factures</a>
            </li>
            <li class="sidebar-item">
                <a href="messages.php">
                    <i class="fa-solid fa-envelope"></i> Messages / Chat
                    <?php if ($unreadMessages > 0): ?>
                        <span class="badge"><?php echo $unreadMessages; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="sidebar-item active">
                <a href="guide.php"><i class="fa-solid fa-circle-question"></i> Guide d'utilisation / Guia</a>
            </li>
            <li class="sidebar-item">
                <a href="marketplace.php"><i class="fa-solid fa-store"></i> Marketplace</a>
            </li>
            <li class="sidebar-item" style="margin-top: auto;">
                <a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion / Sair</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <!-- Right Content Area -->
    <div class="main-wrapper">
        <header class="navbar">
            <h1>Guia do Utilizador</h1>
            <div class="user-menu">
                <div class="user-avatar">
                    <?php 
                        $words = explode(' ', $client['name']);
                        $initials = '';
                        foreach ($words as $w) {
                            $initials .= strtoupper(substr($w, 0, 1));
                        }
                        echo htmlspecialchars(substr($initials, 0, 2));
                    ?>
                </div>
                <div class="user-info">
                    <span><?php echo htmlspecialchars($client['name']); ?></span>
                    <small>Portail Client</small>
                </div>
            </div>
        </header>

        <main class="content-container">
            <div class="panel-card">
                
                <div class="guide-header">
                    <h2>Portal do Cliente</h2>
                    <p>Guia completo para acompanhamento dos seus serviços de forma simples e transparente.</p>
                </div>

                <!-- Bem-vindo -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-handshake"></i> Bem-vindo</h3>
                    <div class="guide-content">
                        <p>O Portal do Cliente foi criado para permitir que acompanhe os seus serviços de forma simples e transparente.</p>
                        <p>Através do portal poderá:</p>
                        <ul>
                            <li>Consultar os seus projetos</li>
                            <li>Acompanhar o estado dos serviços</li>
                            <li>Visualizar orçamentos</li>
                            <li>Consultar faturas</li>
                            <li>Aceder a documentos</li>
                            <li>Comunicar diretamente com a equipa</li>
                            <li>Acompanhar pagamentos</li>
                        </ul>
                    </div>
                </div>

                <!-- Como Aceder -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-right-to-bracket"></i> Como Aceder</h3>
                    <div class="guide-content">
                        <p>Aceda ao endereço fornecido pela empresa.</p>
                        <p>Introduza:</p>
                        <ul>
                            <li><strong>E-mail</strong> cadastrado</li>
                            <li><strong>Palavra-passe</strong> fornecida</li>
                        </ul>
                        <p>Depois selecione <strong>Entrar</strong>.</p>
                    </div>
                </div>

                <!-- Página Inicial -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-gauge"></i> Página Inicial</h3>
                    <div class="guide-content">
                        <p>Após o login verá o Dashboard do Cliente.</p>
                        <p>Aqui encontra:</p>
                        <ul>
                            <li>Projetos ativos</li>
                            <li>Estado dos serviços</li>
                            <li>Saldo pendente</li>
                            <li>Documentos recentes</li>
                            <li>Últimas mensagens</li>
                        </ul>
                    </div>
                </div>

                <!-- Os Meus Projetos -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-list-check"></i> Os Meus Projetos</h3>
                    <div class="guide-content">
                        <p>Nesta área pode consultar:</p>
                        <ul>
                            <li>Serviços contratados</li>
                            <li>Datas previstas</li>
                            <li>Estado atual</li>
                            <li>Informações importantes</li>
                        </ul>
                        <p>Cada projeto possui o seu histórico próprio.</p>
                    </div>
                </div>

                <!-- Orçamentos -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-file-signature"></i> Orçamentos</h3>
                    <div class="guide-content">
                        <p>Aqui encontra todos os orçamentos emitidos.</p>
                        <p>Para cada orçamento pode:</p>
                        <ul>
                            <li>Consultar detalhes</li>
                            <li>Transferir PDF</li>
                            <li>Ver estado</li>
                            <li>Aceitar orçamento quando disponível</li>
                        </ul>
                        <p>Estados possíveis: 
                            <span class="badge-info">Rascunho</span>
                            <span class="badge-info">Enviado</span>
                            <span class="badge-info">Aceite</span>
                            <span class="badge-info">Recusado</span>
                        </p>
                    </div>
                </div>

                <!-- Faturas -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-file-invoice-dollar"></i> Faturas</h3>
                    <div class="guide-content">
                        <p>Nesta secção pode visualizar:</p>
                        <ul>
                            <li>Faturas emitidas</li>
                            <li>Valor total</li>
                            <li>Estado do pagamento</li>
                            <li>PDF para download</li>
                        </ul>
                        <p>Estados possíveis: 
                            <span class="badge-info">Em aberto</span>
                            <span class="badge-info">Parcialmente paga</span>
                            <span class="badge-info">Paga</span>
                        </p>
                    </div>
                </div>

                <!-- Pagamentos -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-credit-card"></i> Pagamentos</h3>
                    <div class="guide-content">
                        <p>Permite consultar:</p>
                        <ul>
                            <li>Pagamentos efetuados</li>
                            <li>Valores recebidos</li>
                            <li>Datas de pagamento</li>
                        </ul>
                        <p>Serve como histórico financeiro dos seus serviços.</p>
                    </div>
                </div>

                <!-- Mensagens -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-envelope"></i> Mensagens</h3>
                    <div class="guide-content">
                        <p>A área de mensagens permite comunicar diretamente com a equipa.</p>
                        <p>Pode:</p>
                        <ul>
                            <li>Enviar mensagens</li>
                            <li>Receber respostas</li>
                            <li>Consultar histórico</li>
                        </ul>
                        <p>Todas as conversas ficam registadas.</p>
                    </div>
                </div>

                <!-- Documentos -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-folder-open"></i> Documentos</h3>
                    <div class="guide-content">
                        <p>Centraliza todos os documentos relacionados com os seus serviços.</p>
                        <p>Exemplos:</p>
                        <ul>
                            <li>Orçamentos</li>
                            <li>Faturas</li>
                            <li>Recibos</li>
                            <li>Documentação complementar</li>
                        </ul>
                    </div>
                </div>

                <!-- Segurança -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-shield-halved"></i> Segurança</h3>
                    <div class="guide-content">
                        <p>O acesso é pessoal. Nunca partilhe a sua palavra-passe ou dados de acesso.</p>
                        <p>Por motivos de segurança:</p>
                        <ul>
                            <li>Cada cliente vê apenas os seus próprios dados.</li>
                            <li>Não é possível aceder a informações de outros clientes.</li>
                            <li>Todas as ações ficam protegidas por mecanismos de segurança.</li>
                        </ul>
                    </div>
                </div>

                <!-- Perguntas Frequentes -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-circle-question"></i> Perguntas Frequentes (FAQ)</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question">Não encontro um orçamento <i class="fa-solid fa-chevron-down"></i></div>
                        <div class="faq-answer">Verifique a secção "Orçamentos". Se continuar sem aparecer, contacte a equipa.</div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">Não encontro uma fatura <i class="fa-solid fa-chevron-down"></i></div>
                        <div class="faq-answer">Verifique a secção "Faturas". Se for recente, pode levar alguns minutos para ser processada pela administração.</div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">Esqueci-me da palavra-passe <i class="fa-solid fa-chevron-down"></i></div>
                        <div class="faq-answer">Contacte a empresa para solicitar a redefinição ou uma senha temporária.</div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">Posso ver os documentos antigos? <i class="fa-solid fa-chevron-down"></i></div>
                        <div class="faq-answer">Sim. Todos os documentos associados à sua conta permanecem disponíveis no histórico do Portal.</div>
                    </div>
                </div>

                <!-- Precisa de ajuda? -->
                <div class="guide-section">
                    <h3 class="section-title"><i class="fa-solid fa-circle-info"></i> Precisa de ajuda?</h3>
                    <div class="guide-content">
                        <p>Utilize a área de <strong>Mensagens</strong> dentro do Portal. A equipa responderá diretamente através do sistema.</p>
                    </div>
                </div>

                <div class="guide-footer">
                    <p>Portal do Cliente - LIMA Solutions ERP</p>
                    <p style="margin-top: 4px; opacity: 0.7;">Versão 1.0</p>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Simple FAQ toggle script
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const item = button.parentElement;
                item.classList.toggle('open');
            });
        });
    </script>
</body>
</html>
