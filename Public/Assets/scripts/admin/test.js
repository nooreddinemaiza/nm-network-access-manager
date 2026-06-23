function usersModalX() {
    return {
        // État
        isOpen: false,
        loading: false,
        viewModalOpen: false,
        editModalOpen: false,
        editLoading: false,

        // Données
        users: [],
        groups: [],
        filteredUsers: [],
        selectedUser: null,

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

        // Filtres
        searchTerm: '',
        selectedGroup: 'all',
        selectedStatus: 'all',
        onlineFilter: 'all',

        // Pagination
        currentPage: 1,
        itemsPerPage: 10,

        // Stats
        stats: {
            total: 0,
            active: 0,
            suspended: 0,
            expired: 0
        },
        errors: {},

        // Computed - Groupes disponibles
        get availableGroups() {
            return window.groups;
        },

        // Computed - Total pages
        get totalPages() {
            return Math.ceil(this.filteredUsers.length / this.itemsPerPage);
        },

        // Computed - Index de début
        get startIndex() {
            return (this.currentPage - 1) * this.itemsPerPage;
        },

        // Computed - Index de fin
        get endIndex() {
            return Math.min(this.startIndex + this.itemsPerPage, this.filteredUsers.length);
        },

        // Computed - Utilisateurs paginés
        get paginatedUsers() {
            return this.filteredUsers.slice(this.startIndex, this.endIndex);
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
            window.addEventListener('open-users-modal', () => {
                this.openModal();
            });
        },

        // Ouvrir le modal
        async openModal() {
            this.isOpen = true;
            await this.loadUsers();
        },

        // Fermer le modal
        closeModal() {
            this.isOpen = false;
            this.resetFilters();
        },

        // Charger les utilisateurs
        async loadUsers() {
            this.loading = true;

            try {
                const response = await fetch('/dashboard/users/list', {
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
                    const statusMapping = {
                        'suspended': 'Suspendu',
                        'expired': 'Expiré',
                        'active': 'Actif',
                    };

                    this.users = (result.data || []).map((user, index) => ({
                        id: user.id || `temp_${index}`,
                        username: user.username || 'N/A',
                        fullname: user.fullname || null,
                        group: user.group || 'Non assigné',
                        group_id: user.group_id || -1,
                        status: user.status,
                        labelStatus: statusMapping[user.status] || 'Actif',
                        original_status: user.status,
                        expires_at: user.expires_at || null,
                        created_at: user.created_at || null
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
            this.stats.total = this.users.length;
            this.stats.active = this.users.filter(u => u.status === 'active').length;
            this.stats.suspended = this.users.filter(u => u.status === 'suspended').length;
            this.stats.expired = this.users.filter(u => u.status === 'expired').length;
        },

        // Appliquer les filtres
        applyFilters() {
            let filtered = [...this.users];

            if (this.searchTerm.trim()) {
                const search = this.searchTerm.toLowerCase();
                filtered = filtered.filter(user => {
                    const username = (user.username || '').toLowerCase();
                    const fullname = (user.fullname || '').toLowerCase();
                    const group = (user.group || '').toLowerCase();
                    const date = this.formatDate(user.expires_at).toLowerCase();
                    return username.includes(search) ||
                        fullname.includes(search) ||
                        group.includes(search) ||
                        date.includes(search);
                });
            }

            if (this.selectedGroup !== 'all') {
                filtered = filtered.filter(u => u.group === this.selectedGroup);
            }

            if (this.selectedStatus !== 'all') {
                filtered = filtered.filter(u => u.status === this.selectedStatus);
            }

            this.filteredUsers = filtered;
            this.currentPage = 1;
        },

        // Réinitialiser les filtres
        resetFilters() {
            this.searchTerm = '';
            this.selectedGroup = 'all';
            this.selectedStatus = 'all';
            this.currentPage = 1;
            this.filteredUsers = [...this.users];
        },

        // Navigation pagination
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
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
                    this.calculateStats();
                    this.applyFilters();

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

        // Voir les détails d'un utilisateur
        viewUser(user) {
            this.selectedUser = user;
            this.viewModalOpen = true;
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
        }
    };
}