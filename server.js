const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');
const app = express();
const PORT = process.env.PORT || 3000;

// Configuration
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname)));

// CORS headers
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, X-Session-ID');
    if (req.method === 'OPTIONS') {
        res.sendStatus(200);
    } else {
        next();
    }
});

// Configuration de la base de données SQLite
const dbPath = process.env.NODE_ENV === 'production' 
    ? '/app/data/medicare.db' 
    : '/home/mayssa/projet-medicare/medicare.db';
const db = new sqlite3.Database(dbPath);

// Création des tables
db.serialize(() => {
    // Table des utilisateurs
    db.run(`CREATE TABLE IF NOT EXISTS utilisateurs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        email TEXT UNIQUE NOT NULL,
        mot_de_passe TEXT NOT NULL,
        role TEXT NOT NULL CHECK (role IN ('medecin', 'secretaire')),
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // Table des médecins
    db.run(`CREATE TABLE IF NOT EXISTS medecins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_utilisateur INTEGER NOT NULL,
        specialite TEXT,
        telephone TEXT,
        adresse TEXT,
        FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id) ON DELETE CASCADE
    )`);

    // Table des patients
    db.run(`CREATE TABLE IF NOT EXISTS patients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        prenom TEXT NOT NULL,
        date_naissance DATE,
        telephone TEXT,
        email TEXT,
        adresse TEXT,
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // Table des rendez-vous
    db.run(`CREATE TABLE IF NOT EXISTS rendez_vous (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_patient INTEGER NOT NULL,
        id_medecin INTEGER NOT NULL,
        date_heure DATETIME NOT NULL,
        statut TEXT DEFAULT 'planifie' CHECK (statut IN ('planifie', 'confirme', 'annule', 'passe')),
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE
    )`);

    // Table des consultations (avec migration si nécessaire)
    db.run(`CREATE TABLE IF NOT EXISTS consultations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_rendez_vous INTEGER,
        id_medecin INTEGER NOT NULL,
        id_patient INTEGER NOT NULL,
        date_heure DATETIME NOT NULL,
        motif TEXT,
        diagnostic TEXT,
        observations TEXT,
        date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous(id) ON DELETE SET NULL,
        FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE,
        FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE
    )`, (err) => {
        if (err) {
            console.error('Erreur création table consultations:', err);
            return;
        }
        
        // Vérifier si la migration est nécessaire
        db.all("PRAGMA table_info(consultations)", (err, columns) => {
            if (err) return;
            
            const hasOldRendezVousColumn = columns.some(col => 
                col.name === 'id_rendez_vous' && col.notnull === 1
            );
            
            if (hasOldRendezVousColumn) {
                console.log('Migration de la table consultations...');
                
                // Sauvegarder les données existantes
                db.all("SELECT * FROM consultations", (err, rows) => {
                    if (err) {
                        console.error('Erreur sauvegarde consultations:', err);
                        return;
                    }
                    
                    // Supprimer l'ancienne table
                    db.run("DROP TABLE consultations", (err) => {
                        if (err) {
                            console.error('Erreur suppression ancienne table:', err);
                            return;
                        }
                        
                        // Recréer la table avec la nouvelle structure
                        db.run(`CREATE TABLE consultations (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            id_rendez_vous INTEGER,
                            id_medecin INTEGER NOT NULL,
                            id_patient INTEGER NOT NULL,
                            date_heure DATETIME NOT NULL,
                            motif TEXT,
                            diagnostic TEXT,
                            observations TEXT,
                            date_consultation DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (id_rendez_vous) REFERENCES rendez_vous(id) ON DELETE SET NULL,
                            FOREIGN KEY (id_medecin) REFERENCES medecins(id) ON DELETE CASCADE,
                            FOREIGN KEY (id_patient) REFERENCES patients(id) ON DELETE CASCADE
                        )`, (err) => {
                            if (err) {
                                console.error('Erreur recréation table:', err);
                                return;
                            }
                            
                            // Restaurer les données (sans id_rendez_vous)
                            if (rows.length > 0) {
                                const stmt = db.prepare("INSERT INTO consultations (id, id_medecin, id_patient, date_heure, motif, diagnostic, observations, date_consultation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                
                                rows.forEach(row => {
                                    stmt.run([row.id, row.id_medecin, row.id_patient, row.date_heure, row.motif, row.diagnostic, row.observations, row.date_consultation]);
                                });
                                
                                stmt.finalize(() => {
                                    console.log('Migration consultations terminée');
                                });
                            } else {
                                console.log('Table consultations recréée (aucune donnée à restaurer)');
                            }
                        });
                    });
                });
            }
        });
    });

    // Table des médicaments prescrits dans les consultations
    db.run(`CREATE TABLE IF NOT EXISTS consultation_medicaments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        id_consultation INTEGER NOT NULL,
        id_medicament INTEGER NOT NULL,
        posologie TEXT NOT NULL,
        FOREIGN KEY (id_consultation) REFERENCES consultations(id) ON DELETE CASCADE,
        FOREIGN KEY (id_medicament) REFERENCES medicaments(id) ON DELETE CASCADE
    )`);

    // Table des médicaments
    db.run(`CREATE TABLE IF NOT EXISTS medicaments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        description TEXT,
        stock INTEGER DEFAULT 0,
        prix REAL,
        date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);

    // Table des spécialités
    db.run(`CREATE TABLE IF NOT EXISTS specialites (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT UNIQUE NOT NULL,
        description TEXT
    )`);

    // Insérer des médicaments de test si la table est vide
    db.get("SELECT COUNT(*) as count FROM medicaments", (err, row) => {
        if (!err && row.count === 0) {
            const medicamentsTest = [
                ['Paracétamol', 'Antidouleur et antipyrétique', 100, 5.50],
                ['Ibuprofène', 'Anti-inflammatoire non stéroïdien', 50, 8.20],
                ['Amoxicilline', 'Antibiotique de la famille des pénicillines', 30, 12.00],
                ['Doliprane', 'Antidouleur', 80, 4.30],
                ['Aspirine', 'Antidouleur et anti-inflammatoire', 60, 6.75]
            ];
            
            const stmt = db.prepare("INSERT INTO medicaments (nom, description, stock, prix) VALUES (?, ?, ?, ?)");
            medicamentsTest.forEach(med => {
                stmt.run(med);
            });
            stmt.finalize();
        }
    });
});

// Middleware pour les sessions simples
let sessions = {};

const sessionMiddleware = (req, res, next) => {
    const sessionId = req.headers['x-session-id'] || req.query.session_id || Date.now().toString();
    req.session = sessions[sessionId] || {};
    
    // Session de test pour le débogage (médecin)
    if (sessionId === 'test-admin-session') {
        req.session.user = {
            id: 1,
            nom: 'Dr. Martin',
            email: 'martin@medic.com',
            role: 'medecin'
        };
    }
    sessions[sessionId] = req.session;
    res.setHeader('X-Session-ID', sessionId);
    next();
};

app.use(sessionMiddleware);

// Routes API
app.post('/api/login', (req, res) => {
    const { email, password } = req.body;
    
    db.get(
        `SELECT u.*, m.specialite 
         FROM utilisateurs u 
         LEFT JOIN medecins m ON u.id = m.id_utilisateur 
         WHERE u.email = ?`,
        [email],
        (err, user) => {
            if (err) return res.status(500).json({ error: 'Erreur serveur' });
            if (!user) return res.status(401).json({ error: 'Email ou mot de passe incorrect' });
            
            // Pour la démo, on vérifie si le mot de passe est "password"
            if (password === 'password') {
                req.session.user = {
                    id: user.id,
                    nom: user.nom,
                    email: user.email,
                    role: user.role,
                    specialite: user.specialite
                };
                res.json({ 
                    success: true, 
                    user: req.session.user,
                    redirect: '/dashboard.html'
                });
            } else {
                res.status(401).json({ error: 'Email ou mot de passe incorrect' });
            }
        }
    );
});

app.post('/api/logout', (req, res) => {
    const sessionId = req.headers['x-session-id'];
    if (sessionId) {
        delete sessions[sessionId];
    }
    res.json({ success: true });
});

app.get('/api/user', (req, res) => {
    if (req.session.user) {
        res.json({ user: req.session.user });
    } else {
        res.status(401).json({ error: 'Non connecté' });
    }
});

app.get('/api/dashboard/stats', (req, res) => {
    console.log('STATS ROUTE CALLED!!!');
    
    if (!req.session.user) {
        console.log('STATS: User not connected');
        return res.status(401).json({ error: 'Non connecté' });
    }
    
    console.log('STATS: User connected:', req.session.user.nom);
    
    // Get current date using JavaScript local time to avoid SQLite timezone issues
    const now = new Date();
    const today = now.getFullYear() + '-' + 
                  String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                  String(now.getDate()).padStart(2, '0'); // Format: YYYY-MM-DD local time
    const currentMonth = now.getMonth() + 1; // 1-12
    const currentYear = now.getFullYear();
    
    console.log('STATS: Today date:', today, 'Month:', currentMonth, 'Year:', currentYear);
    
    const stats = {};
    
    db.get("SELECT COUNT(*) as count FROM patients", (err, row) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        stats.patients_total = row.count;
        
        db.get("SELECT COUNT(DISTINCT u.id) as count FROM utilisateurs u JOIN medecins m ON u.id = m.id_utilisateur WHERE u.role = 'medecin'", (err, row) => {
            if (err) return res.status(500).json({ error: 'Erreur serveur' });
            stats.medecins_total = row.count;
            
            // Use JavaScript date instead of SQLite DATE('now')
            db.get("SELECT COUNT(*) as count FROM rendez_vous WHERE DATE(date_heure) = ?", [today], (err, row) => {
                if (err) return res.status(500).json({ error: 'Erreur serveur' });
                stats.rdv_aujourd_hui = row.count;
                console.log('STATS: Today appointments:', row.count, 'for date:', today);
                
                // Use JavaScript month/year instead of SQLite strftime
                db.get("SELECT COUNT(*) as count FROM consultations WHERE strftime('%m', date_heure) = ? AND strftime('%Y', date_heure) = ?", 
                    [currentMonth.toString().padStart(2, '0'), currentYear.toString()], (err, row) => {
                    if (err) return res.status(500).json({ error: 'Erreur serveur' });
                    console.log('DEBUG consultations_mois:', row.count);
                    stats.consultations_mois = row.count;
                    
                    res.json({
                        patients_total: stats.patients_total,
                        medecins_total: stats.medecins_total,
                        rdv_aujourd_hui: stats.rdv_aujourd_hui,
                        consultations_mois: stats.consultations_mois
                    });
                });
            });
        });
    });
});

app.get('/api/patients', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const search = req.query.search || '';
    let sql = "SELECT * FROM patients";
    let params = [];
    
    if (search) {
        sql += " WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ?";
        params = [`%${search}%`, `%${search}%`, `%${search}%`];
    }
    
    sql += " ORDER BY nom, prenom";
    
    db.all(sql, params, (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

app.get('/api/rendezvous', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    let sql = `
        SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
               u.nom as medecin_nom, m.specialite 
        FROM rendez_vous rv 
        JOIN patients p ON rv.id_patient = p.id 
        JOIN medecins m ON rv.id_medecin = m.id 
        JOIN utilisateurs u ON m.id_utilisateur = u.id 
        ORDER BY rv.date_heure DESC 
        LIMIT 10
    `;
    
    db.all(sql, [], (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

// Routes pour les patients
app.get('/api/patients', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const search = req.query.search || '';
    let sql = "SELECT * FROM patients";
    let params = [];
    
    if (search) {
        sql += " WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ?";
        params = [`%${search}%`, `%${search}%`, `%${search}%`];
    }
    
    sql += " ORDER BY nom, prenom";
    
    db.all(sql, params, (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

app.post('/api/patients', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { nom, prenom, date_naissance, telephone, email, adresse } = req.body;
    
    // Validation des entrées
    if (!nom || !prenom || nom.trim() === '' || prenom.trim() === '') {
        return res.status(400).json({ error: 'Le nom et le prénom sont obligatoires' });
    }
    
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return res.status(400).json({ error: 'Format d\'email invalide' });
    }
    
    if (date_naissance && !/^\d{4}-\d{2}-\d{2}$/.test(date_naissance)) {
        return res.status(400).json({ error: 'Format de date invalide (AAAA-MM-JJ requis)' });
    }
    
    if (telephone && !/^[0-9\+\-\s\(\)]+$/.test(telephone)) {
        return res.status(400).json({ error: 'Format de téléphone invalide' });
    }
    
    db.run(
        `INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse) 
         VALUES (?, ?, ?, ?, ?, ?)`,
        [nom.trim(), prenom.trim(), date_naissance, telephone, email, adresse],
        function(err) {
            if (err) {
                if (err.code === 'SQLITE_CONSTRAINT') {
                    return res.status(400).json({ error: 'Cet email est déjà utilisé' });
                }
                return res.status(500).json({ error: 'Erreur lors de l\'ajout du patient' });
            }
            res.json({ id: this.lastID, message: 'Patient ajouté avec succès' });
        }
    );
});

app.put('/api/patients/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { nom, prenom, date_naissance, telephone, email, adresse } = req.body;
    const patientId = req.params.id;
    
    // Validation des entrées
    if (!nom || !prenom || nom.trim() === '' || prenom.trim() === '') {
        return res.status(400).json({ error: 'Le nom et le prénom sont obligatoires' });
    }
    
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return res.status(400).json({ error: 'Format d\'email invalide' });
    }
    
    if (date_naissance && !/^\d{4}-\d{2}-\d{2}$/.test(date_naissance)) {
        return res.status(400).json({ error: 'Format de date invalide (AAAA-MM-JJ requis)' });
    }
    
    if (telephone && !/^[0-9\+\-\s\(\)]+$/.test(telephone)) {
        return res.status(400).json({ error: 'Format de téléphone invalide' });
    }
    
    if (!patientId || isNaN(patientId)) {
        return res.status(400).json({ error: 'ID patient invalide' });
    }
    
    db.run(
        `UPDATE patients SET nom = ?, prenom = ?, date_naissance = ?, telephone = ?, email = ?, adresse = ? 
         WHERE id = ?`,
        [nom.trim(), prenom.trim(), date_naissance, telephone, email, adresse, patientId],
        function(err) {
            if (err) {
                if (err.code === 'SQLITE_CONSTRAINT') {
                    return res.status(400).json({ error: 'Cet email est déjà utilisé' });
                }
                return res.status(500).json({ error: 'Erreur lors de la modification du patient' });
            }
            if (this.changes === 0) return res.status(404).json({ error: 'Patient non trouvé' });
            res.json({ message: 'Patient modifié avec succès' });
        }
    );
});

app.delete('/api/patients/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const patientId = req.params.id;
    
    // Vérifier si le patient a des rendez-vous
    db.get("SELECT COUNT(*) as count FROM rendez_vous WHERE id_patient = ?", [patientId], (err, row) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        
        if (row.count > 0) {
            return res.status(400).json({ error: 'Impossible de supprimer ce patient : il a des rendez-vous associés' });
        }
        
        db.run("DELETE FROM patients WHERE id = ?", [patientId], function(err) {
            if (err) return res.status(500).json({ error: 'Erreur lors de la suppression du patient' });
            if (this.changes === 0) return res.status(404).json({ error: 'Patient non trouvé' });
            res.json({ message: 'Patient supprimé avec succès' });
        });
    });
});

// Routes pour les médecins
app.get('/api/medecins', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const search = req.query.search || '';
    let sql = `
        SELECT m.*, u.nom, u.email 
        FROM medecins m 
        JOIN utilisateurs u ON m.id_utilisateur = u.id
    `;
    let params = [];
    
    if (search) {
        sql += " WHERE u.nom LIKE ? OR m.specialite LIKE ?";
        params = [`%${search}%`, `%${search}%`];
    }
    
    sql += " ORDER BY u.nom";
    
    db.all(sql, params, (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

app.post('/api/medecins', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { nom, email, specialite, telephone, adresse, mot_de_passe } = req.body;
    
    // Validation des entrées
    if (!nom || !email || nom.trim() === '' || email.trim() === '') {
        return res.status(400).json({ error: 'Le nom et l\'email sont obligatoires' });
    }
    
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return res.status(400).json({ error: 'Format d\'email invalide' });
    }
    
    if (telephone && !/^[0-9\+\-\s\(\)]+$/.test(telephone)) {
        return res.status(400).json({ error: 'Format de téléphone invalide' });
    }
    
    if (mot_de_passe && mot_de_passe.length < 6) {
        return res.status(400).json({ error: 'Le mot de passe doit contenir au moins 6 caractères' });
    }
    
    db.serialize(() => {
        db.run(
            "INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, 'medecin')",
            [nom.trim(), email.trim(), mot_de_passe || 'password'],
            function(err) {
                if (err) {
                    if (err.code === 'SQLITE_CONSTRAINT') {
                        return res.status(400).json({ error: 'Cet email est déjà utilisé' });
                    }
                    return res.status(500).json({ error: 'Erreur lors de la création de l\'utilisateur' });
                }
                
                const userId = this.lastID;
                
                db.run(
                    "INSERT INTO medecins (id_utilisateur, specialite, telephone, adresse) VALUES (?, ?, ?, ?)",
                    [userId, specialite, telephone, adresse],
                    function(err) {
                        if (err) return res.status(500).json({ error: 'Erreur lors de l\'ajout du médecin' });
                        res.json({ id: this.lastID, message: 'Médecin ajouté avec succès' });
                    }
                );
            }
        );
    });
});

app.put('/api/medecins/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { nom, email, specialite, telephone, adresse, mot_de_passe } = req.body;
    const medecinId = req.params.id;
    
    db.serialize(() => {
        // Mettre à jour l'utilisateur
        let updateUserSql = "UPDATE utilisateurs SET nom = ?, email = ?";
        let updateUserParams = [nom, email];
        
        if (mot_de_passe) {
            updateUserSql += ", mot_de_passe = ?";
            updateUserParams.push(mot_de_passe);
        }
        
        updateUserSql += " WHERE id = (SELECT id_utilisateur FROM medecins WHERE id = ?)";
        updateUserParams.push(medecinId);
        
        db.run(updateUserSql, updateUserParams, (err) => {
            if (err) return res.status(500).json({ error: 'Erreur lors de la modification de l\'utilisateur' });
            
            // Mettre à jour le médecin
            db.run(
                "UPDATE medecins SET specialite = ?, telephone = ?, adresse = ? WHERE id = ?",
                [specialite, telephone, adresse, medecinId],
                function(err) {
                    if (err) return res.status(500).json({ error: 'Erreur lors de la modification du médecin' });
                    if (this.changes === 0) return res.status(404).json({ error: 'Médecin non trouvé' });
                    res.json({ message: 'Médecin modifié avec succès' });
                }
            );
        });
    });
});

app.delete('/api/medecins/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const medecinId = req.params.id;
    
    db.serialize(() => {
        // Récupérer l'id_utilisateur
        db.get("SELECT id_utilisateur FROM medecins WHERE id = ?", [medecinId], (err, row) => {
            if (err) return res.status(500).json({ error: 'Erreur serveur' });
            if (!row) return res.status(404).json({ error: 'Médecin non trouvé' });
            
            const userId = row.id_utilisateur;
            
            // Supprimer le médecin
            db.run("DELETE FROM medecins WHERE id = ?", [medecinId], (err) => {
                if (err) return res.status(500).json({ error: 'Erreur lors de la suppression du médecin' });
                
                // Supprimer l'utilisateur
                db.run("DELETE FROM utilisateurs WHERE id = ?", [userId], (err) => {
                    if (err) return res.status(500).json({ error: 'Erreur lors de la suppression de l\'utilisateur' });
                    res.json({ message: 'Médecin supprimé avec succès' });
                });
            });
        });
    });
});

// Routes pour les consultations
app.get('/api/consultations', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const search = req.query.search || '';
    let sql = `
        SELECT c.*, 
               p.nom as patient_nom, p.prenom as patient_prenom,
               u.nom as medecin_nom, m.specialite
        FROM consultations c
        JOIN patients p ON c.id_patient = p.id
        JOIN medecins m ON c.id_medecin = m.id
        JOIN utilisateurs u ON m.id_utilisateur = u.id
    `;
    let params = [];
    
    if (search) {
        sql += " WHERE (p.nom LIKE ? OR p.prenom LIKE ? OR u.nom LIKE ? OR c.motif LIKE ?)";
        params = [`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`];
    }
    
    sql += " ORDER BY c.date_heure DESC";
    
    db.all(sql, params, (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

app.get('/api/consultations/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const consultationId = req.params.id;
    
    if (!consultationId || isNaN(consultationId)) {
        return res.status(400).json({ error: 'ID consultation invalide' });
    }
    
    const sql = `
        SELECT c.*, 
               p.nom as patient_nom, p.prenom as patient_prenom, p.date_naissance, p.telephone, p.email,
               u.nom as medecin_nom, m.specialite, m.telephone as medecin_telephone
        FROM consultations c
        JOIN patients p ON c.id_patient = p.id
        JOIN medecins m ON c.id_medecin = m.id
        JOIN utilisateurs u ON m.id_utilisateur = u.id
        WHERE c.id = ?
    `;
    
    db.get(sql, [consultationId], (err, row) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        if (!row) return res.status(404).json({ error: 'Consultation non trouvée' });
        res.json(row);
    });
});

app.post('/api/consultations', (req, res) => {
    console.log('=== DEBUG CONSULTATION POST ===');
    console.log('Session ID:', req.headers['x-session-id']);
    console.log('Session:', req.session);
    console.log('User:', req.session.user);
    console.log('================================');
    if (!req.session.user) {
        console.log('ERREUR: Utilisateur non connecté!');
        return res.status(401).json({ error: 'Non connecté' });
    }
    
    const { id_patient, id_medecin, date_heure, motif, diagnostic, observations, medicaments } = req.body;
    
    // Validation des champs obligatoires
    if (!id_patient || !id_medecin || !date_heure) {
        return res.status(400).json({ error: 'Les champs patient, médecin et date/heure sont obligatoires' });
    }
    
    db.serialize(() => {
        db.run(
            `INSERT INTO consultations (id_patient, id_medecin, date_heure, motif, diagnostic, observations) 
             VALUES (?, ?, ?, ?, ?, ?)`,
            [id_patient, id_medecin, date_heure, motif, diagnostic, observations],
            function(err) {
                if (err) {
                    console.error('Erreur insertion consultation:', err);
                    return res.status(500).json({ error: 'Erreur lors de l\'ajout de la consultation: ' + err.message });
                }
                
                const consultationId = this.lastID;
                
                // Ajouter les médicaments s'il y en a
                if (medicaments && medicaments.length > 0) {
                    const stmt = db.prepare("INSERT INTO consultation_medicaments (id_consultation, id_medicament, posologie) VALUES (?, ?, ?)");
                    
                    medicaments.forEach(med => {
                        if (med.id_medicament && med.posologie) {
                            stmt.run([consultationId, med.id_medicament, med.posologie]);
                        }
                    });
                    
                    stmt.finalize((err) => {
                        if (err) {
                            console.error('Erreur insertion médicaments:', err);
                        }
                    });
                }
                
                res.json({ id: consultationId, message: 'Consultation ajoutée avec succès' });
            }
        );
    });
});

app.put('/api/consultations/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { id_patient, id_medecin, date_heure, motif, diagnostic, observations, medicaments } = req.body;
    const consultationId = req.params.id;
    
    // Validation des champs obligatoires
    if (!id_patient || !id_medecin || !date_heure) {
        return res.status(400).json({ error: 'Les champs patient, médecin et date/heure sont obligatoires' });
    }
    
    db.serialize(() => {
        db.run(
            `UPDATE consultations SET id_patient = ?, id_medecin = ?, date_heure = ?, motif = ?, diagnostic = ?, observations = ? 
             WHERE id = ?`,
            [id_patient, id_medecin, date_heure, motif, diagnostic, observations, consultationId],
            function(err) {
                if (err) {
                    console.error('Erreur modification consultation:', err);
                    return res.status(500).json({ error: 'Erreur lors de la modification de la consultation: ' + err.message });
                }
                if (this.changes === 0) return res.status(404).json({ error: 'Consultation non trouvée' });
                
                // Mettre à jour les médicaments s'ils sont fournis
                if (medicaments !== undefined) {
                    // Supprimer les anciens médicaments
                    db.run("DELETE FROM consultation_medicaments WHERE id_consultation = ?", [consultationId], (err) => {
                        if (err) {
                            console.error('Erreur suppression anciens médicaments:', err);
                            return;
                        }
                        
                        // Ajouter les nouveaux médicaments
                        if (medicaments && medicaments.length > 0) {
                            const stmt = db.prepare("INSERT INTO consultation_medicaments (id_consultation, id_medicament, posologie) VALUES (?, ?, ?)");
                            
                            medicaments.forEach(med => {
                                if (med.id_medicament && med.posologie) {
                                    stmt.run([consultationId, med.id_medicament, med.posologie]);
                                }
                            });
                            
                            stmt.finalize((err) => {
                                if (err) {
                                    console.error('Erreur insertion nouveaux médicaments:', err);
                                }
                            });
                        }
                    });
                }
                
                res.json({ message: 'Consultation modifiée avec succès' });
            }
        );
    });
});

app.delete('/api/consultations/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const consultationId = req.params.id;
    
    db.run("DELETE FROM consultations WHERE id = ?", [consultationId], function(err) {
        if (err) return res.status(500).json({ error: 'Erreur lors de la suppression de la consultation' });
        if (this.changes === 0) return res.status(404).json({ error: 'Consultation non trouvée' });
        res.json({ message: 'Consultation supprimée avec succès' });
    });
});

// Routes pour les rendez-vous (améliorées)
app.get('/api/rendezvous', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const search = req.query.search || '';
    const status = req.query.status || '';
    let sql = `
        SELECT rv.*, 
               p.nom as patient_nom, p.prenom as patient_prenom,
               u.nom as medecin_nom, m.specialite
        FROM rendez_vous rv
        JOIN patients p ON rv.id_patient = p.id
        JOIN medecins m ON rv.id_medecin = m.id
        JOIN utilisateurs u ON m.id_utilisateur = u.id
    `;
    let params = [];
    let conditions = [];
    
    if (search) {
        conditions.push("(p.nom LIKE ? OR p.prenom LIKE ? OR u.nom LIKE ?)");
        params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }
    
    if (status) {
        conditions.push("rv.statut = ?");
        params.push(status);
    }
    
    if (conditions.length > 0) {
        sql += " WHERE " + conditions.join(" AND ");
    }
    
    sql += " ORDER BY rv.date_heure DESC";
    
    db.all(sql, params, (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

app.post('/api/rendezvous', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { id_patient, id_medecin, date_heure, statut, notes } = req.body;
    
    db.run(
        `INSERT INTO rendez_vous (id_patient, id_medecin, date_heure, statut, notes) 
         VALUES (?, ?, ?, ?, ?)`,
        [id_patient, id_medecin, date_heure, statut || 'planifie', notes],
        function(err) {
            if (err) return res.status(500).json({ error: 'Erreur lors de l\'ajout du rendez-vous' });
            res.json({ id: this.lastID, message: 'Rendez-vous ajouté avec succès' });
        }
    );
});

app.put('/api/rendezvous/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const { id_patient, id_medecin, date_heure, statut, notes } = req.body;
    const rdvId = req.params.id;
    
    db.run(
        `UPDATE rendez_vous SET id_patient = ?, id_medecin = ?, date_heure = ?, statut = ?, notes = ? 
         WHERE id = ?`,
        [id_patient, id_medecin, date_heure, statut, notes, rdvId],
        function(err) {
            if (err) return res.status(500).json({ error: 'Erreur lors de la modification du rendez-vous' });
            if (this.changes === 0) return res.status(404).json({ error: 'Rendez-vous non trouvé' });
            res.json({ message: 'Rendez-vous modifié avec succès' });
        }
    );
});

app.delete('/api/rendezvous/:id', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    const rdvId = req.params.id;
    
    db.run("DELETE FROM rendez_vous WHERE id = ?", [rdvId], function(err) {
        if (err) return res.status(500).json({ error: 'Erreur lors de la suppression du rendez-vous' });
        if (this.changes === 0) return res.status(404).json({ error: 'Rendez-vous non trouvé' });
        res.json({ message: 'Rendez-vous supprimé avec succès' });
    });
});

// Routes pour les médicaments
app.get('/api/medicaments', (req, res) => {
    if (!req.session.user) return res.status(401).json({ error: 'Non connecté' });
    
    db.all("SELECT * FROM medicaments ORDER BY nom", (err, rows) => {
        if (err) return res.status(500).json({ error: 'Erreur serveur' });
        res.json(rows);
    });
});

// Route principale
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

// Initialisation des données de test
const initTestData = () => {
    db.serialize(() => {
        // Vérifier si des données existent déjà
        db.get("SELECT COUNT(*) as count FROM utilisateurs", (err, row) => {
            if (err || row.count > 0) return;
            
            // Insertion des utilisateurs de test
            const users = [
                ['Dr. Martin', 'martin@medic.com', 'password', 'medecin'],
                ['Dr. Dupont', 'dupont@medic.com', 'password', 'medecin'],
                ['Secrétaire', 'secret@medic.com', 'password', 'secretaire']
            ];
            
            users.forEach(user => {
                db.run("INSERT INTO utilisateurs (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)", user);
            });
            
            // Insertion des médecins
            setTimeout(() => {
                db.run("INSERT INTO medecins (id_utilisateur, specialite, telephone, adresse) VALUES (1, 'Cardiologie', '0123456789', '15 Rue de la Santé, Paris')");
                db.run("INSERT INTO medecins (id_utilisateur, specialite, telephone, adresse) VALUES (2, 'Dermatologie', '0234567890', '22 Avenue des Médecins, Lyon')");
            }, 100);
            
            // Insertion des patients
            const patients = [
                ['Durand', 'Jean', '1985-03-15', '0612345678', 'jean.durand@email.com', '5 Rue Victor Hugo, Paris'],
                ['Martin', 'Sophie', '1990-07-22', '0623456789', 'sophie.martin@email.com', '12 Avenue des Champs-Élysées, Paris'],
                ['Petit', 'Pierre', '1978-11-30', '0634567890', 'pierre.petit@email.com', '25 Rue de la République, Lyon']
            ];
            
            patients.forEach(patient => {
                db.run("INSERT INTO patients (nom, prenom, date_naissance, telephone, email, adresse) VALUES (?, ?, ?, ?, ?, ?)", patient);
            });
            
            // Insertion des consultations de test
            setTimeout(() => {
                const consultations = [
                    [1, 1, '2025-12-03 10:00:00', 'Douleur thoracique', 'Angine stable', 'Patient stressé'],
                    [2, 2, '2025-12-03 14:30:00', 'Éruption cutanée', 'Eczéma atopique', 'Éviter allergènes'],
                    [3, 1, '2025-12-03 09:00:00', 'Visite de routine', 'Bonne santé', 'Vaccinations à jour']
                ];
                
                consultations.forEach(consultation => {
                    db.run("INSERT INTO consultations (id_patient, id_medecin, date_consultation, motif, diagnostic, observations) VALUES (?, ?, ?, ?, ?, ?)", consultation);
                });
            }, 200);
            
            // Insertion des médicaments
            const medicaments = [
                ['Paracétamol', 'Antidouleur et antipyrétique', 100, 2.50],
                ['Amoxicilline', 'Antibiotique à large spectre', 50, 8.90],
                ['Ibuprofène', 'Anti-inflammatoire non stéroïdien', 75, 4.20]
            ];
            
            medicaments.forEach(med => {
                db.run("INSERT INTO medicaments (nom, description, stock, prix) VALUES (?, ?, ?, ?)", med);
            });
        });
    });
};

// Démarrer le serveur
app.listen(PORT, () => {
    console.log(`🏥 MediCare serveur démarré sur http://localhost:${PORT}`);
    console.log(`📊 Tableau de bord: http://localhost:${PORT}/dashboard.html`);
    console.log(`🔑 Comptes de démonstration:`);
    console.log(`   Médecin: martin@medic.com / password`);
    console.log(`   Secrétaire: secret@medic.com / password`);
    
    // Initialiser les données de test
    initTestData();
});

module.exports = app;