// Firebase Configuration (Compat Version)
// IMPORTANTE: Substitua os valores abaixo pelas chaves do seu projeto no Console do Firebase:
// https://console.firebase.google.com/

var firebaseConfig = {
    apiKey: "AIzaSyDGBPn4_Sf3tZ7MC6RwnfJopyCY8LCfYas",
    authDomain: "catedral-connect-6c55e.firebaseapp.com",
    projectId: "catedral-connect-6c55e",
    storageBucket: "catedral-connect-6c55e.firebasestorage.app",
    messagingSenderId: "854523174149",
    appId: "1:854523174149:web:4d7a98753417c036f8eb6c"
};

// Initialize Firebase
// Verifica se o firebase já foi carregado para evitar erros de múltipla inicialização
if (typeof firebase !== 'undefined') {
    // SINGLETON PATTERN: Only initialize if not already initialized
    if (firebase.apps.length === 0) {
        firebase.initializeApp(firebaseConfig);
        console.log("Firebase inicializado com sucesso!");
    } else {
        console.log("Firebase já inicializado (Singleton Safe).");
    }

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
        // PERSISTENCE SESSION: Login will reset on tab close as per user request
        firebase.auth().setPersistence(firebase.auth.Auth.Persistence.SESSION)
            .then(() => console.log("Firebase Auth: Persistence set to SESSION (No Auto-Login)"))
            .catch(e => console.warn("Persistence Error:", e));
    }
    window.db = firebase.firestore();
    window.storage = firebase.storage ? firebase.storage() : null;
    window.firebaseConfig = firebaseConfig; // Expose for secondary apps

    // --- DATABASE NAMESPACING: LAUSANNE (ROOT) ---
    // Lausanne operates on root collections (no prefix)
    window.DB_PREFIX = '';
    window.SITE_NAME = 'Catedral Lausanne';
    // No interception needed for root access
} else {
    console.error("Erro: SDK do Firebase não encontrado. Verifique os scripts no HTML.");
}
