<?php
include "db.local.php";;
function h($s){ return htmlspecialchars($s ?? "", ENT_QUOTES, "UTF-8"); }

$q = isset($_GET["q"]) ? trim($_GET["q"]) : "";
$status = isset($_GET["status"]) ? trim($_GET["status"]) : "Todos";
$sort = isset($_GET["sort"]) ? trim($_GET["sort"]) : "rating_desc";

$statuses = ["Todos","Completado","En progreso","Probado","Abandonado","Wishlist"];

$where = [];
$params = [];
$types = "";

if ($q !== "") {
  $where[] = "(title LIKE ? OR genre LIKE ? OR platform LIKE ? OR status LIKE ? OR tags LIKE ? OR note LIKE ?)";
  $like = "%$q%";
  foreach([1,2,3,4,5,6] as $_){ $params[] = $like; $types .= "s"; }
}

if ($status !== "Todos") {
  $where[] = "status = ?";
  $params[] = $status; $types .= "s";
}

$orderBy = "rating DESC";
if ($sort === "rating_asc") $orderBy = "rating ASC";
if ($sort === "hours_desc") $orderBy = "hours DESC";
if ($sort === "hours_asc") $orderBy = "hours ASC";
if ($sort === "title_asc") $orderBy = "title ASC";

$sql = "SELECT id, title, platform, genre, hours, rating, status, tags, note
        FROM games";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY $orderBy, id DESC";

$stmt = $conexion->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$games = $res->fetch_all(MYSQLI_ASSOC);

// TOP 10 (siempre por nota desc)
$top = $conexion->query("SELECT id, title, rating, status FROM games ORDER BY rating DESC, hours DESC, id DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

$count = count($games);
$avg = $count ? array_sum(array_map(fn($g)=>(float)$g["rating"], $games)) / $count : 0;
$totalHours = $count ? array_sum(array_map(fn($g)=>(int)$g["hours"], $games)) : 0;

function stars($rating){
  $s = $rating / 2.0;
  $full = (int)floor($s);
  $half = ($s - $full) >= 0.5 ? 1 : 0;
  $empty = 5 - $full - $half;
  return str_repeat("★",$full) . ($half ? "⯪" : "") . str_repeat("☆",$empty);
}

function tag_list($tagsRaw){
  $tags = array_filter(array_map("trim", explode(",", (string)$tagsRaw)));
  return $tags;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ranking · Virginia</title>
  <style>
    :root{
      --bg:#0b0f16; --bg2:#0d121b; --line: rgba(255,255,255,.10);
      --text:#e9edf3; --muted: rgba(233,237,243,.70); --soft: rgba(233,237,243,.55);
      --accent:#7c4dff; --r: 16px; --shadow: 0 18px 60px rgba(0,0,0,.45);
      --danger:#ff4d6d;
    }
    *{box-sizing:border-box}
    body{
      margin:0;font-family:system-ui,Segoe UI,Roboto,Arial;color:var(--text);
      background:
        radial-gradient(900px 600px at 10% 10%, rgba(124,77,255,.14), transparent 60%),
        radial-gradient(900px 600px at 90% 20%, rgba(124,77,255,.08), transparent 60%),
        linear-gradient(180deg, var(--bg), var(--bg2));
    }
    .wrap{max-width:1100px;margin:0 auto;padding:26px 18px 64px;}
    a{color:inherit;text-decoration:none}

    .top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 0;}
    .brand{display:flex;align-items:center;gap:12px;padding:10px 12px;border-radius:999px;border:1px solid var(--line);background:rgba(0,0,0,.18);}
    .dot{width:10px;height:10px;border-radius:999px;background:var(--accent);box-shadow:0 0 0 4px rgba(124,77,255,.18)}
    .brand strong{letter-spacing:.2px}
    .brand small{display:block;color:var(--soft);margin-top:2px;font-size:12px}
    .pill{padding:10px 12px;border-radius:999px;border:1px solid var(--line);background:rgba(255,255,255,.03);font-size:13px}

    .hero{border:1px solid var(--line);background:rgba(255,255,255,.04);border-radius:var(--r);box-shadow:var(--shadow);padding:18px;}
    h1{margin:0 0 6px;font-size:clamp(24px,3.4vw,34px);letter-spacing:-.5px}
    .sub{margin:0;color:var(--muted);line-height:1.6}

    .stats{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;color:var(--soft);font-size:13px}
    .stat{border:1px solid var(--line);background:rgba(0,0,0,.18);padding:8px 10px;border-radius:999px}

    .btnline{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 13px;border-radius:12px;border:1px solid var(--line);background:rgba(255,255,255,.06);font-weight:900;font-size:13px}
    .btn.primary{background:var(--accent);color:#0b0f16;border-color:rgba(255,255,255,.10)}

    form.filters{margin-top:14px;display:grid;grid-template-columns:1.2fr .8fr .8fr .4fr;gap:10px}
    @media(max-width:860px){form.filters{grid-template-columns:1fr 1fr}}
    input,select,button{
      width:100%;padding:11px 12px;border-radius:12px;border:1px solid var(--line);
      background:rgba(255,255,255,.04);color:var(--text);font-size:14px;outline:none
    }
    button{cursor:pointer;background:var(--accent);color:#0b0f16;border-color:rgba(255,255,255,.10);font-weight:900}
    button:hover{filter:brightness(1.05)}

    /* Top 10 */
    .top10{margin-top:14px;border:1px solid var(--line);background:rgba(0,0,0,.18);border-radius:var(--r);padding:14px}
    .top10 h2{margin:0 0 10px;font-size:14px;letter-spacing:.2px;color:rgba(233,237,243,.86)}
    .top10list{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}
    @media(max-width:780px){.top10list{grid-template-columns:1fr}}
    .topitem{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:10px 12px;border-radius:14px;border:1px solid var(--line);background:rgba(255,255,255,.04)}
    .topitem b{font-size:13px}
    .mini{color:var(--soft);font-size:12px}
    .rank{font-weight:900; color:rgba(233,237,243,.85); width:22px}

    /* Cards */
    .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px}
    @media(max-width:980px){.grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:620px){.grid{grid-template-columns:1fr}}

    .card{
      border:1px solid var(--line);
      background:rgba(255,255,255,.04);
      border-radius:var(--r);
      padding:16px;
      box-shadow:0 14px 36px rgba(0,0,0,.25);
      min-height: 190px;
    }

    .title{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
    .title h3{margin:0;font-size:15px;line-height:1.25}

    .right{display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end}

    .badge{
      font-size:12px;padding:6px 10px;border-radius:999px;border:1px solid var(--line);
      background:rgba(0,0,0,.18);color:rgba(233,237,243,.86);white-space:nowrap
    }

    .action-btn{
      display:inline-flex;align-items:center;justify-content:center;
      padding:7px 10px;border-radius:12px;border:1px solid var(--line);
      background:rgba(255,255,255,.06);font-weight:900;font-size:12px;color:var(--text);
      text-decoration:none;line-height:1;
    }
    .action-btn:hover{filter:brightness(1.07)}
    .action-btn.danger{background:rgba(255,77,109,.95);color:#0b0f16;border-color:rgba(255,255,255,.10)}

    .meta{margin-top:6px;color:var(--muted);font-size:13px;line-height:1.5}
    .stars{margin-top:10px;font-size:16px;letter-spacing:1px}
    .note{margin-top:10px;color:var(--soft);font-size:12.5px;line-height:1.55}

    .tags{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap}
    .tag{
      font-size:12px;padding:7px 10px;border-radius:999px;border:1px solid var(--line);
      background:rgba(0,0,0,.18);color:rgba(233,237,243,.86);
    }

    footer{
      margin-top:30px;padding-top:16px;border-top:1px solid var(--line);
      display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;color:var(--soft);font-size:12.5px
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <div class="brand">
        <div class="dot"></div>
        <div>
          <strong>Ranking privado</strong>
          <small>Videojuegos · MariaDB</small>
        </div>
      </div>
      <a class="pill" href="http://www.miweb.com">← Web pública</a>
    </div>

    <div class="hero">
      <h1>Ranking de videojuegos</h1>
      <p class="sub">Puntuando el mejor hobby del mundo</p>

      <div class="stats">
        <span class="stat">🎮 Juegos: <b><?php echo $count; ?></b></span>
        <span class="stat">⭐ Media: <b><?php echo number_format($avg, 2); ?>/10</b></span>
        <span class="stat">⏱️ Horas: <b><?php echo $totalHours; ?></b></span>
      </div>

      <div class="btnline">
        <a class="btn primary" href="add.php">+ Añadir juego</a>
        <a class="btn" href="http://localhost/phpmyadmin">phpMyAdmin</a>
      </div>

      <form class="filters" method="GET">
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Buscar (título, tags, género…)" />
        <select name="status">
          <?php foreach($statuses as $st): ?>
            <option value="<?php echo h($st); ?>" <?php echo ($status===$st) ? "selected" : ""; ?>>
              <?php echo h($st); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <select name="sort">
          <option value="rating_desc" <?php echo ($sort==="rating_desc")?"selected":""; ?>>Nota (↓)</option>
          <option value="rating_asc"  <?php echo ($sort==="rating_asc") ?"selected":""; ?>>Nota (↑)</option>
          <option value="hours_desc"  <?php echo ($sort==="hours_desc") ?"selected":""; ?>>Horas (↓)</option>
          <option value="hours_asc"   <?php echo ($sort==="hours_asc")  ?"selected":""; ?>>Horas (↑)</option>
          <option value="title_asc"   <?php echo ($sort==="title_asc")  ?"selected":""; ?>>Título (A→Z)</option>
        </select>
        <button type="submit">Aplicar</button>
      </form>

      <div class="top10">
        <h2>🏆 Top 10 (por nota)</h2>
        <div class="top10list">
          <?php if (!$top): ?>
            <div class="mini">Aún no hay juegos para rankear.</div>
          <?php endif; ?>

          <?php foreach($top as $i => $t): ?>
            <div class="topitem">
              <div style="display:flex; gap:10px; align-items:center;">
                <div class="rank"><?php echo $i+1; ?></div>
                <div>
                  <b><?php echo h($t["title"]); ?></b><br>
                  <span class="mini"><?php echo h($t["status"]); ?></span>
                </div>
              </div>
              <div class="mini">⭐ <?php echo h($t["rating"]); ?>/10</div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <div class="grid">
      <?php if (!$games): ?>
        <div class="card" style="grid-column:1 / -1;">
          <h3>Vacío</h3>
          <p class="meta">Añade el primero con el botón.</p>
        </div>
      <?php endif; ?>

      <?php foreach($games as $g): ?>
        <div class="card">
          <div class="title">
            <h3><?php echo h($g["title"]); ?></h3>

            <div class="right">
              <span class="badge"><?php echo h($g["status"]); ?></span>
              <a class="action-btn" href="edit.php?id=<?php echo (int)$g["id"]; ?>">Editar</a>
              <a class="action-btn danger" href="delete.php?id=<?php echo (int)$g["id"]; ?>">Borrar</a>
            </div>
          </div>

          <div class="meta">
            <?php echo h($g["genre"]); ?> · <?php echo h($g["platform"]); ?><br>
            ⏱️ <?php echo (int)$g["hours"]; ?>h · ⭐ <?php echo h($g["rating"]); ?>/10
          </div>

          <div class="stars"><?php echo h(stars((float)$g["rating"])); ?></div>

          <?php $tags = tag_list($g["tags"] ?? ""); ?>
          <?php if ($tags): ?>
            <div class="tags">
              <?php foreach($tags as $t): ?>
                <span class="tag"><?php echo h($t); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($g["note"])): ?>
            <div class="note"><?php echo h($g["note"]); ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <footer>
      <div>Top 10 + Tags ✅</div>
      <div>PHP + MariaDB ✅</div>
    </footer>
  </div>
</body>
</html>