// Firebase Configuration (Compat Version)
// IMPORTANTE: Substitua os valores abaixo pelas chaves do seu projeto no Console do Firebase:
// https://console.firebase.google.com/

var firebaseConfig = {
    apiKey: "AIzaSyB7XUcNMiPSO9IZd2hDB7bZcilXMD-nzpM",
    authDomain: "catedral-connect-bf717.firebaseapp.com",
    projectId: "catedral-connect-bf717",
    storageBucket: "catedral-connect-bf717.firebasestorage.app",
    messagingSenderId: "299301642892",
    appId: "1:299301642892:web:dd23baf839ee737139d1a9"
};

// Initialize Firebase
// Verifica se o firebase já foi carregado para evitar erros de múltipla inicialização
if (typeof firebase !== 'undefined') {
    firebase.initializeApp(firebaseConfig);
    console.log("Firebase inicializado com sucesso!");

    // Disable Offline Persistence completely to avoid IndexedDB lock conflicts across multiple iframes.
    // This solves the 'Failed to obtain primary lease' error and speeds up writes significantly.
    /*
    if (window.location.protocol !== 'file:') {
        firebase.firestore().enablePersistence({ synchronizeTabs: true })
            .catch((err) => {
                if (err.code == 'failed-precondition') {
                    console.warn("Persistence failed: Multiple tabs open without sync support.");
                } else if (err.code == 'unimplemented') {
                    console.warn("Browser doesn't support persistence");
                }
            });
    } else {
        console.warn("Local Dev: Offline Persistence disabled to prevent IndexDB lock errors.");
    }
    */

    // Shortcuts for services
    // Shortcuts for services (Global Scope)
    // Shortcuts for services (Global Scope)
    // Check if auth is available (some pages might not load it)
    if (firebase.auth) {
        window.auth = firebase.auth();
    }
    window.db = firebase.firestore();

    // --- DATABASE NAMESPACING v63.1.3 ---
    // Establishes absolute data isolation for the Bulle branch
    window.DB_PREFIX = 'bulle_';
    const originalCollection = window.db.collection;
    window.db.collection = function(name) {
        // Transparently redirect to the prefixed collection
        const finalName = name.startsWith(window.DB_PREFIX) ? name : window.DB_PREFIX + name;
        return originalCollection.call(this, finalName);
    };

    window.storage = firebase.storage ? firebase.storage() : null;
    window.firebaseConfig = firebaseConfig; // Expose for secondary apps
} else {
    console.error("Erro: SDK do Firebase não encontrado. Verifique os scripts no HTML.");
}
