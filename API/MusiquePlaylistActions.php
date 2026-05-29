<?php

// MusiquePlaylistActions -> CRUD pour les musiques DANS les playlists (ajouter une musique dans une playlist, supprimer une musique d'une playlist)


// CORS -> Seuls ces sites ont le droit d'appeler l'API 
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

// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Classes dont on a besoin
require_once "../Classes/CDao.php";
require_once "../Classes/ClassesControle/CMusique_Playlists.php";
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "auth.php"; // Pour vérifier le JWT et récupérer les infos de l'utilisateur connecté (idUtilisateur, role) -> sécurité pour les actions sur les playlists



// Initialisation des objets nécessaires pour gérer les musiques dans les playlists
$dao = new CDao();
$cplaylists = CPlaylists::getInstance($dao);
$cplaylists->loadPlaylists();
$cmusiquePlaylists = CMusiquePlaylists::getInstance($dao);
$cmusiquePlaylists->loadMusiquePlaylists();




// METHOD DELETE -> DELETE -> Supprimer une musique d'une playlist
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str($_SERVER["QUERY_STRING"], $params);
    $idPlaylist = $params["idPlaylist"] ?? null;
    $idMusique = $params["idMusique"] ?? null;


    // Vérifier que la playlist appartient bien à l'utilisateur
    $playlist = $cplaylists->getPlaylistById($idPlaylist);

    if (!$playlist || $playlist->getIdUtilisateur() != $idUtilisateur) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }


    // Supprimer la musique de la playlist
    $success = $cmusiquePlaylists->deleteMusiquePlaylist($idMusique, $idPlaylist);
    if ($success === true) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $success]);
    }
}





// METHOD POST -> CREATE -> Ajouter une musique dans une playlist 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $idMusique = $data['idMusique'] ?? null;
    $playlists = $data['playlists'] ?? []; // Tableau d'ID de playlists

    // Si pas l'ID de la musique, ou pas l'ID de la playlist -> erreur
    if (!$idMusique || empty($playlists)) {
        echo json_encode(["success" => false, "message" => "Musique ou playlists manquantes"]);
        exit;
    }

    $added = [];
    $errors = [];


    foreach ($playlists as $idPlaylist) {
        $playlist = $cplaylists->getPlaylistById($idPlaylist);


        if (!$playlist || $playlist->getIdUtilisateur() != $idUtilisateur) {
            $errors[] = "Accès refusé pour playlist $idPlaylist";
            continue;
        }


        $result = $cmusiquePlaylists->addMusiquePlaylist($idMusique, $idPlaylist);

        if ($result === true) {
            $added[] = $idPlaylist;

        } else {
            $errors[] = "Playlist $idPlaylist : $result";
        }
    }

    echo json_encode([
        "success" => count($errors) === 0,
        "added" => $added,
        "errors" => $errors
    ]);
    exit;


}

