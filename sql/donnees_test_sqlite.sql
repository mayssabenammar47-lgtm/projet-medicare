-- projet-medicare/sql/donnees_test_sqlite.sql
-- Données de test pour MediCare (SQLite)

-- Insertion des spécialités
INSERT OR IGNORE INTO specialites (nom, description) VALUES
('Médecine générale', 'Médecin traitant pour les problèmes de santé courants'),
('Cardiologie', 'Spécialiste des maladies du cœur et des vaisseaux sanguins'),
('Pédiatrie', 'Médecin spécialisé dans le suivi des enfants'),
('Dermatologie', 'Spécialiste des maladies de la peau'),
('Gynécologie', 'Spécialiste de la santé feminine'),
('Ophtalmologie', 'Spécialiste des maladies des yeux');

-- Insertion des utilisateurs
INSERT OR IGNORE INTO utilisateurs (nom, email, mot_de_passe, role) VALUES
('Admin', 'admin@medicare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Dr Martin', 'dr.martin@medicare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin'),
('Dr Dubois', 'dr.dubois@medicare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin'),
('Secrétaire', 'secretariat@medicare.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'secretaire');

-- Insertion des médecins
INSERT OR IGNORE INTO medecins (id_utilisateur, specialite, telephone, adresse) VALUES
(2, 'Médecine générale', '01 23 45 67 89', '15 Rue de la Santé, 75014 Paris'),
(3, 'Cardiologie', '01 23 45 67 90', '22 Avenue du Cœur, 75015 Paris');

-- Insertion des patients
INSERT OR IGNORE INTO patients (nom, prenom, date_naissance, telephone, email, adresse) VALUES
('Dupont', 'Jean', '1980-05-15', '06 12 34 56 78', 'jean.dupont@email.com', '10 Rue de la Paix, 75001 Paris'),
('Martin', 'Sophie', '1992-08-22', '06 23 45 67 89', 'sophie.martin@email.com', '25 Avenue des Champs-Élysées, 75008 Paris'),
('Bernard', 'Pierre', '1975-12-03', '06 34 56 78 90', 'pierre.bernard@email.com', '5 Place de la Concorde, 75008 Paris'),
('Petit', 'Marie', '1988-03-17', '06 45 67 89 01', 'marie.petit@email.com', '18 Rue Montorgueil, 75002 Paris'),
('Robert', 'Thomas', '1995-07-28', '06 56 78 90 12', 'thomas.robert@email.com', '33 Boulevard Saint-Germain, 75005 Paris');

-- Insertion des médicaments
INSERT OR IGNORE INTO medicaments (nom, description, stock, prix) VALUES
('Doliprane', 'Antalgique et antipyrétique', 100, 2.50),
('Ibuprofène', 'Anti-inflammatoire non stéroïdien', 50, 4.20),
('Amoxicilline', 'Antibiotique de la famille des pénicillines', 30, 8.90),
('Paracétamol', 'Antalgique courant', 150, 1.80),
('Aspirine', 'Antalgique et anti-inflammatoire', 80, 3.10);

-- Insertion des rendez-vous
INSERT OR IGNORE INTO rendez_vous (id_patient, id_medecin, date_heure, statut, notes) VALUES
(1, 1, datetime('now', '+1 day', '09:00:00'), 'planifie', 'Consultation de routine'),
(2, 1, datetime('now', '+1 day', '10:30:00'), 'confirme', 'Suivi de traitement'),
(3, 2, datetime('now', '+2 days', '14:00:00'), 'planifie', 'Consultation cardiologique'),
(4, 1, datetime('now', '-1 day', '11:00:00'), 'passe', 'Consultation terminée'),
(5, 2, datetime('now', '+3 days', '15:30:00'), 'planifie', 'Première consultation');

-- Insertion des consultations
INSERT OR IGNORE INTO consultations (id_medecin, id_patient, date_consultation, motif, symptomes, diagnostic, traitement, notes) VALUES
(1, 4, datetime('now', '-1 day', '11:00:00'), 'Mal de tête', 'Céphalées depuis 3 jours', 'Migraine bénigne', 'Repos et Doliprane si douleur', 'Patient à revoir si symptômes persistent'),
(1, 1, datetime('now', '-3 days', '09:00:00'), 'Contrôle annuel', 'Aucun symptôme particulier', 'Bon état général', 'Maintien hygiène de vie', 'Prochain contrôle dans 1 an');

-- Insertion des factures
INSERT OR IGNORE INTO factures (id_patient, id_consultation, montant, statut) VALUES
(4, 1, 25.00, 'paye'),
(1, 2, 25.00, 'impaye');

-- Insertion des notifications
INSERT OR IGNORE INTO notifications (id_utilisateur, message, lu) VALUES
(2, 'Nouveau rendez-vous planifié pour demain', 0),
(3, 'Consultation à confirmer avec patient Dupont', 1),
(1, 'Nouveau patient inscrit: Robert Thomas', 0);