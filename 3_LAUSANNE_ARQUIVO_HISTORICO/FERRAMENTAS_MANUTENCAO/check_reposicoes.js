const fs = require('fs');

async function main() {
    console.log("Checking replacements...");
    const baseUrl = 'https://firestore.googleapis.com/v1/projects/catedral-connect-267b2/databases/(default)/documents';

    try {
        const res = await fetch(`${baseUrl}/reposicoes?pageSize=300`);
        const data = await res.json();
        
        console.log("\n=== REPOSICOES ===");
        if (data.documents) {
            for (const doc of data.documents) {
                const fields = doc.fields;
                const id = doc.name.split('/').pop();
                const dataEscala = fields.dataEscala?.stringValue;
                const membroNome = fields.membroNome?.stringValue;
                const status = fields.status?.stringValue;
                
                console.log(`Replacement ID: ${id} | Membro: "${membroNome}" | Data: ${dataEscala} | Status: ${status}`);
                
                // Delete if the date is in the past (before 2026-05-28)
                if (dataEscala && dataEscala < '2026-05-28') {
                    console.log(`-> Deleting past replacement request`);
                    const delUrl = `${baseUrl}/reposicoes/${id}`;
                    const delRes = await fetch(delUrl, { method: 'DELETE' });
                    if (delRes.ok) {
                        console.log(`   Successfully deleted.`);
                    } else {
                        console.error(`   Failed to delete.`);
                    }
                }
            }
        } else {
            console.log("No replacements found.");
        }
    } catch (e) {
        console.error("Error checking replacements:", e);
    }
}

main();
