<?php

// Musique -> GET des musiques

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


// Require des classes nécessaires
require_once "../Classes/ClassesControle/CMusiques.php";
require_once "../Classes/CDao.php";


$dao = new CDao(); // Crée la connexion à la BDD
$cmusiques = CMusiques::getInstance($dao);  // Récupère l'instance unique de la classe CMusiques (vide)
$cmusiques->loadMusiques(); // Remplissage de la collection CMusique (depuis la bdd) stockée dans l'instance CMusiques
$musiques = $cmusiques->getMusiques(); // Récupère le tableau d'objets CMusique et le stocke dans "musiques" 




// -- Préparer les données à renvoyer en JSON -- //

$dataMusiques = []; // Tableau vide qui va stocker les données à renvoyer en JSON

// Parcourt de chaque objet CMusique
foreach ($musiques as $m) {

    // Pour chaque objet CMusique, on le convertit en tableau associatif pour permettre la conversion en JSON
    $dataMusiques[] = [
        "idMusique" => $m->getIdMusique(), // J'assigne la valeur de l'ID récupérée par son getter, à sa clé idMusique
        "titre" => $m->getTitre(), // J'assigne la valeur du titre récupérée par son getter, à sa clé titre
        "artiste" => $m->getArtiste(), // etc...
        "album" => $m->getAlbum(),
        "duree" => $m->getDuree(),
        "pochette" => $m->getPochette(),
        "dateSortie" => $m->getDateSortie(),
        "idGenre" => $m->getIdGenre(),
        "libelleGenre" => $m->getLibelleGenre()
    ];
}


header("Content-Type: application/json; charset=utf-8"); // Je dis au front que je lui envoie du JSON

// json_encode -> transforme le tableau de tableaux associatifs en JSON    
echo json_encode($dataMusiques, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// echo -> Envoie les données au front (contexte API, afficher (echo) = envoyer (mis dans la réponse HTTP))
