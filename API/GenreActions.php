<?php

// GenreActions -> CRUD pour les genres (pouvoir admin et éditeur)

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


// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../Classes/ClassesControle/CGenres.php";
require_once "../Classes/CGenre.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Récupère $idUtilisateur et $idRole depuis le JWT

$dao = new CDao();
$cgenres = CGenres::getInstance($dao);
$cgenres->loadGenres();

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);





/* ---------------------------------- */
/* ----------- CREATE GENRE ---------- */
/* ---------------------------------- */
if ($method === "POST") {

    // Vérification du rôle : seuls admin (1) et éditeur (2) peuvent créer
    if (!in_array($idRole, [1, 2])) {
        echo json_encode([
            "success" => false,
            "message" => "Accès refusé : vous n'avez pas le droit de créer un genre"
        ]);
        exit;
    }


    // Récup valeur envoyé par le front, évite une erreur si rien n'est envoyé, supprime les espaces inutiles
    $libelle = trim($data["libelle"] ?? "");


    // Si le champ est vide -> erreur
    if ($libelle === "") {
        echo json_encode([
            "success" => false,
            "message" => "Le nom du genre est vide"
        ]);
        exit;
    }


    // Vérification insensible à la casse
    foreach ($cgenres->getGenres() as $g) {
        if (strtolower($g->getLibelle()) === strtolower($libelle)) {
            echo json_encode([
                "success" => false,
                "message" => "Ce genre existe déjà"
            ]);
            exit;
        }
    }


    // On appelle la méthode pour créer le genre en lui donnant le libelle en question
    $idGenre = $cgenres->addGenre($libelle);


    // Si ça marche, on renvoie le genre avec son ID
    if ($idGenre) {
        echo json_encode([
            "success" => true,
            "genre" => [
                "idGenre" => $idGenre,
                "libelle" => $libelle
            ]
        ]);


        // Sinon -> message d'erreur
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Erreur lors de la création du genre"
        ]);
    }


    exit;
}
// --------------------------- //
// ---- FIN CREATE GENRE ----- //
// --------------------------- //





/* -------------------------------------- */
/* --- DELETE GENRE + VERIF UTILISATION - */
/* -------------------------------------- */
if ($method === "DELETE") {

    // Vérification du rôle : seuls admin (1) et éditeur (2) peuvent supprimer
    if (!in_array($idRole, [1, 2])) {
        echo json_encode([
            "success" => false,
            "message" => "Accès refusé : vous n'avez pas le droit de supprimer un genre"
        ]);
        exit;
    }

    parse_str($_SERVER["QUERY_STRING"], $params);
    $idGenre = $params["idGenre"] ?? null;


    if (!$idGenre) {
        echo json_encode([
            "success" => false,
            "message" => "ID du genre manquant"
        ]);
        exit;
    }




    // Vérifier si le genre est utilisé par des musiques avant de le supprimer

    // S'il est utilisé par une musique -> message d'erreur
    if ($cgenres->isGenreUsed($idGenre)) {
        echo json_encode([
            "success" => false,
            "message" => "Impossible de supprimer ce genre : utilisé par une ou plusieurs musiques"
        ]);
        exit;
    }


    // Si pas utilisé -> suppression normale
    $result = $cgenres->deleteGenre($idGenre);
    if ($result === true) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $result]);
    }

    exit;
}
// FIN DELETE GENRE//