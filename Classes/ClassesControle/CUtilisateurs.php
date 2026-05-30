<?php

require_once "../Classes/CUtilisateur.php";

class CUtilisateurs
{

    // Instance unique (permet de stocker l'unique objet CUtilisateurs qui sera créé dans le Singleton)
    private static $instance = null;

    // Attributs
    private $collUtilisateurs = []; // Collection d'objets CUtilisateur
    private $pdo;

    // Constructeur
    public function __construct(CDao $dao)
    {
        $this->pdo = $dao->getPdo();
    }




    // Méthode getInstance -> Singleton (garantit qu'on n'aura qu'une seule instance de CUtilisateurs dans toute l'application)
    public static function getInstance(CDao $dao)
    // C'est getInstance qui créer l'objet CUtilisateurs -> donc elle a besoin de la connexion à la BDD via CDao
    {
        // On vérifie s'il existe déjà une instance de CUtilisateurs
        if (self::$instance === null) {
            self::$instance = new CUtilisateurs($dao); // Si n'en existe pas -> on en créé une (travaille tjr sur la même instance de CUtilisateurs dans toute l'appli)
        }
        return self::$instance;
    }





    // Charger tous les utilisateurs depuis la BDD
    public function loadUtilisateurs()
    {
        // Requête qui renvoie tous les utilisateurs
        $stmt = $this->pdo->query("SELECT * FROM Utilisateur");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // On vide la collection d'utilisateurs pour être sûr qu'elle ne contient pas de données obsolètes avant de la remplir (si appelé plusieurs fois)
        $this->collUtilisateurs = [];

        // Pour chaque ligne récupérée, on les transforme en objet CUtilisateur et on les ajoute à la collection $collUtilisateurs
        foreach ($rows as $row) {
            $this->collUtilisateurs[] = new CUtilisateur($row['idUtilisateur'], $row['nom'], $row['prenom'], $row['login'], $row['mdpHash'], $row['idRole'], $row['doitChangerMdp']);
        }
    }


    // Retourner le tableau d'objets CUtilisateur
    public function getUtilisateurs()
    {
        return $this->collUtilisateurs;
    }





    // Ajouter un utilisateur
    public function addUtilisateur($nom, $prenom, $login, $mdpHash, $idRole)
    {
        try {

            // Prépare la requête pour ajouter un utilisateur avec ses attributs
            $stmt = $this->pdo->prepare("INSERT INTO Utilisateur (nom, prenom, login, mdpHash, idRole) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $login, $mdpHash, $idRole]);

            // Création de l'objet
            $utilisateur = new CUtilisateur(null, $nom, $prenom, $login, $mdpHash, $idRole);
            // ID null -> auto increment

            // Récupérer l'ID auto généré et mettre à jour l'objet métier
            $utilisateur->setIdUtilisateur($this->pdo->lastInsertId());

            // Ajout à la collection le nouvel utilisateur créé (synchro de la coll avec la BDD)
            $this->collUtilisateurs[] = $utilisateur;

            return $utilisateur->getIdUtilisateur(); // Si ajout succès -> Return l'id 

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
        }
    }





    // Supprimer un utilisateur
    public function deleteUtilisateur($idUtilisateur)
    {
        try {

            // Requête pour supprimer un utilisateur en bindant l'id de l'utilisateur à supprimer
            $stmt = $this->pdo->prepare("DELETE FROM Utilisateur WHERE idUtilisateur = ?");
            $stmt->execute([$idUtilisateur]);


            // Supprimer de la collection locale
            $nouvelleCollection = [];

            foreach ($this->collUtilisateurs as $utilisateur) {

                // On vérifie que l'id de l'utilisateur de la collection est différent de l'id de l'utilisateur à supprimer
                if ($utilisateur->getIdUtilisateur() != $idUtilisateur) {

                    $nouvelleCollection[] = $utilisateur; // Si il est différent -> ce n'est pas celui qu'on veut supprimer -> on le garde dans le nouveau tableau d'utilisateurs
                }
            }

            $this->collUtilisateurs = $nouvelleCollection; // On remplace l'ancienne collection d'utilisateurs par la nouvelle (sans l'utilisateur supprimé)


            return true; // Si suppression réussie -> return true


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la suppression : " . $e->getMessage();
        }
    }




    // Modifier un utilisateur
    public function updateUtilisateur($nom, $prenom, $login, $idRole, $idUtilisateur)
    {
        try {
            // Requête préparée pour modifier un utilisateur avec ses attributs
            $stmt = $this->pdo->prepare("UPDATE Utilisateur SET nom=?, prenom=?, login=?, idRole=? WHERE idUtilisateur=?");
            $stmt->execute([$nom, $prenom, $login, $idRole, $idUtilisateur]);


            // On met à jour l'objet dans la collection locale
            foreach ($this->collUtilisateurs as $utilisateur) {
                if ($utilisateur->getIdUtilisateur() == $idUtilisateur) {
                    $utilisateur->setNom($nom);
                    $utilisateur->setPrenom($prenom);
                    $utilisateur->setLogin($login);
                    $utilisateur->setIdRole($idRole);
                }
            }

            return true; // Si modification réussie -> return true


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la modification : " . $e->getMessage();
        }
    }





    // Récupérer un utilisateur par son login (sert à la connexion ->  quand un user entre son login, appel de cette fonction pour retrouver l'utilisateur correspondant en BDD)
    public function getUtilisateurByLogin($login)
    {

        // Requête qui renvoie l'utilisateur dont le login correspond à celui passé en paramètre
        $stmt = $this->pdo->prepare("SELECT * FROM Utilisateur WHERE login = ?");
        $stmt->execute([$login]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Récupère une seule ligne -> celle qui correspond à l'utilisateur qui possède le login passé en paramètre

        // Si aucun user ne possède ce login là -> null
        if (!$row) {
            return null;
        }

        // Retourne la ligne récupérée transformée en objet CUtilisateur 
        return new CUtilisateur($row['idUtilisateur'], $row['nom'], $row['prenom'], $row['login'], $row['mdpHash'], $row['idRole'], $row['doitChangerMdp']);
    }



    // Changer le mot de passe d'un utilisateur (à la première connexion)
    public function changerMdp($idUtilisateur, $nouveauMdp)
    {
        try {

            $mdpHash = password_hash($nouveauMdp, PASSWORD_BCRYPT); // On hash le nouveau mot de passe reçu par l'utilisateur

            // Prépare la requête qui va modifier le mdp actuel par le nouveau, changer la valeur de doitChangerMdp, pour tel utilisateur
            $stmt = $this->pdo->prepare("UPDATE Utilisateur SET mdpHash = ?, doitChangerMdp = 0 WHERE idUtilisateur = ?");
            $stmt->execute([$mdpHash, $idUtilisateur]);

            // On met à jour l'objet dans la collection locale
            foreach ($this->collUtilisateurs as $utilisateur) {
                if ($utilisateur->getIdUtilisateur() == $idUtilisateur) {
                    $utilisateur->setMdpHash($mdpHash);
                    $utilisateur->setDoitChangerMdp(0);
                }
            }

            return true; // Si modification du mdp réussie -> return true 


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors du changement de mot de passe : " . $e->getMessage();
        }
    }


    



}
