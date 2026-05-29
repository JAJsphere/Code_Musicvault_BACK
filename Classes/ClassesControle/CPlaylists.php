<?php

require_once "../Classes/CPlaylist.php";

class CPlaylists
{

    // Instance unique (permet de stocker l'unique objet CPlaylists qui sera créé dans le Singleton)
    private static $instance = null;

    // Attributs
    private $collPlaylists = []; // Collection d'objets CPlaylist
    private $pdo;

    // Constructeur
    public function __construct(CDao $dao)
    {
        $this->pdo = $dao->getPdo();
    }



    // Méthode getInstance -> Singleton (garantit qu'on n'aura qu'une seule instance de CPlaylists dans toute l'application)
    public static function getInstance(CDao $dao)
    // C'est getInstance qui créer l'objet CPlaylists -> donc elle a besoin de la connexion à la BDD via CDao
    {
        // On vérifie s'il existe déjà une instance de CPlaylists
        if (self::$instance === null) {
            self::$instance = new CPlaylists($dao); // Si n'en existe pas -> on en créé une (travaille tjr sur la même instance de CPlaylists dans toute l'appli)
        }
        return self::$instance;
    }




    // Charger toutes les playlists depuis la BDD
    public function loadPlaylists()
    {

        // Récupère toutes les playlists avec le login de l'utilisateur associé (JOIN pour pouvoir afficher le nom de l'utilisateur associé, plutôt que son ID)
        $stmt = $this->pdo->query("SELECT p.idPlaylist, p.nom, p.idUtilisateur, p.imageCouv, u.login AS loginUtilisateur
                                    FROM Playlist p
                                    JOIN Utilisateur u ON p.idUtilisateur = u.idUtilisateur");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


        // On vide la collection de playlists pour être sûr qu'elle ne contient pas de données obsolètes avant de la remplir (si appelé plusieurs fois)
        $this->collPlaylists = [];

        // Pour chaque ligne récupérée (résultat requête), on les transforme en objet CPalylist et on les ajoute à la collection $collPlaylists
        foreach ($rows as $row) {
            $this->collPlaylists[] = new CPlaylist($row['idPlaylist'], $row['nom'], $row['idUtilisateur'], $row['imageCouv'], $row['loginUtilisateur']);
        }
    }


    // Retourner le tableau d'objets CPlaylist
    public function getPlaylists()
    {
        return $this->collPlaylists;
    }



    // Ajouter une playlist
    public function addPlaylist($nom, $idUtilisateur, $imageCouv)
    {
        try {

            // On prépare la requête qui va insérer une playlist avec ses valeurs dans la BDD
            $stmt = $this->pdo->prepare("INSERT INTO Playlist (nom, idUtilisateur, imageCouv) VALUES (?, ?, ?)");
            $stmt->execute([$nom, $idUtilisateur, $imageCouv]);

            // Création d'un nouvel objet Playlist
            $playlist = new CPlaylist(null, $nom, $idUtilisateur, $imageCouv);
            // ID null -> auto increment

            // Récupérer l'ID auto généré et mettre à jour l'objet
            $playlist->setIdPlaylist($this->pdo->lastInsertId());

            // Ajouter à la collection locale la nouvelle playlist créée
            $this->collPlaylists[] = $playlist;

            return $this->pdo->lastInsertId(); // Ajout réussit -> Return l'id (si on en a besoin après l'appel de la méthode)

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de l'ajout de la playlist : " . $e->getMessage();
        }
    }



    // Supprimer une playlist
    public function deletePlaylist($idPlaylist)
    {
        try {

            // On prépare la requête de suppression de la musique dans la BDD en bindant l'id de la musique à supprimer 
            $stmt = $this->pdo->prepare("DELETE FROM Playlist WHERE idPlaylist = ?");
            $stmt->execute([$idPlaylist]);


            // Supprimer de la collection locale
            $nouvelleCollection = [];

            foreach ($this->collPlaylists as $playlist) {

                // On vérifie que l'id de la playlist de la collection est différent de l'id de la playlist à supprimer
                if ($playlist->getIdPlaylist() != $idPlaylist) {

                    $nouvelleCollection[] = $playlist; // Oui différent -> On garde la playlist dans le nouveau tableau
                }
            }

            // J'écrase mon ancienne collection de playlists par la nouvelle (sans la playlist à supprimer)
            $this->collPlaylists = $nouvelleCollection;


            return true; // Suppression réussie -> Return true

            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la suppression : " . $e->getMessage();
        }
    }




    // Modifier une playlist
    public function updatePlaylist($idPlaylist, $nom, $idUtilisateur, $imageCouv = null)
    {
        try {

            // Prépare la requête pour modifier une playlist avec ses valeurs et l'id précis de la playlist à modifier
            $stmt = $this->pdo->prepare("UPDATE Playlist SET nom=?, idUtilisateur=?, imageCouv=? WHERE idPlaylist=?");
            $stmt->execute([$nom, $idUtilisateur, $imageCouv, $idPlaylist]);

            // On met à jour l'objet dans la collection locale
            foreach ($this->collPlaylists as $playlist) {

                // Est ce que l'ID de la playlist sur laquelle on est en train de boucler, est égal à l'ID de la playlist qu'on veut modifier ?
                if ($playlist->getIdPlaylist() == $idPlaylist) {

                    // Oui -> La playlist est déjà dans la collection, donc on la trouve et on met à jour ses attributs avec les setters
                    $playlist->setNom($nom);
                    $playlist->setIdUtilisateur($idUtilisateur);
                    $playlist->setImageCouv($imageCouv);
                }
            }

            return true; // Modification réussie -> Return true


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la modification : " . $e->getMessage();
        }
    }




    // Fonction pour récupérer une playlist par son ID -> utilisée pour vérifier l'appartenance d'une playlist à un utilisateur dans PlaylistActions.php
    // Utile pour vérifier qu'un utilisateur ne peut pas modifier ou supprimer la playlist de quelqu'un d'autre
    public function getPlaylistById($idPlaylist)
    {
        // Pour chaque playlist de la collection 
        foreach ($this->collPlaylists as $p) {

            // Si l'ID de la playlist courante correspond à l'ID recherché -> on la retourne
            if ($p->getIdPlaylist() == $idPlaylist) {

                return $p; // On retourne l'objet CPlaylist trouvé
            }
        }
        return null; // Si aucune playlist trouvée avec cet ID -> null
    }


}




