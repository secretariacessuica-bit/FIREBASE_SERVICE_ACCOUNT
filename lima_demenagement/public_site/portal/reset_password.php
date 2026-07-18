<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialiser le mot de passe - LIMA Solutions</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007a87;
            --primary-glow: rgba(0, 122, 135, 0.35);
            --bg-dark: #0b1517;
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.07);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --white: #ffffff;
            --error: #f87171;
            --success: #34d399;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at 10% 20%, var(--bg-dark) 0%, #050a0b 90%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
            position: relative;
        }

        .glow-blob {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .blob-1 {
            top: -100px;
            left: -100px;
            animation: float-1 20s infinite ease-in-out;
        }

        .blob-2 {
            bottom: -100px;
            right: -100px;
            animation: float-2 25s infinite ease-in-out;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 40px 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header i {
            font-size: 42px;
            color: var(--primary);
            margin-bottom: 15px;
            text-shadow: 0 0 20px var(--primary-glow);
        }

        .login-header h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 14px;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            color: var(--text-muted);
            font-size: 16px;
        }

        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 14px 15px 14px 45px;
            font-family: inherit;
            font-size: 15px;
            color: var(--white);
            outline: none;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--primary);
            background: rgba(0, 122, 135, 0.02);
            box-shadow: 0 0 0 4px var(--primary-glow);
        }

        .form-input:focus + i {
            color: var(--primary);
        }

        .btn-submit {
            width: 100%;
            background: var(--primary);
            color: var(--white);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px var(--primary-glow);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #008c9c;
            transform: translateY(-2px);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.2);
            color: var(--error);
        }

        .alert-success {
            background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.2);
            color: var(--success);
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--white);
            border-radius: 50%;
            animation: spin 0.8s infinite linear;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="glow-blob blob-1"></div>
    <div class="glow-blob blob-2"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fa-solid fa-lock-open"></i>
                <h2>Réinitialisation</h2>
                <p>Définissez votre nouveau mot de passe sécurisé.</p>
            </div>

            <div id="alert-container"></div>

            <form id="form-reset">
                <div class="form-group">
                    <label for="password">Nouveau mot de passe</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" class="form-input" placeholder="••••••••" required>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmer le mot de passe</label>
                    <div class="input-wrapper">
                        <input type="password" id="password_confirm" class="form-input" placeholder="••••••••" required>
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>

                <button type="submit" id="btn-submit" class="btn-submit">
                    <span>Enregistrer</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token') || '';
        const alertContainer = document.getElementById('alert-container');

        if (!token) {
            alertContainer.innerHTML = `
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Jeton manquant. Veuillez suivre le lien fourni par e-mail.</span>
                </div>
            `;
            document.getElementById('form-reset').style.display = 'none';
        }

        document.getElementById('form-reset').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            const btnSubmit = document.getElementById('btn-submit');
            
            alertContainer.innerHTML = '';
            
            if (password !== passwordConfirm) {
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Les mots de passe ne correspondent pas.</span>
                    </div>
                `;
                return;
            }

            btnSubmit.disabled = true;
            const originalContent = btnSubmit.innerHTML;
            btnSubmit.innerHTML = '<div class="spinner"></div><span>Enregistrement...</span>';
            
            fetch('/api/v1/portal/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'reset_password',
                    token: token,
                    password: password
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Mot de passe modifié avec succès! Redirection...</span>
                        </div>
                    `;
                    setTimeout(() => {
                        window.location.href = '/portal/login.php';
                    }, 2000);
                } else {
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = originalContent;
                    alertContainer.innerHTML = `
                        <div class="alert alert-error">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span>${data.message || 'Une erreur est survenue.'}</span>
                        </div>
                    `;
                }
            })
            .catch(err => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = originalContent;
                alertContainer.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Une erreur réseau est survenue.</span>
                    </div>
                `;
            });
        });
    </script>
</body>
</html>
