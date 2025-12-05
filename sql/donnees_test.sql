-- projet-medicare/sql/donnees_test.sql
-- Données de test pour MediCare

-- Insertion des spécialités
INSERT INTO specialites (nom, description) VALUES
('Cardiologie', 'Spécialiste du cœur et des vaisseaux sanguins'),
('Dermatologie', 'Spécialiste de la peau et des maladies cutanées'),
('Pédiatrie', 'Spécialiste des maladies infantiles'),
('Gynécologie', 'Spécialiste de la santé feminine'),
('Ophtalmologie', 'Spécialiste des yeux et de la vision'),
('Généraliste', 'Médecin généraliste');

-- Insertion des utilisateurs (médecins, secrétaire, admin)
INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Dr. Martin', 'martin@medic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin'),
('Dr. Dupont', 'dupont@medic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin'),
('Dr. Bernard', 'bernard@medic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin'),
('Secrétaire', 'secret@medic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'secretaire'),
('Admin', 'admin@medic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insertion des médecins
INSERT INTO medecins (id_utilisateur, specialite, telephone, adresse) VALUES
(1, 'Cardiologie', '0123456789', '15 Rue de la Santé, 75014 Paris'),
(2, 'Dermatologie', '0234567890', '22 Avenue des Médecins, 69000 Lyon'),
(3, 'Pédiatrie', '0345678901', '8 Boulevard des Enfants, 33000 Bordeaux');

-- Insertion des patients
INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse) VALUES
('Durand', 'Jean', '1985-03-15', '0612345678', 'jean.durand@email.com', '5 Rue Victor Hugo, 75001 Paris'),
('Martin', 'Sophie', '1990-07-22', '0623456789', 'sophie.martin@email.com', '12 Avenue des Champs-Élysées, 75008 Paris'),
('Petit', 'Pierre', '1978-11-30', '0634567890', 'pierre.petit@email.com', '25 Rue de la République, 69000 Lyon'),
('Robert', 'Marie', '1995-05-18', '0645678901', 'marie.robert@email.com', '7 Place de la Concorde, 75008 Paris'),
('Lefebvre', 'Thomas', '1982-09-10', '0656789012', 'thomas.lefebvre@email.com', '18 Rue Gambetta, 44000 Nantes');

-- Insertion des médicaments
INSERT INTO medicaments (nom, description, stock, prix) VALUES
('Paracétamol', 'Antidouleur et antipyrétique', 100, 2.50),
('Amoxicilline', 'Antibiotique à large spectre', 50, 8.90),
('Ibuprofène', 'Anti-inflammatoire non stéroïdien', 75, 4.20),
('Aspirine', 'Antidouleur et anti-inflammatoire', 80, 3.10),
('Doliprane', 'Antidouleur pour adulte', 120, 2.80),
('Vitamine C', 'Complément alimentaire', 200, 5.50),
('Bétaméthasone', 'Corticoïde puissant', 30, 12.90),
('Oméprazole', 'Inhibiteur de la pompe à protons', 60, 7.80);

-- Insertion des rendez-vous
INSERT INTO rendez_vous (id_patient, id_medecin, date_heure, statut, notes) VALUES
(1, 1, '2024-01-15 10:00:00', 'confirme', 'Consultation cardiaque de routine'),
(2, 2, '2024-01-16 14:30:00', 'planifie', 'Examen de peau'),
(3, 3, '2024-01-17 09:00:00', 'confirme', 'Visite pédiatrique annuelle'),
(4, 1, '2024-01-18 11:15:00', 'planifie', 'Suivi cardiologique'),
(5, 2, '2024-01-19 15:45:00', 'confirme', 'Contrôle dermatologique');

-- Insertion des consultations
INSERT INTO consultations (id_rendez_vous, id_medecin, id_patient, date_heure, motif, diagnostic, observations) VALUES
(1, 1, 1, '2024-01-15 10:00:00', 'Douleur thoracique', 'Angine stable', 'Patient stressé, recommander repos'),
(2, 2, 2, '2024-01-16 14:30:00', 'Éruption cutanée', 'Eczéma atopique', 'Éviter les allergènes identifiés'),
(3, 3, 3, '2024-01-17 09:00:00', 'Visite de routine', 'Bonne santé générale', 'Vaccinations à jour');

-- Insertion des prescriptions
INSERT INTO prescriptions (id_consultation, id_medicament, posologie, duree, instructions) VALUES
(1, 1, '1 comprimé 3 fois par jour', '7 jours', 'Prendre après les repas'),
(1, 4, '1 comprimé par jour si douleur', '14 jours', 'Ne pas dépasser 3 comprimés par jour'),
(2, 2, '1 capsule 2 fois par jour', '10 jours', 'Terminer le traitement même si amélioration'),
(3, 5, '1 suppositoire si fièvre > 38°C', '5 jours', 'Surveiller la température');

-- Insertion des factures
INSERT INTO factures (id_patient, id_consultation, montant, statut) VALUES
(1, 1, 50.00, 'paye'),
(2, 2, 45.00, 'impaye'),
(3, 3, 35.00, 'paye');

-- Insertion des notifications
INSERT INTO notifications (id_utilisateur, message, lu) VALUES
(1, 'Nouveau rendez-vous prévu pour le 15/01/2024', FALSE),
(2, 'Patient Sophie Martin en attente de confirmation', FALSE),
(3, 'Rappel : Consultation pédiatrie demain à 9h', TRUE);