<?php

// Utilisateur -> Récupérer la liste des utilisateurs (pour le front-end, partie GESTION utilisateur dans l'admin)


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
require_once "../Classes/ClassesControle/CUtilisateurs.php";
require_once "../Classes/CDao.php";
require_once "auth.php"; // Vérifications -> sécurité pour le JWT, récupère $idUtilisateur et $idRole


$dao = new CDao(); // Crée la connexion à la BDD
$cutilisateurs = CUtilisateurs::getInstance($dao); // Récupère l'instance unique de la classe CUtilisateurs (vide)
$cutilisateurs->loadUtilisateurs(); // Remplissage de la collection CUtilisateur (depuis la bdd) stockée dans l'instance CUtilisateurs



// Vérification du rôle -> On vérifie que la personne est bien admin pour GET tous les utilisateurs
if ($idRole !== 1) {
    echo json_encode(["success" => false, "message" => "Accès refusé"]);
    exit;
}

// On fait ça après avoir vérif le rôle 
$utilisateurs = $cutilisateurs->getUtilisateurs(); // Récupère le tableau d'objets CUtilisateur et le stocke dans "utilisateurs"



// -- Préparer les données à renvoyer en JSON -- //

$dataUtilisateurs = []; // Tableau vide qui va stocker les données à renvoyer en JSON

// Parcourt de chaque objet CUtilisateur
foreach ($utilisateurs as $u) {

    // Pour chaque objet CUtilisateur, on le convertit en tableau associatif pour permettre la conversion en JSON
    $dataUtilisateurs[] = [
        "idUtilisateur" => $u->getIdUtilisateur(), // On associe la clé "idUtilisateur" à sa valeur recup par son get
        "nom" => $u->getNom(),
        "prenom" => $u->getPrenom(),
        "login" => $u->getLogin(),
        "idRole" => $u->getIdRole()
    ];
}

header("Content-Type: application/json; charset=utf-8"); // Je dis au front que je lui envoie du JSON

// json_encode -> transforme le tableau de tableaux associatifs en JSON
echo json_encode($dataUtilisateurs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// echo -> Envoie les données au front (contexte API, afficher (echo) = envoyer (mis dans la réponse HTTP))