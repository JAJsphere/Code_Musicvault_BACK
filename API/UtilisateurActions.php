<?php

// UtilisateurActions -> CRUD Utilisateur (pour le front-end, partie GESTION utilisateur dans l'admin) 

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


// Preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}



// Classes nécessaires
require_once "../Classes/CDao.php";
require_once "../Classes/ClassesControle/CUtilisateurs.php";
require_once "auth.php"; // récupère $idUtilisateur et $idRole depuis le JWT



// Initialisation
$dao = new CDao();
$cutilisateurs = CUtilisateurs::getInstance($dao);
$cutilisateurs->loadUtilisateurs();


// Récupération JSON pour PUT
$input = json_decode(file_get_contents("php://input"), true);




// Create -> créer un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    // Vérification du rôle admin 
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    // Récupérer le JSON envoyé
    $data = json_decode(file_get_contents("php://input"), true);


    // Vérifications des champs obligatoires
    if (empty($data['nom']) || empty($data['prenom']) || empty($data['login']) || empty($data['mdp']) || empty($data['idRole'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires"]);
        exit;
    }

    // Hasher le mot de passe 
    $mdpHash = password_hash($data['mdp'], PASSWORD_DEFAULT);

    // Appel au DAO
    $idUtilisateur = $cutilisateurs->addUtilisateur($data['nom'], $data['prenom'], $data['login'], $mdpHash, $data['idRole']);


    if ($idUtilisateur) {
        echo json_encode([
            "success" => true,
            "utilisateur" => [
                "idUtilisateur" => $idUtilisateur,
                "nom" => $data['nom'],
                "prenom" => $data['prenom'],
                "login" => $data['login'],
                "idRole" => $data['idRole']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => $success]);
        exit;
    }
}






// UPDATE UTILISATEURS
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {

    // Vérification du rôle admin 
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);

    // Attribuer chaque valeur à une variable
    $idUtilisateur = $input['idUtilisateur'] ?? null;
    $nom = $input['nom'] ?? null;
    $prenom = $input['prenom'] ?? null;
    $login = $input['login'] ?? null;
    $idRoleInput = $input['idRole'] ?? null;


    // Vérifications ???
    if (!$idUtilisateur || !$nom || !$prenom || !$login || !$idRole) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Tous les champs sont obligatoires"]);
        exit;
    }

    // Vérifier que l'utilisateur existe
    $utilisateurs = $cutilisateurs->getUtilisateurs();
    $utilisateur = null;
    foreach ($utilisateurs as $u) {
        if ($u->getIdUtilisateur() == $idUtilisateur) {
            $utilisateur = $u;
            break;
        }
    }


    // Si utilisateur n'existe pas
    if (!$utilisateur) {
        http_response_code(404);
        echo json_encode(["success" => false, "message" => "Utilisateur non trouvé"]);
        exit;
    }

    // Appel direct au DAO avec les champs
    $result = $cutilisateurs->updateUtilisateur($nom, $prenom, $login, $idRoleInput, $idUtilisateur);

    if ($result === true) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => $result]);
    }

    exit;
}




// ----------------------------
// ------ METHOD DELETE ------ // A VOIR POUR RENDRE MOINS COMPLIQUEE
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // Vérification du rôle admin 
    if ($idRole !== 1) {
        echo json_encode(["success" => false, "message" => "Accès refusé"]);
        exit;
    }


    try {
        parse_str($_SERVER["QUERY_STRING"], $params);
        $idUtilisateur = $params['idUtilisateur'] ?? null;

        if (!$idUtilisateur) {
            throw new Exception("ID utilisateur manquant");
        }

        $result = $cutilisateurs->deleteUtilisateur($idUtilisateur);

        if ($result === true) {
            echo json_encode(["success" => true, "message" => "Utilisateur supprimé"]);
        } else {
            throw new Exception($result ?? "Erreur inconnue");
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Erreur serveur : " . $e->getMessage()
        ]);
    }

    exit;
}



// ----------------------------
// ------ METHOD PATCH -------- (POUR MODIFIER PARTIELLEMENT, ICI, QUE LE MDP (puis je l'ai pas utilisé encore dans le fichier, ça aurait été lourd d'avori 2 PUT))
// ----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    $data = json_decode(file_get_contents("php://input"), true);
    $nouveauMdp = $data['nouveauMdp'] ?? null;

    if (!$nouveauMdp || strlen($nouveauMdp) < 8) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Mot de passe trop court (8 caractères minimum)"]);
        exit;
    }

    $result = $cutilisateurs->changerMdp($idUtilisateur, $nouveauMdp);

    if ($result === true) {
        echo json_encode(["success" => true, "message" => "Mot de passe changé avec succès"]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => $result]);
    }

    exit;
}