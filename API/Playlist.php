<?php

// Playlist -> Affichage des playlists de l'utilisateur connecté 

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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE,PATCH, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Credentials: true");
}

// Gestion du preflight CORS -> répondre aux requêtes OPTIONS s
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Classes dont on a besoin
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Vérifications -> sécurité pour le JWT



// Initialisation des objets nécessaires pour gérer les playlists
$dao = new CDao();
$cplaylists = CPlaylists::getInstance($dao);
$cplaylists->loadPlaylists();
$playlists = $cplaylists->getPlaylists();



// Filtrer pour ne garder que les playlists de l'utilisateur connecté -> on ne veut pas envoyer les playlists des autres utilisateurs
$dataPlaylists = [];
foreach ($playlists as $p) { // parcourir toutes les playlists
    if ($p->getIdUtilisateur() === $idUtilisateur) { //Si la playlist appartient à l'utilisateur connecté, on la garde
        $dataPlaylists[] = [
            "idPlaylist" => $p->getIdPlaylist(),
            "nom" => $p->getNom(),
            "idUtilisateur" => $p->getIdUtilisateur(),
            "imageCouv" => $p->getImageCouv() ?? ""
        ];

    }
}

//On transforme le tableau PHP en JSON prêt à être utilisé pour le front 
echo json_encode($dataPlaylists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);