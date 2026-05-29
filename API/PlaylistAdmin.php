<?php

// PlaylistAdmin -> Récupérer toutes les playlists des utilisateurs 
// Note -> J'utilise le DELETE de chez PlaylistActions.php pour supp une playlist d'un utilisateur

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

// Gestion du preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Classes nécessaires
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // récupère $idUtilisateur et $idRole depuis le JWT


// Initialisation des objets
$dao = new CDao();
$cplaylists = CPlaylists::getInstance($dao);
$cplaylists->loadPlaylists();
$playlists = $cplaylists->getPlaylists();



// GET → toutes les playlists des utilisateurs (pouvoir admin)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Vérification du rôle admin
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    $dataPlaylists = [];

    foreach ($playlists as $p) {
        $dataPlaylists[] = [
            "idPlaylist" => $p->getIdPlaylist(),
            "nom" => $p->getNom(),
            "loginUtilisateur" => $p->getLoginUtilisateur(),
            "idUtilisateur" => $p->getIdUtilisateur(),
            "imageCouv" => $p->getImageCouv() ?? ""
        ];
    }

    // Tri alphabétique par ID utilisateur (option : plus tard on peut utiliser le nom si tu as un mapping utilisateurs)
    usort($dataPlaylists, function ($a, $b) {
        return $a['idUtilisateur'] <=> $b['idUtilisateur'];
    });

    echo json_encode($dataPlaylists, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}