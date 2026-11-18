<?php


chdir(__DIR__);

require_once "Config/Config.php";
require_once "Helpers/Helpers.php";

// Carga automatica de las clases del nucleo, los controladores y los modelos
require_once "Libraries/Core/Autoload.php";

// El .htaccess envia la ruta completa como un solo texto en el parametro "url" (por ejemplo productos/ver/5)
// Aqui se separa en controlador, metodo y parametros; sin ruta se usa Home/index
$url = trim($_GET["url"] ?? "", "/");
$arrUrl = $url === "" ? [] : explode("/", $url);

$controller = studly($arrUrl[0] ?? "Home");
$method = $arrUrl[1] ?? "index";

// Los parametros restantes llegan al metodo como un solo texto separado por comas
$params = implode(",", array_slice($arrUrl, 2));

require_once "Libraries/Core/Load.php";
