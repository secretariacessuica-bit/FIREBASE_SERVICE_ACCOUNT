<?php
require_once __DIR__ . '/../api/v1/config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - LIMA Solutions</title>
    <!-- FontAwesome for clean icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-teal: #007a87;
            --primary-teal-dark: #005a63;
            --primary-teal-light: #e6f2f3;
            --text-dark: #333333;
            --border-gray: #cccccc;
            --white: #ffffff;
            --bg-light: #f0f3f4;
            --border-radius: 6px;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--bg-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--text-dark);
            padding: 20px;
        }

        .login-card {
            background-color: var(--white);
            padding: 40px 30px;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-header h2 {
            font-size: 24px;
            color: var(--primary-teal);
            margin-bottom: 8px;
            font-weight: bold;
        }

        .login-header p {
            font-size: 13px;
            color: #777777;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
            color: var(--primary-teal);
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999999;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid var(--border-gray);
            border-radius: var(--border-radius);
            outline: none;
            font-family: inherit;
            font-size: 14px;
            transition: var(--transition);
        }

        .form-group input:focus {
            border-color: var(--primary-teal);
            box-shadow: 0 0 0 3px var(--primary-teal-light);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            font-size: 14px;
            font-weight: bold;
            color: var(--white);
            background-color: var(--primary-teal);
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background-color: var(--primary-teal-dark);
            transform: translateY(-1px);
        }

        .back-link {
            display: block;
            margin-top: 25px;
            font-size: 13px;
            color: #888888;
            text-decoration: none;
            transition: var(--transition);
        }

        .back-link:hover {
            color: var(--primary-teal);
        }

        /* Toast notifications */
        .toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background-color: #333333;
            color: var(--white);
            padding: 12px 24px;
            border-radius: var(--border-radius);
            font-size: 13px;
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
        }

        .toast.error {
            background-color: #d9534f;
        }

        .toast.success {
            background-color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <h2>LIMA Solutions</h2>
            <p>Administration Platform login</p>
        </div>
        <form id="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" id="email" placeholder="admin@limasolutions.ch" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" placeholder="••••••••" required>
                </div>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Se connecter
            </button>
        </form>
        <a href="../" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Retour au site
        </a>
    </div>

    <!-- Notification Toast -->
    <div class="toast" id="toast"></div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('login-form');
            const toast = document.getElementById('toast');

            function showToast(message, type = '') {
                toast.textContent = message;
                toast.className = 'toast show ' + type;
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 4000);
            }

            // Check for URL errors
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            if (error === 'timeout') {
                showToast('Votre session a expiré pour cause d\'inactivité. Veuillez vous reconnecter.', 'error');
            } else if (error === 'no_company') {
                showToast('Aucune entreprise associée à votre compte.', 'error');
            }


            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value;

                fetch('../api/v1/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                })
                .then(async (res) => {
                    const data = await res.json();
                    if (res.ok && data.success) {
                        showToast('Connexion réussie ! Redirection...', 'success');
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 1000);
                    } else {
                        showToast(data.message || 'Échec de la connexion.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Login error:', err);
                    showToast('Erreur de conexão com o servidor.', 'error');
                });
            });
        });
    </script>
</body>
</html>
