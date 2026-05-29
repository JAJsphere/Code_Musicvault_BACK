<?php

// MusiquePlaylist -> GET les musiques d'une playlist spécifique

// CORS -> Seuls ces sites ont le droit d'appeler l'API (HTTP et HTTPS comptent comme deux origines différentes)
$corsTAB = [

    "http://localhost:5173",
    "http://localhost:4173",
    "http://172.20.126.1",
    "https://172.20.126.3",
    "http://musicvault.hugoal.fr",
    "https://musicvault.hugoal.fr",

];
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $corsTAB)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH,  OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Credentials: true");
}


// 🔥 Gestion du preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Classes dont on a besoin
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "../Classes/ClassesControle/CMusique_Playlists.php";
require_once "../Classes/CDao.php";
require_once "auth.php";


// Initialisation des objets nécessaires pour gérer les musiques dans les playlists
$dao = new CDao();
$cplaylists = CPlaylists::getInstance($dao);
$cplaylists->loadPlaylists();


// Récupérer l'idPlaylist passé en paramètre GET pour filtrer les musiques de cette playlist
$idPlaylist = $_GET['idPlaylist'] ?? null;

// Vérifier que l'idPlaylist est présent dans les paramètres pour éviter les erreurs et les requêtes inutiles à la base de données
if (!$idPlaylist) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "ID playlist manquant"
    ]);
    exit;
}


// Vérifier que la playlist existe et appartient à l'utilisateur en question
$playlist = $cplaylists->getPlaylistById($idPlaylist);

if (!$playlist || $playlist->getIdUtilisateur() != $idUtilisateur) {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Accès refusé"
    ]);
    exit;
}


// Si tout est ok, on récupère les musiques de la playlist 
$cmusiquePlaylists = CMusiquePlaylists::getInstance($dao);
$cmusiquePlaylists->loadMusiquePlaylists();
$musiques = array_filter($cmusiquePlaylists->getMusiquePlaylists(), fn($mp) => $mp->getIdPlaylist() == $idPlaylist);



// Préparer les données à renvoyer en JSON
$dataMusiquePlaylists = [];
foreach ($musiques as $mp) {
    $dataMusiquePlaylists[] = [
        "idMusique" => $mp->getIdMusique(),
        "idPlaylist" => $mp->getIdPlaylist(),
        "titre" => $mp->getTitre()

    ];
}


//On dit au navigateur qu'on renvoie du JSON 
header("Content-Type: application/json; charset=utf-8");

//On transforme le tableau PHP en JSON prêt à être utilisé pour le front 
echo json_encode($dataMusiquePlaylists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


