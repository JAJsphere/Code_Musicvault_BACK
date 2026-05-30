<?php

// MusiqueActions -> Create, Update, Delete
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

//Classes dont on a besoin
require_once "../Classes/ClassesControle/CMusiques.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Fichier pour gérer l'authentification et récupérer les infos du JWT (idUtilisateur, role)


$dao = new CDao(); // Crée la connexion à la BDD
$cmusiques = CMusiques::getInstance($dao); // Récupère l'instance unique de la classe CMusiques (vide)

// Récup des données par le front 
$data = json_decode(file_get_contents("php://input"), true); // On récupère les données envoyées par le body de mon front (pour UPDATE, et CREATE)
// Json_decode va transformer les données JSON en tab assoc php

// Récup de la méthode dans le header du front (method : post, put...)
$method = $_SERVER['REQUEST_METHOD'];



// METHOD POST -> Créer une nouvelle musique
if ($method === "POST") {

    // Vérification du rôle (seuls admin et editeur peuvent créer une musique)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : rôle insuffisant"]);
        http_response_code(403);
        exit;
    }

    // On passe en param les données recup par le file_get_content du front, dans la méthode addMusique$
    // La ligne insère les données qu'on lui donne dans la BDD (rôle de la méthode)
    // On stocke tout ça dans "id", car la méthode addMusique renvoie l'id de la musique ajoutée (comme ça utile pour update et delete après)
    // Va chercher dans le tableau data la valeur qui a la clé "titre" : { titre: "le titre" }
    $id = $cmusiques->addMusique($data["titre"], $data["artiste"], $data["album"], $data["duree"], $data["pochette"], $data["dateSortie"], $data["idGenre"]);

    // Si l'insert a réussi
    if ($id) {

        // On renvoie un tableau associatif des données de la musique pour que React puisse l'utiliser (indispensable car quand on créer, React ne connait pas l'ID de la nouvelle musique)
        echo json_encode([
            "success" => true, // Pour prévenir React que l'ajout en base a réussit 
            "musique" => [
                "idMusique" => $id, // Renvoie aussi de l'id que React ne possède pas
                "titre" => $data["titre"],
                "artiste" => $data["artiste"],
                "album" => $data["album"],
                "duree" => $data["duree"],
                "pochette" => $data["pochette"],
                "dateSortie" => $data["dateSortie"],
                "idGenre" => $data["idGenre"],
                "libelleGenre" => null // J'ai pas fait de jointure SQL ici, le libellé sera retrouvé côté React grâce à l'idGenre et la liste des genres déjà chargée
            ]
        ]);

        // Erreur -> L'insertion en base a échoué (récupéré et affiché par React)
    } else {
        echo json_encode(["success" => false, "message" => $id]);
    }
}






// METHOD PUT -> Modifier une musique
if ($method === "PUT") {

    // Vérification du rôle (seuls admin et editeur peuvent modifier une musique)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : rôle insuffisant"]);
        http_response_code(403);
        exit;
    }

    // Va modifier cette musique dans la base -> on donne en params de la fonctions, les données envoyées par le front
    // On passe également l'id pour qu'on sache de quelle musique on parle (prédicat dans la méthode)
    $result = $cmusiques->updateMusique($data["idMusique"], $data["titre"], $data["artiste"], $data["album"], $data["duree"], $data["pochette"], $data["dateSortie"], $data["idGenre"]);

    // On vérifie si la modification en base a réussi
    if ($result) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La modification en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $result]);
    }
}




// METHOD DELETE -> Supprimer une musique
if ($method === "DELETE") {

    // Vérification du rôle (seuls admin et editeur peuvent supprimer une musique)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : rôle insuffisant"]);
        http_response_code(403);
        exit;
    }

    parse_str($_SERVER["QUERY_STRING"], $params); // Récupérer l'ID de la musique envoyé par React dans l'URL
                                                // QUERY-STRING -> C'est la partie après le "?" dans l'URL (donc idMusique=5 par ex)
                                                // parse_str -> Transforme la string en tab associatif php
                                                // $params stocke le tab associatif (ici ne contient que l'id de la musique)


    // On passe l'id de la musique à la méthode de suppression pour la supprimer de la base                                                
    $result = $cmusiques->deleteMusique($params["idMusique"]);

    // Vérifie si la suppression a réussi 
    if ($result) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La suppression en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $result]);
    }
}