<?php

// Role -> Récupérer la liste des rôles (pour le front-end, partie GESTION utilisateur dans l'admin)


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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH,OPTIONS");
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

// Require des classes nécessaires
require_once "../Classes/ClassesControle/CRoles.php";
require_once "../Classes/CDao.php";


$dao = new CDao(); // Crée la connexion à la BDD
$croles = CRoles::getInstance($dao); // Récupère l'instance unique de la classe CRoles (vide)
$croles->loadRoles(); // Remplissage de la collection CRole (depuis la bdd) stockée dans l'instance CRoles
$roles = $croles->getRoles(); // Récupère le tableau d'objets CRole et le stocke dans "roles"



// -- Préparer les données à renvoyer en JSON -- //

$dataRoles = []; // Tableau vide qui va stocker les données à renvoyer en JSON

// Parcourt de chaque objet CRole
foreach ($roles as $r) {

    // Pour chaque objet CRole, on le convertit en tableau associatif pour permettre la conversion en JSON
    $dataRoles[] = [
        "idRole" => $r->getIdRole(),
        "libelle" => $r->getLibelle()
    ];
}

header("Content-Type: application/json; charset=utf-8"); // Je dis au front que je lui envoie du JSON

// json_encode -> transforme le tableau de tableaux associatifs en JSON
echo json_encode($dataRoles, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// echo -> Envoie les données au front (contexte API, afficher (echo) = envoyer (mis dans la réponse HTTP))