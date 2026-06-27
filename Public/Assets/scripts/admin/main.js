var stopCheck = false;
function sessionExpired() {
    stopCheck = true;
    document.body.innerHTML = `
        <div class="fixed inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-900 z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 flex flex-col items-center">
                <span class="text-2xl font-bold text-red-600 mb-4">Session terminée</span>
                <p class="mb-6 text-gray-700 dark:text-gray-200">Votre session a expiré. Veuillez vous reconnecter.</p>
                <a href="/dashboard/login" class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Se connecter</a>
            </div>
        </div>
    `;
    document.title = "Session Terminée";
}
function serverClock() {
    return {
        localTimeOffset: 0,
        synced: false,

        date: '',
        time: '',

        async init() {
            this.syncWithServer();
            this.updateDisplay();
            this.startClock();
            this.startSyncTimer();
        },

        async syncWithServer() {
            try {
                const response = await fetch('/helper/time');
                const result = await response.json();
                if (result.success) {
                    const serverDate = new Date(result.time.replace(' ', 'T'));
                    const localDate = new Date();

                    this.localTimeOffset = serverDate.getTime() - localDate.getTime();
                    this.synced = true;
                } else {
                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (e) {
                this.synced = false;
            }
        },

        startClock() {
            setInterval(() => {
                this.updateDisplay();
            }, 1000);
        },

        startSyncTimer() {
            setInterval(() => {
                this.syncWithServer();
            }, 5 * 60 * 1000); // 5 minutes
        },

        updateDisplay() {
            const now = new Date(Date.now() + this.localTimeOffset);

            const days = [
                'Dimanche', 'Lundi', 'Mardi',
                'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'
            ];

            const months = [
                'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'
            ];

            this.date = `${days[now.getDay()]} ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;

            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            const s = String(now.getSeconds()).padStart(2, '0');

            this.time = `${h}:${m}:${s}`;
        }
    }
}
function toast(message, status = 'info') {
    if (stopCheck) {
        return;
    }
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-400 text-black',
        info: 'bg-blue-500'
    }

    const bgColor = colors[status] || colors.info

    const toast = document.createElement('div')
    toast.className = `${bgColor} text-white px-4 py-3 rounded shadow-lg transition transform`
    toast.textContent = message

    document.getElementById('toaster').appendChild(toast)

    toast.style.opacity = 0
    toast.style.transform = 'translateY(10px)'
    setTimeout(() => {
        toast.style.opacity = 1
        toast.style.transform = 'translateY(0)'
    }, 10)

    setTimeout(() => {
        toast.style.opacity = 0
        toast.style.transform = 'translateY(10px)'
        setTimeout(() => {
            toast.remove()
        }, 300)
    }, 3000)
}
function confirmToast(message) {
    return new Promise((resolve) => {
        // Création de l'overlay avec blur
        const overlay = document.createElement('div')
        overlay.className = 'fixed inset-0 z-50 bg-black/20 dark:bg-black/40 backdrop-blur-sm transition-opacity duration-300'
        overlay.style.opacity = '0'

        // Création du toast
        const toast = document.createElement('div')
        toast.className = 'fixed bottom-6 right-6 z-100 bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-sm border border-gray-200 dark:border-gray-700 backdrop-blur-sm'
        toast.style.opacity = '0'
        toast.style.transform = 'translateY(20px) scale(0.95)'
        toast.style.transition = 'all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)'

        toast.innerHTML = `
            <div class="p-5">
                <div class="flex items-start gap-3 mb-4">
                    <div class="shrink-0 w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 leading-relaxed">${message}</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <button id="cancelBtn" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors duration-200">
                        Non
                    </button>
                    <button id="okBtn" class="px-4 py-2 text-sm font-medium text-white bg-linear-to-r from-blue-600 to-blue-700 dark:from-blue-500 dark:to-blue-600 hover:from-blue-700 hover:to-blue-800 dark:hover:from-blue-600 dark:hover:to-blue-700 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                        Oui
                    </button>
                </div>
            </div>
        `

        // Ajout à la page
        document.body.appendChild(overlay)
        document.body.appendChild(toast)

        // Animation d'entrée
        setTimeout(() => {
            overlay.style.opacity = '1'
            toast.style.opacity = '1'
            toast.style.transform = 'translateY(0) scale(1)'
        }, 10)

        const cleanup = (result) => {
            overlay.style.opacity = '0'
            toast.style.opacity = '0'
            toast.style.transform = 'translateY(20px) scale(0.95)'
            setTimeout(() => {
                if (overlay.parentNode) {
                    document.body.removeChild(overlay)
                }
                if (toast.parentNode) {
                    document.body.removeChild(toast)
                }
                document.removeEventListener('keydown', handleEscape)
            }, 400)
            resolve(result)
        }

        // Clic sur l'overlay ferme le toast (retourne false)
        overlay.addEventListener('click', () => {
            cleanup(false)
        })

        // Empêcher la propagation du clic sur le toast
        toast.addEventListener('click', (e) => {
            e.stopPropagation()
        })

        document.getElementById('okBtn').addEventListener('click', () => {
            cleanup(true)
        })

        document.getElementById('cancelBtn').addEventListener('click', () => {
            cleanup(false)
        })

        // Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                cleanup(false)
            }
        }

        document.addEventListener('keydown', handleEscape)
    })
}
function profileManager() {
    return {
        profileView: false,
        openEdit: false,
        openVerify: false,
        openReset: false,

        loading: false,
        editLoading: false,
        verifyLoading: false,
        resetLoading: false,
        errors: {},
        showNewPassword: false,
        showConfirmPassword: false,

        userData: {
            image_path: '/Assets/images/user-default.png'
        },

        editForm: {
            fullname: '',
            email: '',
            username: '',
            phone: ''
        },

        resetForm: {
            password: '',
            password_confirmation: ''
        },

        resetMessage: {
            show: false,
            type: '',
            text: ''
        },
        editMessage: {
            show: false,
            type: '',
            text: ''
        },
        verifyMessage: {
            show: false,
            type: '',
            text: ''
        },
        resetMessage: {
            show: false,
            type: '',
            text: ''
        },
        async loadUserData() {
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('csrf_token', this.getCsrfToken());

                const response = await fetch('/dashboard/profile/data', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const result = await response.json();
                if (result.success) {
                    this.userData = result.data || result;
                    this.setupEditForm();
                } else {
                    if (result.status == 'session_timed_out') {
                        sessionExpired();
                    }
                    this.error = result.message || 'Erreur lors du chargement des données';
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                this.loading = false;
            }
        },

        setupEditForm() {
            if (this.userData) {
                this.editForm = {
                    fullname: this.userData.fullname || '',
                    email: this.userData.email || '',
                    username: this.userData.username || '',
                };
            }
        },
        async openProfile() {
            this.profileView = true;
            await this.loadUserData();
        },

        openEditModal() {
            this.openEdit = true;
            this.setupEditForm();
            this.clearMessage('edit');
        },

        openVerifyModal() {
            this.openVerify = true;
            this.clearMessage('verify');
        },
        // Ouvrir le modal de réinitialisation
        openResetModal() {
            this.openReset = true;
            this.resetForm = {
                password: '',
                password_confirmation: ''
            };
            this.showNewPassword = false;
            this.showConfirmPassword = false;
            this.clearMessage('reset');
        },


        closeAllModals() {
            this.openEdit = false;
            this.openVerify = false;
            this.openReset = false;
        },

        closeProfile() {
            this.profileView = false;
        },
        closeEditModal() {
            this.openEdit = false;
        },

        closeVerifyModal() {
            this.openVerify = false;
            this.clearMessage('verify');
        },

        closeResetModal() {
            this.openReset = false;
            this.resetForm = {
                password: '',
                password_confirmation: ''
            };
            this.showNewPassword = false;
            this.showConfirmPassword = false;
            this.clearMessage('reset');
        },

        async submitReset() {
            this.resetLoading = true;
            this.clearMessage('reset');

            const errors = [];
            const password = this.resetForm.password?.trim();
            const passwordConfirmation = this.resetForm.password_confirmation?.trim();

            // Validations
            if (!password) {
                errors.push("Le mot de passe est requis.");
            } else if (password.length < 8) {
                errors.push("Le mot de passe doit contenir au moins 8 caractères.");
            }

            if (!passwordConfirmation) {
                errors.push("La confirmation du mot de passe est requise.");
            }

            if (password && passwordConfirmation && password !== passwordConfirmation) {
                errors.push("Les mots de passe ne correspondent pas.");
            }

            // Si des erreurs existent, les afficher
            if (errors.length > 0) {
                this.showMessage('reset', 'error', errors.join('<br>'));
                this.resetLoading = false;
                return;
            }

            // Préparer les données
            const formData = new FormData();
            formData.append('password', password);
            formData.append('password_confirmation', passwordConfirmation);
            formData.append('csrf_token', this.getCsrfToken());

            try {
                const response = await fetch('/dashboard/profile/reset-password', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                const message = Array.isArray(result.message) ?
                    result.message.join('<br>') :
                    result.message || (result.success ?
                        'Mot de passe réinitialisé avec succès !' :
                        'Une erreur est survenue');

                if (result.success) {
                    this.showMessage('reset', 'success', message);
                    // Fermer le modal après 2 secondes
                    setTimeout(() => {
                        this.closeResetModal();
                    }, 2000);
                } else {
                    if (result.status === 'session_timed_out') {
                        sessionExpired();
                    }
                    this.showMessage('reset', 'error', message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showMessage('reset', 'error', 'Erreur de connexion au serveur. Veuillez réessayer.');
            } finally {
                this.resetLoading = false;
            }
        },

        async submitEdit() {
            this.editLoading = true;

            const errors = [];
            const fullname = this.editForm.fullname?.trim();
            const email = this.editForm.email?.trim();
            const username = this.editForm.username?.trim();


            if (!fullname) errors.push("Le nom complet est requis.");
            if (!email) errors.push("L'adresse email est requise.");

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email && !emailRegex.test(email)) {
                errors.push("L'adresse email n'est pas valide.");
            }
            if (username && !/^[a-zA-Z0-9_]+$/.test(username)) {
                errors.push("Le nom d'utilisateur est invalide! (user_name123).");
            }

            if (errors.length > 0) {
                this.showMessage('edit', 'error', errors.join('<br>'));
                this.editLoading = false;
                return;
            }

            const hasChanges =
                fullname !== this.userData.fullname ||
                email !== this.userData.email ||
                username !== this.userData.username;

            if (!hasChanges) {
                this.showMessage('edit', 'info', "Aucun changement détecté.");
                this.editLoading = false;
                return;
            }

            const formData = new FormData();
            if (fullname !== this.userData.fullname) formData.append('fullname', fullname);
            if (email !== this.userData.email) formData.append('email', email);
            if (username !== this.userData.username && username) formData.append('username', username);

            if ([...formData.keys()].length) {
                formData.append('csrf_token', this.getCsrfToken());

                try {
                    const response = await fetch('/dashboard/profile/edit', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    const message = result.message;

                    if (result.success) {
                        this.profileView = false;
                        this.showMessage('edit', 'success', message);
                        setTimeout(() => this.closeAllModals(), 2000);
                    } else {
                        if (result.errors) {
                            this.errors = result.errors;
                        }
                        this.showMessage('edit', 'error', message);
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    this.showMessage('edit', 'error', 'Erreur de connexion. Veuillez réessayer.');
                } finally {
                    this.editLoading = false;
                }
            } else {
                this.showMessage('edit', 'info', "Aucun changement détecté.");
                this.editLoading = false;
            }
        },
        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showMessage(type, messageType, text) {
            const messageObj = this[`${type}Message`];
            messageObj.show = true;
            messageObj.type = messageType;
            messageObj.text = text;

            if (messageType === 'success') {
                setTimeout(() => {
                    this.clearMessage(type);
                }, 5000);
            }
        },

        clearMessage(type) {
            const messageObj = this[`${type}Message`];
            messageObj.show = false;
            messageObj.type = '';
            messageObj.text = '';
        },

        clearAllMessages() {
            this.clearMessage('edit');
            this.clearMessage('verify');
            this.clearMessage('reset');
        },

        init() {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });
        }
    }
}
function usersModal2() {
    return {
        // État
        isOpen: false,
        loading: false,
        detailsModalOpen: false,
        showGlobalUsers: false,
        showFilters: false,
        dateMode: 'picker',
        dateFrom: '',
        dateTo: '',
        searchDebounce: null,
        meta: { total: 0, page: 1, per_page: 25, total_pages: 1 },


        detailsModalOpen: false,
        editModalOpen: false,
        editLoading: false,


        // Données
        users: [],
        groups: [],

        // Formulaire d'édition
        editForm: {
            id: null,
            fullname: '',
            username: '',
            password: '',
            expires_at: '',
            status: 'active'
        },
        originalEditForm: {},

        errors: {},

        // Données
        users: [],
        filteredUsers: [],
        selectedUser: null,

        // Filtres
        searchTerm: '',
        selectedGroup: 'all',
        selectedStatus: 'all',
        onlineFilter: 'all',
        selectedYear: 'all',
        selectedMonth: 'all',
        selectedDay: 'all',
        dateFrom: '',
        dateTo: '',
        // Tri
        sortBy: 'id',
        sortOrder: 'desc',

        // Pagination
        currentPage: 1,
        itemsPerPage: 10,

        // users globales
        globalusers: {
            total_users: 0,
            online_users: 0,
            total_sessions: 0,
            total_time: 0,
            avg_time_per_user: 0,
            total_download: 0,
            total_upload: 0,
            total_consumption: 0
        },
        groups: [],
        // Computed
        get totalPages() { return this.meta.total_pages; },
        get startIndex() { return (this.meta.page - 1) * this.meta.per_page; },
        get endIndex() { return Math.min(this.startIndex + this.meta.per_page, this.meta.total); },
        get paginatedUsers() { return this.users; },

        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        get uniqueGroups() {
            return this.groups;
        },

        // Initialisation
        init() {
            window.addEventListener('open-users-modal2', () => {
                this.openModal();
            });
        },

        async openModal() {
            this.isOpen = true;
            this.resetFilters();
        },

        closeModal() {
            this.isOpen = false;
        },
        // Calculer le temps restant avant expiration
        getTimeRemaining(expiresAt) {
            if (!expiresAt) return { text: 'Non défini', color: 'text-gray-600 dark:text-gray-400', icon: '' };

            const now = new Date();
            const expiry = new Date(expiresAt);
            const diff = expiry - now;

            if (diff <= 0) {
                return {
                    text: 'Expiré',
                    color: 'text-red-600 dark:text-red-400 font-bold',
                    icon: '⚠️'
                };
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            let text = '';
            let color = '';
            let icon = '';

            if (days > 30) {
                text = `${days}j`;
                color = 'text-green-600 dark:text-green-400';
                icon = '✓';
            } else if (days > 7) {
                text = `${days}j`;
                color = 'text-yellow-600 dark:text-yellow-400';
                icon = '●';
            } else if (days > 2) {
                text = `${days}j ${hours}h`;
                color = 'text-orange-600 dark:text-orange-400 font-semibold';
                icon = '⚡';
            } else if (days > 0) {
                text = `${days}j ${hours}h`;
                color = 'text-red-600 dark:text-red-400 font-bold';
                icon = '🔥';
            } else if (hours > 0) {
                text = `${hours}h ${minutes}min`;
                color = 'text-red-700 dark:text-red-500 font-bold';
                icon = '🚨';
            } else {
                text = `${minutes}min`;
                color = 'text-red-800 dark:text-red-600 font-bold animate-pulse';
                icon = '⏰';
            }

            return { text, color, icon };
        },
        async loadUsers(page = 1) {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/users/listed', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        page: page,
                        per_page: this.itemsPerPage,
                        search: this.searchTerm.length >= 3 ? this.searchTerm : '',
                        status: this.selectedStatus,
                        online: this.onlineFilter,
                        group: this.selectedGroup,
                        sort_by: this.sortBy,
                        sort_order: this.sortOrder,
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.users = (result.data || []).map(user => ({
                        ...user,
                        total_time: parseInt(user.total_time) || 0,
                        avg_session_time: parseFloat(user.avg_session_time) || 0,
                        total_sessions: parseInt(user.total_sessions) || 0,
                        unique_devices: parseInt(user.unique_devices) || 0,
                        active_sessions: parseInt(user.active_sessions) || 0,
                        current_session_duration: parseInt(user.current_session_duration) || 0,
                        total_download: parseInt(user.total_download) || 0,
                        total_upload: parseInt(user.total_upload) || 0,
                        total_consumption: parseInt(user.total_consumption) || 0,
                    }));

                    this.meta = result.meta;
                    this.groups = result.groups;
                    this.currentPage = result.meta.page;
                    this.filteredUsers = this.users;
                    this.calculateGlobalusers();
                } else {
                    this.showError(result.message || 'Erreur lors du chargement');
                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },
        // Changer le groupe d'un utilisateur
        async changeUserGroup(user, newGroupId, event) {
            const oldGroupId = user.group_id;
            const oldGroup = user.group;

            if (oldGroupId == newGroupId) {
                return;
            }

            // Trouver le nom du nouveau groupe
            const newGroupObj = this.groups.find(g => g.id == newGroupId);
            const newGroupName = newGroupObj ? newGroupObj.name : '-Sans-';

            // Confirmation
            let confirmMessage = `Voulez-vous vraiment changer le groupe de ${user.username}?`;
            if (newGroupId == -1) {
                confirmMessage = `Voulez-vous retirer l'utilisateur "${user.username}" de tous les groupes ?`;
            }
            const confirmed = await confirmToast(confirmMessage);

            if (!confirmed) {
                if (event && event.target) {
                    event.target.value = oldGroupId;
                }
                return;
            }

            // Mise à jour optimiste
            user.group_id = newGroupId;
            user.group = newGroupName;

            try {
                const response = await fetch('/dashboard/managers/switch-user-group', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        user_id: user.id,
                        group: newGroupId
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(`Le groupe de ${user.username} a été changé!`);
                    await this.loadUsers();
                } else {
                    // Restaurer les anciennes valeurs
                    user.group_id = oldGroupId;
                    user.group = oldGroup;
                    if (event && event.target) {
                        event.target.value = oldGroupId;
                    }

                    this.showError(result.message || 'Erreur lors du changement de groupe');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                // Restaurer les anciennes valeurs
                user.group_id = oldGroupId;
                user.group = oldGroup;
                if (event && event.target) {
                    event.target.value = oldGroupId;
                }

                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            }
        },

        // Toggle status
        async toggleUserStatus(user, newStatus, event) {
            const oldStatus = user.status;
            const oldLabelStatus = user.labelStatus;

            if (oldStatus === newStatus) {
                return;
            }

            const statusMapping = {
                'suspended': 'Suspendu',
                'expired': 'Expiré',
                'active': 'Actif',
            };

            user.status = newStatus;
            user.labelStatus = statusMapping[newStatus];

            try {
                const response = await fetch('/dashboard/users/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        user_id: user.id,
                        status: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    const successMessages = {
                        'active': `L'utilisateur ${user.username} est maintenant actif`,
                        'suspended': `L'utilisateur ${user.username} a été suspendu`,
                        'expired': `L'utilisateur ${user.username} est marqué comme expiré`
                    };

                    this.showSuccess(successMessages[newStatus] || 'Statut mis à jour avec succès');
                } else {
                    user.status = oldStatus;
                    user.labelStatus = oldLabelStatus;
                    if (event && event.target) {
                        event.target.value = oldStatus;
                    }

                    this.showError(result.message || 'Erreur lors de la mise à jour du statut');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                user.status = oldStatus;
                user.labelStatus = oldLabelStatus;
                if (event && event.target) {
                    event.target.value = oldStatus;
                }

                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            }
        },

        // Mettre à jour la date d'expiration
        async updateExpiryDate(user, newDate, closeCallback) {
            if (!newDate) {
                this.showError('Veuillez sélectionner une date');
                return;
            }

            const selectedDate = new Date(newDate);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                this.showError('La date d\'expiration doit être dans le futur');
                return;
            }

            const oldExpiryDate = user.expires_at;
            user.expires_at = newDate;

            try {
                const response = await fetch('/dashboard/users/update-expiry', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        user_id: user.id,
                        expires_at: newDate
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(`Date d'expiration mise à jour pour ${user.username}`);

                    if (closeCallback) {
                        closeCallback();
                    }

                    await this.loadUsers();
                } else {
                    user.expires_at = oldExpiryDate;
                    this.showError(result.message || 'Erreur lors de la mise à jour de la date');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                user.expires_at = oldExpiryDate;
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            }
        },

        // Voir les détails d'un utilisateur
        viewUser(user) {
            this.selectedUser = user;
            this.detailsModalOpen = true;
        },

        // Modifier un utilisateur
        editUser(user) {
            this.editForm = {
                id: user.id,
                fullname: user.fullname || '',
                username: user.username,
                password: '',
                expires_at: this.formatDateForInput(user.expires_at),
                status: user.status
            };

            this.originalEditForm = { ...this.editForm };
            this.editModalOpen = true;
        },
        async disconnect(user_id) {
            try {
                const response = await fetch('/dashboard/users/disconnect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        user_id: user_id
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('Utilisateur déconnecté avec succès');
                } else {
                    if (result.errors) {
                        this.errors = result.errors;
                    }
                    this.showError(result.message || 'Erreur lors de la déconnexion');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.editLoading = false;
            }
        },
        // Sauvegarder les modifications
        async saveUserEdit() {
            // Détecter les changements
            const changes = {};
            let hasChanges = false;

            if (this.editForm.fullname !== this.originalEditForm.fullname) {
                changes.fullname = this.editForm.fullname;
                hasChanges = true;
            }

            if (this.editForm.username !== this.originalEditForm.username) {
                changes.username = this.editForm.username;
                hasChanges = true;
            }

            if (this.editForm.password.trim() !== '') {
                changes.password = this.editForm.password;
                hasChanges = true;
            }

            if (this.editForm.expires_at !== this.originalEditForm.expires_at) {
                changes.expires_at = this.editForm.expires_at;
                hasChanges = true;
            }

            if (this.editForm.status !== this.originalEditForm.status) {
                changes.status = this.editForm.status;
                hasChanges = true;
            }

            if (!hasChanges) {
                this.showError('Aucune modification apportée');
                return;
            }

            this.editLoading = true;

            try {
                const response = await fetch('/dashboard/users/edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        user_id: this.editForm.id,
                        ...changes
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('Utilisateur modifié avec succès');
                    this.editModalOpen = false;
                    await this.loadUsers();
                } else {
                    if (result.errors) {
                        this.errors = result.errors;
                    }
                    this.showError(result.message || 'Erreur lors de la modification');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.editLoading = false;
            }
        },

        openAddUserModal() {
            Alpine.store('adduserModal').open();
        },

        // Utilitaires pour les dates
        getTodayDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        formatDateForInput(dateString) {
            if (!dateString) return this.getTodayDate();
            try {
                const date = new Date(dateString);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            } catch (error) {
                return this.getTodayDate();
            }
        },

        formatExpiryDate(dateString) {
            if (!dateString) return 'Non défini';

            try {
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = date - now;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                if (diffDays < 0) {
                    return 'Expiré';
                } else if (diffDays === 0) {
                    return 'Expire aujourd\'hui';
                } else if (diffDays === 1) {
                    return 'Expire demain';
                } else if (diffDays <= 7) {
                    return `Expire dans ${diffDays}j`;
                } else {
                    return date.toLocaleDateString('fr-FR', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                }
            } catch (error) {
                return dateString;
            }
        },

        isExpiringSoon(dateString) {
            if (!dateString) return false;

            try {
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = date - now;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                return diffDays >= 0 && diffDays <= 7;
            } catch (error) {
                return false;
            }
        },

        // Utilitaires généraux
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        },

        getInitials(name) {
            if (!name) return '?';
            return name.substring(0, 2).toUpperCase();
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },
        calculateGlobalusers() {
            this.globalusers.total_users = this.meta.total;
            this.globalusers.online_users = this.users.filter(s => s.is_online).length;
            this.globalusers.total_sessions = this.users.reduce((sum, s) => sum + s.total_sessions, 0);
            this.globalusers.total_time = this.users.reduce((sum, s) => sum + s.total_time, 0);
            this.globalusers.total_download = this.users.reduce((sum, s) => sum + s.total_download, 0);
            this.globalusers.total_upload = this.users.reduce((sum, s) => sum + s.total_upload, 0);
            this.globalusers.total_consumption = this.users.reduce((sum, s) => sum + s.total_consumption, 0);
            this.globalusers.avg_time_per_user = this.globalusers.total_users > 0
                ? this.globalusers.total_time / this.globalusers.total_users
                : 0;
        },

        applyFilters() {
            this.loadUsers(1);
        },
        onSearchInput() {
            clearTimeout(this.searchDebounce);
            if (this.searchTerm.length > 0 && this.searchTerm.length < 3) return; // attendre 3 chars
            this.searchDebounce = setTimeout(() => this.loadUsers(1), 400);
        },
        resetFilters() {
            this.searchTerm = '';
            this.selectedGroup = 'all';
            this.selectedStatus = 'all';
            this.onlineFilter = 'all';
            this.sortBy = 'total_time';
            this.sortOrder = 'desc';
            this.currentPage = 1;
            this.selectedYear = 'all';
            this.selectedMonth = 'all';
            this.selectedDay = 'all';
            this.dateFrom = '';
            this.dateTo = ''; this.dateMode = 'picker';
            this.dateFrom = '';
            this.dateTo = '';
            this.applyFilters();
        },
        // Nouvelles méthodes
        setQuickRange(days) {
            const today = new Date();
            const from = new Date();
            from.setDate(today.getDate() - (days - 1));

            this.dateTo = today.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.applyFilters();
        },

        isQuickRange(days) {
            if (!this.dateFrom || !this.dateTo) return false;
            const today = new Date().toISOString().split('T')[0];
            const from = new Date();
            from.setDate(new Date().getDate() - (days - 1));
            return this.dateTo === today && this.dateFrom === from.toISOString().split('T')[0];
        },
        onMonthChange() {
            this.selectedDay = 'all';
            this.applyFilters();
        },
        get availableYears() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let y = currentYear; y >= currentYear - 4; y--) {
                years.push(y);
            }
            return years;
        },
        changeSortBy(field) {
            if (this.sortBy === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortOrder = 'desc';
            }
            this.applyFilters();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.meta.total_pages) {
                this.loadUsers(page);
            }
        },

        viewDetails(stat) {
            this.selectedUser = stat;
            this.detailsModalOpen = true;
        },

        // Formatage
        formatDuration(seconds) {
            if (!seconds || seconds === 0) return '0s';

            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            const parts = [];
            if (hours > 0) parts.push(`${hours}h`);
            if (minutes > 0) parts.push(`${minutes}m`);
            if (secs > 0 || parts.length === 0) parts.push(`${secs}s`);

            return parts.join(' ');
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';

            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        },

        formatRelativeDate(dateString) {
            if (!dateString) return '-';

            try {
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                const minutes = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);

                if (minutes < 1) return 'À l\'instant';
                if (minutes < 60) return `Il y a ${minutes}min`;
                if (hours < 24) return `Il y a ${hours}h`;
                if (days < 7) return `Il y a ${days}j`;

                return this.formatDate(dateString);
            } catch (error) {
                return dateString;
            }
        },

        getInitials(name) {
            if (!name) return '?';
            return name.substring(0, 2).toUpperCase();
        },

        getStatusColor(status) {
            const colors = {
                'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'suspended': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'expired': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
            };
            return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
        },

        getTimeColor(seconds) {
            if (seconds > 7200) return 'text-green-600 dark:text-green-400 font-semibold';
            if (seconds > 3600) return 'text-blue-600 dark:text-blue-400';
            if (seconds > 1800) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        getConsumptionColor(bytes) {
            const gb = bytes / (1024 * 1024 * 1024);
            if (gb > 10) return 'text-red-600 dark:text-red-400 font-semibold';
            if (gb > 5) return 'text-orange-600 dark:text-orange-400';
            if (gb > 1) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        // Utilitaires
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },

    };
}
function userStatsModal() {
    return {
        // État
        isOpen: false,
        loading: false,
        detailsModalOpen: false,
        showGlobalStats: false,
        showFilters: false,
        dateMode: 'picker',
        dateFrom: '',
        dateTo: '',
        searchDebounce: null,
        meta: { total: 0, page: 1, per_page: 25, total_pages: 1 },

        // Données
        stats: [],
        filteredStats: [],
        selectedUser: null,

        // Filtres
        searchTerm: '',
        selectedGroup: 'all',
        selectedStatus: 'all',
        onlineFilter: 'all',
        selectedYear: 'all',
        selectedMonth: 'all',
        selectedDay: 'all',
        dateFrom: '',
        dateTo: '',
        // Tri
        sortBy: 'total_time',
        sortOrder: 'desc',

        // Pagination
        currentPage: 1,
        itemsPerPage: 10,

        // Stats globales
        globalStats: {
            total_users: 0,
            online_users: 0,
            total_sessions: 0,
            total_time: 0,
            avg_time_per_user: 0,
            total_download: 0,
            total_upload: 0,
            total_consumption: 0
        },
        groups: [],
        // Computed
        get totalPages() { return this.meta.total_pages; },
        get startIndex() { return (this.meta.page - 1) * this.meta.per_page; },
        get endIndex() { return Math.min(this.startIndex + this.meta.per_page, this.meta.total); },
        get paginatedStats() { return this.stats; },

        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        get uniqueGroups() {
            return this.groups;
        },
        get availableDays() {
            // Le filtre jour nécessite qu'un mois soit sélectionné
            if (this.selectedMonth === 'all') return [];

            const year = this.selectedYear !== 'all' ? parseInt(this.selectedYear) : new Date().getFullYear();
            const month = parseInt(this.selectedMonth);
            const daysInMonth = new Date(year, month, 0).getDate(); // 0 = dernier jour du mois précédent

            const days = [];
            for (let d = 1; d <= daysInMonth; d++) {
                days.push(d);
            }
            return days;
        },

        // Initialisation
        init() {
            window.addEventListener('open-user-stats-modal', () => {
                this.openModal();
            });
        },
        async openModal() {
            this.isOpen = true;
            await this.loadStats();
        },

        closeModal() {
            this.isOpen = false;
            this.resetFilters();
        },

        async loadStats(page = 1) {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/stats/list', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        page: page,
                        per_page: this.itemsPerPage,
                        search: this.searchTerm.length >= 3 ? this.searchTerm : '',
                        status: this.selectedStatus,
                        online: this.onlineFilter,
                        group: this.selectedGroup,
                        sort_by: this.sortBy,
                        sort_order: this.sortOrder,
                        year: this.selectedYear,
                        month: this.selectedMonth, day: this.selectedDay,
                        date_from: this.dateMode === 'range' ? this.dateFrom : '',
                        date_to: this.dateMode === 'range' ? this.dateTo : '',
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.stats = (result.data || []).map(stat => ({
                        ...stat,
                        total_time: parseInt(stat.total_time) || 0,
                        avg_session_time: parseFloat(stat.avg_session_time) || 0,
                        total_sessions: parseInt(stat.total_sessions) || 0,
                        unique_devices: parseInt(stat.unique_devices) || 0,
                        active_sessions: parseInt(stat.active_sessions) || 0,
                        current_session_duration: parseInt(stat.current_session_duration) || 0,
                        total_download: parseInt(stat.total_download) || 0,
                        total_upload: parseInt(stat.total_upload) || 0,
                        total_consumption: parseInt(stat.total_consumption) || 0,
                    }));

                    this.meta = result.meta;
                    this.groups = result.groups;
                    this.currentPage = result.meta.page;
                    this.filteredStats = this.stats;
                    this.calculateGlobalStats();
                } else {
                    this.showError(result.message || 'Erreur lors du chargement');
                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },

        calculateGlobalStats() {
            this.globalStats.total_users = this.stats.length;
            this.globalStats.online_users = this.stats.filter(s => s.is_online).length;
            this.globalStats.total_sessions = this.stats.reduce((sum, s) => sum + s.total_sessions, 0);
            this.globalStats.total_time = this.stats.reduce((sum, s) => sum + s.total_time, 0);
            this.globalStats.total_download = this.stats.reduce((sum, s) => sum + s.total_download, 0);
            this.globalStats.total_upload = this.stats.reduce((sum, s) => sum + s.total_upload, 0);
            this.globalStats.total_consumption = this.stats.reduce((sum, s) => sum + s.total_consumption, 0);
            this.globalStats.avg_time_per_user = this.globalStats.total_users > 0
                ? this.globalStats.total_time / this.globalStats.total_users
                : 0;
        },

        applyFilters() {
            this.loadStats(1);
        },
        onSearchInput() {
            clearTimeout(this.searchDebounce);
            if (this.searchTerm.length > 0 && this.searchTerm.length < 3) return; // attendre 3 chars
            this.searchDebounce = setTimeout(() => this.loadStats(1), 400);
        },
        resetFilters() {
            this.searchTerm = '';
            this.selectedGroup = 'all';
            this.selectedStatus = 'all';
            this.onlineFilter = 'all';
            this.sortBy = 'total_time';
            this.sortOrder = 'desc';
            this.currentPage = 1;
            this.selectedYear = 'all';
            this.selectedMonth = 'all';
            this.selectedDay = 'all';
            this.dateFrom = '';
            this.dateTo = ''; this.dateMode = 'picker';
            this.dateFrom = '';
            this.dateTo = '';
            this.applyFilters();
        },
        // Nouvelles méthodes
        setQuickRange(days) {
            const today = new Date();
            const from = new Date();
            from.setDate(today.getDate() - (days - 1));

            this.dateTo = today.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.applyFilters();
        },

        isQuickRange(days) {
            if (!this.dateFrom || !this.dateTo) return false;
            const today = new Date().toISOString().split('T')[0];
            const from = new Date();
            from.setDate(new Date().getDate() - (days - 1));
            return this.dateTo === today && this.dateFrom === from.toISOString().split('T')[0];
        },
        onMonthChange() {
            this.selectedDay = 'all';
            this.applyFilters();
        },
        get availableYears() {
            const currentYear = new Date().getFullYear();
            const years = [];
            for (let y = currentYear; y >= currentYear - 4; y--) {
                years.push(y);
            }
            return years;
        },
        changeSortBy(field) {
            if (this.sortBy === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortOrder = 'desc';
            }
            this.applyFilters();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.meta.total_pages) {
                this.loadStats(page);
            }
        },

        viewDetails(stat) {
            this.selectedUser = stat;
            this.detailsModalOpen = true;
        },

        // Formatage
        formatDuration(seconds) {
            if (!seconds || seconds === 0) return '0s';

            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            const parts = [];
            if (hours > 0) parts.push(`${hours}h`);
            if (minutes > 0) parts.push(`${minutes}m`);
            if (secs > 0 || parts.length === 0) parts.push(`${secs}s`);

            return parts.join(' ');
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';

            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        },

        formatRelativeDate(dateString) {
            if (!dateString) return '-';

            try {
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                const minutes = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);

                if (minutes < 1) return 'À l\'instant';
                if (minutes < 60) return `Il y a ${minutes}min`;
                if (hours < 24) return `Il y a ${hours}h`;
                if (days < 7) return `Il y a ${days}j`;

                return this.formatDate(dateString);
            } catch (error) {
                return dateString;
            }
        },

        getInitials(name) {
            if (!name) return '?';
            return name.substring(0, 2).toUpperCase();
        },

        getStatusColor(status) {
            const colors = {
                'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'suspended': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                'expired': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
            };
            return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
        },

        getTimeColor(seconds) {
            if (seconds > 7200) return 'text-green-600 dark:text-green-400 font-semibold';
            if (seconds > 3600) return 'text-blue-600 dark:text-blue-400';
            if (seconds > 1800) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        getConsumptionColor(bytes) {
            const gb = bytes / (1024 * 1024 * 1024);
            if (gb > 10) return 'text-red-600 dark:text-red-400 font-semibold';
            if (gb > 5) return 'text-orange-600 dark:text-orange-400';
            if (gb > 1) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        // Utilitaires
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },

    };
}

function siteStatsModal() {
    return {
        // État
        isOpen: false,
        loading: false,
        showGlobalStats: false,
        showFilters: false,
        searchDebounce: null,
        meta: { total: 0, page: 1, per_page: 25, total_pages: 1 },

        // Données
        stats: [],
        filteredStats: [],
        selectedUser: null,

        // Filtres
        searchTerm: '',
        selectedGroup: 'all',
        selectedStatus: 'all',
        onlineFilter: 'all',
        selectedYear: 'all',
        selectedMonth: 'all',
        selectedDay: 'all',
        // Tri
        sortBy: 'domain',
        sortOrder: 'desc',

        // Pagination
        currentPage: 1,
        itemsPerPage: 10,

        // Stats globales
        globalStats: {
            total_sites: 0,
        },
        sites: [],
        // Computed
        get totalPages() { return this.meta.total_pages; },
        get startIndex() { return (this.meta.page - 1) * this.meta.per_page; },
        get endIndex() { return Math.min(this.startIndex + this.meta.per_page, this.meta.total); },
        get paginatedStats() { return this.stats; },

        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        get uniqueGroups() {
            return this.groups;
        },
        get availableDays() {
            // Le filtre jour nécessite qu'un mois soit sélectionné
            if (this.selectedMonth === 'all') return [];

            const year = this.selectedYear !== 'all' ? parseInt(this.selectedYear) : new Date().getFullYear();
            const month = parseInt(this.selectedMonth);
            const daysInMonth = new Date(year, month, 0).getDate();

            const days = [];
            for (let d = 1; d <= daysInMonth; d++) {
                days.push(d);
            }
            return days;
        },

        // Initialisation
        init() {
            window.addEventListener('open-site-stats-modal', () => {
                this.openModal();
            });
        },

        async openModal() {
            this.isOpen = true;
            await this.loadStats();
        },

        closeModal() {
            this.isOpen = false;
            this.resetFilters();
        },

        async loadStats(page = 1) {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/stats/sites', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        page: page,
                        per_page: this.itemsPerPage,
                        search: this.searchTerm.length >= 3 ? this.searchTerm : '',
                        sort_by: this.sortBy,
                        sort_order: this.sortOrder,
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.stats = (result.data || []).map(stat => ({
                        ...stat,
                        total_sites: parseInt(stat.total_sites) || 0,
                    }));

                    this.meta = result.meta;
                    this.sites = result.sites;
                    this.currentPage = result.meta.page;
                    this.filteredStats = this.stats;
                    this.calculateGlobalStats();
                } else {
                    this.showError(result.message || 'Erreur lors du chargement');
                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },

        calculateGlobalStats() {
            this.globalStats.total_sites = this.meta.total; // ← utiliser meta.total
        },

        applyFilters() {
            this.loadStats(1);
        },
        onSearchInput() {
            clearTimeout(this.searchDebounce);
            if (this.searchTerm.length > 0 && this.searchTerm.length < 3) return; // attendre 3 chars
            this.searchDebounce = setTimeout(() => this.loadStats(1), 400);
        },
        resetFilters() {
            this.searchTerm = '';
            this.onlineFilter = 'all';
            this.sortBy = 'domain';
            this.sortOrder = 'desc';
            this.currentPage = 1;
            this.applyFilters();
        },
        changeSortBy(field) {
            if (this.sortBy === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortOrder = 'desc';
            }
            this.applyFilters();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.meta.total_pages) {
                this.loadStats(page);
            }
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        },

        formatRelativeDate(dateString) {
            if (!dateString) return '-';

            try {
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                const minutes = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);

                if (minutes < 1) return 'À l\'instant';
                if (minutes < 60) return `Il y a ${minutes}min`;
                if (hours < 24) return `Il y a ${hours}h`;
                if (days < 7) return `Il y a ${days}j`;

                return this.formatDate(dateString);
            } catch (error) {
                return dateString;
            }
        },

        getInitials(name) {
            if (!name) return '?';
            return name.substring(0, 2).toUpperCase();
        },

        getTimeColor(seconds) {
            if (seconds > 7200) return 'text-green-600 dark:text-green-400 font-semibold';
            if (seconds > 3600) return 'text-blue-600 dark:text-blue-400';
            if (seconds > 1800) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },
        // Utilitaires
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },

    };
}
function linksModal() {
    return {
        // État
        isOpen: false,
        loading: false,
        updatingStatus: {},

        // Données
        links: [],
        groups: [],
        filteredlinks: [],

        // Filtres
        searchTerm: '',
        selectedGroup: 'all',
        selectedStatus: 'all',

        // Pagination
        currentPage: 1,
        itemsPerPage: 10,

        // Stats
        stats: {
            total: 0,
            active: 0,
            inactive: 0
        },
        errors: {},

        // Computed
        get totalPages() {
            return Math.ceil(this.filteredlinks.length / this.itemsPerPage);
        },

        get startIndex() {
            return (this.currentPage - 1) * this.itemsPerPage;
        },

        get endIndex() {
            return Math.min(this.startIndex + this.itemsPerPage, this.filteredlinks.length);
        },

        get paginatedlinks() {
            return this.filteredlinks.slice(this.startIndex, this.endIndex);
        },

        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        // Initialisation
        init() {
            window.addEventListener('open-links-modal', () => {
                this.openModal();
            });

            window.addEventListener('reload-links', () => {
                this.loadlinks();
            });
        },

        // Ouvrir le modal
        async openModal() {
            this.isOpen = true;
            this.resetStores();
            await this.loadlinks();
        },

        // Fermer le modal
        closeModal() {
            this.isOpen = false;
            this.resetStores();
            this.resetFilters();
        },
        resetStores() {
            this.links = [];
            this.groups = [];
        },
        // Charger les liens
        async loadlinks() {
            this.loading = true;

            try {
                const response = await fetch('/dashboard/links/list', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.links = (result.data || []).map((link, index) => ({
                        id: link.id || `temp_${index}`,
                        group: link.group || `-`,
                        creator: link.creator || `temp_${index}`,
                        token: link.token || 'N/A',
                        max_uses: link.max_uses || null,
                        uses: link.uses || 0,
                        expires_at: link.expires_at,
                        status: link.status || 'active',
                        created_by: link.created_by,
                        created_at: link.created_at || null,
                        updated_at: link.updated_at || null,
                        last_used_at: link.last_used_at || null,
                        visit: link.link || null,
                    }));
                    this.groups = result.groups;
                    this.calculateStats();
                    this.applyFilters();
                } else {
                    this.showError(result.message || 'Erreur lors du chargement');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },

        // Calculer les statistiques
        calculateStats() {
            this.stats.total = this.links.length;
            this.stats.active = this.links.filter(l => l.status === 'active').length;
            this.stats.inactive = this.links.filter(l => l.status !== 'active').length;
        },

        // Appliquer les filtres
        applyFilters() {
            let filtered = [...this.links];

            if (this.searchTerm.trim()) {
                const search = this.searchTerm.toLowerCase();
                filtered = filtered.filter(link => {
                    const group = (link.group || '').toLowerCase();
                    return group.includes(search);
                });
            }

            if (this.selectedGroup !== 'all') {
                filtered = filtered.filter(link => link.group === this.selectedGroup);
            }

            if (this.selectedStatus !== 'all') {
                filtered = filtered.filter(link => link.status === this.selectedStatus);
            }

            this.filteredlinks = filtered;
            this.currentPage = 1;
        },

        // Réinitialiser les filtres
        resetFilters() {
            this.searchTerm = '';
            this.selectedGroup = 'all';
            this.selectedStatus = 'all';
            this.currentPage = 1;
            this.filteredlinks = [...this.links];
        },

        // Navigation pagination
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        // Voir les détails d'un lien
        viewlink(link) {
            Alpine.store('viewLinkModal').open(link);
        },

        // Modifier un lien
        editlink(link) {
            Alpine.store('editLinkModal').open(link);
        },

        // Changer le statut d'un lien
        async changeStatus(link, newStatus) {
            if (this.updatingStatus[link.id]) return;

            this.updatingStatus[link.id] = true;

            try {
                const response = await fetch('/dashboard/links/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id: link.id,
                        status: newStatus,
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Mettre à jour le statut localement
                    const linkIndex = this.links.findIndex(l => l.id === link.id);
                    if (linkIndex !== -1) {
                        this.links[linkIndex].status = newStatus;
                    }

                    this.calculateStats();
                    this.applyFilters();
                    this.showSuccess('Statut mis à jour avec succès');
                } else {
                    this.showError(result.message || 'Erreur lors de la mise à jour du statut');
                    // Recharger pour restaurer l'ancien statut
                    await this.loadlinks();
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
                await this.loadlinks();
            } finally {
                this.updatingStatus[link.id] = false;
            }
        },

        // Calculer le temps restant avant expiration
        getTimeRemaining(expiresAt) {
            if (!expiresAt) return { text: 'Jamais', color: 'text-gray-600 dark:text-gray-400' };

            const now = new Date();
            const expiry = new Date(expiresAt);
            const diff = expiry - now;

            if (diff <= 0) {
                return { text: 'Expiré', color: 'text-red-600 dark:text-red-400 font-semibold' };
            }

            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            let text = '';
            let color = '';

            if (days > 7) {
                text = `${days}j`;
                color = 'text-green-600 dark:text-green-400';
            } else if (days > 2) {
                text = `${days}j ${hours}h`;
                color = 'text-yellow-600 dark:text-yellow-400';
            } else if (days > 0) {
                text = `${days}j ${hours}h`;
                color = 'text-orange-600 dark:text-orange-400 font-semibold';
            } else if (hours > 0) {
                text = `${hours}h ${minutes}min`;
                color = 'text-red-600 dark:text-red-400 font-semibold';
            } else {
                text = `${minutes}min`;
                color = 'text-red-700 dark:text-red-500 font-bold';
            }

            return { text, color };
        },

        // Obtenir la couleur pour le nombre d'utilisations
        getUsageColor(link) {
            if (!link.max_uses) {
                return 'text-gray-600 dark:text-gray-400';
            }

            const percentage = (link.uses / link.max_uses) * 100;

            if (percentage >= 100) {
                return 'text-red-600 dark:text-red-400 font-semibold';
            } else if (percentage >= 80) {
                return 'text-orange-600 dark:text-orange-400 font-semibold';
            } else if (percentage >= 50) {
                return 'text-yellow-600 dark:text-yellow-400';
            } else {
                return 'text-green-600 dark:text-green-400';
            }
        },

        // Obtenir le badge de statut
        getStatusBadge(status) {
            const statusConfig = {
                'active': {
                    label: 'Actif',
                    class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                },
                'expired': {
                    label: 'Expiré',
                    class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                },
                'revoked': {
                    label: 'Révoqué',
                    class: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                }
            };

            return statusConfig[status] || statusConfig['active'];
        },

        // Utilitaires
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        }
    };
}
function dashboardStats() {
    return {
        stats: {},
        most_consumer: {},
        most_group: {},
        most_visited_site: {},
        groups: [],
        loading: false,
        autoRefreshInterval: null,

        // Filtres
        showFilters: false,
        selectedGroup: 'all',
        selectedYear: 'all',
        selectedMonth: 'all',
        dateMode: 'picker',   // 'picker' | 'range'
        dateFrom: '',
        dateTo: '',

        get availableYears() {
            const cur = new Date().getFullYear();
            const years = [];
            for (let y = cur; y >= cur - 4; y--) years.push(y);
            return years;
        },

        get availableMonths() {
            return [
                { value: 1, label: 'Janvier' },
                { value: 2, label: 'Février' },
                { value: 3, label: 'Mars' },
                { value: 4, label: 'Avril' },
                { value: 5, label: 'Mai' },
                { value: 6, label: 'Juin' },
                { value: 7, label: 'Juillet' },
                { value: 8, label: 'Août' },
                { value: 9, label: 'Septembre' },
                { value: 10, label: 'Octobre' },
                { value: 11, label: 'Novembre' },
                { value: 12, label: 'Décembre' },
            ];
        },

        hasActiveFilters() {
            return this.selectedGroup !== 'all'
                || this.selectedYear !== 'all'
                || this.selectedMonth !== 'all'
                || (this.dateMode === 'range' && this.dateFrom && this.dateTo);
        },

        resetFilters() {
            this.selectedGroup = 'all';
            this.selectedYear = 'all';
            this.selectedMonth = 'all';
            this.dateFrom = '';
            this.dateTo = '';
            this.dateMode = 'picker';
            this.fetchStats();
        },

        setQuickRange(days) {
            const today = new Date();
            const from = new Date();
            from.setDate(today.getDate() - (days - 1));
            this.dateTo = today.toISOString().split('T')[0];
            this.dateFrom = from.toISOString().split('T')[0];
            this.dateMode = 'range';
            this.fetchStats();
        },

        init() {
            this.fetchStats();
            this.autoRefreshInterval = setInterval(() => this.fetchStats(), 5 * 60 * 1000);
        },

        async fetchStats() {
            if (stopCheck) return;
            this.loading = true;
            try {
                const body = {
                    csrf_token: this.getCsrfToken(),
                    group: this.selectedGroup,
                    year: this.dateMode === 'picker' ? this.selectedYear : 'all',
                    month: this.dateMode === 'picker' ? this.selectedMonth : 'all',
                    date_from: this.dateMode === 'range' ? this.dateFrom : '',
                    date_to: this.dateMode === 'range' ? this.dateTo : '',
                };

                const response = await fetch('/dashboard/stats/totals', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body)
                });

                const result = await response.json();
                if (result.success) {
                    this.stats = result.data;
                    this.most_consumer = result.most_consumer;
                    this.most_group = result.most_group;
                    this.most_visited_site = result.most_visited_site;
                    this.groups = result.groups || [];
                } else {
                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                    toast(result.message, 'error');
                }
            } catch (error) {
                toast('Erreur lors du chargement des statistiques', 'error');
            } finally {
                this.loading = false;
            }
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },
        formatBytes(bytes) {
            if (!bytes || bytes === 0 || bytes === '0') return '0 B';
            const k = 1024, sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        },
        formatDuration(seconds) {
            if (!seconds || seconds <= 0) return '0s';
            const s = parseInt(seconds);
            const d = Math.floor(s / 86400);
            const h = Math.floor((s % 86400) / 3600);
            const m = Math.floor((s % 3600) / 60);
            if (d > 0) return `${d}j ${h}h ${m}m`;
            if (h > 0) return `${h}h ${m}m`;
            return `${m}m`;
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleString('fr-FR');
        },
        formatRelativeDate(dateString) {
            if (!dateString) return '-';
            try {
                const date = new Date(dateString), now = new Date();
                const diff = now - date;
                const minutes = Math.floor(diff / 60000);
                const hours = Math.floor(diff / 3600000);
                const days = Math.floor(diff / 86400000);
                if (minutes < 1) return 'À l\'instant';
                if (minutes < 60) return `Il y a ${minutes}min`;
                if (hours < 24) return `Il y a ${hours}h`;
                if (days < 7) return `Il y a ${days}j`;
                return this.formatDate(dateString);
            } catch { return dateString; }
        },
        isQuickRange(days) {
            if (!this.dateFrom || !this.dateTo) return false;
            const today = new Date().toISOString().split('T')[0];
            const from = new Date();
            from.setDate(new Date().getDate() - (days - 1));
            return this.dateTo === today && this.dateFrom === from.toISOString().split('T')[0];
        },
    }
}
function groupStatsModal() {
    return {
        // État
        isOpen: false,
        loading: false,
        detailsModalOpen: false,
        showGlobalStats: false,
        showFilters: false,

        // Données
        stats: [],
        filteredStats: [],
        selectedGroup: null,

        // Filtres
        searchTerm: '',
        minUsers: '',
        maxUsers: '',
        onlineFilter: 'all',

        // Tri
        sortBy: 'total_time',
        sortOrder: 'desc',

        // Pagination
        currentPage: 1,
        itemsPerPage: 10,

        // Stats globales
        globalStats: {
            total_groups: 0,
            total_users: 0,
            active_users: 0,
            online_users: 0,
            total_sessions: 0,
            total_time: 0,
            total_download: 0,
            total_upload: 0,
            total_consumption: 0,
            avg_users_per_group: 0,
            avg_time_per_group: 0
        },

        // Computed
        get totalPages() {
            return Math.ceil(this.filteredStats.length / this.itemsPerPage);
        },

        get startIndex() {
            return (this.currentPage - 1) * this.itemsPerPage;
        },

        get endIndex() {
            return Math.min(this.startIndex + this.itemsPerPage, this.filteredStats.length);
        },

        get paginatedStats() {
            return this.filteredStats.slice(this.startIndex, this.endIndex);
        },

        get visiblePages() {
            const pages = [];
            const maxVisible = 5;
            let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
            let end = Math.min(this.totalPages, start + maxVisible - 1);

            if (end - start + 1 < maxVisible) {
                start = Math.max(1, end - maxVisible + 1);
            }

            for (let i = start; i <= end; i++) {
                pages.push(i);
            }

            return pages;
        },

        // Initialisation
        init() {
            window.addEventListener('open-group-stats-modal', () => {
                this.openModal();
            });
        },

        async openModal() {
            this.isOpen = true;
            await this.loadStats();
        },

        closeModal() {
            this.isOpen = false;
            this.resetFilters();
        },

        async loadStats() {
            this.loading = true;

            try {
                const response = await fetch('/dashboard/stats/groups', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.stats = (result.data || []).map(stat => ({
                        ...stat,
                        total_users: parseInt(stat.total_users) || 0,
                        active_users: parseInt(stat.active_users) || 0,
                        online_users: parseInt(stat.online_users) || 0,
                        total_sessions: parseInt(stat.total_sessions) || 0,
                        total_time: parseInt(stat.total_time) || 0,
                        total_download: parseInt(stat.total_download) || 0,
                        total_upload: parseInt(stat.total_upload) || 0,
                        total_consumption: parseInt(stat.total_consumption) || 0,
                        avg_time_per_user: stat.total_users > 0 ? stat.total_time / stat.total_users : 0,
                        avg_consumption_per_user: stat.total_users > 0 ? stat.total_consumption / stat.total_users : 0
                    }));

                    this.calculateGlobalStats();
                    this.applyFilters();
                } else {
                    this.showError(result.message || 'Erreur lors du chargement');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },

        calculateGlobalStats() {
            this.globalStats.total_groups = this.stats.length;
            this.globalStats.total_users = this.stats.reduce((sum, s) => sum + s.total_users, 0);
            this.globalStats.active_users = this.stats.reduce((sum, s) => sum + s.active_users, 0);
            this.globalStats.online_users = this.stats.reduce((sum, s) => sum + s.online_users, 0);
            this.globalStats.total_sessions = this.stats.reduce((sum, s) => sum + s.total_sessions, 0);
            this.globalStats.total_time = this.stats.reduce((sum, s) => sum + s.total_time, 0);
            this.globalStats.total_download = this.stats.reduce((sum, s) => sum + s.total_download, 0);
            this.globalStats.total_upload = this.stats.reduce((sum, s) => sum + s.total_upload, 0);
            this.globalStats.total_consumption = this.stats.reduce((sum, s) => sum + s.total_consumption, 0);
            this.globalStats.avg_users_per_group = this.globalStats.total_groups > 0
                ? this.globalStats.total_users / this.globalStats.total_groups
                : 0;
            this.globalStats.avg_time_per_group = this.globalStats.total_groups > 0
                ? this.globalStats.total_time / this.globalStats.total_groups
                : 0;
        },

        applyFilters() {
            let filtered = [...this.stats];

            // Filtre de recherche
            if (this.searchTerm.trim()) {
                const search = this.searchTerm.toLowerCase();
                filtered = filtered.filter(stat => {
                    const groupName = (stat.group_name || '').toLowerCase();
                    return groupName.includes(search);
                });
            }

            // Filtre nombre d'utilisateurs min
            if (this.minUsers !== '') {
                const min = parseInt(this.minUsers);
                filtered = filtered.filter(s => s.total_users >= min);
            }

            // Filtre nombre d'utilisateurs max
            if (this.maxUsers !== '') {
                const max = parseInt(this.maxUsers);
                filtered = filtered.filter(s => s.total_users <= max);
            }

            // Filtre utilisateurs en ligne
            if (this.onlineFilter === 'has_online') {
                filtered = filtered.filter(s => s.online_users > 0);
            } else if (this.onlineFilter === 'no_online') {
                filtered = filtered.filter(s => s.online_users === 0);
            }

            // Tri
            filtered.sort((a, b) => {
                let aVal = a[this.sortBy];
                let bVal = b[this.sortBy];

                if (this.sortOrder === 'asc') {
                    return aVal > bVal ? 1 : -1;
                } else {
                    return aVal < bVal ? 1 : -1;
                }
            });

            this.filteredStats = filtered;
            this.currentPage = 1;
        },

        resetFilters() {
            this.searchTerm = '';
            this.minUsers = '';
            this.maxUsers = '';
            this.onlineFilter = 'all';
            this.sortBy = 'total_time';
            this.sortOrder = 'desc';
            this.currentPage = 1;
            this.applyFilters();
        },

        changeSortBy(field) {
            if (this.sortBy === field) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortBy = field;
                this.sortOrder = 'desc';
            }
            this.applyFilters();
        },

        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        viewDetails(stat) {
            this.selectedGroup = stat;
            this.detailsModalOpen = true;
        },

        // Formatage
        formatDuration(seconds) {
            if (!seconds || seconds === 0) return '0s';

            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = seconds % 60;

            const parts = [];
            if (hours > 0) parts.push(`${hours}h`);
            if (minutes > 0) parts.push(`${minutes}m`);
            if (secs > 0 || parts.length === 0) parts.push(`${secs}s`);

            return parts.join(' ');
        },

        formatBytes(bytes) {
            if (!bytes || bytes === 0) return '0 B';

            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));

            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        },

        getInitials(name) {
            if (!name) return '?';
            return name.substring(0, 2).toUpperCase();
        },

        getGroupColor(index) {
            const colors = [
                'from-blue-500 to-blue-600',
                'from-purple-500 to-purple-600',
                'from-pink-500 to-pink-600',
                'from-indigo-500 to-indigo-600',
                'from-cyan-500 to-cyan-600',
                'from-teal-500 to-teal-600',
                'from-green-500 to-green-600',
                'from-orange-500 to-orange-600'
            ];
            return colors[index % colors.length];
        },

        getUsersColor(total, active) {
            const activePercentage = total > 0 ? (active / total) * 100 : 0;
            if (activePercentage >= 80) return 'text-green-600 dark:text-green-400 font-semibold';
            if (activePercentage >= 50) return 'text-blue-600 dark:text-blue-400';
            if (activePercentage >= 20) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        getTimeColor(seconds) {
            if (seconds > 36000) return 'text-green-600 dark:text-green-400 font-semibold';
            if (seconds > 18000) return 'text-blue-600 dark:text-blue-400';
            if (seconds > 7200) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        getConsumptionColor(bytes) {
            const gb = bytes / (1024 * 1024 * 1024);
            if (gb > 50) return 'text-red-600 dark:text-red-400 font-semibold';
            if (gb > 25) return 'text-orange-600 dark:text-orange-400';
            if (gb > 10) return 'text-yellow-600 dark:text-yellow-400';
            return 'text-gray-600 dark:text-gray-400';
        },

        // Utilitaires
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        }
    };
}
document.addEventListener('alpine:init', () => {
    Alpine.store('confirmRemoveModal', {
        isOpen: false,
        additionnals: '',
        additionalsChecked: false,
        loading: false,
        managerId: null,
        manager: {
            fullname: '',
            title: ''
        },
        error: '',
        FETCH_LINK: '',
        text: '',
        open(manager, type, additionnals = '') {
            this.isOpen = true;
            this.managerId = manager.id;
            this.error = '';
            this.additionnals = additionnals;
            this.additionalsChecked = false;
            this.setManager(manager, type);
        },
        type: '',
        setManager(manager, type) {
            this.type = type;
            switch (type) {
                case 'manager':
                    this.text = 'manager';
                    this.manager.fullname = manager.fullname;
                    this.manager.title = manager.email;
                    this.FETCH_LINK = 'managers';
                    break;
                case 'user':
                    this.text = 'l\'utilisateur';
                    this.manager.fullname = manager.fullname;
                    this.manager.title = manager.username;
                    this.FETCH_LINK = 'users';
                    break;
                case 'group':
                    this.text = 'groupe';
                    this.manager.fullname = manager.name;
                    this.manager.title = '';
                    this.FETCH_LINK = 'groups';
                    break;
                case 'link':
                    this.text = "lien d'invitation";
                    this.manager.fullname = manager.name;
                    this.manager.title = '';
                    this.FETCH_LINK = 'links';
                    break;
                case 'policy':
                    this.text = "Politique";
                    this.manager.fullname = manager.name;
                    this.manager.title = '';
                    this.FETCH_LINK = 'policies';
                    break;
                default:
                    break;
            }
        },
        closeCaller() {
            switch (this.type) {
                case 'manager':
                    Alpine.store('managersListModal').loadManagers();
                    break;
                case 'group':
                    Alpine.store('groupsListModal').loadGroups();
                    break;
                default:
                    break;
            }
        },
        close() {
            this.isOpen = false;
            this.closeCaller();
            this.managerId = null;
            this.error = '';
            this.additionnals = '';
            this.additionalsChecked = false;
        },
        async confirm() {
            if (this.managerId === null || this.managerId === undefined) {
                toast("ID invalide!", 'error')
                return;
            }
            this.loading = true;
            this.error = '';
            try {
                qbody = {
                    id: this.managerId,
                    csrf_token: this.getCsrfToken()
                };
                if (this.additionalsChecked) {
                    qbody.additionnals = this.additionalsChecked;
                }
                const response = await fetch('/dashboard/' + this.FETCH_LINK + '/remove', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(qbody)
                });
                const result = await response.json();
                if (result.success) {
                    toast(result.message || 'Suppression avec succès!');
                    this.close();
                } else {
                    if (result.status == 'session_timed_out') {
                        sessionExpired();
                    }
                    this.error = result.message || 'Erreur lors de la suppression';
                }
            } catch (error) {
                this.error = 'Erreur de connexion. Veuillez réessayer.';
                console.error('Erreur:', error);
            } finally {
                this.loading = false;
            }
        },
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        }
    });
    Alpine.store('deleteStats', {
        // État dédié au modal de suppression (séparé des filtres de liste)
        selectedCible: {},
        isOpen: false,
        deleteScope: 'all',       // 'all' | 'picker' | 'range'
        deleteYear: 'all',
        deleteMonth: 'all',
        deleteDay: 'all',
        deleteFrom: '',
        deleteTo: '',
        tyoe: '',
        get deleteDays() {
            if (this.deleteMonth === 'all' || this.deleteYear === 'all') return [];
            const daysInMonth = new Date(
                parseInt(this.deleteYear),
                parseInt(this.deleteMonth),
                0
            ).getDate();
            return Array.from({ length: daysInMonth }, (_, i) => i + 1);
        },
        get availableYears() {
            const cur = new Date().getFullYear();
            const years = [];
            for (let y = cur; y >= cur - 4; y--) years.push(y);
            return years;
        },

        close() {
            this.isOpen = false;
        },
        open(stat, type) {
            this.isOpen = true;
            this.type = type;
            this.selectedCible = stat;
            this.deleteScope = 'all';
            this.deleteYear = 'all';
            this.deleteMonth = 'all';
            this.deleteDay = 'all';
            this.deleteFrom = '';
            this.deleteTo = '';
        },
        setDeleteRange(days) {
            const today = new Date();
            const from = new Date();
            from.setDate(today.getDate() - (days - 1));
            this.deleteTo = today.toISOString().split('T')[0];
            this.deleteFrom = from.toISOString().split('T')[0];
        },

        isDeleteRange(days) {
            if (!this.deleteFrom || !this.deleteTo) return false;
            const today = new Date().toISOString().split('T')[0];
            const from = new Date();
            from.setDate(new Date().getDate() - (days - 1));
            return this.deleteTo === today && this.deleteFrom === from.toISOString().split('T')[0];
        },

        async confirmDelete() {
            if (!this.selectedCible) return;

            if (this.deleteScope === 'picker' && this.deleteYear === 'all') {
                this.showError('Veuillez sélectionner au moins une année.');
                return;
            }
            if (this.deleteScope === 'range' && (!this.deleteFrom || !this.deleteTo)) {
                this.showError('Veuillez sélectionner une période valide.');
                return;
            }

            let scopeLabel = '';
            if (this.deleteScope === 'all') scopeLabel = 'TOUTES les statistiques';
            if (this.deleteScope === 'picker') scopeLabel = `les stats de ${this.deleteDay !== 'all' ? this.deleteDay + '/' : ''}${this.deleteMonth !== 'all' ? this.deleteMonth + '/' : ''}${this.deleteYear}`;
            if (this.deleteScope === 'range') scopeLabel = `les stats du ${this.deleteFrom} au ${this.deleteTo}`;

            if (!confirm(`Supprimer ${scopeLabel} ?\nCette action est irréversible.`)) return;

            const payload = {
                csrf_token: this.getCsrfToken(),
                type: this.type,
                target_id: this.selectedCible.id ?? this.selectedCible.group_id,
                date_mode: this.deleteScope,
                year: this.deleteYear,
                month: this.deleteMonth,
                day: this.deleteDay,
                date_from: this.deleteScope === 'range' ? this.deleteFrom : '',
                date_to: this.deleteScope === 'range' ? this.deleteTo : '',
            };

            try {
                const response = await fetch('/dashboard/stats/delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(result.message || 'Statistiques supprimées avec succès.');
                    this.isOpen = false;
                } else {
                    this.showError(result.message || 'Erreur lors de la suppression.');
                }
            } catch (error) {
                console.error('Erreur delete:', error);
                this.showError('Erreur de connexion au serveur.');
            }
        },
        // Utilitaires
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },


        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },
    });
    Alpine.store('adduserModal', {
        // État du modal
        isOpen: false,
        loading: false,

        // Données du formulaire
        formData: {
            fullname: '',
            username: '',
            password: '',
            group: '',
            status: 'active',
            csrf_token: ''
        },

        // Erreurs de validation
        errors: {},
        groups: [],

        // Initialisation
        init() {
            if (this.groups.length > 0) {
                this.formData.group = this.groups[0].id;
            }
        },

        // Ouvrir le modal
        async open() {
            await this.getGroups();
            this.resetForm();
            this.isOpen = true;
        },
        async getGroups() {
            try {
                const response = await fetch('/dashboard/groups/list-for-use', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.groups = result.data;
                    this.close();
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;

                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la demande des groupes!';
                    }
                }
            } catch (error) {
                this.errors.general = 'Impossible de lister les groupes.';
            } finally {
                this.loading = false;
            }
        },
        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.formData = {
                fullname: '',
                username: '',
                password: '',
                group: this.groups?.length ? this.groups[0].id : '',
                status: 'active',
                csrf_token: this.getCsrfToken()
            };

            this.errors = {};
            this.loading = false;
        },
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },
        // Validation du formulaire
        validateForm() {
            this.errors = {};

            // Validation fullname
            if (!this.formData.username.trim()) {
                this.errors.username = 'Le nom d\'utilisateur est obligatoire';
            } else if (this.formData.username.trim().length < 6) {
                this.errors.username = 'Le nom d\'utilisateur doit contenir au moins 6 caractères';
            }

            // Validation password
            if (!this.formData.password) {
                this.errors.password = 'Le mot de passe est obligatoire';
            } else if (this.formData.password.length < 6) {
                this.errors.password = 'Le mot de passe doit contenir au moins 6 caractères';
            }
            // Validation username
            if (this.formData.username && !/^[a-zA-Z0-9_]+$/.test(this.formData.username)) {
                this.errors.username = "Le nom d'utilisateur invalide! (user_name123).";
            }

            // Validation status
            if (!this.formData.status) {
                this.errors.status = 'Le status est obligatoire';
            } else if (!['active', 'inactive'].includes(this.formData.status)) {
                this.errors.status = 'Status invalide';
            }
            return Object.keys(this.errors).length === 0;
        },

        // Soumission du formulaire
        async submitForm() {
            if (!this.validateForm()) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch('/dashboard/users/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.formData)
                });

                const result = await response.json();

                if (result.success) {
                    toast(result.message, 'success');
                    this.close();
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;

                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la création';
                    }
                }
            } catch (error) {
                this.errors.general = 'Erreur de connexion. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        }
    });
    Alpine.store('addLinkModal', {
        // État du modal
        isOpen: false,
        loading: false,

        // Données du formulaire
        formData: {
            group: '',
            max_uses: 1,
            expires_at: '',
            status: 'active',
            csrf_token: ''
        },

        // Erreurs de validation
        errors: {},
        groups: [],

        // Ouvrir le modal
        async open() {
            await this.getGroups();
            this.isOpen = true;
            this.resetForm();
        },

        async getGroups() {
            try {
                const response = await fetch('/dashboard/groups/list-for-use', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.groups = result.data;
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;
                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la demande des groupes!';
                    }
                }
            } catch (error) {
                this.errors.general = 'Impossible de lister les groupes.';
            } finally {
                this.loading = false;
            }
        },

        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.formData = {
                max_uses: 1,
                expires_at: '',
                group: this.groups?.length ? this.groups[0].id : '',
                status: 'active',
                csrf_token: this.getCsrfToken()
            };

            this.errors = {};
            this.loading = false;
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        // Validation du formulaire
        validateForm() {
            this.errors = {};

            // Validation max utilisations (doit être positif)
            if (!this.formData.max_uses || this.formData.max_uses <= 0) {
                this.errors.max_uses = "Le nombre d'utilisations doit être positif (supérieur à 0)";
            }

            // Validation de la date d'expiration
            if (this.formData.expires_at) {
                const selectedDate = new Date(this.formData.expires_at);
                const now = new Date();

                if (selectedDate < now) {
                    this.errors.expires_at = "La date d'expiration doit être au moins égale à la date actuelle";
                }
            }

            // Validation status
            if (!this.formData.status) {
                this.errors.status = 'Le status est obligatoire';
            } else if (!['active', 'expired', 'revoked'].includes(this.formData.status)) {
                this.errors.status = 'Status invalide';
            }
            return Object.keys(this.errors).length === 0;
        },

        // Soumission du formulaire
        async submitForm() {
            if (!this.validateForm()) {
                return;
            }

            this.loading = true;

            // Préparer les données à envoyer
            const dataToSend = {
                ...this.formData,
                group: this.formData.group || -1 // Si aucun groupe sélectionné, envoyer -1
            };

            try {
                const response = await fetch('/dashboard/links/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(dataToSend)
                });

                const result = await response.json();

                if (result.success) {
                    toast(result.message, 'success');
                    this.close();
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;
                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la création';
                    }
                }
            } catch (error) {
                this.errors.general = 'Erreur de connexion. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        },

        // Utilitaires pour les dates
        getTodayDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },
    });
    Alpine.store('viewLinkModal', {
        isOpen: false,
        linkData: {},

        open(link) {
            this.linkData = { ...link };
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.linkData = {};
        },

        openEdit() {
            this.close();
            Alpine.store('editLinkModal').open(this.linkData);
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.linkData.visit);
                toast('Lien copié dans le presse-papier!', 'success');
            } catch (err) {
                // Fallback pour les navigateurs qui ne supportent pas clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = this.linkData.visit;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    toast('Lien copié dans le presse-papier!', 'success');
                } catch (err) {
                    toast('Impossible de copier le lien', 'error');
                }
                document.body.removeChild(textArea);
            }
        },

        formatDate(dateString) {
            if (!dateString) return 'Non défini';
            try {
                return new Date(dateString).toLocaleDateString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateString;
            }
        }
    });
    Alpine.store('editLinkModal', {
        isOpen: false,
        loading: false,
        formData: {
            id: null,
            group: '',
            uses: 0,
            max_uses: 0,
            expires_at: '',
            status: 'active',
            csrf_token: ''
        },
        originalFormData: {},
        errors: {},

        open(link) {
            // Convertir le status français en anglais
            const statusReverseMapping = {
                'Actif': 'active',
                'Expiré': 'expired',
                'Révoqué': 'revoked'
            };

            this.formData = {
                id: link.id,
                group: link.group,
                uses: link.uses,
                max_uses: link.max_uses,
                expires_at: this.formatDateTimeForInput(link.expires_at),
                status: statusReverseMapping[link.status] || 'active',
                csrf_token: this.getCsrfToken()
            };
            this.originalFormData = { ...this.formData };
            this.errors = {};
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.formData = {
                id: null,
                group: '',
                uses: 0,
                max_uses: 0,
                expires_at: '',
                status: 'active',
                csrf_token: this.getCsrfToken()
            };
            this.originalFormData = {};
            this.errors = {};
            this.loading = false;
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        validateForm() {
            this.errors = {};

            // Validation de la date d'expiration
            if (this.formData.expires_at) {
                const selectedDate = new Date(this.formData.expires_at);
                const now = new Date();

                if (selectedDate < now) {
                    this.errors.expires_at = "La date d'expiration doit être au moins égale à la date actuelle";
                }
            }
            // Validation max utilisations (doit être positif)
            if (!this.formData.max_uses || this.formData.max_uses <= 0) {
                this.errors.max_uses = "Le nombre d'utilisations doit être positif (supérieur à 0)";
            }
            // Validation status
            if (!this.formData.status) {
                this.errors.status = 'Le status est obligatoire';
            } else if (!['active', 'expired', 'revoked'].includes(this.formData.status)) {
                this.errors.status = 'Status invalide';
            }

            return Object.keys(this.errors).length === 0;
        },

        async submitForm() {
            if (!this.validateForm()) {
                return;
            }

            // Détecter les changements
            const changes = {};
            let hasChanges = false;

            if (this.formData.expires_at !== this.originalFormData.expires_at) {
                changes.expires_at = this.formData.expires_at;
                hasChanges = true;
            }

            if (this.formData.status !== this.originalFormData.status) {
                changes.status = this.formData.status;
                hasChanges = true;
            }


            if (this.formData.max_uses !== this.originalFormData.max_uses) {
                changes.max_uses = this.formData.max_uses;
                hasChanges = true;
            }

            if (!hasChanges) {
                this.errors.message = 'Aucune modification apportée';
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/dashboard/links/edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.formData.csrf_token,
                        id: this.formData.id,
                        ...changes
                    })
                });

                const result = await response.json();

                if (result.success) {
                    toast(result.message || 'Lien modifié avec succès', 'success');
                    this.close();

                    // Recharger la liste des liens
                    window.dispatchEvent(new CustomEvent('reload-links'));
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;
                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la modification';
                    }
                }
            } catch (error) {
                this.errors.general = 'Erreur de connexion. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        },

        getTodayDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },

        formatDateTimeForInput(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            } catch (error) {
                return '';
            }
        }
    });
    Alpine.store('addPolicyModal', {
        // État du modal
        isOpen: false,
        loading: false,

        // Données du formulaire
        formData: {
            name: '',
            description: '',
            expires_at: '',
            status: 'active',
            csrf_token: ''
        },

        // Erreurs de validation
        errors: {},

        // Ouvrir le modal
        open() {
            this.isOpen = true;
            this.resetForm();
        },
        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetForm();
        },

        // Réinitialiser le formulaire
        resetForm() {
            this.formData = {
                name: '',
                description: '',
                expires_at: '',
                status: 'active',
                csrf_token: this.getCsrfToken()
            };
            this.errors = {};
            this.loading = false;
        },
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },
        // Validation du formulaire
        validateForm() {
            this.errors = {};

            // Validation name
            if (!this.formData.name.trim()) {
                this.errors.name = 'Le nom est obligatoire';
            } else if (this.formData.name.trim().length < 3) {
                this.errors.name = 'Le nom doit contenir au moins 3 caractères';
            }
            // Validation status
            if (!this.formData.status) {
                this.errors.status = 'Le status est obligatoire';
            } else if (!['active', 'inactive'].includes(this.formData.status)) {
                this.errors.status = 'Status invalide';
            }
            return Object.keys(this.errors).length === 0;
        },

        // Soumission du formulaire
        async submitForm() {
            if (!this.validateForm()) {
                return;
            }

            this.loading = true;
            try {
                const response = await fetch('/dashboard/policies/add', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(this.formData)
                });

                const result = await response.json();

                if (result.success) {
                    toast(result.message, 'success');
                    this.close();
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;

                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la création';
                    }
                }
            } catch (error) {
                this.errors.general = 'Erreur de connexion. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        },

        getTodayDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },
    });
    Alpine.store('policyListModal', {
        // État du modal
        isOpen: false,
        loading: false,
        profileView: false,

        // Données
        policies: [],
        filteredPolicies: [],
        searchTerm: '',
        selectedStatus: 'all',
        currentPage: 1,
        itemsPerPage: 10,
        policy: {},
        // Statistiques
        stats: {
            total: 0,
            active: 0,
            inactive: 0,
        },

        // Ouvrir le modal
        async open() {
            this.isOpen = true;
            await this.loadPolicies();
        },

        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetFilters();
            this.policies = [];
        },
        profileClose() {
            this.profileView = false;
        },
        // Réinitialiser les filtres
        resetFilters() {
            this.searchTerm = '';
            this.selectedStatus = 'all';
            this.currentPage = 1;
            this.filteredPolicies = [...this.policies];
        },

        // Charger la liste des policies
        async loadPolicies() {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/policies/list', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.policies = result.data || [];
                    this.filteredPolicies = [...this.policies];
                    this.calculateStats();
                } else {
                    if (result.status == 'session_timed_out') {
                        sessionExpired();
                    }
                    toast(result.message, 'error');
                }
            } catch (error) {
                console.error('Erreur de connexion:', error);
            } finally {
                this.loading = false;
            }
        },

        // Calculer les statistiques
        calculateStats() {
            this.stats.total = this.policies.length;
            this.stats.active = this.policies.filter(m => m.status === 'active').length;
            this.stats.inactive = this.policies.filter(m => m.status === 'inactive').length;
        },

        // Filtrer les policies
        filterPolicies() {
            let filtered = [...this.policies];

            // Filtrage par recherche
            if (this.searchTerm.trim()) {
                const search = this.searchTerm.toLowerCase();
                filtered = filtered.filter(policy =>
                    policy.name.toLowerCase().includes(search) ||
                    (policy.description && policy.description.toLowerCase().includes(search)) ||
                    (policy.expires_at && policy.expires_at.includes(search))
                );
            }

            // Filtrage par statut
            if (this.selectedStatus !== 'all') {
                filtered = filtered.filter(policy => policy.status === this.selectedStatus);
            }

            this.filteredPolicies = filtered;
            this.currentPage = 1;
        },

        // Pagination
        get totalPages() {
            return Math.ceil(this.filteredPolicies.length / this.itemsPerPage);
        },

        get paginatedPolicies() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredPolicies.slice(start, end);
        },

        // Navigation pagination
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        // Changer le statut d'un policy
        async toggleStatus(policyId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            try {
                const response = await fetch('/dashboard/policies/toggle-status', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        policy_id: policyId,
                        status: newStatus,
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Mettre à jour localement
                    const policyIndex = this.policies.findIndex(m => m.id === policyId);
                    if (policyIndex !== -1) {
                        this.policies[policyIndex].status = newStatus;
                        this.filterPolicies();
                        this.calculateStats();
                    }
                } else {
                    if (result.status == 'session_timed_out') {
                        sessionExpired();
                    }
                    toast(result.message, 'error');
                }
            } catch (error) {
                console.error('Erreur lors du changement de statut:', error);
            }
        },

        // Obtenir le token CSRF
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        // Formater la date
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        // Obtenir la classe CSS pour le statut
        getStatusClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                'inactive': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            };
            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
        },

        // Obtenir le texte du statut
        getStatusText(status) {
            const texts = {
                'active': 'Actif',
                'inactive': 'Inactif',
            };
            return texts[status] || status;
        },

        // Méthodes principales
        openProfile(policy) {
            this.policy = policy;
            this.profileView = true;
        },
        // Fonctions utilitaires
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                // Vérifier la taille
                if (file.size > 5 * 1024 * 1024) {
                    alert('La taille du fichier ne doit pas dépasser 5MB');
                    event.target.value = '';
                    return;
                }

                // Vérifier le type
                if (!file.type.match('image.*')) {
                    alert('Veuillez sélectionner un fichier image valide');
                    event.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.$refs.previewImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        getImageUrl(imagePath) {
            return imagePath;
        },

        formatPhone(phone) {
            if (!phone) return '';
            return phone.startsWith('+') ? phone : '+' + phone;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
    });
    Alpine.store('editPolicyModal', {
        isOpen: false,
        loading: false,
        // Données du formulaire
        formData: {
            name: '',
            description: '',
            expires_at: '',
            status: 'active',
            csrf_token: ''
        },
        originalFormData: {},
        errors: {},

        open(policy) {
            // Convertir le status français en anglais
            const statusReverseMapping = {
                'Actif': 'active',
                'Inactif': 'inactive',
            };

            this.formData = {
                id: policy.id,
                name: policy.name,
                description: policy.description,
                expires_at: this.formatDateTimeForInput(policy.expires_at),
                status: statusReverseMapping[policy.status] || 'active',
                csrf_token: this.getCsrfToken()
            };
            this.originalFormData = { ...this.formData };
            this.errors = {};
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.formData = {
                id: null,
                name: '',
                description: '',
                expires_at: '',
                status: 'active',
                csrf_token: this.getCsrfToken()
            };
            this.originalFormData = {};
            this.errors = {};
            this.loading = false;
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        validateForm() {
            this.errors = {};

            // Validation de la date d'expiration
            if (this.formData.expires_at) {
                const selectedDate = new Date(this.formData.expires_at);
                const now = new Date();

                if (selectedDate < now) {
                    this.errors.expires_at = "La date d'expiration est invalide";
                }
            }
            // Validation name
            if (!this.formData.name.trim()) {
                this.errors.name = 'Le nom est obligatoire';
            } else if (this.formData.name.trim().length < 3) {
                this.errors.name = 'Le nom doit contenir au moins 3 caractères';
            }
            // Validation status
            if (!this.formData.status) {
                this.errors.status = 'Le status est obligatoire';
            } else if (!['active', 'inactive'].includes(this.formData.status)) {
                this.errors.status = 'Status invalide';
            }

            return Object.keys(this.errors).length === 0;
        },

        async submitForm() {
            if (!this.validateForm()) {
                return;
            }

            // Détecter les changements
            const changes = {};
            let hasChanges = false;

            if (this.formData.expires_at !== this.originalFormData.expires_at) {
                changes.expires_at = this.formData.expires_at;
                hasChanges = true;
            }

            if (this.formData.status !== this.originalFormData.status) {
                changes.status = this.formData.status;
                hasChanges = true;
            }


            if (this.formData.name !== this.originalFormData.name) {
                changes.name = this.formData.name;
                hasChanges = true;
            }


            if (this.formData.description !== this.originalFormData.description) {
                changes.description = this.formData.description;
                hasChanges = true;
            }

            if (!hasChanges) {
                this.errors.message = 'Aucune modification apportée';
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/dashboard/policies/edit', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.formData.csrf_token,
                        id: this.formData.id,
                        ...changes
                    })
                });

                const result = await response.json();

                if (result.success) {
                    toast(result.message || 'Politique modifiée avec succès', 'success');
                    this.close();
                } else {
                    if (result.errors) {
                        const errorsList = result.errors;
                        Object.keys(errorsList).forEach(element => {
                            this.errors[element] = errorsList[element];
                        });
                    }

                    if (result.message) {
                        this.errors.message = result.message;
                    } else {
                        this.errors.general = 'Une erreur est survenue lors de la modification';
                    }
                }
            } catch (error) {
                this.errors.general = 'Erreur de connexion. Veuillez réessayer.';
            } finally {
                this.loading = false;
            }
        },

        getTodayDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        },

        formatDateTimeForInput(dateString) {
            if (!dateString) return '';
            try {
                const date = new Date(dateString);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            } catch (error) {
                return '';
            }
        }
    });
    Alpine.store('viewPolicyModal', {
        isOpen: false,
        policy: null,

        open(policy) {
            this.policy = policy;
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.policy = null;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleDateString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            return new Date(dateString).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },
        // Obtenir la classe CSS pour le statut
        getStatusClass(status) {
            const classes = {
                'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                'inactive': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            };
            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';
        },
        // Obtenir le texte du statut
        getStatusText(status) {
            const texts = {
                'active': 'Actif',
                'inactive': 'Inactif',
            };
            return texts[status] || status;
        },

    });
    Alpine.store('policyItemModal', {
        // État du modal
        isOpen: false,
        isLoading: false,
        activeTab: 0,

        // Configuration des onglets
        tabs: [
            { name: 'Général', key: 'general' },
            { name: 'Débit', key: 'bandwidth' },
        ],
        policy: {},

        // Données du formulaire
        formData: {
            id: null,
            max_session: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
            max_inactive: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
            max_upload: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
            max_download: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
            max_consommation: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
            sessions: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
            accounting: {
                id: null,
                value: null,
                priority: null,
                enabled: false
            },
        },

        // Données de sauvegarde pour annulation
        originalData: {},

        // Messages d'erreur
        errors: {},

        init() {
            this.resetFormData();
        },

        resetFormData() {
            this.formData = {
                id: null,
                max_session: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
                max_inactive: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
                sessions: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
                accounting: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
                max_upload: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
                max_download: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
                max_consommation: {
                    id: null,
                    value: null,
                    priority: null,
                    enabled: false
                },
            };
        },

        async open(policy) {
            this.policy = policy;
            this.isOpen = true;
            this.activeTab = 0;
            this.errors = {};
            this.isLoading = true;

            try {
                const response = await fetch('/dashboard/policies/items/get', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id: policy.id,
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    if (result.data) {
                        this.resetFormData();
                        this.loadData(result.data);
                        // Sauvegarder les données originales APRÈS le chargement
                        this.originalData = JSON.parse(JSON.stringify(this.formData));
                    }
                } else {
                    this.showNotification('Erreur lors du chargement des données', 'error');
                }
            } catch (error) {
                console.error('Erreur lors du chargement:', error);
                this.showNotification('Erreur de connexion au serveur', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        close() {
            this.isOpen = false;
            this.isLoading = false;
            this.errors = {};
            this.activeTab = 0;
        },

        cancel() {
            // Restaurer les données originales
            this.formData = JSON.parse(JSON.stringify(this.originalData));
            this.close();
        },

        loadData(data) {
            // Charger l'ID de la policy
            if (data.id !== undefined) {
                this.formData.id = data.id;
            }

            // Charger chaque champ avec l'opérateur de coalescence nulle (??)
            const fields = ['max_session', 'max_inactive', 'sessions', 'accounting', 'max_upload', 'max_download', 'max_consommation'];
            fields.forEach(field => {
                if (data[field]) {
                    this.formData[field] = {
                        id: data[field].id ?? null,
                        value: data[field].value ?? null,
                        priority: data[field].priority ?? null,
                        enabled: data[field].enabled ?? false
                    };
                }
            });
        },

        async submitForm() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.errors = {};

            try {
                // Validation côté client
                if (!this.validateForm()) {
                    this.isLoading = false;
                    return;
                }

                // Préparer les données pour l'envoi
                const submitData = this.prepareSubmitData();

                // Vérifier si submitData ne contient QUE policy_id et csrf_token
                const keys = Object.keys(submitData).filter(k => k !== 'csrf_token' && k !== 'policy_id');
                if (keys.length === 0) {
                    this.isLoading = false;
                    this.showNotification('Aucun changement détecté.', 'warning');
                    return;
                }

                // Envoyer la requête
                const response = await fetch('/dashboard/policies/items/edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(submitData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.showNotification(result.message || 'Modification avec succès', 'success');
                    // Mettre à jour les données originales après succès
                    this.originalData = JSON.parse(JSON.stringify(this.formData));
                    this.close();
                } else {
                    if (result.errors) {
                        this.errors = result.errors;
                    } else {
                        this.showNotification(result.message || 'Une erreur est survenue', 'error');
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la soumission:', error);
                this.showNotification('Erreur de connexion au serveur', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        validateForm() {
            const errors = {};

            // Validation de max_session
            if (this.formData.max_session.enabled && this.formData.max_session.value !== null) {
                if (this.formData.max_session.value < 0) {
                    errors.max_session = 'Durée max de session est invalide';
                }
            }

            // Validation de max_inactive
            if (this.formData.max_inactive.enabled && this.formData.max_inactive.value !== null) {
                if (this.formData.max_inactive.value < 0) {
                    errors.max_inactive = 'Durée max d\'inactivité est invalide';
                }
            }
            // Validation de max_upload
            if (this.formData.max_upload.enabled && this.formData.max_upload.value !== null) {
                if (this.formData.max_upload.value < 0) {
                    errors.max_upload = 'Max upload est invalide';
                }
            }

            // Validation de max_download
            if (this.formData.max_download.enabled && this.formData.max_download.value !== null) {
                if (this.formData.max_download.value < 0) {
                    errors.max_download = 'Max upload est invalide';
                }
            }

            // Validation de max_consommation
            if (this.formData.max_consommation.enabled && this.formData.max_consommation.value !== null) {
                if (this.formData.max_consommation.value < 0) {
                    errors.max_consommation = 'Max upload est invalide';
                }
            }
            // Vérifier que max_session >= max_inactive si les deux sont activés
            if (this.formData.max_session.enabled && this.formData.max_inactive.enabled) {
                if (this.formData.max_session.value !== null && this.formData.max_inactive.value !== null) {
                    if (this.formData.max_session.value < this.formData.max_inactive.value) {
                        errors.max_session = 'Durée max de session doit être plus grande que Durée max d\'inactivité';
                    }
                }
            }

            // Validation de sessions
            if (this.formData.sessions.enabled && this.formData.sessions.value !== null) {
                if (this.formData.sessions.value < 0) {
                    errors.sessions = 'Sessions simultanées est invalide';
                }
            }

            // Validation de accounting
            if (this.formData.accounting.enabled && this.formData.accounting.value !== null) {
                if (this.formData.accounting.value < 0) {
                    errors.accounting = 'Intervalle accounting est invalide';
                }
            }

            this.errors = errors;

            if (Object.keys(errors).length > 0) {
                this.goToFirstErrorTab();
                return false;
            }

            return true;
        },

        prepareSubmitData() {
            const data = {
                csrf_token: this.getCsrfToken(),
                policy_id: this.policy.id
            };

            const fields = ['max_session', 'max_inactive', 'sessions', 'accounting', 'max_download', 'max_upload', 'max_consommation'];

            fields.forEach(field => {
                const current = this.formData[field];
                const original = this.originalData[field];

                // Vérifier si le champ a été modifié
                const hasChanged = this.hasFieldChanged(current, original);

                if (hasChanged) {
                    data[field] = {
                        id: current.id,
                        value: current.value,
                        priority: current.priority,
                        enabled: current.enabled
                    };
                }
            });

            return data;
        },

        hasFieldChanged(current, original) {
            // Si la valeur actuelle est vide/nulle, ne pas considérer comme un changement
            // même si priority ou enabled ont changé
            if (this.isValueEmpty(current.value)) {
                // Si l'original était aussi vide, pas de changement
                if (!original || this.isValueEmpty(original.value)) {
                    return false;
                }
                // Si l'original avait une valeur et maintenant c'est vide, c'est un changement (suppression)
                return true;
            }

            // Si pas de données originales et qu'il y a une valeur, c'est un changement
            if (!original) {
                return true;
            }

            // Si la valeur originale était vide et maintenant il y a une valeur, c'est un changement
            if (this.isValueEmpty(original.value)) {
                return true;
            }

            // Comparer toutes les propriétés si les deux ont des valeurs
            return current.enabled !== original.enabled ||
                current.value !== original.value ||
                current.priority !== original.priority;
        },

        isValueEmpty(value) {
            return value === null ||
                value === undefined ||
                value === '' ||
                (typeof value === 'string' && value.trim() === '');
        },

        goToFirstErrorTab() {
            const tabFieldMapping = {
                0: ['max_session', 'max_inactive', 'sessions', 'accounting', 'max_download', 'max_upload', 'max_consommation'],
            };

            for (const [tabIndex, fields] of Object.entries(tabFieldMapping)) {
                if (fields.some(field => this.errors[field])) {
                    this.activeTab = parseInt(tabIndex);
                    break;
                }
            }
        },

        hasTabError(field) {
            return this.errors.hasOwnProperty(field);
        },

        getError(field) {
            return this.errors[field] || '';
        },

        showNotification(message, type = 'info') {
            toast(message, type);
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        setActiveTab(index) {
            this.activeTab = index;
        },

        nextTab() {
            if (this.activeTab < this.tabs.length - 1) {
                this.activeTab++;
            }
        },

        prevTab() {
            if (this.activeTab > 0) {
                this.activeTab--;
            }
        },
    });
    Alpine.store('userPolicyModal', {
        // État du modal
        isOpen: false,
        isLoading: false,
        user_id: '',
        username: '',
        type: '',
        policies: [],

        // Suivi des politiques étendues
        expandedPolicies: [],

        // Données originales pour détecter les changements
        originalPolicies: [],

        // Messages d'erreur
        errors: {},

        /**
         * Ouvre le modal et charge les politiques de l'utilisateur
         */
        async open(user_id, username, type) {
            label = type == 'user' ? 'Utilisateur ' : 'Groupe ';
            this.user_id = user_id;
            this.type = type;
            this.username = label + username;
            this.isOpen = true;
            this.errors = {};
            this.isLoading = true;
            this.expandedPolicies = [];

            try {
                const response = await fetch(`/dashboard/policies/${type}/get`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        user_id: user_id,
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.loadData(result.data);
                } else {
                    toast(result.message || 'Erreur lors du chargement des données', 'error');
                }
            } catch (error) {
                console.error('Erreur lors du chargement:', error);
                toast('Erreur de connexion au serveur', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Charge les données et sauvegarde l'état original
         */
        loadData(data) {
            this.policies = Array.isArray(data) ? data : [];
            // Créer une copie profonde de l'état original
            this.originalPolicies = JSON.parse(JSON.stringify(this.policies));
        },

        /**
         * Ferme le modal et réinitialise l'état
         */
        close() {
            this.isOpen = false;
            this.isLoading = false;
            this.errors = {};
            this.expandedPolicies = [];
            this.policies = [];
            this.originalPolicies = [];
            this.user_id = '';
            this.username = '';
        },

        /**
         * Bascule l'affichage des détails d'une politique
         */
        toggleDetails(policyId) {
            const index = this.expandedPolicies.indexOf(policyId);
            if (index > -1) {
                this.expandedPolicies.splice(index, 1);
            } else {
                this.expandedPolicies.push(policyId);
            }
        },

        /**
         * Modifie l'état d'application d'une politique
         */
        togglePolicy(policyId, applied) {
            const policy = this.policies.find(p => p.id === policyId);
            if (policy) {
                policy.applied = applied;
            }
        },

        toggleSpecialPolicy(policyId, is_special) {
            const policy = this.policies.find(p => p.id === policyId);
            if (policy) {
                policy.is_special = is_special;
            }
        },
        /**
         * Détecte s'il y a des modifications
         */
        hasChanges() {
            if (this.policies.length !== this.originalPolicies.length) {
                return true;
            }

            return this.policies.some((policy, index) => {
                const original = this.originalPolicies.find(p => p.id === policy.id);
                return !original || original.applied !== policy.applied || original.is_special !== policy.is_special;
            });
        },

        /**
         * Prépare les données modifiées pour l'envoi
         */
        prepareSubmitData() {
            const changedPolicies = [];

            this.policies.forEach(policy => {
                const original = this.originalPolicies.find(p => p.id === policy.id);

                // Si la politique n'existait pas ou si son état a changé
                if (!original || original.applied !== policy.applied || original.is_special !== policy.is_special) {
                    let p = {
                        id: policy.id,
                    };
                    if (original.applied !== policy.applied) {
                        p.applied = policy.applied;

                    }
                    if (original.is_special !== policy.is_special) {
                        p.is_special = policy.is_special;
                        if (!p.hasOwnProperty('applied')) {
                            p.applied = policy.applied;
                        }

                    }

                    changedPolicies.push(p);
                }
            });

            return {
                user_id: this.user_id,
                policies: changedPolicies,
                csrf_token: this.getCsrfToken()
            };
        },

        /**
         * Valide le formulaire avant soumission
         */
        validateForm() {
            if (!this.user_id) {
                toast('ID utilisateur manquant', 'error');
                return false;
            }

            if (!this.hasChanges()) {
                toast('Aucune modification détectée', 'warning');
                return false;
            }

            return true;
        },

        /**
         * Soumet les modifications au serveur
         */
        async submitForm() {
            if (this.isLoading) return;

            this.isLoading = true;
            this.errors = {};

            try {
                // Validation côté client
                if (!this.validateForm()) {
                    this.isLoading = false;
                    return;
                }

                // Préparer les données pour l'envoi
                const submitData = this.prepareSubmitData();

                // Vérifier qu'il y a bien des politiques modifiées
                if (!submitData.policies || submitData.policies.length === 0) {
                    this.isLoading = false;
                    toast('Aucune modification détectée', 'warning');
                    return;
                }
                // Envoyer la requête vers la bonne route
                const response = await fetch(`/dashboard/policies/${this.type}/set`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(submitData)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    toast(result.message || 'Politiques mises à jour avec succès', 'success');

                    // Mettre à jour l'état original avec les nouvelles données
                    this.originalPolicies = JSON.parse(JSON.stringify(this.policies));
                } else {
                    if (result.errors) {
                        this.errors = result.errors;
                        // Afficher les erreurs
                        Object.values(result.errors).forEach(error => {
                            toast(error, 'error');
                        });
                    }
                    toast(result.message || 'Une erreur est survenue lors de la mise à jour', 'error');
                }
            } catch (error) {
                console.error('Erreur lors de la soumission:', error);
                toast('Erreur de connexion au serveur', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Récupère le token CSRF
         */
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        /**
         * Réinitialise les modifications et restaure l'état original
         */
        resetChanges() {
            this.policies = JSON.parse(JSON.stringify(this.originalPolicies));
            toast('Modifications annulées', 'info');
        }
    });
});