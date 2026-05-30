<?php

require_once "../Classes/CGenre.php";
class CGenres
{

    // Instance unique (permet de stocker l'unique objet CGenres qui sera créé dans le Singleton)
    private static $instance = null;

    // Attributs
    private $collGenres = []; // Collection d'objets CGenre
    private $pdo;

    // Constructeur
    public function __construct(CDao $dao)
    {
        $this->pdo = $dao->getPdo(); // Récupère la connexion PDO depuis CDao (et stocke dans pdo pour les méthodes de la classe)
    }




    // Méthode getInstance -> Singleton (garantit qu'on n'aura qu'une seule instance de CGenres dans toute l'application)
    // Static -> Pas besoin d'instancier un nouvel objet CGenres pour appeler cette méthode, on peut l'appeler directement via la classe CGenres::getInstance()
    public static function getInstance(CDao $dao)
    // C'est getInstance qui créer l'objet CGenres -> donc elle a besoin de la connexion à la BDD via CDao
    {
        // On vérifie s'il existe déjà une instance de CGenres
        if (self::$instance === null) {

            // Si pas le cas -> on en crée une (garantit qu'on travaille toujours avec la même instance de CGenres dans toute l'application)
            self::$instance = new CGenres($dao);
        }
        return self::$instance;

        // self = CGenres (la classe elle-même)
    }





    // Charger tous les genres depuis la BDD (pour remplir la collection $collGenres, on va pouvoir travailler avec cette collection pour CRUD)
    public function loadGenres()
    {
        // stmt -> contient le résultat de la requête 
        $stmt = $this->pdo->query("SELECT * FROM Genre");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // fetchAll -> récupère toutes les lignes renvoyées, on stocke dans $rows un tableau associatif (clé / valeur)

        // On vide la collection de genres pour être sûr qu'elle ne contient pas de données obsolètes avant de la remplir (si appelé plusieurs fois)
        $this->collGenres = [];


        foreach ($rows as $row) {

            // Pour chaque ligne récupérée, on les transforme en objet CGenre et on les ajoute à la collection $collGenres
            $this->collGenres[] = new CGenre($row['idGenre'], $row['libelle']); // On les précise car CGenre attend ces 2 paramètres dans son constructeur (dans l'ordre)
        }
    }


    // Retourner le tableau d'objets CGenre (la collection de genres créée dans loadGenres)
    public function getGenres()
    {
        return $this->collGenres;
    }




    // Ajouter un genre
    public function addGenre($libelle)
    {
        try {

            // Prépare la requête et l'éxécute en bindant le libellé du genre à ajouter
            $stmt = $this->pdo->prepare("INSERT INTO Genre (libelle) VALUES (?)");
            $stmt->execute([$libelle]); // Execute s'occupe aussi du bind car je lui donne le tableau des valeurs à insérer

            // Création de l'objet
            $genre = new CGenre(null, $libelle); // ID null car généré automatiquement par la BDD 

            // Puis je récupère l'ID généré par la BDD (lastInsertId, le dernier ID inséré dans la BDD) et je le set dans l'objet genre que je viens de créer
            $genre->setIdGenre($this->pdo->lastInsertId());

            // Ajout à la collection de genres le nouveau genre créé (synchro de la coll avec la BDD)
            $this->collGenres[] = $genre;

            // [] -> Ajoute en fin de tableau


            return $this->pdo->lastInsertId(); // Retourne l'id du genre créé si ajout réussi 

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de l'ajout du genre : " . $e->getMessage();
        }

    }




    // Supprimer un genre
    public function deleteGenre($idGenre)
    {
        try {

            //  On prépare la requête de suppression du genre dans la BDD en bindant l'id du genre à supprimer
            $stmt = $this->pdo->prepare("DELETE FROM Genre WHERE idGenre = ?");
            $stmt->execute([$idGenre]);

            // Supprimer le genre de la collection locale
            $nouvelleCollection = [];

            foreach ($this->collGenres as $genre) {

                // On vérifie que l'id du genre de la collection est différent de l'id du genre à supprimer
                if ($genre->getIdGenre() != $idGenre) {

                    $nouvelleCollection[] = $genre; // Si il est différent -> ce n'est pas celui qu'on veut supprimer -> on le garde dans le nouveau tableau de genres
                }
            }

            $this->collGenres = $nouvelleCollection; // On remplace l'ancienne collection de genres par la nouvelle (sans le genre supprimé)


            return true; // Retourne true si la suppression a réussi (facultatif)

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la suppression : " . $e->getMessage();
        }
    }





    // Vérifie si un genre est utilisé par au moins une musique -> pour éviter de supprimer un genre qui est encore référencé dans la table Musique
    public function isGenreUsed($idGenre)
    {
        // Prépare la requête -> Compte le nombre de musiques qui référencent CET id de genre (dans la table Musique)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS count FROM Musique WHERE idGenre = ?");
        $stmt->execute([$idGenre]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Récupère UNE seule ligne (count : nombre)
        $count = $row['count']; // Récupère la valeur du count (le nombre de musiques qui référencent ce genre)

        // Vérifie donc si CE genre est utilisé par au moins une musique (si oui -> true, si false, on peut le delete)
        return $count > 0;
    }


}


