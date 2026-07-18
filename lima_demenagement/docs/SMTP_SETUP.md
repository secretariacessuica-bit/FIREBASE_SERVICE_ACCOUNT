# Configuração do SMTP Real & Parâmetros de Domínio (SPF, DKIM, DMARC)

Para garantir a entregabilidade ideal de e-mails transacionais enviados a partir do **LIMA Solutions ERP** e evitar que estes caiam na caixa de spam do destinatário, deve ser configurada a autenticação de domínio no painel de administração de DNS da **Infomaniak** (para o domínio `limasolutions.ch`).

---

## 1. SPF (Sender Policy Framework)
O SPF é uma entrada TXT no DNS que especifica quais os servidores que estão autorizados a enviar mensagens em nome do seu domínio.

*   **Tipo de registo:** `TXT`
*   **Nome/Host:** `@` (ou vazio)
*   **Valor/Conteúdo:**
    `v=spf1 include:spf.infomaniak.ch ?all`

*Se já possuir um registo SPF configurado, adicione apenas `include:spf.infomaniak.ch` antes do sufixo final `?all` ou `~all`.*

---

## 2. DKIM (DomainKeys Identified Mail)
O DKIM adiciona uma assinatura criptográfica a todas as mensagens enviadas, validando a sua autenticidade.

*   Na consola administrativa da Infomaniak, aceda a **E-mail** > **Gerir endereço de email** > **Parâmetros Globais** (ou Configurações de Domínio).
*   Ative o **DKIM** para o domínio `limasolutions.ch`.
*   A Infomaniak irá gerar automaticamente as chaves e atualizar as zonas DNS. Se gerido noutro registrador de DNS, copie a chave gerada e crie um registo do tipo `TXT` com o nome do selector fornecido.

---

## 3. DMARC (Domain-based Message Authentication, Reporting, and Conformance)
O DMARC utiliza o SPF e o DKIM para ditar as instruções ao servidor de e-mail de destino sobre o que fazer caso uma mensagem falhe a autenticação.

*   **Tipo de registo:** `TXT`
*   **Nome/Host:** `_dmarc` (ou `_dmarc.limasolutions.ch.`)
*   **Valor/Conteúdo:**
    `v=DMARC1; p=quarantine; pct=100; rua=mailto:postmaster@limasolutions.ch`

*   **`p=quarantine`**: Coloca na pasta de spam as mensagens que falharem a verificação.
*   **`pct=100`**: Aplica a política a 100% dos e-mails enviados.
*   **`rua=mailto:postmaster@limasolutions.ch`**: Envia relatórios diários de conformidade para a conta administrativa.

---

## 4. Ligação Segura no ERP
Após configurar os registos DNS, certifique-se de que as constantes de ligação segura em `/private/config.php` estão definidas corretamente de acordo com as diretrizes do provedor de e-mail.
