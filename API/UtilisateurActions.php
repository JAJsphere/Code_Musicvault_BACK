<?php

// UtilisateurActions.php -> Create, Update, Delete, Patch
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


// Classes dont on a besoin
require_once "../Classes/CDao.php";
require_once "../Classes/ClassesControle/CUtilisateurs.php";
require_once "auth.php"; // Fichier pour gérer l'authentification et récupérer les infos du JWT (idUtilisateur, role)


// Initialisation des objets nécessaires pour gérer les utilisateurs
$dao = new CDao(); // Crée la connexion à la BDD
$cutilisateurs = CUtilisateurs::getInstance($dao); // Récupère l'instance unique de la classe CUtilisateurs (vide)
$cutilisateurs->loadUtilisateurs(); // On charge la collection depuis la BDD (nécessaire pour vérifier qu'un utilisateur existe avant de le modifier/supprimer)

// Récup des données envoyées par le body du front (pour UPDATE et CREATE)
// Json_decode va transformer les données JSON en tab assoc php
$data = json_decode(file_get_contents("php://input"), true);




// METHOD POST -> Créer un nouvel utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification du rôle (seul l'admin peut créer un utilisateur)
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // Hasher le mot de passe reçu en clair depuis le front avant de l'insérer en BDD 
    $mdpHash = password_hash($data['mdp'], PASSWORD_DEFAULT); // PASSWORD_DEFAULT -> L'algo de hachage, qui se met à jour automatiquement selon ce que PHP recommande

    // On passe en param les données récupérées par le front dans la méthode addUtilisateur
    // La ligne insère les données qu'on lui donne dans la BDD (rôle de la méthode)
    // On stocke tout ça dans "idUtilisateur", car la méthode addUtilisateur renvoie l'id de l'utilisateur ajouté
    $idUtilisateur = $cutilisateurs->addUtilisateur($data['nom'], $data['prenom'], $data['login'], $mdpHash, $data['idRole']);

    // Si l'insert a réussi
    if ($idUtilisateur) {

        // On renvoie un tableau associatif des données de l'utilisateur pour que React puisse l'utiliser (indispensable car quand on crée, React ne connait pas l'ID du nouvel utilisateur)
        echo json_encode([
            "success" => true, // Pour prévenir React que l'ajout en base a réussi
            "utilisateur" => [
                "idUtilisateur" => $idUtilisateur, // Renvoie aussi l'id que React ne possède pas
                "nom" => $data['nom'],
                "prenom" => $data['prenom'],
                "login" => $data['login'],
                "idRole" => $data['idRole']
            ]
        ]);

        // Erreur -> L'insertion en base a échoué (récupéré et affiché par React)
    } else {
        echo json_encode(["success" => false, "message" => $idUtilisateur]);
        exit;
    }
}





// METHOD PUT -> Modifier un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    // Vérification du rôle (seul l'admin peut modifier un utilisateur)
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // Va modifier cet utilisateur dans la base -> on donne en params de la fonction, les données envoyées par le front
    // On passe également l'id pour qu'on sache de quel utilisateur on parle (prédicat dans la méthode)
    $result = $cutilisateurs->updateUtilisateur($data['nom'], $data['prenom'], $data['login'], $data['idRole'], $data['idUtilisateur']);

    // On vérifie si la modification en base a réussi
    if ($result === true) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La modification en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $result]);

    }


}




// METHOD DELETE -> Supprimer un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // Vérification du rôle (seul l'admin peut supprimer un utilisateur)
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    parse_str($_SERVER["QUERY_STRING"], $params); // Récupère l'ID de l'utilisateur envoyé par React dans l'URL
    // QUERY_STRING -> C'est la partie après le "?" dans l'URL (donc idUtilisateur=5 par ex)
    // parse_str -> Transforme la string en tab associatif php
    // $params stocke le tab associatif (ici ne contient que l'id de l'utilisateur)

    // On passe l'id de l'utilisateur à la méthode de suppression pour le supprimer de la base
    $result = $cutilisateurs->deleteUtilisateur($params['idUtilisateur']);

    // Vérifier si la suppression a réussi
    if ($result === true) {
        echo json_encode(["success" => true]);
    } else {

        // Erreur -> La suppression en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $result]);
    }
}




// METHOD PATCH -> Modifier partiellement un utilisateur (ici uniquement le mot de passe)
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    // On passe l'id de l'utilisateur connecté (via JWT) et le nouveau mot de passe à la méthode
    $result = $cutilisateurs->changerMdp($idUtilisateur, $data['nouveauMdp']);

    // On vérifie si la modification en base a réussi
    if ($result === true) {
        echo json_encode(["success" => true, "message" => "Mot de passe changé avec succès"]);
    } else {

        // Erreur -> La modification en base a échoué (récupéré et affiché par React)
        echo json_encode(["success" => false, "message" => $result]);
    }
}