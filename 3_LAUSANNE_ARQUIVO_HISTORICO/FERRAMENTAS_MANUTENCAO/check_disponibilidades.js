const fs = require('fs');

async function main() {
    console.log("Checking for past standbys/volunteers...");
    const baseUrl = 'https://firestore.googleapis.com/v1/projects/catedral-connect-267b2/databases/(default)/documents';
    const cutoffDate = '2026-05-28';

    try {
        const res = await fetch(`${baseUrl}/diaconia_escala_disponibilidades?pageSize=300`);
        const data = await res.json();
        
        console.log("\n=== DISPONIBILIDADES ===");
        if (data.documents) {
            for (const doc of data.documents) {
                const fields = doc.fields;
                const id = doc.name.split('/').pop();
                const dataCulto = fields.dataCulto?.stringValue;
                const membroNome = fields.membroNome?.stringValue;
                
                console.log(`Standby ID: ${id} | Membro: "${membroNome}" | Data: ${dataCulto}`);
                
                if (dataCulto && dataCulto < cutoffDate) {
                    console.log(`-> Deleting past Standby: ${membroNome} on ${dataCulto}`);
                    const delUrl = `${baseUrl}/diaconia_escala_disponibilidades/${id}`;
                    const delRes = await fetch(delUrl, { method: 'DELETE' });
                    if (delRes.ok) {
                        console.log(`   Successfully deleted.`);
                    } else {
                        console.error(`   Failed to delete.`);
                    }
                }
            }
        } else {
            console.log("No standbys found.");
        }
    } catch (e) {
        console.error("Error checking standbys:", e);
    }
}

main();
