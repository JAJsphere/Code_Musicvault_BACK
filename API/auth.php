<?php

/* Ce fichier sert à : 
    - Vérifier si l'utilisateur est connecté via un token JWT
    - Bloquer l'accès si le token est absent ou invalide
    - Récupèrer les informations de l'utilisateur (id, rôle) depuis le token 
*/


require __DIR__ . '/../vendor/autoload.php'; // Charge les bibliothèques externes (Composer), sans ça le Token ne fonctionne pas
// Composer -> Télécharge la librairie JWT
// autload -> Ça charge automatiquement toutes les classes de Composer (sinon si je fais JWT::encode() -> ça mettrait que la classe PHP n'existe pas)
use Firebase\JWT\JWT; // Permet d’utiliser JWT::encode() et JWT::encode() au lieu de Firebase\JWT\JWT::encode() 
use Firebase\JWT\Key; // Sert à vérifier le token avec une clé : new Key($secret_key, 'HS256')


// Fichiers pour lire le .env qui contient la clé du token
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$secret_key = $_ENV['JWT_SECRET']; // Clé secrète du token

$headers = getallheaders(); // Récupérer les headers de la requête pour vérifier la présence du token JWT  (Ex : Authorization: Bearer (le token) et Content-Type: application/json)


// Vérifier si il y a un token présent dans les headers -> sécurité minimale (empêche les users sans token de faire une requête)
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token manquant"]);
    exit;
}


$token = str_replace("Bearer ", "", $headers['Authorization']);
// Extraire le token JWT du header "Authorization" en enlevant le préfixe "Bearer" car JWT::decode() attend le token brut (sans mot devant)


// Vérifier que le token est valide et décoder les informations qu'il contient 
try {

    /* $header -> contient HEADER + PAYLOAD + SIGNATURE (suite de chiffres et lettres), comme les données sont brutes, 
         il faut les décoder avec JWT::decode() */

    // key -> recalcul de la signature du token avec la clé secrète du serv + l'algo 
    // Et on compare si la signature du $token est la même que celle recalculée
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));

    $idUtilisateur = $decoded->idUtilisateur; // On prend l'id utilisateur stocké dans le token (champ dans le token) et on le stocke
    $idRole = $decoded->idRole; // On prend le rôle de l'user stocké dans le token (champ dans le token) et on le stocke

    // Stocke -> Pour savoir qui fait la requête et ce qu'il a le droit de faire

    // Erreur -> Token faux ou invalide
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token invalide"]);
    exit;
}