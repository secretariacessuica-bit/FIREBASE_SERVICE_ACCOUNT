# Regras de Deploy (Workspace Diaconia)

- **Deploy Duplo:** Até que a migração para a plataforma "informatick" seja concluída, todos os deploys devem ser feitos obrigatoriamente nos DOIS links (projetos Firebase), correspondentes a Produção e Legado.
- **Link 1 (Produção):**
  - Projeto: `diaconia-a38f1` (alias `prod`)
  - Comando: `firebase deploy --only hosting --project prod`
  - Hosting: https://ces-diaconia-prod.web.app (ou https://diaconia-a38f1.web.app)
- **Link 2 (Legado):**
  - Projeto: `catedral-connect-267b2` (alias `legacy`)
  - Comando: `firebase deploy --only hosting --project legacy`
  - Hosting: https://catedral-connect-267b2.web.app
