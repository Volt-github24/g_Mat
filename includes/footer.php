            </div> <!-- Fin du contenu principal -->
        </div> <!-- Fin de la ligne -->
    </div> <!-- Fin du container-fluid -->
    
    <!-- Footer -->
    <footer class="footer mt-auto py-3 bg-dark text-white">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <span>© 2024 ONACC - Gestion de Matériel Informatique</span>
                </div>
                <div class="col-md-6 text-end">
                    <small>Version 1.0 | 
                        <?php 
                        echo isset($_SESSION['user_id']) ? 
                            'Connecté en tant que: ' . htmlspecialchars($_SESSION['nom']) . 
                            ' (' . htmlspecialchars($_SESSION['role']) . ')' : 
                            'Non connecté'; 
                        ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Scripts personnalisés -->
    <script src="../assets/js/app.js"></script>
    
    <!-- Affichage des notifications -->
    <?php if (isset($_SESSION['notification'])): ?>
    <script>
        $(document).ready(function() {
            showNotification('<?php echo $_SESSION['notification']['type']; ?>', 
                           '<?php echo addslashes($_SESSION['notification']['message']); ?>');
            <?php unset($_SESSION['notification']); ?>
        });
    </script>
    <?php endif; ?>
    
    <script>
    // Fonction pour afficher les notifications
    function showNotification(type, message) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const icon = {
            'success': 'bi-check-circle',
            'error': 'bi-exclamation-circle',
            'warning': 'bi-exclamation-triangle',
            'info': 'bi-info-circle'
        }[type] || 'bi-info-circle';
        
        const alertHTML = `
            <div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="bi ${icon} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        `;
        
        $('body').append(alertHTML);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').alert('close');
        }, 5000);
    }
    
    // Confirmation avant suppression
    function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
        return confirm(message);
    }
    
    // Initialiser DataTables
    $(document).ready(function() {
        $('table.data-table').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/fr-FR.json"
            },
            "pageLength": 25,
            "responsive": true
        });
    });
    </script>
</body>
</html>