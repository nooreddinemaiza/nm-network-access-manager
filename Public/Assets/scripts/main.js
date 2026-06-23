// Gestion du thème
const themeToggle = document.getElementById('theme-toggle');
themeToggle?.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
});

function toast(message, status = 'info') {
    const colors = {
        success: 'bg-emerald-500 border-emerald-400',
        error: 'bg-red-500 border-red-400',
        warning: 'bg-yellow-400 text-black border-yellow-300',
        info: 'bg-blue-500 border-blue-400'
    };

    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };

    const bgColor = colors[status] || colors.info;
    const icon = icons[status] || icons.info;

    const toast = document.createElement('div');
    toast.className = `${bgColor} text-white px-6 py-4 rounded-2xl shadow-xl transition-all transform flex items-center space-x-3 border backdrop-blur-sm`;

    toast.innerHTML = `
        <span class="text-lg font-bold">${icon}</span>
        <span class="font-medium">${message}</span>
    `;

    const container = document.getElementById('toast-container');
    if (!container) {
        // Créer le container s'il n'existe pas
        const toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'fixed top-4 right-4 z-[100] space-y-3';
        document.body.appendChild(toastContainer);
    }

    document.getElementById('toast-container').appendChild(toast);

    // Animation d'entrée
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100px) scale(0.8)';

    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0) scale(1)';
    }, 10);

    // Animation de sortie et suppression
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px) scale(0.8)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 4000);
}