-- projet-medicare/sql/creation_tables.sql
-- Création des tables principales pour MediCare

-- Table des utilisateurs (médecins, secrétaire)
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('medecin', 'secretaire') NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des médecins (détails spécifiques)
CREATE TABLE IF NOT EXISTS medecins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    specialite VARCHAR(100),
    telephone VARCHAR(20),
    adresse TEXT,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table des patients
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    telephone VARCHAR(20),
    email VARCHAR(150),
    adresse TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des rendez-vous
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_medecin INT NOT NULL,
    date_heure DATETIME NOT NULL,
    statut ENUM('planifie', 'confirme', 'annule', 'passe') DEFAULT 'planifie',
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE
);

-- Table des consultations
CREATE TABLE IF NOT EXISTS consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_rendez_vous INT NOT NULL,
    id_medecin INT NOT NULL,
    id_patient INT NOT NULL,
    date_heure DATETIME NOT NULL,
    motif VARCHAR(255),
    diagnostic TEXT,
    observations TEXT,
    date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE,
    FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE
);

-- Table des prescriptions (médicaments)
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_consultation INT NOT NULL,
    id_medicament INT NOT NULL,
    posologie VARCHAR(255),
    duree VARCHAR(100),
    instructions TEXT,
    FOREIGN KEY (id_consultation) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medicament) REFERENCES medicaments(id) ON DELETE CASCADE
);

-- Table des médicaments
CREATE TABLE IF NOT EXISTS medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    stock INT DEFAULT 0,
    prix DECIMAL(10,2),
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des spécialités médicales
CREATE TABLE IF NOT EXISTS specialites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

-- Table des factures
CREATE TABLE IF NOT EXISTS factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_patient INT NOT NULL,
    id_consultation INT,
    montant DECIMAL(10,2) NOT NULL,
    date_facture DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('paye', 'impaye', 'annule') DEFAULT 'impaye',
    FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (id_consultation) REFERENCES consultations(id) ON DELETE CASCADE
);

-- Table des historiques (logs)
CREATE TABLE IF NOT EXISTS historiques (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT,
    action VARCHAR(255) NOT NULL,
    table_concerne VARCHAR(100),
    id_concerne INT,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_notification DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);