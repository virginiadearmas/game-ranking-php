<?php
include "db.local.php";
function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0) { http_response_code(400); die("ID inválido"); }

// Si confirman (POST), borramos
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $stmt = $conexion->prepare("DELETE FROM games WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  header("Location: index.php");
  exit;
}

// Si no, mostramos confirmación con info del juego
$stmt = $conexion->prepare("SELECT title FROM games WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$game = $res->fetch_assoc();
if (!$game) { http_response_code(404); die("Juego no encontrado"); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Borrar juego</title>
  <style>
    body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b0f16;color:#e9edf3}
    .wrap{max-width:700px;margin:0 auto;padding:26px 18px}
    .card{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);border-radius:16px;padding:16px}
    .muted{color:rgba(233,237,243,.7)}
    .btn{display:inline-block;padding:11px 13px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);font-weight:700;text-decoration:none;color:#e9edf3}
    .danger{background:#ff4d6d;color:#0b0f16;border-color:rgba(255,255,255,.10)}
    .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Confirmar borrado</h1>
    <div class="card">
      <p>Vas a borrar:</p>
      <h2><?php echo h($game["title"]); ?></h2>
      <p class="muted">Esto no se puede deshacer.</p>

      <form method="POST" class="row">
        <button class="btn danger" type="submit">Sí, borrar</button>
        <a class="btn" href="index.php">Cancelar</a>
      </form>
    </div>
  </div>
</body>
</html>