<?php

// PlaylistActions.php -> CRUD pour les playlists

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



// Gestion du preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Classes dont on a besoin
require_once "../Classes/ClassesControle/CPlaylists.php";
require_once "../Classes/CDao.php";
require_once "../Classes/CPlaylist.php";
require_once "auth.php";


// Initialisation des objets nécessaires pour gérer les playlists
$dao = new CDao();
$cplaylists = CPlaylists::getInstance($dao);
$cplaylists->loadPlaylists();

// Récupération de la méthode HTTP utilisée servant à déterminer l'action à effectuer (POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);





// METHOD POST -> CREATE -> Créer une nouvelle playlist
if ($method === "POST") {

    $idPlaylist = $cplaylists->addPlaylist($data["nom"], $idUtilisateur, $data["imageCouv"]);

    if ($idPlaylist) {
        echo json_encode([
            "success" => true,
            "playlist" => [
                "idPlaylist" => $idPlaylist,
                "nom" => $data["nom"],
                "idUtilisateur" => $idUtilisateur,
                "imageCouv" => $data["imageCouv"] ?? ""
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de la création"]);
        exit;
    }
}




// METHOD PUT -> UPDATE -> Modifier une playlist (avec vérification que la playlist appartient bien à l'utilisateur connecté -> car on ne veut pas qu'un utilisateur puisse modifier la playlist d'un autre utilisateur)
if ($method === "PUT") {

    // Récupérer la playlist depuis la collection
    $playlistExistante = $cplaylists->getPlaylistById($data["idPlaylist"]);

    // Vérifier qu'elle existe et qu'elle appartient à l'utilisateur connecté 
    if (!$playlistExistante || ($idRole !== 1 && $playlistExistante->getIdUtilisateur() != $idUtilisateur)) { // L'admin peut modifier toutes les playlists, les utilisateurs normaux ne peuvent modifier que leurs propres playlists
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // Appel direct au DAO sans créer d'objet
    $success = $cplaylists->updatePlaylist($data["idPlaylist"], $data["nom"], $playlistExistante->getIdUtilisateur(), $data["imageCouv"] ?? null);


    if ($success === true) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $success]);
    }
}




// METHOD DELETE -> DELETE -> Supprimer une playlist (avec vérification que la playlist appartient bien à l'utilisateur connecté -> car on ne veut pas qu'un utilisateur puisse supprimer la playlist d'un autre utilisateur)
if ($method === "DELETE") {
    parse_str($_SERVER["QUERY_STRING"], $params);

    $idPlaylist = $params["idPlaylist"];

    // Vérifier que la playlist appartient à l'utilisateur connecté
    $playlist = $cplaylists->getPlaylistById($idPlaylist); // On va créer cette méthode dans CPlaylists

    if (!$playlist || ($idRole !== 1 && $playlist->getIdUtilisateur() != $idUtilisateur)) { // L'admin peut supprimer toutes les playlists, les utilisateurs normaux ne peuvent supprimer que leurs propres playlists
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // Supprimer la playlist    
    $success = $cplaylists->deletePlaylist($idPlaylist);
    if ($success === true) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $success]);
    }
}