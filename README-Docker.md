# MediCare - Docker Deployment

MediCare est une application web complète pour la gestion des cabinets médicaux, développée en Node.js avec une base de données SQLite.

## 🚀 Lancement rapide avec Docker

### Prérequis
- Docker installé sur votre machine
- Docker Compose (généralement inclus avec Docker)

### 1. Cloner le projet
```bash
git clone <repository-url>
cd projet-medicare
```

### 2. Lancer l'application
```bash
docker-compose up -d
```

### 3. Accéder à l'application
- URL: http://localhost:3000
- Tableau de bord: http://localhost:3000/dashboard.html

### 4. Arrêter l'application
```bash
docker-compose down
```

## 📋 Comptes de démonstration
- **Médecin**: martin@medic.com / password
- **Secrétaire**: secret@medic.com / password

## 🗂️ Structure du projet
```
projet-medicare/
├── Dockerfile              # Configuration Docker
├── docker-compose.yml      # Configuration Docker Compose
├── .dockerignore          # Fichiers ignorés par Docker
├── server.js              # Serveur Node.js
├── package.json           # Dépendances Node.js
├── index.html             # Page d'accueil
├── css/                   # Styles
├── js/                    # Scripts JavaScript
├── api/                   # API endpoints
├── pages/                 # Pages de l'application
└── data/                  # Base de données SQLite (créé automatiquement)
```

## 🔧 Configuration Docker

### Variables d'environnement
- `NODE_ENV`: Environnement (production)
- `PORT`: Port d'écoute (3000)

### Volumes
- `./data:/app/data`: Persistance de la base de données SQLite
- `./logs:/app/logs`: Logs de l'application

### Health Check
L'application inclut un health check qui vérifie la disponibilité de l'API toutes les 30 secondes.

## 🐛 Dépannage

### Problèmes courants
1. **Port déjà utilisé**: Modifiez le port dans `docker-compose.yml`
2. **Permissions**: Assurez-vous que Docker a les droits nécessaires
3. **Base de données**: La base de données est créée automatiquement au premier lancement

### Logs
```bash
# Voir les logs de l'application
docker-compose logs -f

# Voir les logs du conteneur
docker logs medicare-app
```

### Reconstruction
```bash
# Reconstruire l'image Docker
docker-compose build --no-cache

# Relancer avec reconstruction
docker-compose up --build -d
```

## 📦 Partage avec un ami

Pour partager l'application avec un ami:

1. **Partager les fichiers**:
   ```bash
   # Créer une archive
   tar -czf medicare-docker.tar.gz .
   
   # Ou utiliser git
   git init
   git add .
   git commit -m "Initial commit"
   git remote add origin <repository-url>
   git push -u origin main
   ```

2. **Instructions pour votre ami**:
   - Installer Docker sur sa machine
   - Cloner/télécharger les fichiers
   - Lancer: `docker-compose up -d`
   - Accéder à: http://localhost:3000

## 🔄 Mise à jour
```bash
# Arrêter l'application
docker-compose down

# Mettre à jour les fichiers
git pull  # ou remplacer les fichiers manuellement

# Relancer avec mise à jour
docker-compose up --build -d
```

## 📊 Fonctionnalités
- Gestion des patients
- Gestion des médecins
- Prise de rendez-vous
- Consultations médicales
- Prescription de médicaments
- Tableau de bord statistique
- Interface responsive moderne

## 🔒 Sécurité
- Gestion de sessions
- Validation des entrées
- CORS configuré
- Health checks automatiques