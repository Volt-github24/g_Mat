/**
 * Scripts JavaScript pour l'application
 */

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser les popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Auto-dismiss les alertes après 5 secondes
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// Fonction pour afficher un modal de confirmation
function showConfirmationModal(title, message, callback) {
    const modalHTML = `
        <div class="modal fade" id="confirmationModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-danger" id="confirmButton">Confirmer</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter le modal au DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Afficher le modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
    
    // Gérer la confirmation
    document.getElementById('confirmButton').addEventListener('click', function() {
        modal.hide();
        callback();
        
        // Nettoyer le modal
        setTimeout(() => {
            document.getElementById('confirmationModal').remove();
        }, 300);
    });
    
    // Nettoyer le modal quand il est fermé
    document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Fonction pour exporter un tableau en Excel
function exportTableToExcel(tableId, filename = 'export') {
    const table = document.getElementById(tableId);
    if (!table) {
        console.error('Tableau non trouvé:', tableId);
        return;
    }
    
    // Créer un workbook
    const wb = XLSX.utils.book_new();
    
    // Convertir le tableau en worksheet
    const ws = XLSX.utils.table_to_sheet(table);
    
    // Ajouter le worksheet au workbook
    XLSX.utils.book_append_sheet(wb, ws, "Feuille1");
    
    // Télécharger le fichier Excel
    XLSX.writeFile(wb, `${filename}_${new Date().toISOString().slice(0,10)}.xlsx`);
}

// Fonction pour générer un QR Code
function generateQRCode(elementId, text) {
    if (!text) return;
    
    // Nettoyer l'élément
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '';
        
        // Générer le QR Code
        new QRCode(element, {
            text: text,
            width: 128,
            height: 128,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
}

// Fonction pour filtrer un tableau
function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    const searchLower = searchText.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchLower) ? '' : 'none';
    });
}

// Fonction pour formater un nombre en devise
function formatCurrency(amount, currency = 'FCFA') {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount) + ' ' + currency;
}

// Fonction pour afficher un spinner de chargement
function showLoading() {
    const loadingHTML = `
        <div class="loading-overlay" style="
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
        ">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', loadingHTML);
}

// Fonction pour cacher le spinner
function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
        overlay.remove();
    }
}

// Gestionnaire pour les formulaires AJAX
document.addEventListener('submit', function(e) {
    const form = e.target;
    
    if (form.classList.contains('ajax-form')) {
        e.preventDefault();
        
        showLoading();
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: form.method,
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                showNotification('success', data.message);
                
                if (data.redirect) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
                
                if (data.reload) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                showNotification('error', data.message);
            }
        })
        .catch(error => {
            hideLoading();
            showNotification('error', 'Erreur: ' + error.message);
        });
    }
});