<?php

// MusiquePlaylist -> GET les musiques d'une playlist spécifique

// CORS -> Seuls ces sites ont le droit d'appeler l'API
$corsTAB = [

    "http://localhost:5173",
    "http://localhost:4173",
    "http://172.20.126.1",
    "https://172.20.126.3",
    "http://musicvault.hugoal.fr",
    "https://musicvault.hugoal.fr",

];

// Autorisation CORS : Si une des URL qui appelle l'API est dans la liste, on autorise les headers
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $corsTAB)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH,  OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Credentials: true");
}

// Preflight CORS -> Le navigateur envoie toujours une requête OPTIONS avant la vraie requête pour vérifier qu'il a le droit d'appeler l'API
// On répond juste 200 OK et on coupe, le navigateur envoie ensuite la vraie requête
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require des classes nécessaires
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "../Classes/ClassesControle/CMusique_Playlists.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Vérifications -> sécurité pour le JWT, récupère $idUtilisateur et $idRole


// On charge la collection de playlists
$dao = new CDao(); // Crée la connexion à la BDD
$cplaylists = CPlaylists::getInstance($dao); // Récupère l'instance unique de la classe CPlaylists (vide)
$cplaylists->loadPlaylists(); // Remplissage de la collection CPlaylist (depuis la bdd) stockée dans l'instance CPlaylists


// Récupère l'idPlaylist depuis l'URL (?idPlaylist=42) ?? = sinon si n'existe pas, prendre null
$idPlaylist = $_GET['idPlaylist'] ?? null;

// Arrête tout si aucun idPlaylist dans l'URL
if (!$idPlaylist) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID playlist manquant"]);
    exit;
}


$playlist = $cplaylists->getPlaylistById($idPlaylist); // Récupère la playlist correspondant à l'id dans l'URL (donc une seule stockée dans $playlist)

//  Si l'id de l'utilisateur de CETTE playlist qu'on a recup de l'url, ne correspond pas à l'id de l'utilisateur connecté, alors ca veut dire que ce n'est pas SA playlist
// L'utilisateur ne pourra pas get les musiques d'une playlist qui ne lui appartient pas    
if (!$playlist || $playlist->getIdUtilisateur() != $idUtilisateur) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Accès refusé"]);
    exit;
}


// On charge la collection des musiques dans telle playlist
$cmusiquePlaylists = CMusiquePlaylists::getInstance($dao); // Récupère l'instance unique de la classe CMusiquePlaylists (vide)
$cmusiquePlaylists->loadMusiquePlaylists(); // Remplissage de la collection CMusique_Playlist (depuis la bdd)
$toutesLesMusiques = $cmusiquePlaylists->getMusiquePlaylists(); // Je get la collection d'objets CMusiqueplaylist que je stocke dans $toutesLesMusiques


// Récupérer uniquement les musiques de la playlist demandée dans l'URL, parmi toutes les musiques de toutes les playlists
$musiques = [];

// Parcourt toutes les musiques 
foreach ($toutesLesMusiques as $mp) {

    // Vérifie si la musique appartient à la playlist demandée 
    if ($mp->getIdPlaylist() == $idPlaylist) {

        // Si oui -> on la garde dans le tableau
        $musiques[] = $mp;
    }
}




// -- Préparer les données à renvoyer en JSON -- //

// Tableau vide qui va stocker les données à renvoyer en JSON
$dataMusiquePlaylists = [];

// Parcourt de chaque objet CMusique_Playlist
foreach ($musiques as $mp) {

    // Pour chaque objet CMusique_Playlist, on le convertit en tableau associatif pour permettre la conversion en JSON
    $dataMusiquePlaylists[] = [
        "idMusique" => $mp->getIdMusique(), // Associe la valeur à la clé idMusique
        "idPlaylist" => $mp->getIdPlaylist(),
        "titre" => $mp->getTitre()
    ];
}

header("Content-Type: application/json; charset=utf-8"); // Je dis au front que je lui envoie du JSON

// json_encode -> transforme le tableau de tableaux associatifs en JSON
echo json_encode($dataMusiquePlaylists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// echo -> Envoie les données au front (contexte API, afficher (echo) = envoyer (mis dans la réponse HTTP))