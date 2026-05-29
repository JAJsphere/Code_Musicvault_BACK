<?php

require_once "../Classes/CMusique_Playlist.php";
class CMusiquePlaylists
{

    // Instance unique (permet de stocker l'unique objet CMusiquePlaylists qui sera créé dans le Singleton)
    private static $instance = null;

    // Attributs
    private $collMusiquePlaylists = []; // Collection d'objets MusiquePlaylist
    private $pdo;

    // Constructeur
    public function __construct(CDao $dao)
    {
        $this->pdo = $dao->getPdo();
    }





    // Méthode getInstance -> Singleton (garantit qu'on n'aura qu'une seule instance de CMusiquePlaylists dans toute l'application)
    public static function getInstance(CDao $dao)
    // C'est getInstance qui créer l'objet CMusiquePlaylists -> donc elle a besoin de la connexion à la BDD via CDao
    {
        // On vérifie s'il existe déjà une instance de CMusiquePlaylists
        if (self::$instance === null) {
            self::$instance = new CMusiquePlaylists($dao); // Si n'en existe pas -> on en créé une (travaille tjr sur la même instance de CMusiquePlaylists dans toute l'appli)
        }
        return self::$instance;
    }




    // Charger toutes les relations musique-playlist depuis la BDD
    public function loadMusiquePlaylists()
    {

        // Requête pour récupérer toutes les relations musique-playlist avec les noms des musiques (JOIN car dans Musique_Playlist -> que les ID)
        $stmt = $this->pdo->query("SELECT mp.idMusique, mp.idPlaylist, m.titre FROM Musique_Playlist mp JOIN Musique m ON mp.idMusique = m.idMusique");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC); // Récupérer toutes les lignes renvoyées 

        // On vide la collection pour être sûr qu'elle ne contient pas de données obsolètes avant de la remplir (si appelé plusieurs fois)
        $this->collMusiquePlaylists = [];


        foreach ($rows as $row) {

            // Pour chaque ligne récupérée, on les transforme en objet CMusiquePlaylist 
            $mp = new MusiquePlaylist($row['idMusique'], $row['idPlaylist']);
            $mp->setTitre($row['titre']); // Le nom de la musique n'est pas en paramètre de MusiquePlaylist (car provient de la table Musique), donc obligé de le set que maintenant

            // On ajoute tout à la collection collMusiquePlaylists
            $this->collMusiquePlaylists[] = $mp;

        }
    }


    // Retourner le tableau d'objets MusiquePlaylist
    public function getMusiquePlaylists()
    {
        return $this->collMusiquePlaylists;
    }




    // Ajouter un lien entre une musique et une playlist -> "cette musique appartient à cette playlist"
    public function addMusiquePlaylist($idMusique, $idPlaylist)
    {
        // Vérifier si la musique est déjà dans la playlist (éviter les doublons dans une playlist) 
        if ($this->existeMusiqueDansPlaylist($idMusique, $idPlaylist)) {
            return "La musique est déjà dans cette playlist";
        }

        try {

            // Requête préparée qui ajoute une musique dans une playlist (créer une liaison)
            $stmt = $this->pdo->prepare("INSERT INTO Musique_Playlist (idMusique, idPlaylist) VALUES (?, ?)");
            $stmt->execute([$idMusique, $idPlaylist]);

            // Création de l'objet
            $mp = new MusiquePlaylist($idMusique, $idPlaylist);

            // Ajouter à la collection locale
            $this->collMusiquePlaylists[] = $mp;

            return true; // Si ajout réussit -> Return true


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de l'ajout : " . $e->getMessage();
        }
    }




    // Supprimer une relation entre une musique et une playlist 
    public function deleteMusiquePlaylist($idMusique, $idPlaylist)
    {
        try {

            // Requête préparée pour supprimer telle musique de telle playlist
            $stmt = $this->pdo->prepare("DELETE FROM Musique_Playlist WHERE idMusique=? AND idPlaylist=?");
            $stmt->execute([$idMusique, $idPlaylist]);


            // Supprimer de la collection locale
            $nouvelleCollection = [];

            foreach ($this->collMusiquePlaylists as $mp) {

                // On vérifie que la relation de la collection n'est pas celle qu'on veut supprimer (comparaison des deux IDs, car pas d'ID unique, combinaison des deux = clé composite)
                if (!($mp->getIdMusique() == $idMusique && $mp->getIdPlaylist() == $idPlaylist)) {

                    $nouvelleCollection[] = $mp; // Ce n'est pas la bonne relation -> on garde la musique dans la playlist
                }
            }

            // On remplace l'ancienne collection par la nouvelle (sans la relation supprimée)
            $this->collMusiquePlaylists = $nouvelleCollection;


            return true; // Si suppression réussie -> return true 


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return "Erreur lors de la suppression : " . $e->getMessage();
        }
    }




    // Savoir si une musique existe dans une playlist (utilisée pour l'ajout d'une relation)
    public function existeMusiqueDansPlaylist($idMusique, $idPlaylist)
    {
        try {

            // Requête préparée qui compte (max 1) le nombre de fois que cette musique existe dans cette playlist en BDD
            $stmt = $this->pdo->prepare("SELECT COUNT(*) AS count FROM Musique_Playlist WHERE idMusique = ? AND idPlaylist = ?");
            $stmt->execute([$idMusique, $idPlaylist]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC); // Récupère le résultat du COUNT sous forme de tableau associatif
            return $row['count'] > 0; // On accède à la valeur de la colonne count dans le tableau via sa clé (count) et on vérifie si la musique est déjà dedans   


            // Erreur -> Affiche un message d'erreur (au lieu de planter l'application)
        } catch (Exception $e) {
            return false;
        }
    }


}

