# ⚠️ LEIA ANTES DE EDITAR — APP ES DIACONIA [MÓDULO 5]

> Protocolo ativo. Qualquer edição nesta pasta deve respeitar estas regras.
> Protocolo completo: `docs/PROTOCOLO_CONSULTA_PADRAO.md`

---

## 📍 IDENTIDADE DESTE MÓDULO

| Campo | Valor |
|---|---|
| **Filial** | CES Lausanne (piloto) |
| **Tipo** | App interno — controle de escalas e serviços diaconais |
| **Status** | 🧪 **EM TESTES** — não é produção |
| **Firebase Project** | `catedral-connect-267b2` |
| **Hosting Target** | `diaconia` |
| **Deploy** | `firebase deploy --only hosting:diaconia` |

## ⚠️ ATENÇÃO — STATUS DE TESTES

- Este módulo está em **fase piloto em Lausanne**
- Mudanças podem ser mais experimentais, mas devem ser documentadas
- Quando aprovado, poderá ser expandido para outras filiais da CES
- **Não assumir que o que funciona aqui já está validado para produção**

## 📄 PÁGINAS DESTE MÓDULO

| Arquivo | Função |
|---|---|
| `index.html` | App principal de escalas e diaconia |

## 🔒 REGRAS DESTE MÓDULO

1. **SEMPRE** confirmar `firebase use diaconia-project` antes de deploy
2. **NUNCA** usar Project IDs de Bulle ou Lausanne aqui
3. Coleções Firestore deste módulo usam prefixo `diaconia_*`
4. Push Notifications gerenciadas por `sw-notifications.js` — não remover

## 💾 BACKUP

Antes de grandes mudanças, salvar versão em:
`3_LAUSANNE_ARQUIVO_HISTORICO/REPOSITORIO_DE_RESTAURACAO_E_VERSOES/`

---
*Protocolo v1.0 | CES Ecossistema Digital*
