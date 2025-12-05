<?php
/**
 * projet-medicare/config/database.php
 * Configuration de la base de données avec PDO (SQLite)
 */

// Configuration de la base de données SQLite
define('DB_PATH', __DIR__ . '/../medicare.db');

// Options PDO pour la sécurité et la performance
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false
];

// Connexion à la base de données SQLite
try {
    $pdo = new PDO("sqlite:" . DB_PATH, null, null, $options);
    
    // Activer les contraintes de clés étrangères
    $pdo->exec("PRAGMA foreign_keys = ON");
    
    // Créer les tables si elles n'existent pas
    createTablesIfNotExists();
    
} catch (PDOException $e) {
    // En production, logger l'erreur plutôt que de l'afficher
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

/**
 * Créer les tables si elles n'existent pas
 */
function createTablesIfNotExists() {
    global $pdo;
    
    // Lire le fichier de création des tables
    $sqlFile = __DIR__ . '/../sql/creation_tables_sqlite.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
    }
    
    // Insérer les données de test si la base est vide
    $userCount = $pdo->query("SELECT COUNT(*) as count FROM utilisateurs")->fetch()['count'];
    if ($userCount == 0) {
        $testDataFile = __DIR__ . '/../sql/donnees_test_sqlite.sql';
        if (file_exists($testDataFile)) {
            $sql = file_get_contents($testDataFile);
            $pdo->exec($sql);
        }
    }
}

/**
 * Fonction pour exécuter une requête préparée
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        die("Erreur lors de l'exécution de la requête: " . $e->getMessage());
    }
}

/**
 * Fonction pour récupérer plusieurs lignes
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @return array
 */
function fetchAll($sql, $params = []) {
    return executeQuery($sql, $params)->fetchAll();
}

/**
 * Fonction pour récupérer une seule ligne
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @return array|null
 */
function fetchOne($sql, $params = []) {
    $result = executeQuery($sql, $params)->fetch();
    return $result !== false ? $result : null;
}

/**
 * Fonction pour insérer des données et récupérer l'ID
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @return int
 */
function insert($sql, $params = []) {
    global $pdo;
    
    executeQuery($sql, $params);
    return $pdo->lastInsertId();
}

/**
 * Fonction pour mettre à jour des données
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @return int Le nombre de lignes affectées
 */
function update($sql, $params = []) {
    return executeQuery($sql, $params)->rowCount();
}

/**
 * Fonction pour supprimer des données
 * @param string $sql La requête SQL
 * @param array $params Les paramètres à lier
 * @return int Le nombre de lignes supprimées
 */
function delete($sql, $params = []) {
    return executeQuery($sql, $params)->rowCount();
}
?>