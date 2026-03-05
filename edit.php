<?php
include "db.local.php";
function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
if ($id <= 0) { http_response_code(400); die("ID inválido"); }

$allowedStatus = ["Completado","En progreso","Probado","Abandonado","Wishlist"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $title = trim($_POST["title"] ?? "");
  $platform = trim($_POST["platform"] ?? "");
  $genre = trim($_POST["genre"] ?? "");
  $hours = (int)($_POST["hours"] ?? 0);
  $rating = (float)($_POST["rating"] ?? 0);
  $status = trim($_POST["status"] ?? "En progreso");
  $tags = trim($_POST["tags"] ?? "");
  $note = trim($_POST["note"] ?? "");

  if ($title === "") die("El título es obligatorio");
  if ($rating < 0 || $rating > 10) die("Nota inválida");
  if ($hours < 0) die("Horas inválidas");
  if (!in_array($status, $allowedStatus, true)) die("Estado inválido");

  $stmt = $conexion->prepare(
    "UPDATE games
     SET title=?, platform=?, genre=?, hours=?, rating=?, status=?, tags=?, note=?
     WHERE id=?"
  );
  $stmt->bind_param("sssiddssi", $title, $platform, $genre, $hours, $rating, $status, $tags, $note, $id);
  $stmt->execute();

  header("Location: index.php");
  exit;
}

$stmt = $conexion->prepare("SELECT * FROM games WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$g = $res->fetch_assoc();
if (!$g) { http_response_code(404); die("Juego no encontrado"); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Editar juego</title>
  <style>
    body{margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;background:#0b0f16;color:#e9edf3}
    .wrap{max-width:900px;margin:0 auto;padding:26px 18px}
    .card{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);border-radius:16px;padding:16px}
    .muted{color:rgba(233,237,243,.7)}
    label{display:block;margin-top:12px;font-size:13px;color:rgba(233,237,243,.8)}
    input,select,textarea{width:100%;padding:11px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);color:#e9edf3;outline:none}
    textarea{min-height:110px;resize:vertical}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
    @media(max-width:720px){.row{grid-template-columns:1fr}}
    .btn{margin-top:14px;display:inline-block;padding:11px 13px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:#7c4dff;color:#0b0f16;font-weight:900;cursor:pointer}
    .btn2{margin-top:14px;display:inline-block;padding:11px 13px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);text-decoration:none;color:#e9edf3}
    .btns{display:flex;gap:10px;flex-wrap:wrap}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Editar juego</h1>
    <p class="muted">Actualiza y guarda.</p>

    <div class="card">
      <form method="POST">
        <label>Título *</label>
        <input name="title" required value="<?php echo h($g["title"]); ?>">

        <div class="row">
          <div>
            <label>Plataforma</label>
            <input name="platform" value="<?php echo h($g["platform"]); ?>">
          </div>
          <div>
            <label>Género</label>
            <input name="genre" value="<?php echo h($g["genre"]); ?>">
          </div>
        </div>

        <div class="row">
          <div>
            <label>Horas</label>
            <input type="number" min="0" name="hours" value="<?php echo (int)$g["hours"]; ?>">
          </div>
          <div>
            <label>Nota (0–10)</label>
            <input type="number" step="0.1" min="0" max="10" name="rating" value="<?php echo h($g["rating"]); ?>">
          </div>
        </div>

        <label>Estado</label>
        <select name="status">
          <?php foreach($allowedStatus as $st): ?>
            <option <?php echo ($g["status"]===$st)?"selected":""; ?>><?php echo h($st); ?></option>
          <?php endforeach; ?>
        </select>

        <label>Tags (separadas por comas)</label>
        <input name="tags" value="<?php echo h($g["tags"] ?? ""); ?>" placeholder="Historia, Cozy, Moral">

        <label>Opinión</label>
        <textarea name="note"><?php echo h($g["note"]); ?></textarea>

        <div class="btns">
          <button class="btn" type="submit">Guardar cambios</button>
          <a class="btn2" href="index.php">Cancelar</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>