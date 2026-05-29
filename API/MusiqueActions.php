<?php

// MusiqueActions.php -> CRUD pour les musiques (GET ailleurs)

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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Credentials: true");
}

//Gestion du preflight CORS -> API accepte les actions (PUT, DELETE, POST)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

//Classes dont on a besoin
require_once "../Classes/ClassesControle/CMusiques.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Fichier pour gérer l'authentification et récupérer les infos du JWT (idUtilisateur, role)

$dao = new CDao(); //Contient la connexion à la BDD
$cmusiques = CMusiques::getInstance($dao); //Initialisation de l'objet CMusiques qui contient toutes les fonctions pour gérer les musiques

$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);




//METHOD POST -> CREATE -> Créer une nouvelle musique
if ($method === "POST") {

    // Vérification du rôle (même si front-end le fait, sécurité++ côté back-end)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : rôle insuffisant"]);
        http_response_code(403);
        exit;
    }


    $id = $cmusiques->addMusique($data["titre"], $data["artiste"], $data["album"], $data["duree"], $data["pochette"], $data["dateSortie"], $data["idGenre"]); // <-- faire en sorte que addMusique retourne l'ID généré
    if ($id) {

        // On renvoie un tableau associatif pour que React puisse l'utiliser (indispensable car quand on créer, React ne connait pas l'ID de la nouvelle musique)
        echo json_encode([
            "success" => true,
            "musique" => [
                "idMusique" => $id,
                "titre" => $data["titre"],
                "artiste" => $data["artiste"],
                "album" => $data["album"],
                "duree" => $data["duree"],
                "pochette" => $data["pochette"],
                "dateSortie" => $data["dateSortie"],
                "idGenre" => $data["idGenre"],
                "libelleGenre" => null
            ]
        ]);


    } else {
        echo json_encode(["success" => false, "message" => "Erreur lors de l'insertion"]); // En cas d'erreur
    }
}






//METHOD PUT -> UPDATE -> Modifier une musique
if ($method === "PUT") {

    // Vérification du rôle (même si front-end le fait, sécurité++ côté back-end)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : rôle insuffisant"]);
        http_response_code(403);
        exit;
    }


    // Va modifier cette musique dans la base avec ces nouvelles valeurs 
    $cmusiques->updateMusique($data["idMusique"], $data["titre"], $data["artiste"], $data["album"], $data["duree"], $data["pochette"], $data["dateSortie"], $data["idGenre"]); //Méthode updateMusique de mon objet CMusiques -> permet de faire un UPDATE SQL dans la BDD
    echo json_encode(["success" => true]); //Renvoie d'une réponse JSON 
}





//METHOD DELETE -> DELETE -> Supprimer une musique
if ($method === "DELETE") {

    // Vérification du rôle (même si front-end le fait, sécurité++ côté back-end)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : rôle insuffisant"]);
        http_response_code(403);
        exit;
    }

    parse_str($_SERVER["QUERY_STRING"], $params);
    $cmusiques->deleteMusique($params["idMusique"]); // Permet de faire un DELETE SQL dans la BDD en passant l'ID de la musique à delete
    echo json_encode(["success" => true]); //Renvoie d'une réponse JSON
}
