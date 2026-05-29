<?php

require_once "../Classes/CRole.php";

class CRoles
{

    // Instance unique (permet de stocker l'unique objet CRoles qui sera créé dans le Singleton)
    private static $instance = null;

    // Attributs
    private $collRoles = []; // Collection d'objets CRole
    private $pdo;

    // Constructeur
    public function __construct(CDao $dao)
    {
        $this->pdo = $dao->getPdo();
    }



    // Méthode getInstance -> Singleton (garantit qu'on n'aura qu'une seule instance de CRoles dans toute l'application)
    public static function getInstance(CDao $dao)
    // C'est getInstance qui créer l'objet CRoles -> donc elle a besoin de la connexion à la BDD via CDao
    {
        // On vérifie s'il existe déjà une instance de CRoles
        if (self::$instance === null) {
            self::$instance = new CRoles($dao); // Si n'en existe pas -> on en créé une (travaille tjr sur la même instance de CRoles dans toute l'appli)
        }
        return self::$instance;
    }



    // Charger tous les rôles depuis la BDD
    public function loadRoles()
    {
        // Requête qui récupère tous les rôles de la BDD
        $stmt = $this->pdo->query("SELECT * FROM Role");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // On vide la collection de roles pour être sûr qu'elle ne contient pas de données obsolètes avant de la remplir (si appelé plusieurs fois)
        $this->collRoles = [];

        // Pour chaque ligne récupérée, on les transforme en objet CRole et on les ajoute à la collection $collRoles
        foreach ($rows as $row) {
            $this->collRoles[] = new CRole($row['idRole'], $row['libelle']);
        }
    }


    // Retourner le tableau d'objets CRole
    public function getRoles()
    {
        return $this->collRoles;
    }



}
