// CES Diaconia - Database & CRUD Module (Firestore SDK v8 compat)

const DbService = {
    // --- CACHE SYSTEM (Fase 2.1) ---
    _cache: {
        membros: null,
        setores: null,
        produtos: null,
        escalas: null,
        timestamps: {
            membros: 0,
            setores: 0,
            produtos: 0,
            escalas: 0
        }
    },
    _cacheTTL: 300000, // 5 minutos em milissegundos
    cacheStats: {
        leiturasEconomizadas: 0,
        leiturasReais: 0,
        ultimaAtualizacao: null
    },

    isCacheValido(chave) {
        const agora = Date.now();
        return this._cache[chave] !== null && (agora - this._cache.timestamps[chave] < this._cacheTTL);
    },

    limparCache(chave) {
        if (chave) {
            console.log(`[Cache] Invalidando cache para a chave: ${chave}`);
            this._cache[chave] = null;
            this._cache.timestamps[chave] = 0;
        } else {
            console.log("[Cache] Invalidando todo o cache");
            Object.keys(this._cache).forEach(k => {
                if (k !== 'timestamps') {
                    this._cache[k] = null;
                    this._cache.timestamps[k] = 0;
                }
            });
        }
    },

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
                        nome: "Acolhimento e Integraï¿½ï¿½o",
                        funcoes: ["Acolhimento", "Integraï¿½ï¿½o"],
                        cor: "#4A154B" // purple
                    },
                    {
                        id: "limpeza",
                        nome: "Limpeza",
                        funcoes: ["Limpeza geral", "Salï¿½o e banheiros", "ï¿½reas externas", "Reposiï¿½ï¿½o de produtos"],
                        cor: "#1E3A8A" // deep blue
                    },
                    {
                        id: "manutencao",
                        nome: "Manutenï¿½ï¿½o",
                        funcoes: ["Manutenï¿½ï¿½o predial", "Elï¿½trica", "Hidrï¿½ulica", "Ar-condicionado", "Reparos gerais"],
                        cor: "#065F46" // green
                    }
                ];

                for (let sec of sectors) {
                    await db.collection('setores').doc(sec.id).set(sec);
                }

                // 5. Seed Products
                const initialProducts = [
                    { nome: "Papel higiï¿½nico", setorId: "limpeza", quantidade: 30, status: "ativo" },
                    { nome: "Sabonete lï¿½quido", setorId: "limpeza", quantidade: 15, status: "ativo" },
                    { nome: "Desinfetante", setorId: "limpeza", quantidade: 10, status: "ativo" },
                    { nome: "Detergente", setorId: "limpeza", quantidade: 12, status: "ativo" },
                    { nome: "Saco de lixo 100L", setorId: "limpeza", quantidade: 50, status: "ativo" },
                    { nome: "Esponja de limpeza", setorId: "limpeza", quantidade: 20, status: "ativo" },
                    { nome: "ï¿½lcool 70%", setorId: "limpeza", quantidade: 15, status: "ativo" },
                    
                    { nome: "Lï¿½mpada LED 9W", setorId: "manutencao", quantidade: 15, status: "ativo" },
                    { nome: "Fita isolante", setorId: "manutencao", quantidade: 8, status: "ativo" },
                    { nome: "Pilhas AA (para microfone)", setorId: "manutencao", quantidade: 24, status: "ativo" },
                    { nome: "Filtro de ar-condicionado", setorId: "manutencao", quantidade: 6, status: "ativo" },
                    { nome: "Parafuso e bucha 8mm", setorId: "manutencao", quantidade: 100, status: "ativo" },
                    
                    { nome: "Copo descartï¿½vel 200ml", setorId: "acolhimento_integracao", quantidade: 200, status: "ativo" },
                    { nome: "Fita crepe", setorId: "acolhimento_integracao", quantidade: 5, status: "ativo" },
                    { nome: "Crachï¿½ de visitante", setorId: "acolhimento_integracao", quantidade: 40, status: "ativo" },
                    { nome: "Caneta esferogrï¿½fica azul", setorId: "acolhimento_integracao", quantidade: 15, status: "ativo" },
                    
                    { nome: "Pilhas AAA", setorId: "diaconia_templo", quantidade: 20, status: "ativo" },
                    { nome: "Rï¿½dio comunicador (reserva)", setorId: "diaconia_templo", quantidade: 2, status: "ativo" },
                    { nome: "Lanterna LED", setorId: "diaconia_templo", quantidade: 4, status: "ativo" },
                    { nome: "Capa de chuva descartï¿½vel", setorId: "diaconia_templo", quantidade: 30, status: "ativo" }
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

    // --- AUTENTICAï¿½ï¿½O POR NOME (Firestore-based) ---
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
                return { success: false, error: "Nome nï¿½o encontrado. Verifique se digitou o nome completo corretamente." };
            }

            if (matchedDoc.data.senha !== password) {
                return { success: false, error: "Senha incorreta. Entre em contato com o Supervisor Geral caso nï¿½o lembre sua senha." };
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
            console.error("Erro na autenticaï¿½ï¿½o:", e);
            return { success: false, error: "Erro de conexï¿½o com o banco de dados." };
        }
    },

    // --- MEMBROS CRUD ---
    async getMembros() {
        if (this.isCacheValido('membros')) {
            this.cacheStats.leiturasEconomizadas += this._cache.membros.length || 1;
            return this._cache.membros;
        }
        const snap = await db.collection('membros').orderBy('nome').get();
        const data = snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
        this._cache.membros = data;
        this._cache.timestamps.membros = Date.now();
        this.cacheStats.leiturasReais += data.length;
        this.cacheStats.ultimaAtualizacao = new Date();
        return data;
    },

    async saveMembro(id, data) {
        if (id) {
            await db.collection('membros').doc(id).update(data);
        } else {
            data.criadoEm = firebase.firestore.FieldValue.serverTimestamp();
            await db.collection('membros').add(data);
        }
        this.limparCache('membros');
    },

    async deleteMembro(id) {
        await db.collection('membros').doc(id).delete();
        this.limparCache('membros');
    },

    // --- SETORES ---
    async getSetores() {
        if (this.isCacheValido('setores')) {
            this.cacheStats.leiturasEconomizadas += this._cache.setores.length || 1;
            return this._cache.setores;
        }
        const snap = await db.collection('setores').get();
        const data = snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
        this._cache.setores = data;
        this._cache.timestamps.setores = Date.now();
        this.cacheStats.leiturasReais += data.length;
        this.cacheStats.ultimaAtualizacao = new Date();
        return data;
    },

    async saveSetor(id, data) {
        await db.collection('setores').doc(id).set(data);
        this.limparCache('setores');
    },

    // --- PRODUTOS CRUD ---
    async getProdutos() {
        if (this.isCacheValido('produtos')) {
            this.cacheStats.leiturasEconomizadas += this._cache.produtos.length || 1;
            return this._cache.produtos;
        }
        const snap = await db.collection('produtos').orderBy('nome').get();
        const data = snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
        this._cache.produtos = data;
        this._cache.timestamps.produtos = Date.now();
        this.cacheStats.leiturasReais += data.length;
        this.cacheStats.ultimaAtualizacao = new Date();
        return data;
    },

    async saveProduto(id, data) {
        if (id) {
            await db.collection('produtos').doc(id).update(data);
        } else {
            await db.collection('produtos').add(data);
        }
        this.limparCache('produtos');
    },

    async registrarMovimentacaoEstoque(produtoId, tipo, quantidade, observacao, usuarioNome) {
        const prodDoc = await db.collection('produtos').doc(produtoId).get();
        if (!prodDoc.exists) {
            throw new Error("Produto nï¿½o encontrado");
        }
        const prodData = prodDoc.data();
        const currentQty = typeof prodData.quantidade === 'number' ? prodData.quantidade : 0;
        const newQty = tipo === 'entrada' ? currentQty + quantidade : currentQty - quantidade;

        // Atualiza a quantidade do produto no estoque
        await db.collection('produtos').doc(produtoId).update({
            quantidade: newQty
        });

        // Cria a movimentaï¿½ï¿½o de estoque
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

    // --- REPOSIï¿½ï¿½ES CRUD ---
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
            this.limparCache('escalas');
            return id;
        } else {
            data.criadoEm = firebase.firestore.FieldValue.serverTimestamp();
            const docRef = await db.collection('cultos').add(data);
            this.limparCache('escalas');
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
        this.limparCache('escalas');
    },

    // --- ESCALAS CRUD ---
    async getEscalas(setorId = null, dataInicio = null, dataFim = null, cultoId = null) {
        let rawList = [];
        if (this.isCacheValido('escalas')) {
            rawList = this._cache.escalas;
            this.cacheStats.leiturasEconomizadas += rawList.length || 1;
        } else {
            const snap = await db.collection('escalas').get();
            rawList = snap.docs.map(doc => ({ id: doc.id, ...doc.data() }));
            this._cache.escalas = rawList;
            this._cache.timestamps.escalas = Date.now();
            this.cacheStats.leiturasReais += rawList.length;
            this.cacheStats.ultimaAtualizacao = new Date();
        }

        // Filtro local otimizado e ordenaï¿½ï¿½o em memï¿½ria
        let list = [...rawList];
        if (setorId) {
            list = list.filter(item => item.setorId === setorId);
        }
        if (cultoId) {
            list = list.filter(item => item.cultoId === cultoId);
        }
        if (dataInicio && dataFim && !cultoId) {
            list = list.filter(item => item.data >= dataInicio && item.data <= dataFim);
        }
        
        // Ordena por data e horï¿½rio de inï¿½cio
        list.sort((a, b) => {
            if (a.data !== b.data) return a.data.localeCompare(b.data);
            const tA = a.horarioInicio || '00:00';
            const tB = b.horarioInicio || '00:00';
            return tA.localeCompare(tB);
        });
        
        return list;
    },

    async saveEscala(id, data) {
        if (id) {
            await db.collection('escalas').doc(id).update(data);
        } else {
            await db.collection('escalas').add(data);
        }
        this.limparCache('escalas');
    },

    async deleteEscala(id) {
        await db.collection('escalas').doc(id).delete();
        this.limparCache('escalas');
    },

    async updatePresenca(id, statusPresenca) {
        const updateData = { statusPresenca };
        if (statusPresenca === 'Recusada') {
            updateData.rejeicaoResolvida = false;
        }
        await db.collection('escalas').doc(id).update(updateData);
        this.limparCache('escalas');
    },

    // --- SERVIï¿½OS & EXECUï¿½ï¿½O ---
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

    // --- MENSAGENS PARA A SUPERVISï¿½O (CES Diaconia v3.2) ---
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
    },

    // --- HISTï¿½RICO DE SUBSTITUIï¿½ï¿½ES ---
    async addSubstituicaoLog(logData) {
        logData.dataHora = firebase.firestore.FieldValue.serverTimestamp();
        await db.collection('historico_substituicoes').add(logData);
    },

    async getSubstituicoesHistorico() {
        const snap = await db.collection('historico_substituicoes').orderBy('dataHora', 'desc').limit(100).get();
        return snap.docs.map(doc => {
            const data = doc.data();
            return {
                id: doc.id,
                ...data,
                dataHora: data.dataHora ? data.dataHora.toDate() : new Date()
            };
        });
    },

    // --- HISTï¿½RICO DE AFASTAMENTOS ---
    async saveAfastamento(membroId, data) {
        data.dataRegistro = firebase.firestore.FieldValue.serverTimestamp();
        await db.collection('membros').doc(membroId).update({
            afastamento: data
        });
    },

    async getHistoricoAfastamentos() {
        const snap = await db.collection('membros').get();
        const list = [];
        snap.docs.forEach(doc => {
            const m = doc.data();
            if (m.afastamento) {
                list.push({
                    membroId: doc.id,
                    membroNome: m.nome,
                    ...m.afastamento
                });
            }
        });
        return list;
    },

    // --- SCORE DE CONFIABILIDADE (Fase 2) ---
    // Calcula score 0-100 baseado nos ï¿½ltimos 12 meses de escalas do membro.
    // Requer mï¿½nimo de 5 escalas para classificaï¿½ï¿½o efetiva.
    // Retorna { score, classificacao, total, confirmadas, recusadas, faltas, emAvaliacao }
    calcularScoreConfiabilidade(escalasDoMembro) {
        const hoje = new Date();
        const dozeAtras = new Date(hoje);
        dozeAtras.setFullYear(dozeAtras.getFullYear() - 1);
        const dozeAtrasStr = dozeAtras.toISOString().split('T')[0];

        const escalas12m = escalasDoMembro.filter(e => e.data >= dozeAtrasStr);

        const total = escalas12m.length;
        if (total < 5) {
            return { score: null, classificacao: 'Em avaliaï¿½ï¿½o', total, confirmadas: 0, recusadas: 0, faltas: 0, emAvaliacao: true };
        }

        const confirmadas = escalas12m.filter(e => e.statusPresenca === 'Confirmada').length;
        const recusadas   = escalas12m.filter(e => e.statusPresenca === 'Recusada').length;
        const faltas      = escalas12m.filter(e => e.statusPresenca === 'Ausente').length;

        // Pontuaï¿½ï¿½o ponderada:
        //   +40 pts por confirmaï¿½ï¿½o normalizada
        //   +20 pts por presenï¿½a (escalas sem recusa/falta)
        //   -25 pts por cancelamento
        //   -35 pts por falta injustificada
        const pontos = (confirmadas * 40) + ((total - recusadas - faltas) * 20) - (recusadas * 25) - (faltas * 35);
        const maxPontos = total * 60; // mï¿½ximo teï¿½rico (todas confirmadas)
        let score = maxPontos > 0 ? Math.round((pontos / maxPontos) * 100) : 50;
        score = Math.max(0, Math.min(100, score));

        let classificacao;
        if (score >= 80) classificacao = 'Excelente';
        else if (score >= 60) classificacao = 'Bom';
        else if (score >= 40) classificacao = 'Regular';
        else classificacao = 'Crï¿½tico';

        return { score, classificacao, total, confirmadas, recusadas, faltas, emAvaliacao: false };
    },

    // Persiste o score calculado de volta no documento do membro
    async salvarScoreConfiabilidade(membroId, scoreData) {
        await db.collection('membros').doc(membroId).update({
            scoreConfiabilidade: scoreData.score,
            scoreClassificacao: scoreData.classificacao,
            scoreAtualizadoEm: firebase.firestore.FieldValue.serverTimestamp()
        });
    },

    // --- INDISPONIBILIDADE MENSAL (Fase 2) ---
    // diasMap: { "2026-06-08": "nao_posso" | "prefiro_nao" | "posso" }
    async saveIndisponibilidade(membroId, diasMap) {
        await db.collection('membros').doc(membroId).update({
            indisponibilidades_mensais: diasMap,
            indisponibilidadeAtualizadoEm: firebase.firestore.FieldValue.serverTimestamp()
        });
    },

    async getIndisponibilidades(membroId) {
        const doc = await db.collection('membros').doc(membroId).get();
        if (!doc.exists) return {};
        return doc.data().indisponibilidades_mensais || {};
    },

    // Retorna mapa { membroId: { nome, diasMap } } para visï¿½o do admin
    async getAllIndisponibilidades() {
        const snap = await db.collection('membros').get();
        const result = {};
        snap.docs.forEach(doc => {
            const d = doc.data();
            if (d.indisponibilidades_mensais && Object.keys(d.indisponibilidades_mensais).length > 0) {
                result[doc.id] = {
                    nome: d.nome,
                    diasMap: d.indisponibilidades_mensais
                };
            }
        });
        return result;
    },

    // --- ARQUIVAMENTO SEGURO DE DADOS HISTï¿½RICOS (Fase 2.1) ---
    // Mover de 'escalas' para 'escalas_arquivadas' dados com mais de 14 meses (margem de seguranï¿½a para o score de 12 meses)
    async arquivarDadosHistoricos() {
        const hoje = new Date();
        const catorzeMesesAtras = new Date(hoje);
        catorzeMesesAtras.setMonth(catorzeMesesAtras.getMonth() - 14);
        const dataLimiteStr = catorzeMesesAtras.toISOString().split('T')[0];

        console.log(`[Arquivamento] Buscando escalas anteriores a: ${dataLimiteStr}`);
        const snap = await db.collection('escalas')
            .where('data', '<', dataLimiteStr)
            .get();

        if (snap.empty) {
            console.log("[Arquivamento] Nenhuma escala para arquivar.");
            await this.registrarControleArquivamento(0);
            return 0;
        }

        let totalArquivado = 0;
        for (let doc of snap.docs) {
            const data = doc.data();
            const docId = doc.id;

            // 1. Grava cï¿½pia exata na coleï¿½ï¿½o 'escalas_arquivadas'
            await db.collection('escalas_arquivadas').doc(docId).set({
                ...data,
                arquivadoEm: firebase.firestore.FieldValue.serverTimestamp()
            });

            // 2. Valida gravaï¿½ï¿½o bem sucedida (lendo de volta)
            const validationDoc = await db.collection('escalas_arquivadas').doc(docId).get();
            if (validationDoc.exists) {
                // 3. Confirmada a gravaï¿½ï¿½o segura, remove original
                await db.collection('escalas').doc(docId).delete();
                totalArquivado++;
            } else {
                console.error(`[Arquivamento] Falha crï¿½tica de validaï¿½ï¿½o para a escala: ${docId}. Cancelando remoï¿½ï¿½o original.`);
            }
        }

        await this.registrarControleArquivamento(totalArquivado);
        this.limparCache('escalas');
        return totalArquivado;
    },

    async registrarControleArquivamento(total) {
        await db.collection('controle_arquivamento').add({
            executadoEm: firebase.firestore.FieldValue.serverTimestamp(),
            totalDocumentosArquivados: total,
            status: 'Sucesso'
        });
    },

    async getUltimoArquivamento() {
        try {
            const snap = await db.collection('controle_arquivamento')
                .orderBy('executadoEm', 'desc')
                .limit(1)
                .get();

            if (snap.empty) return null;
            const data = snap.docs[0].data();
            return {
                executadoEm: data.executadoEm ? data.executadoEm.toDate() : null,
                total: data.totalDocumentosArquivados,
                status: data.status
            };
        } catch(e) {
            console.error(e);
            return null;
        }
    },

    async getMetricasSaudeSistema() {
        try {
            const membrosSnap = await db.collection('membros').get();
            const escalasSnap = await db.collection('escalas').get();
            const produtosSnap = await db.collection('produtos').get();
            
            // Coleï¿½ï¿½o arquivada
            const arquivadosSnap = await db.collection('escalas_arquivadas').get();

            return {
                membrosAtivos: membrosSnap.size,
                escalasAtivas: escalasSnap.size,
                produtosCadastrados: produtosSnap.size,
                escalasArquivadas: arquivadosSnap.size,
                totalDocumentos: membrosSnap.size + escalasSnap.size + produtosSnap.size + arquivadosSnap.size
            };
        } catch (e) {
            console.error("Erro ao computar mï¿½tricas de saï¿½de:", e);
            return {
                membrosAtivos: 0,
                escalasAtivas: 0,
                produtosCadastrados: 0,
                escalasArquivadas: 0,
                totalDocumentos: 0
            };
        }
    }
};

