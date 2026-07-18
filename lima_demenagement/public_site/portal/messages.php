<?php
// LIMA Solutions ERP - Client Portal Messages View
require_once 'auth.php';

$clientId = $_SESSION['client_id'];
$companyId = $_SESSION['client_company_id'];

// Company Info
$stmtComp = $pdo->prepare("SELECT name, main_color FROM companies WHERE id = :id LIMIT 1");
$stmtComp->execute(['id' => $companyId]);
$company = $stmtComp->fetch();
$companyName = $company['name'] ?? 'LIMA Solutions';
$mainColor = $company['main_color'] ?? '#007a87';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo htmlspecialchars($companyName); ?></title>
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

        .sidebar {
            width: 260px;
            background-color: #0f172a;
            color: var(--white);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
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

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: rgba(255, 255, 255, 0.4);
            text-align: center;
        }

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

        .content-container {
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            flex-grow: 1;
        }

        /* Chat Panel Card */
        .chat-card {
            background-color: var(--white);
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            height: 600px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            overflow: hidden;
        }

        .chat-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-gray);
            background-color: #fafbfd;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-header i {
            color: var(--primary);
            font-size: 18px;
        }

        .chat-header h3 {
            font-size: 15px;
            font-weight: 700;
        }

        /* Message flow area */
        .chat-messages {
            flex-grow: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
            background-color: #f8fafc;
        }

        .msg-row {
            display: flex;
            width: 100%;
        }

        .msg-row.client {
            justify-content: flex-end;
        }

        .msg-row.staff {
            justify-content: flex-start;
        }

        .msg-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 14px;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .msg-row.client .msg-bubble {
            background-color: var(--primary);
            color: var(--white);
            border-bottom-right-radius: 4px;
        }

        .msg-row.staff .msg-bubble {
            background-color: var(--white);
            color: var(--text-dark);
            border-bottom-left-radius: 4px;
            border: 1px solid var(--border-gray);
        }

        .msg-meta {
            font-size: 10px;
            margin-top: 6px;
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            opacity: 0.7;
        }

        .msg-row.staff .msg-meta {
            color: var(--text-light);
            justify-content: flex-start;
        }

        .msg-row.client .msg-meta {
            color: rgba(255,255,255,0.8);
        }

        /* Footer Input controls */
        .chat-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border-gray);
            background-color: var(--white);
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .chat-input {
            flex-grow: 1;
            padding: 12px 16px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            resize: none;
            height: 48px;
            transition: var(--transition);
        }

        .chat-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .btn-send {
            background-color: var(--primary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius);
            padding: 0 20px;
            height: 48px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-send:hover {
            opacity: 0.9;
        }

        .btn-send:disabled {
            background-color: var(--text-light);
            cursor: not-allowed;
        }

        .placeholder-box {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-light);
            margin: auto;
        }

        .placeholder-box i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <i class="fa-solid fa-cube"></i>
            <h2><?php echo htmlspecialchars($companyName); ?></h2>
        </div>
        <ul class="sidebar-menu">
            <li class="sidebar-item">
                <a href="index.php"><i class="fa-solid fa-gauge"></i> Tableau de bord</a>
            </li>
            <li class="sidebar-item">
                <a href="quotes.php"><i class="fa-solid fa-file-signature"></i> Mes Devis</a>
            </li>
            <li class="sidebar-item">
                <a href="invoices.php"><i class="fa-solid fa-file-invoice-dollar"></i> Mes Factures</a>
            </li>
            <li class="sidebar-item active">
                <a href="messages.php"><i class="fa-solid fa-envelope"></i> Messages</a>
            </li>
            <li class="sidebar-item">
                <a href="guide.php"><i class="fa-solid fa-circle-question"></i> Guide d'utilisation</a>
            </li>
            <li class="sidebar-item">
                <a href="marketplace.php"><i class="fa-solid fa-store"></i> Marketplace</a>
            </li>
            <li class="sidebar-item" style="margin-top: auto;">
                <a href="logout.php" style="color: #f87171;"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <span>&copy; 2026 LIMA Solutions</span>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="navbar">
            <h1>Messages / Central de Chat</h1>
        </header>

        <main class="content-container">
            <div class="chat-card">
                <div class="chat-header">
                    <i class="fa-solid fa-comments"></i>
                    <h3>Chat de communication directe avec notre équipe administrative</h3>
                </div>
                
                <div id="chat-messages" class="chat-messages">
                    <div class="placeholder-box" id="chat-placeholder">
                        <i class="fa-solid fa-comments"></i>
                        <p>Chargement des messages...</p>
                    </div>
                </div>

                <form id="form-chat" class="chat-footer">
                    <input type="text" id="chat-input" class="chat-input" placeholder="Saisissez votre message ici..." required autocomplete="off">
                    <button type="submit" id="btn-send" class="btn-send">
                        <span>Envoyer</span> <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </main>
    </div>

    <script>
        const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        const chatMessages = document.getElementById('chat-messages');
        const chatInput = document.getElementById('chat-input');
        const btnSend = document.getElementById('btn-send');
        const placeholder = document.getElementById('chat-placeholder');

        let lastMessageCount = 0;

        function loadMessages() {
            fetch('/api/v1/portal/messages.php')
                .then(res => res.json())
                .then(res => {
                    if (res.success && res.data && res.data.messages) {
                        const msgs = res.data.messages;
                        
                        if (msgs.length === 0) {
                            chatMessages.innerHTML = `
                                <div class="placeholder-box">
                                    <i class="fa-solid fa-comments"></i>
                                    <p>Aucun message pour l'instant. Saisissez un message ci-dessous pour démarrer la discussion.</p>
                                </div>
                            `;
                            lastMessageCount = 0;
                            return;
                        }

                        // Render bubbles
                        let html = '';
                        msgs.forEach(m => {
                            const isClient = m.sender_type === 'client';
                            const rowClass = isClient ? 'client' : 'staff';
                            const date = new Date(m.created_at);
                            const formattedTime = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) + ' ' + date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
                            
                            html += `
                                <div class="msg-row ${rowClass}">
                                    <div class="msg-bubble">
                                        <div class="msg-text">${escapeHtml(m.message)}</div>
                                        <div class="msg-meta">
                                            <span>${formattedTime}</span>
                                            ${isClient ? '' : '<span style="font-weight: 700;">(Staff)</span>'}
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        chatMessages.innerHTML = html;

                        // Scroll to bottom if new messages loaded
                        if (msgs.length > lastMessageCount) {
                            chatMessages.scrollTop = chatMessages.scrollHeight;
                            lastMessageCount = msgs.length;
                        }
                    }
                })
                .catch(err => {
                    console.error('Error fetching messages:', err);
                });
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        document.getElementById('form-chat').addEventListener('submit', function(e) {
            e.preventDefault();
            const message = chatInput.value.trim();
            if (!message) return;

            chatInput.disabled = true;
            btnSend.disabled = true;

            fetch('/api/v1/portal/messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    message: message,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                chatInput.disabled = false;
                btnSend.disabled = false;
                if (data.success) {
                    chatInput.value = '';
                    loadMessages();
                } else {
                    alert(data.message || 'Erreur lors de l\'envoi.');
                }
            })
            .catch(err => {
                chatInput.disabled = false;
                btnSend.disabled = false;
                console.error(err);
                alert('Erreur réseau.');
            });
        });

        // Initial load and set interval
        loadMessages();
        setInterval(loadMessages, 5000);
    </script>
</body>
</html>
