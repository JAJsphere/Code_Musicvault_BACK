<?php


require __DIR__ . '/../vendor/autoload.php'; // Charge les bibliothèques externes (Composer), sans ça le Token ne fonctionne pas (Composer -> Télécharge la librairie JWT)
use \Firebase\JWT\JWT; // Permet d’utiliser JWT::encode() et JWT::encode() au lieu de Firebase\JWT\JWT::encode()



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


// Récupération des données envoyées par le front (mon login / MDP de ma page front LOGIN)
$input = json_decode(file_get_contents("php://input"), true); //  Contient les données brutes envoyées en JSON, et le true dit à json_decode de convertir le JSON en tab associatif PHP
$login = $input['login'] ?? ''; // Valeur du champ
$password = $input['password'] ?? '';



// Si le Login ou MDP pas rentré, alors erreur -> il faut entrer le couple login / MDP obligatoirement 
if (!$login || !$password) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Champs manquants"]);
    exit;
}



require_once "../Classes/CDao.php";
require_once "../Classes/ClassesControle/CUtilisateurs.php";


// Fichiers pour lire le .env qui contient la clé du token
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$dao = new CDao(); // Crée la connexion à la BDD
$pdo = $dao->getPdo(); // Récupère l'objet PDO  




$cutilisateurs = CUtilisateurs::getInstance($dao); // Récupère l'instance unique de CUtilisateurs
$user = $cutilisateurs->getUtilisateurByLogin($login); // Récupère l'utilisateur correspondant au login entré


// Vérifie que l'utilisateur existe en BDD et que le mot de passe entré correspond au hash stocké
if (!$user || !password_verify($password, $user->getMdpHash())) { // password_verify -> Compare si le hash du mdp en clair correspond à celui stocké
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Identifiants incorrects"]);
    exit;
}

// Et si le mdp est le bon -> L'utilisateur est authentifié, et on peut générer son token JWT


// Générer le JWT 
$secret_key = $_ENV['JWT_SECRET']; // Clé secrète que mon serveur utilise pour signer le token (dans le .env)
$issuedAt = time(); // Date de création
$expire = $issuedAt + 3600; // Durée de vie du token (ici 1H)

// Le contenu du token (tab associatif, clé / valeur)
$payload = [
    "iat" => $issuedAt, // Date de création
    "exp" => $expire, // Date d'expiration
    "idUtilisateur" => $user->getIdUtilisateur(),
    "login" => $user->getLogin(),
    "idRole" => $user->getIdRole(),
    "doitChangerMdp" => (bool) $user->getDoitChangerMdp() // True ou False
];

// Fabrication du token final composé du payload, de la clé secrète du serveur (pour signer le token) et de son algo de signature
$jwt = JWT::encode($payload, $secret_key, 'HS256');


// Convertit les données à envoyer au front en JSON
echo json_encode([
    "success" => true,   // Indicateur -> connexion réussie ou non
    "token" => $jwt,
    "idUtilisateur" => $user->getIdUtilisateur(),
    "login" => $user->getLogin(),
    "idRole" => $user->getIdRole(),
    "doitChangerMdp" => (bool) $user->getDoitChangerMdp()

    // On renvoie encore les données utilisateur même si elles sont déjà stockées dans le token pour simplifier l'utilisation côté frontend et éviter au client de devoir décoder le JWT

]);

