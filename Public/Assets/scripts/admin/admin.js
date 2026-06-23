document.addEventListener('alpine:init', () => {
    Alpine.store('addManagerModal', {
        // État du modal
        isOpen: false,
        loading: false,

        // Données du formulaire
        formData: {
            fullname: '',
            email: '',
            password: '',
            status: 'active',
            csrf_token: ''
        },

        // Erreurs de validation
        errors: {},

        // Ouvrir le modal
        open() {
            Alpine.store('managersListModal').close();
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
                fullname: '',
                email: '',
                password: '',
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
            if (!this.formData.fullname.trim()) {
                this.errors.fullname = 'Le nom complet est obligatoire';
            } else if (this.formData.fullname.trim().length < 3) {
                this.errors.fullname = 'Le nom complet doit contenir au moins 3 caractères';
            }

            // Validation email
            if (!this.formData.email.trim()) {
                this.errors.email = 'L\'email est obligatoire';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.formData.email)) {
                this.errors.email = 'Format d\'email invalide';
            }

            // Validation password
            if (!this.formData.password) {
                this.errors.password = 'Le mot de passe est obligatoire';
            } else if (this.formData.password.length < 6) {
                this.errors.password = 'Le mot de passe doit contenir au moins 6 caractères';
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
                const response = await fetch('/dashboard/managers/add', {
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
    Alpine.store('managersListModal', {
        // État du modal
        isOpen: false,
        loading: false,
        profileView: false,

        // Données
        managers: [],
        filteredManagers: [],
        searchTerm: '',
        selectedStatus: 'all',
        currentPage: 1,
        itemsPerPage: 10,
        manager: {},
        // Statistiques
        stats: {
            total: 0,
            active: 0,
            inactive: 0,
        },

        // Ouvrir le modal
        async open() {
            this.isOpen = true;
            await this.loadManagers();
        },

        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetFilters();
            this.managers = [];
        },
        profileClose() {
            this.profileView = false;
        },
        // Réinitialiser les filtres
        resetFilters() {
            this.searchTerm = '';
            this.selectedStatus = 'all';
            this.currentPage = 1;
            this.filteredManagers = [...this.managers];
        },

        // Charger la liste des managers
        async loadManagers() {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/managers/list', {
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
                    this.managers = result.data || [];
                    this.filteredManagers = [...this.managers];
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
            this.stats.total = this.managers.length;
            this.stats.active = this.managers.filter(m => m.status === 'active').length;
            this.stats.inactive = this.managers.filter(m => m.status === 'inactive').length;
        },

        // Filtrer les managers
        filterManagers() {
            let filtered = [...this.managers];

            // Filtrage par recherche
            if (this.searchTerm.trim()) {
                const search = this.searchTerm.toLowerCase();
                filtered = filtered.filter(manager =>
                    manager.fullname.toLowerCase().includes(search) ||
                    manager.email.toLowerCase().includes(search) ||
                    (manager.phone && manager.phone.includes(search))
                );
            }

            // Filtrage par statut
            if (this.selectedStatus !== 'all') {
                filtered = filtered.filter(manager => manager.status === this.selectedStatus);
            }

            this.filteredManagers = filtered;
            this.currentPage = 1; // Reset to first page
        },

        // Pagination
        get totalPages() {
            return Math.ceil(this.filteredManagers.length / this.itemsPerPage);
        },

        get paginatedManagers() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredManagers.slice(start, end);
        },

        // Navigation pagination
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        // Changer le statut d'un manager
        async toggleStatus(managerId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            try {
                const response = await fetch('/dashboard/managers/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        manager_id: managerId,
                        status: newStatus,
                        csrf_token: this.getCsrfToken()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // Mettre à jour localement
                    const managerIndex = this.managers.findIndex(m => m.id === managerId);
                    if (managerIndex !== -1) {
                        this.managers[managerIndex].status = newStatus;
                        this.filterManagers();
                        this.calculateStats();
                    }
                } else {
                    if (result.status == 'session_timed_out') {
                        sessionExpired();
                    }
                    toast(result.message, 'error');
                }
            } catch (error) {
                toast(result.message, 'error');
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
        openProfile(manager) {
            this.manager = manager;
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
    Alpine.store('editManagerModal', {
        // État du modal
        isOpen: false,
        loading: false,
        managerId: null,

        // Données du formulaire
        formData: {
            fullname: '',
            email: '',
            password: '',
            status: 'active',
            csrf_token: ''
        },

        // Données originales pour comparaison
        originalData: {},

        // Erreurs de validation
        errors: {},

        // Ouvrir le modal avec les données du manager
        open(manager) {
            Alpine.store('managersListModal').close();
            Alpine.store('managersListModal').profileClose();
            this.isOpen = true;
            this.managerId = manager.id;
            this.loadManagerData(manager);
        },

        // Charger les données du manager
        loadManagerData(manager) {
            this.formData = {
                fullname: manager.fullname || '',
                email: manager.email || '',
                password: '', // Toujours vide pour l'édition
                status: manager.status || 'active',
                csrf_token: this.getCsrfToken()
            };

            // Sauvegarder les données originales (sans le mot de passe)
            this.originalData = {
                fullname: manager.fullname || '',
                email: manager.email || '',
                status: manager.status || 'active'
            };

            this.errors = {};
            this.loading = false;
        },

        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetForm();
        },

        // Réinitialiser le formulaire
        resetForm() {
            this.formData = {
                fullname: '',
                email: '',
                password: '',
                status: 'active',
                csrf_token: ''
            };
            this.originalData = {};
            this.managerId = null;
            this.errors = {};
            this.loading = false;
        },

        // Obtenir le token CSRF
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        // Vérifier si des modifications ont été apportées
        hasChanges() {
            const currentData = {
                fullname: this.formData.fullname,
                email: this.formData.email,
                status: this.formData.status
            };

            return JSON.stringify(currentData) !== JSON.stringify(this.originalData) ||
                this.formData.password.length > 0;
        },

        // Validation du formulaire
        validateForm() {
            this.errors = {};

            // Validation fullname
            if (!this.formData.fullname.trim()) {
                this.errors.fullname = 'Le nom complet est obligatoire';
            } else if (this.formData.fullname.trim().length < 2) {
                this.errors.fullname = 'Le nom complet doit contenir au moins 2 caractères';
            }

            // Validation email
            if (!this.formData.email.trim()) {
                this.errors.email = 'L\'email est obligatoire';
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.formData.email)) {
                this.errors.email = 'Format d\'email invalide';
            }

            // Validation password (optionnel pour l'édition)
            if (this.formData.password && this.formData.password.length < 6) {
                this.errors.password = 'Le mot de passe doit contenir au moins 6 caractères';
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

            // Vérifier s'il y a des modifications
            if (!this.hasChanges()) {
                this.errors.general = 'Aucune modification détectée';
                return;
            }

            this.loading = true;
            try {
                // Préparer les données à envoyer
                const dataToSend = {
                    manager_id: this.managerId,
                    fullname: this.formData.fullname,
                    email: this.formData.email,
                    status: this.formData.status,
                    csrf_token: this.formData.csrf_token
                };

                // Ajouter le mot de passe seulement s'il est renseigné
                if (this.formData.password) {
                    dataToSend.password = this.formData.password;
                }

                const response = await fetch('/dashboard/managers/edit', {
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
                    if (result.status == 'session_timed_out') {
                        sessionExpired();
                    }
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
                console.error('Erreur:', error);
            } finally {
                this.loading = false;
            }
        }
    });
    Alpine.store('groupsListModal', {
        // État du modal
        isOpen: false,
        loading: false,
        profileView: false,

        // Données
        groups: [],
        moderators: [],
        filteredGroups: [],
        searchTerm: '',
        selectedStatus: 'all',
        currentPage: 1,
        itemsPerPage: 10,
        group: {},
        // Statistiques
        stats: {
            total: 0,
            active: 0,
            inactive: 0,
        },

        // Ouvrir le modal
        async open() {
            this.isOpen = true;
            await this.loadGroups();
        },

        // Fermer le modal
        close() {
            this.isOpen = false;
            this.resetFilters();
            this.groups = [];
        },
        // Réinitialiser les filtres
        resetFilters() {
            this.searchTerm = '';
            this.selectedStatus = 'all';
            this.currentPage = 1;
            this.filteredGroups = [...this.groups];
        },

        // Charger la liste des groups
        async loadGroups() {
            this.loading = true;
            try {
                const response = await fetch('/dashboard/groups/list', {
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
                    this.groups = result.data || [];
                    this.filteredGroups = [...this.groups];
                    this.moderators = result.moderators;
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

        // Ouvrir le profil du groupe (NOUVELLE MÉTHODE)
        openProfile(group) {
            if (Alpine.store('groupViewModal')) {
                Alpine.store('groupViewModal').open(group);
            }
        },

        // Calculer les statistiques
        calculateStats() {
            this.stats.total = this.groups.length;
        },

        // Filtrer les groups
        filterGroups() {
            let filtered = [...this.groups];

            // Filtrage par recherche
            if (this.searchTerm.trim()) {
                const search = this.searchTerm.toLowerCase();
                filtered = filtered.filter(group =>
                    group.name.toLowerCase().includes(search) ||
                    group.description.toLowerCase().includes(search)
                );
            }

            // Filtrage par statut
            if (this.selectedStatus !== 'all') {
                filtered = filtered.filter(group => group.status === this.selectedStatus);
            }

            this.filteredGroups = filtered;
            this.currentPage = 1; // Reset to first page
        },

        // Pagination
        get totalPages() {
            return Math.ceil(this.filteredGroups.length / this.itemsPerPage);
        },

        get paginatedGroups() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredGroups.slice(start, end);
        },

        // Changer le manager d'un groupe
        async changeModerator(group, newModerator, event) {
            const oldModerator = group.moderator;

            if (oldModerator === newModerator) {
                return;
            }

            // Confirmation
            confirmMessage = `Voulez-vous vraiment changer le manager de ${group.name}?`;
            if (newModerator == -1) {
                confirmMessage = `Voulez-vous laisser le groupe "${group.name}" sans manager?`
            }
            const confirmed = await confirmToast(confirmMessage);

            if (!confirmed) {
                if (event && event.target) {
                    event.target.value = oldModerator;
                }
                return;
            }

            group.moderator = newModerator;

            try {
                const response = await fetch('/dashboard/groups/switch-moderator', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        group_id: group.id,
                        moderator: newModerator
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(`Le mangager de ${group.name} a été changé`);
                    await this.loadGroups();
                } else {
                    group.moderator = oldModerator;
                    if (event && event.target) {
                        event.target.value = oldModerator;
                    }

                    this.showError(result.message || 'Erreur lors du changement du manager');

                    if (result.status === 'session_timed_out' && typeof sessionExpired === 'function') {
                        sessionExpired();
                    }
                }
            } catch (error) {
                group.moderator = oldModerator;
                if (event && event.target) {
                    event.target.value = oldModerator;
                }

                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            }
        },

        // Toggle status du groupe (NOUVELLE MÉTHODE)
        async toggleStatus(groupId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            const actionText = newStatus === 'active' ? 'activer' : 'désactiver';

            const confirmed = await confirmToast(`Voulez-vous vraiment ${actionText} ce groupe?`);

            if (!confirmed) return;

            try {
                const response = await fetch('/dashboard/groups/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        group_id: groupId,
                        status: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(`Groupe ${actionText === 'activer' ? 'activé' : 'désactivé'} avec succès`);
                    await this.loadGroups();
                } else {
                    if (result.status === 'session_timed_out') {
                        sessionExpired();
                    }
                    this.showError(result.message || `Erreur lors de l'opération`);
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            }
        },

        // Navigation pagination
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        // Obtenir le token CSRF
        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        // Formater la date
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

        // Obtenir le texte du statut
        getStatusText(status) {
            const texts = {
                'active': 'Actif',
                'inactive': 'Inactif',
            };
            return texts[status] || status;
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        }
    });
    Alpine.store('groupViewModal', {
        isOpen: false,
        group: null,

        open(group) {
            this.group = group;
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.group = null;
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
        }
    });
    Alpine.store('editGroupModal', {
        isOpen: false,
        loading: false,
        group: {
            id: '',
            name: '',
            description: '',
            max_members: 0,
            moderator: ''
        },
        originalGroup: {}, // Stocke les valeurs originales
        errors: [],

        open(group) {
            // Conserver une copie des valeurs originales
            this.originalGroup = {
                id: group.id,
                name: group.name,
                description: group.description,
                moderator: group.moderator,
                max_members: group.max_members
            };

            // Copier pour l'édition
            this.group = {
                id: group.id,
                name: group.name,
                description: group.description,
                moderator: group.moderator,
                max_members: group.max_members
            };

            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.group = {
                id: '',
                name: '',
                description: '',
                max_members: 0,
                moderator: ''
            };
            this.originalGroup = {};
        },

        // Détecte les champs qui ont changé
        getChangedFields() {
            const changes = { id: this.group.id };

            if (this.group.name !== this.originalGroup.name) {
                changes.name = this.group.name;
            }

            if (this.group.description !== this.originalGroup.description) {
                changes.description = this.group.description;
            }

            if (this.group.max_members !== this.originalGroup.max_members) {
                changes.max_members = this.group.max_members;
            }

            if (this.group.moderator !== this.originalGroup.moderator) {
                changes.moderator = this.group.moderator;
            }

            return changes;
        },

        // Vérifie s'il y a des modifications
        hasChanges() {
            return this.group.name !== this.originalGroup.name ||
                this.group.description !== this.originalGroup.description ||
                this.group.max_members !== this.originalGroup.max_members ||
                this.group.moderator !== this.originalGroup.moderator;
        },

        async save() {
            // Vérifier s'il y a des modifications
            if (!this.hasChanges()) {
                this.showError('Aucune modification détectée');
                return;
            }

            if (!this.validateForm()) {
                return;
            }

            this.loading = true;

            try {
                // Récupérer uniquement les champs modifiés
                const changedFields = this.getChangedFields();

                const response = await fetch('/dashboard/groups/edit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        ...changedFields
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('Groupe modifié avec succès');
                    this.close();
                    this.errors = [];

                    if (Alpine.store('groupsListModal')) {
                        await Alpine.store('groupsListModal').loadGroups();
                    }
                } else {
                    if (result.status === 'session_timed_out') {
                        sessionExpired();
                    }
                    this.showError(result.message || 'Erreur lors de la modification');
                    this.errors = result.errors;
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            if (!this.group.name || this.group.name.trim() === '') {
                this.showError('Le nom du groupe est requis');
                return false;
            }

            if (!this.group.description || this.group.description.trim() === '') {
                this.showError('La description est requise');
                return false;
            }

            return true;
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        }
    });
    Alpine.store('addGroupModal', {
        isOpen: false,
        loading: false,
        group: {
            name: '',
            description: '',
            moderator: '',
            max_members: 40
        },
        errors: [],
        open() {
            this.resetForm();
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
            this.resetForm();
        },

        resetForm() {
            this.group = {
                name: '',
                description: '',
                moderator: '',
                max_members: 40
            };
        },

        async save() {
            if (!this.validateForm()) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('/dashboard/groups/add', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: this.getCsrfToken(),
                        name: this.group.name,
                        description: this.group.description,
                        moderator: this.group.moderator,
                        max_members: this.group.max_members
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess('Groupe créé avec succès');
                    this.errors = [];
                    this.close();
                    if (Alpine.store('groupsListModal')) {
                        await Alpine.store('groupsListModal').loadGroups();
                    }
                } else {
                    if (result.status === 'session_timed_out') {
                        sessionExpired();
                    }
                    this.errors = result.errors;
                    this.showError(result.message || 'Erreur lors de la création');
                }
            } catch (error) {
                console.error('Erreur:', error);
                this.showError('Erreur de connexion au serveur');
            } finally {
                this.loading = false;
            }
        },

        validateForm() {
            if (!this.group.name || this.group.name.trim() === '') {
                this.showError('Le nom du groupe est requis');
                return false;
            }

            if (!this.group.description || this.group.description.trim() === '') {
                this.showError('La description est requise');
                return false;
            }

            return true;
        },

        getCsrfToken() {
            const metaTag = document.querySelector('meta[name="dashbord-csrf"]');
            return metaTag ? metaTag.getAttribute('content') : '';
        },

        showError(message) {
            if (typeof toast === 'function') {
                toast(message, 'error');
            } else {
                console.error('✗', message);
            }
        },

        showSuccess(message) {
            if (typeof toast === 'function') {
                toast(message, 'success');
            } else {
                console.log('✓', message);
            }
        }
    });
});
