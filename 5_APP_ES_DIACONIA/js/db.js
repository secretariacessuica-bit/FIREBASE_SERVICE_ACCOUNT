// CES Diaconia - Database & CRUD Module (Firestore SDK v8 compat)

const DbService = {
    // --- INITIAL SEED METHOD ---
    async checkAndSeedDatabase() {
        try {
            console.log("Checking if database needs seeding...");
            const membersSnap = await db.collection('membros').limit(1).get();
            
            if (membersSnap.empty) {
                console.log("Database empty! Seeding initial data...");
                
                // 1. Seed Admin Principal (CES Diaconia Lausanne)
                await db.collection('membros').doc('admin_default').set({
                    nome: "Wanderson Rossini",
                    email: "admin@diaconia.com",
                    senha: "Ces120222.",
                    perfil: "admin",
                    setor: null,
                    funcao: "Administrador",
                    status: "ativo",
                    criadoEm: firebase.firestore.FieldValue.serverTimestamp()
                });

                // 4. Seed Sectors
                const sectors = [
                    {
                        id: "diaconia_templo",
                        nome: "Diaconia do Templo",
                        funcoes: ["Portaria", "Check-in", "Apoio Interno", "Ronda"],
                        cor: "#127369" // teal
                    },
                    {
                        id: "acolhimento_integracao",
                        nome: "Acolhimento e Integração",
                        funcoes: ["Acolhimento", "Integração"],
                        cor: "#4A154B" // purple
                    },
                    {
                        id: "limpeza",
                        nome: "Limpeza",
                        funcoes: ["Limpeza geral", "Salão e banheiros", "Áreas externas", "Reposição de produtos"],
                        cor: "#1E3A8A" // deep blue
                    },
                    {
                        id: "manutencao",
                        nome: "Manutenção",
                        funcoes: ["Manutenção predial", "Elétrica", "Hidráulica", "Ar-condicionado", "Reparos gerais"],
                        cor: "#065F46" // green
                    }
                ];

                for (let sec of sectors) {
                    await db.collection('setores').doc(sec.id).set(sec);
                }

                // 5. Seed Products
                const initialProducts = [
                    { nome: "Papel higiênico", setorId: "limpeza", quantidade: 30, status: "ativo" },
                    { nome: "Sabonete líquido", setorId: "limpeza", quantidade: 15, status: "ativo" },
                    { nome: "Desinfetante", setorId: "limpeza", quantidade: 10, status: "ativo" },
                    { nome: "Detergente", setorId: "limpeza", quantidade: 12, status: "ativo" },
                    { nome: "Saco de lixo 100L", setorId: "limpeza", quantidade: 50, status: "ativo" },
                    { nome: "Esponja de limpeza", setorId: "limpeza", quantidade: 20, status: "ativo" },
                    { nome: "Álcool 70%", setorId: "limpeza", quantidade: 15, status: "ativo" },
                    
                    { nome: "Lâmpada LED 9W", setorId: "manutencao", quantidade: 15, status: "ativo" },
                    { nome: "Fita isolante", setorId: "manutencao", quantidade: 8, status: "ativo" },
                    { nome: "Pilhas AA (para microfone)", setorId: "manutencao", quantidade: 24, status: "ativo" },
                    { nome: "Filtro de ar-condicionado", setorId: "manutencao", quantidade: 6, status: "ativo" },
                    { nome: "Parafuso e bucha 8mm", setorId: "manutencao", quantidade: 100, status: "ativo" },
                    
                    { nome: "Copo descartável 200ml", setorId: "acolhimento_integracao", quantidade: 200, status: "ativo" },
                    { nome: "Fita crepe", setorId: "acolhimento_integracao", quantidade: 5, status: "ativo" },
                    { nome: "Crachá de visitante", setorId: "acolhimento_integracao", quantidade: 40, status: "ativo" },
                    { nome: "Caneta esferográfica azul", setorId: "acolhimento_integracao", quantidade: 15, status: "ativo" },
                    
                    { nome: "Pilhas AAA", setorId: "diaconia_templo", quantidade: 20, status: "ativo" },
                    { nome: "Rádio comunicador (reserva)", setorId: "diaconia_templo", quantidade: 2, status: "ativo" },
                    { nome: "Lanterna LED", setorId: "diaconia_templo", quantidade: 4, status: "ativo" },
                    { nome: "Capa de chuva descartável", setorId: "diaconia_templo", quantidade: 30, status: "ativo" }
                ];

                for (let prod of initialProducts) {
                    await db.collection('produtos').add(prod);
                }

                // 6. Aviso de boas-vindas
                await db.collection('avisos').add({
                    titulo: "Bem-vindos ao App de Escala!",
                    conteudo: "O sistema de escala da Diac\u00f4nia CES Lausanne est\u00e1 no ar. Cada membro receber\u00e1 suas escalas diretamente aqui. Confirme sua presen\u00e7a assim que receber a notifica\u00e7\u00e3o.",
                    autorNome: "Wanderson Rossini",
                    data: firebase.firestore.FieldValue.serverTimestamp()
                });

                console.log("Database seeded successfully!");
            } else {
                console.log("Database already has data. Seeding skipped.");
            }
        } catch (e) {
            console.error("Error checking or seeding database:", e);
        }
    },

    // Normalize a string for comparison: lowercase + remove accents
    normalizeStr(str) {
        return (str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    },

    // --- AUTENTICAÇÃO POR NOME (Firestore-based) ---
    async authenticateUser(nomeDigitado, password) {
        try {
            // Fetch all active members and compare normalized names locally
            // (Firestore doesn't support case-insensitive or accent-insensitive queries)
            const snap = await db.collection('membros')
                .where('status', '==', 'ativo')
                .get();

            if (snap.empty) {
                return { success: false, error: "Nenhum membro ativo encontrado." };
            }

            const nomeNorm = this.normalizeStr(nomeDigitado);
            let matchedDoc = null;

            snap.forEach(doc => {
                const data = doc.data();
                const docNomeNorm = this.normalizeStr(data.nome);
                if (docNomeNorm === nomeNorm) {
                    matchedDoc = { doc, data };
                }
            });

            if (!matchedDoc) {
                return { success: false, error: "Nome não encontrado. Verifique se digitou o nome completo corretamente." };
            }

            if (matchedDoc.data.senha !== password) {
                return { success: false, error: "Senha incorreta. Entre em contato com o Supervisor Geral caso não lembre sua senha." };
            }

            const doc = matchedDoc.doc;
            const data = matchedDoc.data;
            return {
                success: true,
                user: {
                    id: doc.id,
                    nome: data.nome,
                    email: data.email,
                    perfil: data.perfil,
                    setor: data.setor,
                    setores: data.setores || (data.setor ? [data.setor] : []),
                    funcao: data.funcao,
                    fotoUrl: data.fotoUrl || null,
                    eRepositor: data.eRepositor || false
                }
            };
        } catch (e) {
            console.error("Erro na autenticação:", e);
            return { success: false, error: "Erro de conexão com o banco de dados." };
        }
    },

    // --- MEMBROS CRUD ---
    async getMembros() {
        const snap = await db.collection('membros').orderBy('nome').get();
        return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    },

    async saveMembro(id, data) {
        if (id) {
            await db.collection('membros').doc(id).update(data);
        } else {
            data.criadoEm = firebase.firestore.FieldValue.serverTimestamp();
            await db.collection('membros').add(data);
        }
    },

    async deleteMembro(id) {
        await db.collection('membros').doc(id).delete();
    },

    // --- SETORES ---
    async getSetores() {
        const snap = await db.collection('setores').get();
        return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    },

    async saveSetor(id, data) {
        await db.collection('setores').doc(id).set(data);
    },

    // --- PRODUTOS CRUD ---
    async getProdutos() {
        const snap = await db.collection('produtos').orderBy('nome').get();
        return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    },

    async saveProduto(id, data) {
        if (id) {
            await db.collection('produtos').doc(id).update(data);
        } else {
            await db.collection('produtos').add(data);
        }
    },

    async registrarMovimentacaoEstoque(produtoId, tipo, quantidade, observacao, usuarioNome) {
        const prodDoc = await db.collection('produtos').doc(produtoId).get();
        if (!prodDoc.exists) {
            throw new Error("Produto não encontrado");
        }
        const prodData = prodDoc.data();
        const currentQty = typeof prodData.quantidade === 'number' ? prodData.quantidade : 0;
        const newQty = tipo === 'entrada' ? currentQty + quantidade : currentQty - quantidade;

        // Atualiza a quantidade do produto no estoque
        await db.collection('produtos').doc(produtoId).update({
            quantidade: newQty
        });

        // Cria a movimentação de estoque
        const movimentacao = {
            produtoId,
            produtoNome: prodData.nome,
            setorId: prodData.setorId || 'limpeza',
            tipo,
            quantidade,
            observacao: observacao || '',
            usuarioNome: usuarioNome || 'Sistema',
            dataMovimentacao: firebase.firestore.FieldValue.serverTimestamp()
        };
        await db.collection('historico_estoque').add(movimentacao);
        return newQty;
    },

    async getMovimentacoesEstoque(setorId) {
        let query = db.collection('historico_estoque');
        if (setorId) {
            query = query.where('setorId', '==', setorId);
        }
        const snap = await query.orderBy('dataMovimentacao', 'desc').limit(50).get();
        return snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                dataMovimentacao: data.dataMovimentacao ? data.dataMovimentacao.toDate() : new Date()
            };
        });
    },

    // --- REPOSIÇÕES CRUD ---
    async getReposicoes() {
        const snap = await db.collection('reposicoes').orderBy('dataSolicitacao', 'desc').get();
        return snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                dataSolicitacao: data.dataSolicitacao ? data.dataSolicitacao.toDate() : new Date()
            };
        });
    },

    async addReposicao(data) {
        data.dataSolicitacao = firebase.firestore.FieldValue.serverTimestamp();
        data.status = 'Pendente';
        await db.collection('reposicoes').add(data);
    },

    async updateStatusReposicao(id, status) {
        await db.collection('reposicoes').doc(id).update({ status });
    },

    // --- AVISOS CRUD ---
    async getAvisos() {
        const snap = await db.collection('avisos').get();
        let list = snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                data: data.data ? data.data.toDate() : new Date()
            };
        });

        // Filter out expired notices in local timezone date (YYYY-MM-DD)
        const tzOffset = new Date().getTimezoneOffset() * 60000;
        const hojeStr = new Date(Date.now() - tzOffset).toISOString().split('T')[0];
        list = list.filter(item => {
            if (!item.dataExpiracao) return true;
            return item.dataExpiracao >= hojeStr;
        });

        // Sort by date descending
        list.sort((a, b) => b.data - a.data);
        return list;
    },

    async saveAviso(data) {
        data.data = firebase.firestore.FieldValue.serverTimestamp();
        await db.collection('avisos').add(data);
    },

    async deleteAviso(id) {
        await db.collection('avisos').doc(id).delete();
    },

    // --- CULTOS CRUD ---
    async getCultos(dataInicio = null, dataFim = null) {
        let query = db.collection('cultos');
        const snap = await query.get();
        let list = snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
        
        if (dataInicio && dataFim) {
            list = list.filter(item => item.data >= dataInicio && item.data <= dataFim);
        }
        
        // Ordenar por data e horarioInicio
        list.sort((a, b) => {
            if (a.data !== b.data) return a.data.localeCompare(b.data);
            return a.horarioInicio.localeCompare(b.horarioInicio);
        });
        
        return list;
    },

    async saveCulto(id, data) {
        if (id) {
            await db.collection('cultos').doc(id).update(data);
            
            // Se o nome do culto mudou, atualizar as escalas vinculadas denormalizadas
            if (data.nome) {
                const escalasSnap = await db.collection('escalas').where('cultoId', '==', id).get();
                if (!escalasSnap.empty) {
                    const batch = db.batch();
                    escalasSnap.docs.forEach(doc => {
                        batch.update(doc.ref, { cultoNome: data.nome });
                    });
                    await batch.commit();
                }
            }
            return id;
        } else {
            data.criadoEm = firebase.firestore.FieldValue.serverTimestamp();
            const docRef = await db.collection('cultos').add(data);
            return docRef.id;
        }
    },

    async deleteCulto(id) {
        await db.collection('cultos').doc(id).delete();
        
        // Deleta as escalas vinculadas
        const escalasSnap = await db.collection('escalas').where('cultoId', '==', id).get();
        if (!escalasSnap.empty) {
            const batch = db.batch();
            escalasSnap.docs.forEach(doc => {
                batch.delete(doc.ref);
            });
            await batch.commit();
        }
    },

    // --- ESCALAS CRUD ---
    async getEscalas(setorId = null, dataInicio = null, dataFim = null, cultoId = null) {
        let query = db.collection('escalas');
        
        if (setorId) {
            query = query.where('setorId', '==', setorId);
        }
        if (cultoId) {
            query = query.where('cultoId', '==', cultoId);
        }
        
        const snap = await query.get();
        let list = snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
        
        // Manual sorting/filtering to simplify Firestore indexing requirements
        if (dataInicio && dataFim && !cultoId) {
            list = list.filter(item => item.data >= dataInicio && item.data <= dataFim);
        }
        
        // Sort by date then start time
        list.sort((a, b) => {
            if (a.data !== b.data) return a.data.localeCompare(b.data);
            return a.horarioInicio.localeCompare(b.horarioInicio);
        });
        
        return list;
    },

    async saveEscala(id, data) {
        if (id) {
            await db.collection('escalas').doc(id).update(data);
        } else {
            await db.collection('escalas').add(data);
        }
    },

    async deleteEscala(id) {
        await db.collection('escalas').doc(id).delete();
    },

    async updatePresenca(id, statusPresenca) {
        const updateData = { statusPresenca };
        if (statusPresenca === 'Recusada') {
            updateData.rejeicaoResolvida = false;
        }
        await db.collection('escalas').doc(id).update(updateData);
    },

    // --- SERVIÇOS & EXECUÇÃO ---
    async iniciarServico(escalaId, membroId, membroNome, setorId, funcao, data, horarioInicio, horarioFim) {
        // Update Escala status
        await db.collection('escalas').doc(escalaId).update({
            statusServico: 'Em andamento'
        });

        // Add to active services
        const servicoRef = await db.collection('servicos').add({
            escalaId,
            membroId,
            membroNome,
            setorId,
            funcao,
            data,
            horarioInicio,
            horarioFim,
            iniciadoEm: firebase.firestore.FieldValue.serverTimestamp(),
            status: 'Em andamento',
            observacoes: ""
        });

        return servicoRef.id;
    },

    async finalizarServico(servicoId, escalaId, observacoes) {
        const agora = firebase.firestore.FieldValue.serverTimestamp();
        
        // Update service
        await db.collection('servicos').doc(servicoId).update({
            finalizadoEm: agora,
            status: 'Finalizado',
            observacoes: observacoes
        });

        // Update corresponding scale
        await db.collection('escalas').doc(escalaId).update({
            statusServico: 'Finalizado',
            observacoes: observacoes
        });
    },

    async getServicosEmAndamento() {
        const snap = await db.collection('servicos')
            .where('status', '==', 'Em andamento')
            .get();
        return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    },

    async getHistoricoServicos() {
        const snap = await db.collection('servicos')
            .where('status', '==', 'Finalizado')
            .get();
        
        let list = snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                iniciadoEm: data.iniciadoEm ? data.iniciadoEm.toDate() : null,
                finalizadoEm: data.finalizadoEm ? data.finalizadoEm.toDate() : null
            };
        });

        // Sort by finalization date descending
        list.sort((a, b) => {
            if (!a.finalizadoEm) return 1;
            if (!b.finalizadoEm) return -1;
            return b.finalizadoEm - a.finalizadoEm;
        });

        return list;
    },

    async getRepositores() {
        const snap = await db.collection('membros')
            .where('eRepositor', '==', true)
            .where('status', '==', 'ativo')
            .get();
        return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    },

    async addNotificacao(data) {
        data.data = firebase.firestore.FieldValue.serverTimestamp();
        data.lida = false;
        await db.collection('notificacoes').add(data);
    },

    async getNotificacoesUsuario(usuarioId) {
        const snap = await db.collection('notificacoes')
            .where('paraUsuarioId', '==', usuarioId)
            .orderBy('data', 'desc')
            .limit(30)
            .get();
        return snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                data: data.data ? data.data.toDate() : new Date()
            };
        });
    },

    async marcarNotificacoesComoLidas(usuarioId) {
        const snap = await db.collection('notificacoes')
            .where('paraUsuarioId', '==', usuarioId)
            .where('lida', '==', false)
            .get();
        
        if (snap.empty) return;
        
        const batch = db.batch();
        snap.docs.forEach(doc => {
            batch.update(db.collection('notificacoes').doc(doc.id), { lida: true });
        });
        await batch.commit();
    },

    // --- STANDBY / VOLUNTARIADO (CES Diaconia v3.2) ---
    async saveStandby(data) {
        data.criadoEm = firebase.firestore.FieldValue.serverTimestamp();
        await db.collection('disponibilidades').add(data);
    },

    async getStandbys() {
        const snap = await db.collection('disponibilidades').get();
        return snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
    },

    async deleteStandby(id) {
        await db.collection('disponibilidades').doc(id).delete();
    },

    // --- MENSAGENS PARA A SUPERVISÃO (CES Diaconia v3.2) ---
    async saveSupervisionMessage(membroId, membroNome, content) {
        const msg = {
            membroId,
            membroNome,
            conteudo: content,
            lida: false,
            criadoEm: firebase.firestore.FieldValue.serverTimestamp()
        };
        await db.collection('mensagens_supervisao').add(msg);
    },

    async getSupervisionMessages() {
        const snap = await db.collection('mensagens_supervisao').where('lida', '==', false).get();
        let list = snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                criadoEm: data.criadoEm ? data.criadoEm.toDate() : new Date()
            };
        });
        list.sort((a, b) => b.criadoEm - a.criadoEm);
        return list;
    },

    async marcarMensagemComoLida(id) {
        await db.collection('mensagens_supervisao').doc(id).update({ lida: true });
    },

    async getMuralConfig() {
        try {
            const doc = await db.collection('configuracoes').doc('mural').get();
            if (doc.exists) {
                return doc.data();
            }
            return null;
        } catch (e) {
            console.error("Erro ao buscar configuracoes do mural:", e);
            return null;
        }
    },

    async saveMuralConfig(data) {
        await db.collection('configuracoes').doc('mural').set(data);
    }
};
