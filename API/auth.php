<?php

// Ici, on vérifie : qui fait la requête, est ce qu'il est connecté, quel est son rôle 

require __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "cebaa9a72e9180eb6ae6eed7f458d79c04e27d33dcc45a0cfe37a92149d81ef4";

$headers = getallheaders(); // Récupérer les headers de la requête pour vérifier la présence du token JWT dans le header "Authorization

// Vérifier que le token est présent dans les headers de la requête
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token manquant"]);
    exit;
} /* Si le token est manquant, on renvoie une erreur 401 Unauthorized et un message d'erreur en JSON, puis on arrête l'exécution 
du script avec exit() pour éviter de faire des requêtes inutiles à la base de données ou d'exécuter du code qui ne devrait pas l'être.*/

$token = str_replace("Bearer ", "", $headers['Authorization']); // Extraire le token JWT du header "Authorization" (en enlevant le préfixe "Bearer " qui est souvent utilisé pour indiquer que le token est un JWT)


// Vérifier que le token est valide et décoder les informations qu'il contient (idUtilisateur, idRole) pour les utiliser dans les autres fichiers de l'API (ex: pour vérifier que l'utilisateur a le droit de faire une action sur une playlist qui lui appartient)
try {
    $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
    $idUtilisateur = $decoded->idUtilisateur;
    $idRole = $decoded->idRole; // 1 admin | 2 editeur | 3 user
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Token invalide"]);
    exit;
}