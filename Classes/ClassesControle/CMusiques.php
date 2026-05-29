<?php

require_once "../Classes/CMusique.php";
class CMusiques
{

    // Instance unique (permet de stocker l'unique objet CMusiques qui sera créé dans le Singleton)
    private static $instance = null;

    // Attributs
    private $collMusiques = [];  // Collection d'objets CMusique
    private $pdo;

    // Constructeur
    public function __construct(CDao $dao)
    {
        $this->pdo = $dao->getPdo(); // Récupère la connexion PDO depuis CDao (et stocke dans pdo pour les méthodes de la classe)
    }




    // Méthode getInstance -> Singleton (garantit qu'on n'aura qu'une seule instance de CMusiques dans toute l'application)
    public static function getInstance(CDao $dao)
    // C'est getInstance qui créer l'objet CMusiques-> donc elle a besoin de la connexion à la BDD via CDao
    {
        // On vérifie s'il existe déjà une instance de CMusiques
        if (self::$instance === null) {
            self::$instance = new CMusiques($dao); // Si n'en existe pas -> on en créé une (travaille tjr sur la même instance de CMusiques dans toute l'appli)
        }
        return self::$instance;
    }



    // Charger toutes les musiques depuis la BDD (pour remplir la collection $collMusiques)
    public function loadMusiques()
    {
        // Récupère toutes les musiques avec le libellé du genre associé (JOIN entre Musique et Genre sur idGenre) (le libelle du genre n'est pas dans la table musique)
        $stmt = $this->pdo->query("SELECT Musique.*, libelle FROM Musique JOIN Genre ON Musique.idGenre = Genre.idGenre");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // On vide la collection de musiques pour être sûr qu'elle ne contient pas de données obsolètes avant de la remplir (si appelé plusieurs fois)
        $this->collMusiques = [];

        // Pour chaque ligne récupérée (résultat requête), on les transforme en objet CMusique et on les ajoute à la collection $collMusiques
        foreach ($rows as $row) {
            $this->collMusiques[] = new CMusique($row['idMusique'], $row['titre'], $row['artiste'], $row['album'], $row['duree'], $row['pochette'], $row['dateSortie'], $row['idGenre'], $row['libelle']);
        }
    }



    // Retourner le tableau d'objets Musique 
    public function getMusiques()
    {
        return $this->collMusiques;
    }




    // Ajouter une musique
    public function addMusique($titre, $artiste, $album, $duree, $pochette, $dateSortie, $idGenre)
    {
        try {

            // Prépare la requête pour insérer une nouvelle musique dans la table Musique avec tous ses attributs
            $stmt = $this->pdo->prepare("INSERT INTO Musique (titre, artiste, album, duree, pochette, dateSortie, idGenre) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titre, $artiste, $album, $duree, $pochette, $dateSortie, $idGenre]);

            // Création de l'objet
            $musique = new CMusique(null, $titre, $artiste, $album, $duree, $pochette, $dateSortie, $idGenre, null);
            // ID = null car auto increment, et libelleGenre aussi car le constructeur l'attend et qu'on l'a pas à ce moment là (car dans la table Genre)

            // Récupérer l'ID auto généré + set dans l'objet genre 
            $musique->setIdMusique($this->pdo->lastInsertId());

            // Ajout à la collection de musiques la nouvelle musique créée (synchro de la coll avec la BDD)
            $this->collMusiques[] = $musique;


            return $this->pdo->lastInsertId(); // Si ajout réussi -> return l'id (si on en a besoin après l'appel de la méthode)

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de l'ajout de la musique : " . $e->getMessage();
        }
    }





    //Supprimer une musique
    public function deleteMusique($idMusique)
    {
        try {

            // On prépare la requête de suppression de la musique dans la BDD en bindant l'id de la musique à supprimer 
            $stmt = $this->pdo->prepare("DELETE FROM Musique WHERE idMusique = ?");
            $stmt->execute([$idMusique]);

            // Supprimer de la collection locale
            $nouvelleCollection = [];

            foreach ($this->collMusiques as $musique) {

                // On vérifie que l'id de la musique de la collection est différent de l'id de la musique à supprimer
                if ($musique->getIdMusique() != $idMusique) {

                    $nouvelleCollection[] = $musique; // Oui différent -> On garde la musique dans le nouveau tableau
                }
            }

            // J'écrase mon ancienne collection de musiques par la nouvelle (sans le genre à supprimer)
            $this->collMusiques = $nouvelleCollection;

            return true; // Si suppression réussie -> return True

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la suppression : " . $e->getMessage();
        }
    }





    // Modifier une musique
    public function updateMusique($idMusique, $titre, $artiste, $album, $duree, $pochette, $dateSortie, $idGenre)
    {
        try {

            // On prépare le requête qui modifie une musique avec les nouvelles données + where pour cibler la musique à modifier
            $stmt = $this->pdo->prepare(
                "UPDATE Musique 
                 SET titre = ?, artiste = ?, album = ?, duree = ?, pochette = ?, dateSortie = ?, idGenre = ? 
                 WHERE idMusique = ?"
            );

            $stmt->execute([$titre, $artiste, $album, $duree, $pochette, $dateSortie, $idGenre, $idMusique]);


            // On met à jour l'objet dans la collection locale
            foreach ($this->collMusiques as $musique) {
                if ($musique->getIdMusique() == $idMusique) {
                    $musique->setTitre($titre);
                    $musique->setArtiste($artiste);
                    $musique->setAlbum($album);
                    $musique->setDuree($duree);
                    $musique->setPochette($pochette);
                    $musique->setDateSortie($dateSortie);
                    $musique->setIdGenre($idGenre);
                }
            }

            return true; // Modification réussie -> return true

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la modification : " . $e->getMessage();
        }
    }

}
