// Firebase Configuration for CES Diaconia (Standalone SPA)
// Using project: catedral-connect-267b2 (diaconia)

var firebaseConfig = {
    apiKey: "AIzaSyAtwHODax7kq0keaLuON1ZxbNfdaBP7yfo",
    authDomain: "catedral-connect-267b2.firebaseapp.com",
    projectId: "catedral-connect-267b2",
    storageBucket: "catedral-connect-267b2.firebasestorage.app",
    messagingSenderId: "524359049819",
    appId: "1:524359049819:web:d4788e1c64767e0818557e"
};

// Initialize Firebase
if (typeof firebase !== 'undefined') {
    if (firebase.apps.length === 0) {
        firebase.initializeApp(firebaseConfig);
        console.log("Firebase inicializado para CES Diaconia!");
    } else {
        console.log("Firebase já inicializado.");
    }

    // Nota: Este app usa autenticação customizada por nome via Firestore.
    // Firebase Auth NÃO é utilizado — removido para evitar sessões fantasma no IndexedDB.

    window.db = firebase.firestore();
    window.firebaseConfig = firebaseConfig;

    // --- NAMESPACING DE DADOS ---
    // Todas as coleções do app serão prefixadas com 'diaconia_escala_'
    // para isolar os dados e não conflitar com outros projetos CES.
    window.DB_PREFIX = 'diaconia_escala_';
    const originalCollection = window.db.collection;
    window.db.collection = function(name) {
        const finalName = name.startsWith(window.DB_PREFIX) ? name : window.DB_PREFIX + name;
        return originalCollection.call(this, finalName);
    };
    console.log("Firestore Namespacing Ativo com prefixo: " + window.DB_PREFIX);
} else {
    console.error("SDK do Firebase não encontrado. Carregue os SDKs antes deste script.");
}
