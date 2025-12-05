/**
 * Fonctionnalités modernes additionnelles
 */

// Gestion du thème (clair/sombre)
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Animation de transition
    document.body.style.transition = 'background-color 0.3s ease';
}

// Recherche en temps réel
function initLiveSearch() {
    const searchInputs = document.querySelectorAll('[data-live-search]');
    
    searchInputs.forEach(input => {
        let searchTimeout;
        
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            const targetId = this.getAttribute('data-live-search');
            const target = document.getElementById(targetId);
            
            if (!target) return;
            
            // Afficher le loader
            showLoader(target);
            
            searchTimeout = setTimeout(() => {
                performLiveSearch(query, target);
            }, 300);
        });
    });
}

function performLiveSearch(query, target) {
    // Simulation de recherche AJAX
    setTimeout(() => {
        if (query.length < 2) {
            target.innerHTML = '<p class="text-muted">Entrez au moins 2 caractères</p>';
            return;
        }
        
        // Remplacer par un véritable appel AJAX
        fetch(`api/search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data, target);
            })
            .catch(error => {
                target.innerHTML = '<p class="text-danger">Erreur de recherche</p>';
            });
    }, 500);
}

function displaySearchResults(results, target) {
    if (results.length === 0) {
        target.innerHTML = '<p class="text-muted">Aucun résultat trouvé</p>';
        return;
    }
    
    let html = '<div class="search-results">';
    results.forEach(result => {
        html += `
            <div class="search-result-item" onclick="selectSearchResult('${result.id}', '${result.type}')">
                <div class="search-result-icon">
                    <i class="fas fa-${getIconForType(result.type)}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-subtitle">${result.subtitle}</div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    target.innerHTML = html;
}

function getIconForType(type) {
    const icons = {
        'patient': 'user',
        'medecin': 'user-md',
        'rendezvous': 'calendar',
        'consultation': 'stethoscope',
        'medicament': 'pills'
    };
    return icons[type] || 'file';
}

// Notifications système
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type} slide-in`;
    notification.innerHTML = `
        <div class="notification-icon">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
        </div>
        <div class="notification-content">
            <div class="notification-message">${message}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    const container = document.getElementById('notifications-container') || createNotificationsContainer();
    container.appendChild(notification);
    
    // Auto-suppression
    if (duration > 0) {
        setTimeout(() => {
            closeNotification(notification.querySelector('.notification-close'));
        }, duration);
    }
}

function createNotificationsContainer() {
    const container = document.createElement('div');
    container.id = 'notifications-container';
    container.className = 'notifications-container';
    document.body.appendChild(container);
    return container;
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function closeNotification(button) {
    const notification = button.closest('.notification');
    notification.classList.add('slide-out');
    setTimeout(() => {
        notification.remove();
    }, 300);
}

// Loader animé
function showLoader(element) {
    element.innerHTML = `
        <div class="loader-container">
            <div class="loader"></div>
            <p>Chargement...</p>
        </div>
    `;
}

function hideLoader(element) {
    const loader = element.querySelector('.loader-container');
    if (loader) {
        loader.remove();
    }
}

// Validation de formulaire avancée
function initAdvancedFormValidation() {
    const forms = document.querySelectorAll('[data-validate-advanced]');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Validation en temps réel
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('error')) {
                    validateField(input);
                }
            });
        });
        
        // Validation à la soumission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
    });
}

function validateField(field) {
    const rules = field.getAttribute('data-rules') || '';
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Règles de validation
    if (rules.includes('required') && !value) {
        isValid = false;
        errorMessage = 'Ce champ est obligatoire';
    } else if (rules.includes('email') && value && !isValidEmail(value)) {
        isValid = false;
        errorMessage = 'Veuillez entrer une adresse email valide';
    } else if (rules.includes('phone') && value && !isValidPhone(value)) {
        isValid = false;
        errorMessage = 'Veuillez entrer un numéro de téléphone valide';
    } else if (rules.includes('date') && value && !isValidDate(value)) {
        isValid = false;
        errorMessage = 'Veuillez entrer une date valide';
    }
    
    // Affichage du résultat
    const feedback = field.parentNode.querySelector('.field-feedback') || createFieldFeedback(field);
    
    if (isValid) {
        field.classList.remove('error');
        field.classList.add('success');
        feedback.textContent = '';
        feedback.className = 'field-feedback success';
    } else {
        field.classList.remove('success');
        field.classList.add('error');
        feedback.textContent = errorMessage;
        feedback.className = 'field-feedback error';
    }
    
    return isValid;
}

function createFieldFeedback(field) {
    const feedback = document.createElement('div');
    feedback.className = 'field-feedback';
    field.parentNode.appendChild(feedback);
    return feedback;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function isValidPhone(phone) {
    return /^[\d\s\-\+\(\)]+$/.test(phone) && phone.replace(/\D/g, '').length >= 10;
}

function isValidDate(date) {
    return !isNaN(Date.parse(date));
}

// Tableaux interactifs
function initInteractiveTables() {
    const tables = document.querySelectorAll('.table-interactive');
    
    tables.forEach(table => {
        // Tri des colonnes
        const headers = table.querySelectorAll('th[data-sort]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => sortTable(table, header));
        });
        
        // Filtrage
        const filterInput = table.parentNode.querySelector('[data-table-filter]');
        if (filterInput) {
            filterInput.addEventListener('input', () => filterTable(table, filterInput.value));
        }
    });
}

function sortTable(table, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const isAscending = header.classList.contains('sort-asc');
    
    // Supprimer les classes de tri existantes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Trier les lignes
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        if (isAscending) {
            return bValue.localeCompare(aValue);
        } else {
            return aValue.localeCompare(bValue);
        }
    });
    
    // Réorganiser les lignes
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour la classe de tri
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
}

function filterTable(table, query) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    const lowerQuery = query.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(lowerQuery) ? '' : 'none';
    });
}

// Export de données
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            let text = col.textContent.trim();
            // Échapper les guillemets et ajouter des guillemets si nécessaire
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = `"${text.replace(/"/g, '""')}"`;
            }
            return text;
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    downloadFile(csvContent, filename, 'text/csv');
}

function downloadFile(content, filename, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Raccourcis clavier
function initKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K pour la recherche
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('[data-live-search], input[type="search"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Échap pour fermer les modales
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                openModal.classList.remove('show');
            }
        }
        
        // Ctrl/Cmd + S pour sauvegarder (dans les formulaires)
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            const form = document.querySelector('form');
            if (form) {
                e.preventDefault();
                form.submit();
            }
        }
    });
}

// Initialisation des nouvelles fonctionnalités
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initLiveSearch();
    initAdvancedFormValidation();
    initInteractiveTables();
    initKeyboardShortcuts();
    initGlobalSearch();
    
    // Animation des éléments au scroll
    initScrollAnimations();
});

// Recherche globale
function initGlobalSearch() {
    const searchInput = document.getElementById('global-search');
    const searchResults = document.getElementById('search-results');
    let searchTimeout;
    
    if (!searchInput || !searchResults) return;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }
        
        // Afficher le loader
        searchResults.innerHTML = `
            <div class="search-loader">
                <div class="loader"></div>
                <p>Recherche en cours...</p>
            </div>
        `;
        searchResults.style.display = 'block';
        
        searchTimeout = setTimeout(() => {
            performGlobalSearch(query);
        }, 300);
    });
    
    // Fermer la recherche en cliquant dehors
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
}

function performGlobalSearch(query) {
    const searchResults = document.getElementById('search-results');
    
    fetch(`../api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displayGlobalSearchResults(data, searchResults);
        })
        .catch(error => {
            searchResults.innerHTML = `
                <div class="search-no-results">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erreur lors de la recherche</p>
                </div>
            `;
        });
}

function displayGlobalSearchResults(results, container) {
    if (results.length === 0) {
        container.innerHTML = `
            <div class="search-no-results">
                <i class="fas fa-search"></i>
                <p>Aucun résultat trouvé</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="search-results">';
    
    // Grouper par type
    const groupedResults = {};
    results.forEach(result => {
        if (!groupedResults[result.type]) {
            groupedResults[result.type] = [];
        }
        groupedResults[result.type].push(result);
    });
    
    // Afficher par type
    const typeLabels = {
        'patient': 'Patients',
        'medecin': 'Médecins',
        'rendezvous': 'Rendez-vous',
        'consultation': 'Consultations',
        'medicament': 'Médicaments'
    };
    
    Object.keys(groupedResults).forEach(type => {
        html += `
            <div class="search-category">
                <div class="search-category-title">
                    <i class="fas fa-${getIconForType(type)}"></i>
                    ${typeLabels[type] || type}
                </div>
        `;
        
        groupedResults[type].forEach(result => {
            html += `
                <a href="${result.url}" class="search-result-item" onclick="closeSearchResults()">
                    <div class="search-result-icon ${result.type}">
                        <i class="fas fa-${getIconForType(result.type)}"></i>
                    </div>
                    <div class="search-result-content">
                        <div class="search-result-title">${result.title}</div>
                        <div class="search-result-subtitle">${result.subtitle}</div>
                    </div>
                </a>
            `;
        });
        
        html += '</div>';
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function closeSearchResults() {
    const searchResults = document.getElementById('search-results');
    if (searchResults) {
        searchResults.style.display = 'none';
    }
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        searchInput.value = '';
    }
}

function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);
    
    // Observer les éléments avec animation
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
}