document.addEventListener('DOMContentLoaded', () => {
    const contactForm = document.getElementById('contact-form');
    const toast = document.getElementById('toast');

    // Toast helper
    function showToast(message, type = 'success') {
        toast.textContent = message;
        toast.className = 'toast'; // reset class
        toast.classList.add('show');
        if (type === 'error') {
            toast.style.backgroundColor = '#ef4444';
        } else {
            toast.style.backgroundColor = '#10b981';
        }
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    // ─── UTM Tracking Logic ──────────────────────────────────────────────────
    // Parse UTM parameters from query string and store them in sessionStorage
    function extractAndStoreUTMs() {
        const urlParams = new URLSearchParams(window.location.search);
        const utms = ['utm_source', 'utm_medium', 'utm_campaign'];
        
        utms.forEach(param => {
            if (urlParams.has(param)) {
                sessionStorage.setItem(param, urlParams.get(param));
            }
        });

        // Capture referer if it's external and not yet stored
        if (document.referrer && !document.referrer.includes(window.location.hostname)) {
            if (!sessionStorage.getItem('referer_url')) {
                sessionStorage.setItem('referer_url', document.referrer);
            }
        }
    }

    // Inject UTM parameters from sessionStorage into form hidden fields
    function injectUTMsIntoForm() {
        if (!contactForm) return;

        const utms = ['utm_source', 'utm_medium', 'utm_campaign'];
        utms.forEach(param => {
            const storedValue = sessionStorage.getItem(param);
            const inputField = document.getElementById(param);
            if (storedValue && inputField) {
                inputField.value = storedValue;
            }
        });

        const refererField = document.getElementById('referer_url');
        const storedReferer = sessionStorage.getItem('referer_url') || document.referrer;
        if (storedReferer && refererField) {
            refererField.value = storedReferer;
        }
    }

    // Run tracking logic
    extractAndStoreUTMs();
    injectUTMsIntoForm();

    // ─── Global Navigation Logic ─────────────────────────────────────────────
    window.toggleMobileMenu = function() {
        const navLinks = document.getElementById('nav-links');
        if (navLinks) {
            navLinks.classList.toggle('mobile-active');
        }
    };

    window.toggleDropdown = function(event, id) {
        event.preventDefault();
        event.stopPropagation();
        const dropdown = document.getElementById(id);
        if (!dropdown) return;

        const isShowing = dropdown.style.display === 'block';
        
        // Close all dropdowns
        document.querySelectorAll('.dropdown-content').forEach(el => {
            el.style.display = 'none';
        });
        
        // Toggle clicked
        if (!isShowing) {
            dropdown.style.display = 'block';
        }
    };

    // Close the dropdown if the user clicks outside of it
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.nav-dropdown') && !event.target.closest('.lang-selector')) {
            document.querySelectorAll('.dropdown-content').forEach(el => {
                el.style.display = 'none';
            });
        }
    });

    // ─── Form Submission Logic ───────────────────────────────────────────────
    if (contactForm) {
        contactForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Collect form data
            const formData = new FormData(contactForm);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            // Submit via AJAX
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Envoi en cours...';

            fetch('/api/v1/leads/leads.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    showToast(resData.message || 'Votre demande a été enregistrée avec succès !', 'success');
                    contactForm.reset();
                    // Re-inject UTMs for future submissions if needed
                    injectUTMsIntoForm();
                } else {
                    showToast(resData.message || 'Une erreur est survenue lors de la soumission.', 'error');
                }
            })
            .catch(err => {
                showToast('Erreur réseau. Veuillez réessayer.', 'error');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalBtnText;
            });
        });
    }
});
