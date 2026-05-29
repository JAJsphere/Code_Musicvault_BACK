<?php

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

// 🔥 Gestion du preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once "../Classes/ClassesControle/CGenres.php";
require_once "../Classes/CDao.php";


$dao = new CDao();
$cgenres = CGenres::getInstance($dao);
$cgenres->loadGenres();
$genres = $cgenres->getGenres();


$dataGenres = [];
foreach ($genres as $g) {
    $dataGenres[] = [
        "idGenre" => $g->getIdGenre(),
        "libelle" => $g->getLibelle()
    ];
}

//On dit au navigateur qu'on renvoie du JSON : 
header("Content-Type: application/json; charset=utf-8");

//On transforme le tableau PHP en JSON prêt à être utilisé pour le front :
echo json_encode($dataGenres, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
