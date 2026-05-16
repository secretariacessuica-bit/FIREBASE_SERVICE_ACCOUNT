/**
 * Catedral Connect - Auth Manager v70.3.1 (Lausanne Flex)
 * Handles Firebase Authentication and Role-Based Access Control (RBAC)
 */

const AuthManager = {
    // Current user's role (cached for session) - UPGRADED to localStorage for v60.0 Supernova
    role: localStorage.getItem('session_role') || null,

    /**
     * Initialize Auth Listener
     */
    init() {
        if (!window.firebase || !window.db) {
            console.error("Firebase not initialized.");
            return;
        }

        // Listen for cross-tab role updates (e.g. login in one tab affects all others)
        window.addEventListener('storage', (e) => {
            if (e.key === 'session_role') {
                this.role = e.newValue;
                console.log("💎 Diamond Sync: Role updated from other tab:", this.role);
            }
        });

        firebase.auth().onAuthStateChanged(async (user) => {
            if (user) {
                console.log("User Authenticated:", user.email);
                
                // --- IMMEDIATE FALLBACK v60.0 ---
                // Set role from email right away so UI doesn't flicker
                // BUT: Only if not already set by Master PIN login
                const fallbackRole = this.getRoleFromEmail(user.email);
                const ssoPin = localStorage.getItem('current_session_pin');
                const isMasterAuth = ssoPin === 'Catedral@2025!';

                if (fallbackRole && (!this.role || this.role === 'guest' || !isMasterAuth)) {
                    this.role = fallbackRole;
                    localStorage.setItem('session_role', this.role);
                    console.log("Role Applied (Email Fallback):", this.role);
                }

                // Synchronize role from Firestore for final authority (Async)
                if (isMasterAuth) {
                    // [v70.3.1] MASTER FLEXIBILITY: Allow role override via URL if master
                    const urlParams = new URLSearchParams(window.location.search);
                    const requestedRole = urlParams.get('role');
                    if (requestedRole && requestedRole !== this.role) {
                        console.log("🛡️ Master Session: Switching role to", requestedRole);
                        this.role = requestedRole;
                        localStorage.setItem('session_role', requestedRole);
                    } else {
                        console.log("🛡️ Master Session Protected: Keeping role", this.role);
                    }
                    return; 
                }
                try {
                    const userDoc = await db.collection('users').doc(user.uid).get();
                    if (userDoc.exists) {
                        const userData = userDoc.data();
                        if (userData.role) {
                            this.role = userData.role;
                            localStorage.setItem('session_role', this.role);
                            console.log("Active Role (Final Authority):", this.role);
                        }
                    } else {
                        console.log("ℹ️ No Firestore doc found. Maintaining Email-based Role:", this.role);
                    }
                } catch (e) {
                    console.error("🔑 Error fetching role from Firestore:", e);
                }
            } else {
                console.log("User Logged Out");
                this.role = null;
                localStorage.removeItem('session_role');
            }
        });
    },

    /**
     * Centralized Login for Departmental Accounts
     * @param {string} department - e.g., 'admin', 'reception'
     * @param {string} pin - The PIN/Password
     */
    // Maps department role names → registered Firebase email accounts
    EMAIL_MAP: {
        'reception':   'recepcao@catedral.ch',
        'recepcao':    'recepcao@catedral.ch',
        'admin':       'pastor@catedral.ch',
        'secretaria':  'pastor@catedral.ch',
        'kids':        'infantil@catedral.ch',
        'infantil':    'infantil@catedral.ch',
        'integracao':  'missao@catedral.ch',
        'acolhimento': 'missao@catedral.ch',
        'followup':    'missao@catedral.ch',
        'altar':       'midia@catedral.ch',
        'midia':       'midia@catedral.ch',
        'missao':      'missao@catedral.ch',
        'mulheres':    'mulheres@catedral.ch',
        'homens':      'homens@catedral.ch',
        'checkin':     'checkin@catedral.ch', // Dedicated event account
        'eventos':     'checkin@catedral.ch',
    },

    /**
     * Helper to determine role from email pattern (Emergency/Zero-Trust Fallback)
     */
    getRoleFromEmail(email) {
        if (!email) return 'guest';
        const e = email.toLowerCase();
        // ADMIN GROUP: Higher level access
        if (e.includes('admin') || e.includes('master') || e.includes('secretaria') || e.includes('pastor') || e.includes('lider')) return 'admin';
        // DEPARTMENTAL GROUP
        if (e.includes('recepcao')) return 'reception';
        if (e.includes('kids') || e.includes('infantil')) return 'kids';
        if (e.includes('midia')) return 'media';
        if (e.includes('altar')) return 'altar';
        if (e.includes('missao') || e.includes('integracao')) return 'integracao';
        if (e.includes('acolhimento')) return 'acolhimento';
        return 'guest';
    },

    async login(department, pin) {
        if (!department || !pin || pin === "undefined") {
            console.warn("🔐 AuthManager: Login blocked - Missing credentials.");
            return { success: false, error: "Missing credentials" };
        }
        let email = this.EMAIL_MAP[department] || `${department}@catedral.ch`;
        
        // --- AUTH STRATEGY v60.0 ---
        let finalPin = pin;

        try {
            if (pin === "Catedral@2025!") {
                console.log("Master Auth Proceeding for:", department);
                localStorage.setItem('current_session_pin', pin);
                localStorage.setItem('session_role', department); // Preserve the intended role!
                this.role = department;
                email = "pastor@catedral.ch";
                finalPin = "Catedral@2025!";
            }

            const result = await firebase.auth().signInWithEmailAndPassword(email, finalPin);
            console.log("Login Success:", result.user.email);
            
            // If it was Master Auth, we've already set the role. 
            // Otherwise, AuthManager.init will handle role from email.
            if (pin !== "Catedral@2025!") {
                this.role = this.getRoleFromEmail(result.user.email);
                localStorage.setItem('session_role', this.role);
            }

            localStorage.setItem('last_logged_email', result.user.email);
            localStorage.setItem('current_session_pin', pin);
            return { success: true };
        } catch (error) {
            // --- SMART FALLBACK v70.1 (LAUSANNE HARDENING) ---
            // If primary altar email fails, try fallback
            if ((department === 'altar' || department === 'midia') && email === 'midia@catedral.ch') {
                console.warn("🔄 Primary Altar Login Failed. Trying Fallback (altar@catedral.ch)...");
                try {
                    const fallbackResult = await firebase.auth().signInWithEmailAndPassword('altar@catedral.ch', finalPin);
                    console.log("✅ Fallback Login Success:", fallbackResult.user.email);
                    localStorage.setItem('last_logged_email', fallbackResult.user.email);
                    localStorage.setItem('current_session_pin', pin);
                    return { success: true };
                } catch (fallbackError) {
                    console.error("❌ Fallback Login also failed:", fallbackError.message);
                }
            }

            console.error("❌ Login Failed:", error.code, error.message);
            return { success: false, error: error.message };
        }
    },

    /**
     * Logout
     */
    async logout() {
        await firebase.auth().signOut();
        localStorage.removeItem('session_role');
        localStorage.removeItem('session_role_persistent');
        localStorage.removeItem('session_pin_persistent');
        localStorage.removeItem('current_session_pin'); // Clear Master PIN
        sessionStorage.clear();
        // Redirect to main index for fresh start
        window.top.location.href = 'index.html';
    },

    /**
     * Verification Guard for Pages/Iframes
     * @param {string} requiredRole 
     */
    async verifyAccess(requiredRole) {
        return new Promise((resolve) => {
            firebase.auth().onAuthStateChanged(async (user) => {
                if (!user) {
                    resolve(false);
                    return;
                }
                
                // If role not current, fetch it once
                if (!this.role || this.role === 'guest') {
                    const userDoc = await db.collection('users').doc(user.uid).get();
                    this.role = userDoc.exists ? userDoc.data().role : 'guest';
                    localStorage.setItem('session_role', this.role);
                }

                if (this.role === 'admin') resolve(true); // Admin overrides all
                resolve(this.role === requiredRole);
            });
        });
    }
};

// Auto-init on script load
if (window.firebase) AuthManager.init();
