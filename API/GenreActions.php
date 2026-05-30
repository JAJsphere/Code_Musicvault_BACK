<?php

// GenreActions.php -> Create, Delete
// But : Faire l'action en BDD (insérer, supprimer) et prévenir React en renvoyant les données (sauf si erreur)

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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH,  OPTIONS");
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
require_once "../Classes/ClassesControle/CGenres.php";
require_once "../Classes/CGenre.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Fichier pour gérer l'authentification et récupérer les infos du JWT (idUtilisateur, role)

// Initialisation des objets nécessaires pour gérer les genres
$dao = new CDao(); // Crée la connexion à la BDD
$cgenres = CGenres::getInstance($dao); // Récupère l'instance unique de la classe CGenres (vide)
$cgenres->loadGenres(); // On charge la collection depuis la BDD (nécessaire pour vérifier qu'un genre existe déjà avant de l'insérer)

// Récup de la méthode dans le header du front (method : post, delete...)
$method = $_SERVER['REQUEST_METHOD'];

// Récup des données envoyées par le body du front (pour CREATE)
// Json_decode va transformer les données JSON en tab assoc php
$data = json_decode(file_get_contents("php://input"), true);




// METHOD POST -> Créer un nouveau genre
if ($method === "POST") {

    // Vérification du rôle (seuls admin et éditeur peuvent créer un genre)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : vous n'avez pas le droit de créer un genre"]);
        exit;
    }


    // Vérification -> Empêcher d'insérer un rôle déjà existant en BDD 
    // Parcourt de tous les genres de la collection
    foreach ($cgenres->getGenres() as $g) {

        // Pour chaque genre existant, on compare avec celui reçu du front (les deux en minuscules)
        // Si identiques = genre existe déjà -> bloqué
        if (strtolower($g->getLibelle()) === strtolower($data["libelle"])) {

            echo json_encode(["success" => false, "message" => "Ce genre existe déjà"]);
            exit;
        }
    }

    // On passe en param le libelle récupéré du front dans la méthode addGenre
    // La ligne insère les données qu'on lui donne dans la BDD (rôle de la méthode)
    // On stocke tout ça dans "idGenre", car la méthode addGenre renvoie l'id du genre ajouté
    $idGenre = $cgenres->addGenre($data["libelle"]);

    // Si l'insert a réussi
    if ($idGenre) {

        // On renvoie un tableau associatif des données du genre pour que React puisse l'utiliser (indispensable car quand on crée, React ne connait pas l'ID du nouveau genre)
        echo json_encode([
            "success" => true, // Pour prévenir React que l'ajout en base a réussi
            "genre" => [
                "idGenre" => $idGenre, // Renvoie aussi l'id que React ne possède pas
                "libelle" => $data["libelle"]
            ]
        ]);

        // Erreur -> L'insertion en base a échoué (récupéré et affiché par React)
    } else {
        echo json_encode(["success" => false, "message" => $idGenre]);
    }

}




// METHOD DELETE -> Supprimer un genre
if ($method === "DELETE") {

    // Vérification du rôle (seuls admin et éditeur peuvent supprimer un genre)
    if ($idRole !== 1 && $idRole !== 2) {
        echo json_encode(["success" => false, "message" => "Accès refusé : vous n'avez pas le droit de supprimer un genre"]);
        exit;
    }

    parse_str($_SERVER["QUERY_STRING"], $params); // Récupère l'ID du genre envoyé par React dans l'URL
    // QUERY_STRING -> C'est la partie après le "?" dans l'URL (donc idGenre=5 par ex)
    // parse_str -> Transforme la string en tab associatif php
    // $params stocke le tab associatif (ici ne contient que l'id du genre)

    $idGenre = $params["idGenre"]; // Stocke l'id du genre récupéré de l'url


    // Vérifier si le genre est utilisé par des musiques avant de le supprimer 
    if ($cgenres->isGenreUsed($idGenre)) {
        echo json_encode(["success" => false, "message" => "Impossible de supprimer ce genre : utilisé par une ou plusieurs musiques"]);
        exit;
    }

    // On passe l'id du genre à la méthode de suppression pour le supprimer de la base
    $result = $cgenres->deleteGenre($idGenre);

    // Vérifie si la suppression a réussi
    if ($result === true) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La suppression en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $result]);
    }

}