<?php
require __DIR__ . '/../vendor/autoload.php'; // Composer autoload pour JWT
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

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
    header("Access-Control-Allow-Methods: POST, PUT, DELETE, PATCH, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Credentials: true");
}



// Pour gérer le preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// Récupération des données envoyées par le front (mon login / MDP de ma page front LOGIN)
$input = json_decode(file_get_contents("php://input"), true);
$login = $input['login'] ?? '';
$password = $input['password'] ?? '';



// Si le Login ou MDP pas rentré, alors erreur -> il faut entrer le couple login / MDP obligatoirement 
if (!$login || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Champs manquants"]);
    exit;
}



// ----------------------------
// Connexion à la base de données
// ----------------------------
require_once "../Classes/CDao.php";  // Inclure la classe

// On créer notre objet pdo pour connexion base
$dao = new CDao();
$pdo = $dao->getPdo();


// ----------------------------
// Vérifier l'utilisateur (pas normal le sql ici)
// ----------------------------
$stmt = $pdo->prepare("SELECT idUtilisateur, login, mdpHash, idRole, doitChangerMdp FROM utilisateur WHERE login = :login LIMIT 1");
$stmt->execute(['login' => $login]); // Remplace login par valeur réelle 
$user = $stmt->fetch(PDO::FETCH_ASSOC);


// Si identifiants ne correspondent pas en base avec ceux entrés -> identifiants incorrects
if (!$user || !password_verify($password, $user['mdpHash'])) { //password_verify -> Vérifie avec les hashs
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Identifiants incorrects"]);
    exit;
}



// -------------------------------------------
// Générer le JWT (si utilisateur correspond)
// -------------------------------------------
$secret_key = "cebaa9a72e9180eb6ae6eed7f458d79c04e27d33dcc45a0cfe37a92149d81ef4";  // Clé secrète que mon serveur utilise pour signer le token 
$issuedAt = time();
$expire = $issuedAt + 3600; // Durée de vie du token (ici 1H)

$payload = [
    "iat" => $issuedAt,
    "exp" => $expire,
    "idUtilisateur" => $user['idUtilisateur'],
    "login" => $user['login'],
    "idRole" => $user['idRole'],
    "doitChangerMdp" => (bool) $user['doitChangerMdp'] // 👈
];

$jwt = JWT::encode($payload, $secret_key, 'HS256');

// ----------------------------
// Réponse au front JSON
// ----------------------------
echo json_encode([
    "success" => true,
    "token" => $jwt,
    "idUtilisateur" => $user['idUtilisateur'],
    "idRole" => $user['idRole'],
    "login" => $user['login'],
    "doitChangerMdp" => (bool) $user['doitChangerMdp'] // 👈
]);



/* ETAPES REALISEES DANS CE FICHIER : 
- Tu récupères les données envoyées par le front (login + password).
- Tu vérifies que ces champs ne sont pas vides.
- Tu récupères l’utilisateur en BDD avec PDO (ou ta classe CDao).
- Tu vérifies que le mot de passe correspond (password_verify).
- Tu crées le payload du JWT et tu as la clé secrète. 
- Génération du TOKEN et envoie de la réponse JSON au front */