function pageData() { return { darkMode: localStorage.getItem('darkMode') === 'true' || false, show: false, init() { this.updateDarkMode(); }, toggleDarkMode() { this.darkMode = !this.darkMode; this.updateDarkMode(); }, updateDarkMode() { if (this.darkMode) { document.documentElement.classList.add('dark'); localStorage.setItem('darkMode', 'true'); } else { document.documentElement.classList.remove('dark'); localStorage.setItem('darkMode', 'false'); } } } } function inviteForm() {
    return {
        formData: { fullname: '', username: '', password: '', csrf_token: '' },
        errors: {}, generalError: '', loading: false, show: false, validateForm() { this.errors = {}; let isValid = true; this.formData.csrf_token = this.getCsrfToken(); if (!this.formData.fullname.trim()) { this.errors.fullname = 'Le nom complet est requis'; isValid = false; } else if (this.formData.fullname.trim().length < 3) { this.errors.fullname = 'Le nom complet doit contenir au moins 3 caractères'; isValid = false; } if (!this.formData.username.trim()) { this.errors.username = "Le nom d'utilisateur est requis"; isValid = false; } else if (this.formData.username.trim().length < 3) { this.errors.username = "Le nom d'utilisateur doit contenir au moins 3 caractères"; isValid = false; } else if (!/^[a-zA-Z0-9_]+$/.test(this.formData.username)) { this.errors.username = "Le nom d'utilisateur ne peut contenir que des lettres, chiffres et underscores"; isValid = false; } if (!this.formData.password) { this.errors.password = 'Le mot de passe est requis'; isValid = false; } else if (this.formData.password.length < 8) { this.errors.password = 'Le mot de passe doit contenir au moins 8 caractères'; isValid = false; } return isValid; }, async submitForm() {
            this.generalError = ''; if (!this.validateForm()) { return; } this.loading = true; try {
                const response = await fetch('/invite/', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.formData)
                }); const result = await response.json();
                if (result.success) {
                    toast('Votre compte est créé avec succès');
                    setTimeout(function () {
                        window.location.href = window.location.href;
                    }, 5000);
                } else { this.errors = result.errors; this.generalError = result.message; }
            } catch (error) { this.generalError = 'Erreur de connexion au serveur'; } finally { this.loading = false; }
        }, getCsrfToken() { const metaTag = document.querySelector('meta[name="invite-csrf"]'); console.log(metaTag.getAttribute('content')); return metaTag ? metaTag.getAttribute('content') : ''; },
    }
}
function parseDuration(durationStr) {
    const parts = durationStr.match(/(\d+)\s*j|(\d+)\s*h|(\d+)\s*min|(\d+)\s*s/g); let days = 0, hours = 0, minutes = 0, seconds = 0; if (parts) { parts.forEach(part => { const value = parseInt(part); if (part.includes('j')) days = value; else if (part.includes('h')) hours = value; else if (part.includes('min')) minutes = value; else if (part.includes('s')) seconds = value; }); } return { days, hours, minutes, seconds };
} function formatDuration(days, hours, minutes, seconds) { return `${days} j ${hours} h ${minutes} min ${seconds} s`; } function decrementTime(time) { let { days, hours, minutes, seconds } = time; seconds--; if (seconds < 0) { seconds = 59; minutes--; if (minutes < 0) { minutes = 59; hours--; if (hours < 0) { hours = 23; days--; if (days < 0) { return { days: 0, hours: 0, minutes: 0, seconds: 0, finished: true }; } } } } return { days, hours, minutes, seconds, finished: false }; } const remainingSpan = document.getElementById('remaining'); const initialDuration = remainingSpan.textContent; let currentTime = parseDuration(initialDuration); setInterval(() => { currentTime = decrementTime(currentTime); if (currentTime.finished) { remainingSpan.textContent = "0 j 0 h 0 min 0 s"; setInterval(() => { window.location.href = window.location.href; }, 2000); return; } remainingSpan.textContent = formatDuration(currentTime.days, currentTime.hours, currentTime.minutes, currentTime.seconds); }, 1000);