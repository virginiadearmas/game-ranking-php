<?php
include "db.local.php";;
function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

$allowedStatus = ["Completado","En progreso","Probado","Abandonado","Wishlist"];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST["title"] ?? "");
  $platform = trim($_POST["platform"] ?? "");
  $genre = trim($_POST["genre"] ?? "");
  $hours = (int)($_POST["hours"] ?? 0);
  $rating = (float)($_POST["rating"] ?? 0);
  $status = trim($_POST["status"] ?? "En progreso");
  $tags = trim($_POST["tags"] ?? "");
  $note = trim($_POST["note"] ?? "");

  if ($title === "") $errors[] = "El título es obligatorio.";
  if ($rating < 0 || $rating > 10) $errors[] = "La nota debe estar entre 0 y 10.";
  if ($hours < 0) $errors[] = "Las horas no pueden ser negativas.";
  if (!in_array($status, $allowedStatus, true)) $errors[] = "Estado inválido.";

  if (!$errors) {
    $stmt = $conexion->prepare(
      "INSERT INTO games (title, platform, genre, hours, rating, status, tags, note)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssiddss", $title, $platform, $genre, $hours, $rating, $status, $tags, $note);
    $stmt->execute();
    header("Location: index.php");
    exit;
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Añadir juego</title>
  <style>
    body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b0f16;color:#e9edf3}
    .wrap{max-width:900px;margin:0 auto;padding:26px 18px}
    .card{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);border-radius:16px;padding:16px}
    .muted{color:rgba(233,237,243,.7)}
    label{display:block;margin-top:12px;font-size:13px;color:rgba(233,237,243,.8)}
    input,select,textarea{width:100%;padding:11px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#e9edf3;outline:none}
    textarea{min-height:90px;resize:vertical}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media(max-width:720px){.row{grid-template-columns:1fr}}
    .btn{margin-top:14px;display:inline-block;padding:11px 13px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:#7c4dff;color:#0b0f16;font-weight:900;cursor:pointer}
    .btn2{margin-top:14px;display:inline-block;padding:11px 13px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);text-decoration:none;color:#e9edf3}
    .msg{margin-top:12px;padding:10px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(0,0,0,.18)}
    ul{margin:10px 0 0;padding-left:18px}
    .btns{display:flex;gap:10px;flex-wrap:wrap}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Añadir juego</h1>
    <p class="muted">Se guarda en MariaDB y aparece en tu ranking.</p>

    <div class="card">
      <?php if ($errors): ?>
        <div class="msg">
          ❌ Revisa:
          <ul>
            <?php foreach($errors as $e): ?>
              <li><?php echo h($e); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST">
        <label>Título *</label>
        <input name="title" required>

        <div class="row">
          <div>
            <label>Plataforma</label>
            <input name="platform" placeholder="PC, PS4, Switch...">
          </div>
          <div>
            <label>Género</label>
            <input name="genre" placeholder="Narrativa, JRPG, Gestión...">
          </div>
        </div>

        <div class="row">
          <div>
            <label>Horas (aprox.)</label>
            <input name="hours" type="number" min="0" value="0">
          </div>
          <div>
            <label>Nota (0–10) *</label>
            <input name="rating" type="number" step="0.1" min="0" max="10" value="8.0" required>
          </div>
        </div>

        <label>Estado</label>
        <select name="status">
          <option>Completado</option>
          <option selected>En progreso</option>
          <option>Probado</option>
          <option>Abandonado</option>
          <option>Wishlist</option>
        </select>

        <label>Tags (separadas por comas)</label>
        <input name="tags" placeholder="Historia, Decisiones, Cozy">

        <label>Opinión</label>
        <textarea name="note" placeholder="Tu mini opinión..."></textarea>

        <div class="btns">
          <button class="btn" type="submit">Guardar</button>
          <a class="btn2" href="index.php">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>