<?php

// MusiquePlaylistActions.php -> Create, Delete
// But : Faire l'action en BDD (insérer, supprimer une musique dans une playlist) et prévenir React en renvoyant les données (sauf si erreur)

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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE,PATCH, OPTIONS");
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

// Classes dont on a besoin
require_once "../Classes/CDao.php";
require_once "../Classes/ClassesControle/CMusique_Playlists.php";
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "auth.php"; // Fichier pour gérer l'authentification et récupérer les infos du JWT (idUtilisateur, role)


// Initialisation des objets nécessaires pour gérer les musiques dans les playlists
$dao = new CDao(); // Crée la connexion à la BDD
$cplaylists = CPlaylists::getInstance($dao); // Récupère l'instance unique de la classe CPlaylists (vide)
$cplaylists->loadPlaylists(); // On charge la collection depuis la BDD (nécessaire pour vérifier qu'une playlist appartient bien à l'utilisateur connecté)

$cmusiquePlaylists = CMusiquePlaylists::getInstance($dao); // Récupère l'instance unique de la classe CMusiquePlaylists (vide)
$cmusiquePlaylists->loadMusiquePlaylists(); // On charge la collection depuis la BDD (nécessaire pour gérer les liaisons musique/playlist)


// Récup de la méthode dans le header du front (method : post, delete...)
$method = $_SERVER['REQUEST_METHOD'];

// Récup des données envoyées par le body du front (pour CREATE)
// Json_decode va transformer les données JSON en tab assoc php
$data = json_decode(file_get_contents("php://input"), true);




// METHOD POST -> Ajouter une musique dans une (ou plusieurs) playlist(s)
if ($method === 'POST') {

    $idMusique = $data['idMusique']; // ID de la musique qu'on veut ajouter, envoyé par le front
    $playlists = $data['playlists']; // Tableau d'ID de playlists car on peut ajouter une musique dans plusieurs playlists en même temps

    $added = []; // Tableau qui stockera les playlists où l'ajout a réussi
    $errors = []; // Tableau qui stockera les erreurs si certains ajouts ont échoué


    // On parcourt chaque playlist dans laquelle on veut ajouter la musique
    foreach ($playlists as $idPlaylist) {

        // Vérifier que la playlist appartient bien à l'utilisateur connecté avant d'ajouter (On ne veut pas qu'un utilisateur puisse ajouter une musique dans la playlist de quelqu'un d'autre)
        $playlist = $cplaylists->getPlaylistById($idPlaylist);

        // Si la playlist n'existe pas ou n'appartient pas à l'utilisateur connecté -> on bloque
        if (!$playlist || $playlist->getIdUtilisateur() != $idUtilisateur) {
            $errors[] = "Accès refusé pour playlist $idPlaylist";
            continue;
        }

        // On passe les ids de la musique et de la playlist à la méthode d'ajout pour insérer la liaison en base
        $result = $cmusiquePlaylists->addMusiquePlaylist($idMusique, $idPlaylist);

        // Vérifie si l'ajout a réussi
        if ($result === true) {
            $added[] = $idPlaylist; // On stocke l'id de la playlist dans laquelle la musique a été ajoutée (Pour renvoyer à React la liste des playlists où l'ajout a réussi)
        } else {

            // Erreur -> L'insertion en base a échoué (récupéré et affiché par React)
            $errors[] = "Playlist $idPlaylist : $result";
        }
    }

    // On renvoie le résultat à React -> success si aucune erreur, sinon on renvoie les erreurs
    // count($errors) compte le nombre d'éléments dans le tableau $errors -> Si = 0 ça veut dire que pas d'erreur, donc success = true s/ Sinon success = false
    // $added contient les IDs des playlists où l'ajout a réussi
    // $errors contient les messages d'erreur des playlists où ça a échoué
    echo json_encode(["success" => count($errors) === 0,"added" => $added,"errors" => $errors]);
}




// METHOD DELETE -> Supprimer une musique d'une playlist
if ($method === 'DELETE') {

    parse_str($_SERVER["QUERY_STRING"], $params); // Récupère les IDs envoyés par React dans l'URL
                                                  // QUERY_STRING -> C'est la partie après le "?" dans l'URL (donc idPlaylist=5&idMusique=3 par ex)
                                                  // parse_str -> Transforme la string en tab associatif php
                                                  // $params stocke le tab associatif (contient l'id de la playlist et l'id de la musique)

    $idPlaylist = $params["idPlaylist"]; // ID de la playlist récupérée de l'url
    $idMusique = $params["idMusique"]; // ID de la musique récupérée de l'url

    // Vérifier que la playlist appartient bien à l'utilisateur connecté avant de supprimer
    $playlist = $cplaylists->getPlaylistById($idPlaylist);

    // Si la playlist n'existe pas ou n'appartient pas à l'utilisateur connecté -> on bloque
    if (!$playlist || $playlist->getIdUtilisateur() != $idUtilisateur) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // On passe les id de la musique et de la playlist à la méthode de suppression pour supprimer la liaison en base
    $success = $cmusiquePlaylists->deleteMusiquePlaylist($idMusique, $idPlaylist);

    // Vérifie si la suppression a réussi
    if ($success === true) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La suppression en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $success]);
    }
}

