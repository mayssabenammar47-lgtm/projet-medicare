<?php
/**
 * projet-medicare/includes/footer.php
 * Pied de page commun à toutes les pages
 */
?>
    </main>
    
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>MediCare</h3>
                    <p>Plateforme de gestion de cabinet médical</p>
                    <p>&copy; <?php echo date('Y'); ?> MediCare. Tous droits réservés.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Liens utiles</h4>
                    <ul>
                        <li><a href="../pages/dashboard.php">Tableau de bord</a></li>
                        <li><a href="../pages/patients/patients.php">Patients</a></li>
                        <li><a href="../pages/rendez_vous/rendez_vous.php">Rendez-vous</a></li>
                        <li><a href="../pages/consultations/consultation_form.php">Consultations</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p><i class="fas fa-envelope"></i> contact@medicare.com</p>
                    <p><i class="fas fa-phone"></i> 01 23 45 67 89</p>
                    <p><i class="fas fa-map-marker-alt"></i> 15 Rue de la Santé, 75014 Paris</p>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="../js/script.js"></script>
    
    <?php if (isset($_SESSION['message'])): ?>
    <script>
        // Afficher les messages système
        document.addEventListener('DOMContentLoaded', function() {
            showMessage('<?php echo addslashes($_SESSION['message']['text']); ?>', '<?php echo $_SESSION['message']['type']; ?>');
        });
    </script>
    <?php 
        unset($_SESSION['message']);
    endif; 
    ?>
</body>
</html>