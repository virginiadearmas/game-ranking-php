<?php

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "game_rank";
$port = 3306; // cambia a 3307 si tu MySQL usa ese puerto

$conexion = new mysqli($host, $user, $password, $database, $port);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8");