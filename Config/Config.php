<?php

// Datos de conexion a la base de datos
const DB_HOST = "localhost";
const DB_PORT = "3320";
const DB_NAME = "evaluacion_daw2";
const DB_USER = "root";
const DB_PASS = "357951";
const DB_CHARSET = "utf8mb4";

const APP_NAME = "Sistema Web";
// DEBUG muestra el detalle de los errores; debe estar en false en un entorno de produccion
const DEBUG = true;

// La URL base se calcula con el esquema, el host y la carpeta donde esta index.php
$esquema = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") ? "https" : "http";
$host = $_SERVER["HTTP_HOST"] ?? "localhost";
$carpeta = rtrim(str_replace("\\", "/", dirname($_SERVER["SCRIPT_NAME"] ?? "")), "/.");
$carpeta = implode("/", array_map("rawurlencode", explode("/", $carpeta)));
define("BASE_URL", $esquema . "://" . $host . $carpeta);
