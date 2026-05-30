const fs = require('fs');

async function main() {
    console.log("Checking supervision messages...");
    const baseUrl = 'https://firestore.googleapis.com/v1/projects/catedral-connect-267b2/databases/(default)/documents';

    try {
        const res = await fetch(`${baseUrl}/diaconia_escala_mensagens_supervisao?pageSize=300`);
        const data = await res.json();
        
        console.log("\n=== MENSAGENS SUPERVISAO ===");
        if (data.documents) {
            for (const doc of data.documents) {
                const fields = doc.fields;
                const id = doc.name.split('/').pop();
                const enviadoEm = fields.enviadoEm?.timestampValue || fields.enviadoEm?.stringValue;
                const membroNome = fields.membroNome?.stringValue;
                const conteudo = fields.conteudo?.stringValue;
                const lida = fields.lida?.booleanValue;
                
                console.log(`Msg ID: ${id} | De: "${membroNome}" | Conteúdo: "${conteudo}" | Enviado Em: ${enviadoEm} | Lida: ${lida}`);
                
                // Let's delete if it is from a date in the past, or if we want to clear them to keep it clean.
                // Since this is a test/homologation database and the user wants to clear past dummy data,
                // we can delete all messages that were sent before today (or all of them since they are dummy).
                // Let's delete messages sent before 2026-05-28.
                if (enviadoEm) {
                    const datePart = enviadoEm.substring(0, 10);
                    if (datePart < '2026-05-28') {
                        console.log(`-> Deleting past message from ${membroNome}`);
                        const delUrl = `${baseUrl}/diaconia_escala_mensagens_supervisao/${id}`;
                        const delRes = await fetch(delUrl, { method: 'DELETE' });
                        if (delRes.ok) {
                            console.log(`   Successfully deleted.`);
                        } else {
                            console.error(`   Failed to delete.`);
                        }
                    }
                }
            }
        } else {
            console.log("No supervision messages found.");
        }
    } catch (e) {
        console.error("Error checking supervision messages:", e);
    }
}

main();
