<?php

// PlaylistActions.php -> Create, Update, Delete
// But : Faire l'action en BDD (insérer, supprimer, modifier) et prévenir React en renvoyant les données (sauf si erreur)

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
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "../Classes/CDao.php";
require_once "../Classes/CPlaylist.php";
require_once "auth.php"; // Fichier pour gérer l'authentification et récupérer les infos du JWT (idUtilisateur, role)


// Initialisation des objets nécessaires pour gérer les playlists
$dao = new CDao(); // Crée la connexion à la BDD
$cplaylists = CPlaylists::getInstance($dao); // Récupère l'instance unique de la classe CPlaylists (vide)
$cplaylists->loadPlaylists(); // On charge la collection depuis la BDD (nécessaire pour vérifier qu'une playlist existe et appartient bien à l'utilisateur connecté avant de la modifier/supprimer)

// Récup de la méthode dans le header du front (method : post, put...)
$method = $_SERVER['REQUEST_METHOD'];

// Récup des données envoyées par le body du front (pour UPDATE et CREATE)
// Json_decode va transformer les données JSON en tab assoc php
$data = json_decode(file_get_contents("php://input"), true);




// METHOD POST -> Créer une nouvelle playlist
if ($method === "POST") {

    // On passe en param les données récupérées par le file_get_content du front, dans la méthode addPlaylist
    // La ligne insère les données qu'on lui donne dans la BDD (rôle de la méthode)
    // On stocke tout ça dans "idPlaylist", car la méthode addPlaylist renvoie l'id de la playlist ajoutée (utile pour update et delete après)
    // Va chercher dans le tableau data la valeur qui a la clé "nom" : { nom: "Ma playlist" }
    $idPlaylist = $cplaylists->addPlaylist($data["nom"], $idUtilisateur, $data["imageCouv"]);


    // Si l'insert a réussi
    if ($idPlaylist) {

        // On renvoie un tableau associatif des données de la playlist pour que React puisse l'utiliser (indispensable car quand on crée, React ne connait pas l'ID de la nouvelle playlist)
        echo json_encode([
            "success" => true, // Pour prévenir React que l'ajout en base a réussi
            "playlist" => [
                "idPlaylist" => $idPlaylist, // Renvoie aussi l'id que React ne possède pas
                "nom" => $data["nom"],
                "idUtilisateur" => $idUtilisateur, // Provient du JWT de auth
                "imageCouv" => $data["imageCouv"] ?? ""
            ]
        ]);

    // Erreur -> L'insertion en base a échoué (récupéré et affiché par React)
    } else {
        echo json_encode(["success" => false, "message" => $idPlaylist]);
        exit;
    }
}




// METHOD PUT -> Modifier une playlist
if ($method === "PUT") {

    // Récupérer la playlist depuis la collection pour vérifier qu'elle existe et qu'elle appartient à l'utilisateur connecté
    // Je donne en param de ma fonction l'id de la playlist que je récupère du front
    $playlistExistante = $cplaylists->getPlaylistById($data["idPlaylist"]);

    // Vérifier qu'elle existe et qu'elle appartient à l'utilisateur connecté
    // L'admin peut modifier toutes les playlists, les utilisateurs normaux ne peuvent modifier que leurs propres playlists
    if (!$playlistExistante || ($idRole !== 1 && $playlistExistante->getIdUtilisateur() != $idUtilisateur)) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // Va modifier cette playlist dans la base -> on donne en params de la fonction, les données envoyées par le front
    // On passe également l'id pour qu'on sache de quelle playlist on parle (prédicat dans la méthode)
    $success = $cplaylists->updatePlaylist($data["idPlaylist"], $data["nom"], $playlistExistante->getIdUtilisateur(), $data["imageCouv"] ?? null);

    // On vérifie si la modification en base a réussi
    if ($success === true) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La modification en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $success]);
    }
}




// METHOD DELETE -> Supprimer une playlist
if ($method === "DELETE") {

    parse_str($_SERVER["QUERY_STRING"], $params); // Récupère l'ID de la playlist envoyé par React dans l'URL
                                                  // QUERY_STRING -> C'est la partie après le "?" dans l'URL (donc idPlaylist=5 par ex)
                                                  // parse_str -> Transforme la string en tab associatif php
                                                  // $params stocke le tab associatif (ici ne contient que l'id de la playlist)

    $idPlaylist = $params["idPlaylist"]; // Récupère la valeur de idPlaylist et la stocke

    // Récupérer la playlist depuis la collection pour vérifier qu'elle appartient à l'utilisateur connecté
    $playlist = $cplaylists->getPlaylistById($idPlaylist);

    // L'admin peut supprimer toutes les playlists, les utilisateurs normaux ne peuvent supprimer que leurs propres playlists
    if (!$playlist || ($idRole !== 1 && $playlist->getIdUtilisateur() != $idUtilisateur)) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // On passe l'id de la playlist à la méthode de suppression pour la supprimer de la base
    $success = $cplaylists->deletePlaylist($idPlaylist);

    // Vérifie si la suppression a réussi
    if ($success === true) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La suppression en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $success]);
    }
}