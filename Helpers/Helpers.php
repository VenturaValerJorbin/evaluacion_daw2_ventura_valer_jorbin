<?php

function debug($param)
{
    echo "<pre>";
    var_dump($param);
    echo "</pre>";
}

// Escapa un valor para mostrarlo en HTML sin que se interprete como codigo
function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Construye una URL absoluta del sistema a partir de una ruta relativa
function url($ruta = "")
{
    return BASE_URL . ($ruta === "" ? "" : "/" . ltrim($ruta, "/"));
}

// Convierte un segmento de URL en nombre de clase: detalle_venta -> DetalleVenta
function studly($texto)
{
    $texto = ucwords(strtolower(str_replace(["-", "_"], " ", $texto)));
    return str_replace(" ", "", $texto);
}
