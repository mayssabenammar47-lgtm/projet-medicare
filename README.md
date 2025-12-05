<<<<<<< HEAD
# MediCare - Plateforme de Gestion de Cabinet Médical

Une application web complète pour la gestion des cabinets médicaux, développée en PHP/MySQL avec une interface moderne et responsive.

## 🏥 Fonctionnalités

### Gestion des Patients
- **CRUD complet**: Ajout, modification, suppression et consultation des patients
- **Recherche avancée**: Recherche par nom, prénom, ou numéro de téléphone
- **Historique médical**: Accès à l'historique complet des consultations et prescriptions
- **Pagination**: Gestion efficace des grands volumes de patients

### Gestion des Rendez-vous
- **Vue calendrier**: Interface intuitive pour visualiser les rendez-vous
- **Vue liste**: Affichage détaillé avec filtres et recherche
- **Planification**: Prise de rendez-vous rapide avec vérification des disponibilités
- **Statuts**: Gestion des statuts (confirmé, en attente, annulé, terminé)

### Gestion des Consultations
- **Formulaire complet**: Saisie des signes vitaux, symptômes, diagnostic
- **Prescriptions dynamiques**: Ajout/suppression de médicaments pendant la consultation
- **Ordonnances**: Génération d'ordonnances formatées
- **Historique**: Suivi complet de l'évolution des patients

### Gestion des Médicaments
- **Inventaire complet**: Gestion du stock avec alertes de faible quantité
- **Réapprovisionnement**: Suivi des commandes et réceptions
- **Informations détaillées**: Dosage, effets secondaires, contre-indications
- **Alertes automatiques**: Notifications pour les stocks critiques

### Gestion des Médecins
- **Administration**: Gestion des comptes médecins (admin uniquement)
- **Statistiques**: Vue d'ensemble de l'activité par médecin
- **Profils**: Informations complètes et spécialisations

### Tableau de Bord
- **Statistiques en temps réel**: Vue d'ensemble de l'activité du cabinet
- **Accès rapide**: Raccourcis vers les fonctionnalités principales
- **Personnalisé**: Interface adaptée selon le rôle (médecin, secrétaire, admin)

## 🔐 Sécurité

- **Authentification sécurisée**: Hachage des mots de passe avec bcrypt
- **Contrôle d'accès**: Gestion des rôles (médecin, secrétaire, administrateur)
- **Protection CSRF**: Jetons de sécurité pour les formulaires
- **Validation des données**: Nettoyage et validation des entrées utilisateur
- **Sessions sécurisées**: Gestion appropriée des sessions PHP

## 🛠️ Stack Technique

- **Backend**: PHP 8.0+
- **Base de données**: MySQL 5.7+ / MariaDB 10.2+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Design**: Responsive Design, CSS Grid, Flexbox
- **Architecture**: MVC pattern, Programmation orientée objet

## 📋 Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7+ ou MariaDB 10.2+
- Serveur web (Apache recommandé)
- Extension PHP requises :
  - `pdo_mysql`
  - `mbstring`
  - `json`
  - `session`

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone <repository-url>
cd projet-medicare
```

### 2. Configuration de la base de données

L'application utilise maintenant **SQLite** pour une meilleure portabilité :

1. La base de données SQLite sera automatiquement créée dans `medicare.db`
2. Les tables et les données de test seront insérées automatiquement
3. Aucune configuration manuelle requise

### 3. Configuration du serveur web

#### Option 1: Serveur PHP intégré (recommandé pour développement)

```bash
php -S localhost:8000
```

Puis accédez à : http://localhost:8000

#### Option 2: Apache/Nginx

1. Placez le dossier dans votre répertoire web
2. Assurez-vous que PHP 8.0+ est installé
3. Configurez le VirtualHost si nécessaire

### 4. Permissions

Assurez-vous que le serveur web peut écrire dans le dossier :
```bash
chmod -R 755 /chemin/vers/projet-medicare
```

### 4. Configuration du serveur web

#### Apache
Assurez-vous que `mod_rewrite` est activé et configurez le VirtualHost :

```apache
<VirtualHost *:80>
    DocumentRoot /chemin/vers/projet-medicare
    ServerName medicare.local
    
    <Directory /chemin/vers/projet-medicare>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### XAMPP/WAMP
Placez le dossier dans `htdocs` (XAMPP) ou `www` (WAMP) et accédez via :
```
http://localhost/projet-medicare/
```

### 5. Permissions

Assurez-vous que le serveur web peut écrire dans les logs si nécessaire :
```bash
chmod -R 755 /chemin/vers/projet-medicare
chown -R www-data:www-data /chemin/vers/projet-medicare
```

## 👤 Comptes de démonstration

L'application inclut automatiquement des comptes de démonstration :

### Administrateur
- **Email**: admin@medicare.com
- **Mot de passe**: password
- **Accès**: Toutes les fonctionnalités

### Médecin
- **Email**: dr.martin@medicare.com
- **Mot de passe**: password
- **Accès**: Patients, rendez-vous, consultations

### Secrétaire
- **Email**: secretariat@medicare.com
- **Mot de passe**: password
- **Accès**: Patients, rendez-vous (limité)

## 📁 Structure du projet

```
projet-medicare/
├── config/
│   └── database.php          # Configuration base de données
├── css/
│   └── style.css             # Styles principaux
├── includes/
│   ├── header.php            # En-tête HTML
│   ├── footer.php            # Pied de page HTML
│   └── functions.php         # Fonctions utilitaires
├── js/
│   └── script.js             # JavaScript client
├── pages/
│   ├── dashboard.php         # Tableau de bord
│   ├── patients/
│   │   ├── patients.php      # Gestion patients
│   │   └── patients_form.php # Formulaire patient
│   ├── rendez_vous/
│   │   ├── rendez_vous.php   # Gestion rendez-vous
│   │   └── calendrier.php    # Vue calendrier
│   ├── consultations/
│   │   └── consultation_form.php # Formulaire consultation
│   ├── medecins/
│   │   └── medecins.php      # Gestion médecins (admin)
│   └── medicaments/
│       └── medicaments.php   # Gestion médicaments (admin)
├── sql/
│   ├── creation_tables.sql   # Création des tables
│   ├── contraintes.sql       # Contraintes et triggers
│   └── donnees_test.sql      # Données de test
├── index.php                 # Page de connexion
├── logout.php                # Déconnexion
└── README.md                 # Ce fichier
```

## 🔧 Personnalisation

### Ajouter un nouveau rôle

1. Modifier la table `utilisateurs` pour ajouter le nouveau rôle
2. Mettre à jour `config/database.php` dans la fonction `estAdmin()`
3. Adapter les contrôles d'accès dans les pages concernées

### Modifier le design

- Les styles principaux sont dans `css/style.css`
- Le design utilise CSS Grid et Flexbox pour la responsivité
- Les couleurs et thèmes peuvent être personnalisés via les variables CSS

### Étendre les fonctionnalités

- Ajouter de nouvelles tables dans le dossier `sql/`
- Créer de nouvelles pages dans le dossier `pages/`
- Utiliser les fonctions utilitaires dans `includes/functions.php`

## 🐛 Dépannage

### Problèmes courants

**Erreur de connexion à la base de données**
- Vérifiez les identifiants dans `config/database.php`
- Assurez-vous que le service MySQL est démarré
- Vérifiez que l'utilisateur a les droits sur la base de données

**Page blanche**
- Activez l'affichage des erreurs PHP :
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
- Vérifiez les logs d'erreurs du serveur web

**URLs qui ne fonctionnent pas**
- Assurez-vous que `mod_rewrite` est activé (Apache)
- Vérifiez la configuration du VirtualHost
- Pour XAMPP/WAMP, utilisez les URLs relatives

### Support

Pour toute question ou problème :
1. Vérifiez les logs d'erreurs PHP et Apache
2. Consultez la documentation des prérequis
3. Testez avec les données de démonstration fournies

## 📝 License

Ce projet est développé à des fins éducatives et démonstratives.

## 🤝 Contribution

Les contributions sont les bienvenues ! Merci de suivre les étapes :
1. Fork le projet
2. Créer une branche de fonctionnalité
3. Committer les changements
4. Pousser vers la branche
5. Créer une Pull Request

---

**Développé avec ❤️ pour les professionnels de santé**
=======
# projet-medicare
projet bd medicare
>>>>>>> 171b79a9c43c43fef14d6f5791fa9c7388821e56
