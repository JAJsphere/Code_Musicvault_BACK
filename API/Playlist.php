<?php

// Playlist -> GET des playlists de l'utilisateur connecté

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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH, OPTIONS");
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
require_once "../Classes/CDao.php";
require_once "auth.php"; // Vérifications -> sécurité pour le JWT


$dao = new CDao(); // Crée la connexion à la BDD
$cplaylists = CPlaylists::getInstance($dao); // Récupère l'instance unique de la classe CPlaylists (vide)
$cplaylists->loadPlaylists(); // Remplissage de la collection CPlaylist (depuis la bdd) stockée dans l'instance CPlaylists
$playlists = $cplaylists->getPlaylists(); // Récupère le tableau d'objets CPlaylist et le stocke dans "playlists"


// -- Préparer les données à renvoyer en JSON -- //

$dataPlaylists = []; // Tableau vide qui va stocker les données à renvoyer en JSON

// Parcourt de chaque objet CPlaylist -> On filtre pour ne garder que les playlists de l'utilisateur connecté
foreach ($playlists as $p) {
 
    // Si la playlist appartient à l'utilisateur connecté, on la garde (compare l'id de l'utilisateur correspondant à telle playlist, avec celui de l'user connecté)
    if ($p->getIdUtilisateur() === $idUtilisateur) {
        // idUtilisateur provient de auth.php -> On veut ne renvoyer que les playlists qui appartiennent à l'utilisateur connecté, pas toutes

        // Pour chaque objet CPlaylist, on le convertit en tableau associatif pour permettre la conversion en JSON
        $dataPlaylists[] = [
            "idPlaylist" => $p->getIdPlaylist(), // J'assigne la valeur de l'ID récupérée par son getter, à sa clé idPlaylist
            "nom" => $p->getNom(),
            "idUtilisateur" => $p->getIdUtilisateur(),
            "imageCouv" => $p->getImageCouv() ?? ""
        ];
    }
}

header("Content-Type: application/json; charset=utf-8"); // Je dis au front que je lui envoie du JSON

// json_encode -> transforme le tableau de tableaux associatifs en JSON
echo json_encode($dataPlaylists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// echo -> Envoie les données au front (contexte API, afficher (echo) = envoyer (mis dans la réponse HTTP))