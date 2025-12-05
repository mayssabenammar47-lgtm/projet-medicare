-- projet-medicare/sql/creation_tables_sqlite.sql
-- Création des tables principales pour MediCare (SQLite)

-- Table des utilisateurs (médecins, secrétaire)
CREATE TABLE IF NOT EXISTS utilisateurs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    mot_de_passe TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('medecin', 'secretaire')),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME
);

-- Table des médecins (détails spécifiques)
CREATE TABLE IF NOT EXISTS medecins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_utilisateur INTEGER NOT NULL,
    specialite TEXT,
    telephone TEXT,
    adresse TEXT,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Table des patients
CREATE TABLE IF NOT EXISTS patients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    date_naissance DATE,
    telephone TEXT,
    email TEXT,
    adresse TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des rendez-vous
CREATE TABLE IF NOT EXISTS rendez_vous (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_patient INTEGER NOT NULL,
    id_medecin INTEGER NOT NULL,
    date_heure DATETIME NOT NULL,
    statut TEXT NOT NULL DEFAULT 'planifie' CHECK (statut IN ('planifie', 'confirme', 'annule', 'passe')),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE
);

-- Table des consultations
CREATE TABLE IF NOT EXISTS consultations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_medecin INTEGER NOT NULL,
    id_patient INTEGER NOT NULL,
    date_consultation DATETIME NOT NULL,
    motif TEXT,
    symptomes TEXT,
    diagnostic TEXT,
    traitement TEXT,
    notes TEXT,
    FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE,
    FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE
);

-- Table des médicaments
CREATE TABLE IF NOT EXISTS medicaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT NOT NULL,
    description TEXT,
    stock INTEGER DEFAULT 0,
    prix REAL,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Table des spécialités médicales
CREATE TABLE IF NOT EXISTS specialites (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT UNIQUE NOT NULL,
    description TEXT
);

-- Table des factures
CREATE TABLE IF NOT EXISTS factures (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_patient INTEGER NOT NULL,
    id_consultation INTEGER,
    montant REAL NOT NULL,
    date_facture DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut TEXT NOT NULL DEFAULT 'impaye' CHECK (statut IN ('paye', 'impaye', 'annule')),
    FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (id_consultation) REFERENCES consultations(id) ON DELETE CASCADE
);

-- Table des notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_utilisateur INTEGER,
    message TEXT NOT NULL,
    lu INTEGER DEFAULT 0 CHECK (lu IN (0, 1)),
    date_notification DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- Index pour optimiser les performances
CREATE INDEX IF NOT EXISTS idx_patients_nom ON patients(nom, prenom);
CREATE INDEX IF NOT EXISTS idx_rendez_vous_date ON rendez_vous(date_heure);
CREATE INDEX IF NOT EXISTS idx_consultations_date ON consultations(date_consultation);
CREATE INDEX IF NOT EXISTS idx_utilisateurs_email ON utilisateurs(email);
CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(id_utilisateur);