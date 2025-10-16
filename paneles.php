<?php
// --- Configuración de conexión ---
$host = "10.10.10.109";
$username = "icons_user";
$password = "Icons2025!";
$database = "icons2025";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
  die("❌ Error de conexión: " . $conn->connect_error);
}

// --- Consulta SQL: Paneles y ponencias ---
$sql_paneles = "
SELECT 
  t.id AS id_panel,
  t.no AS numero,
  t.fecha,
  t.salon,
  t.horario,
  t.moderador,
  p.id AS id_ponencia,
  p.nombre_apellido,
  p.afiliacion_institucional,
  p.ocupacion,
  p.titulo_ponencia,
  p.descripcion_ponencia,
  p.autoria,
  p.coautoria,
  a.nombre AS autor_extra
FROM panel AS t
LEFT JOIN ponencias AS p ON t.id = p.id_panel
LEFT JOIN coautores AS a ON p.id = a.id_ponencia
ORDER BY STR_TO_DATE(t.fecha, '%d de %M de %Y'), t.id, p.id;
";

$result_paneles = $conn->query($sql_paneles);
if (!$result_paneles) {
  die("Error en la consulta SQL (paneles): " . $conn->error);
}

// --- Agrupar paneles por fecha ---
$paneles_por_fecha = [];
while ($row = $result_paneles->fetch_assoc()) {
  $fecha = trim($row['fecha']);
  $id_panel = $row['id_panel'];
  $id_ponencia = $row['id_ponencia'];

  if (!isset($paneles_por_fecha[$fecha][$id_panel])) {
    $paneles_por_fecha[$fecha][$id_panel] = [
      'numero' => $row['numero'],
      'fecha' => $row['fecha'],
      'salon' => $row['salon'],
      'horario' => $row['horario'],
      'moderador' => $row['moderador'],
      'ponencias' => []
    ];
  }

  if (!isset($paneles_por_fecha[$fecha][$id_panel]['ponencias'][$id_ponencia])) {
    $paneles_por_fecha[$fecha][$id_panel]['ponencias'][$id_ponencia] = [
      'nombre_apellido' => $row['nombre_apellido'],
      'afiliacion_institucional' => $row['afiliacion_institucional'],
      'ocupacion' => $row['ocupacion'],
      'titulo_ponencia' => $row['titulo_ponencia'],
      'descripcion_ponencia' => $row['descripcion_ponencia'],
      'autoria' => $row['autoria'],
      'coautores' => []
    ];
  }

  if (!empty($row['autor_extra'])) {
    $paneles_por_fecha[$fecha][$id_panel]['ponencias'][$id_ponencia]['coautores'][] = $row['autor_extra'];
  }
}

// --- Consulta SQL: Lanzamientos de libros ---
$sql_libros = "
SELECT 
  id, salon, fecha, horario, titulo_libro, moderador,
  correo_moderador, afiliacion_moderador, comentaristas, afiliacion_comentaristas
FROM lanzamientos_libros
ORDER BY STR_TO_DATE(fecha, '%d de %M de %Y'), id;
";

$result_libros = $conn->query($sql_libros);
if (!$result_libros) {
  die("Error en la consulta SQL (libros): " . $conn->error);
}

// --- Agrupar libros por fecha ---
$libros_por_fecha = [];
while ($row = $result_libros->fetch_assoc()) {
  $fecha = trim($row['fecha']);
  $libros_por_fecha[$fecha][] = $row;
}

$conn->close();

// --- Fechas únicas (de paneles o libros) ---
$fechas = array_unique(array_merge(array_keys($paneles_por_fecha), array_keys($libros_por_fecha)));
sort($fechas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Paneles y Lanzamientos de Libros - ICONS 2025</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="section">

  <div class="container">
    <h1 class="title has-text-centered">📅 Paneles y Lanzamientos de Libros</h1>

    <?php if (empty($fechas)): ?>
      <p class="has-text-centered">No hay registros disponibles.</p>
    <?php else: ?>

      <!-- 🔹 Tabs de fechas -->
      <div class="tabs is-centered is-boxed">
        <ul>
          <?php foreach ($fechas as $i => $fecha): ?>
            <li class="<?= $i === 0 ? 'is-active' : '' ?>">
              <a onclick="abrirTab('tab<?= $i ?>', this)">
                <span class="icon is-small"><i class="fas fa-calendar-day"></i></span>
                <span><?= htmlspecialchars($fecha) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- 🔹 Contenido de cada fecha -->
      <?php foreach ($fechas as $i => $fecha): ?>
        <div id="tab<?= $i ?>" class="tab-content" style="<?= $i === 0 ? '' : 'display:none;' ?>">

          <!-- Paneles -->
          <?php if (!empty($paneles_por_fecha[$fecha])): ?>
            <?php foreach ($paneles_por_fecha[$fecha] as $panel): ?>
              <div class="box panel-box">
                <h2 class="title is-5">🧩 Panel <?= htmlspecialchars($panel['numero']) ?></h2>
                <p><strong>📍 Salón:</strong> <?= htmlspecialchars($panel['salon']) ?> | 
                   <strong>⏰ Horario:</strong> <?= htmlspecialchars($panel['horario']) ?></p>
                <p><strong>🎤 Moderador:</strong> <?= htmlspecialchars($panel['moderador']) ?></p>

                <table class="table is-striped is-fullwidth is-hoverable mt-3">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Ponente</th>
                      <th>Afiliación</th>
                      <th>Ocupación</th>
                      <th>Título</th>
                      <th>Autoría</th>
                      <th>Coautores</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($panel['ponencias'] as $id_ponencia => $ponencia): ?>
                      <tr>
                        <td><?= $id_ponencia ?></td>
                        <td><?= htmlspecialchars($ponencia['nombre_apellido']) ?></td>
                        <td><?= htmlspecialchars($ponencia['afiliacion_institucional']) ?></td>
                        <td><?= htmlspecialchars($ponencia['ocupacion']) ?></td>
                        <td>
                          <a href="javascript:void(0)" onclick="mostrarDescripcion(<?= $id_ponencia ?>)">
                            <?= htmlspecialchars($ponencia['titulo_ponencia']) ?>
                          </a>
                        </td>
                        <td><?= htmlspecialchars($ponencia['autoria']) ?></td>
                        <td><?= implode('<br>', array_map('htmlspecialchars', $ponencia['coautores'])) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <!-- Lanzamientos de Libros -->
          <?php if (!empty($libros_por_fecha[$fecha])): ?>
            <hr>
            <h2 class="title is-4 has-text-centered mt-5">📚 Lanzamientos de Libros</h2>
            <?php foreach ($libros_por_fecha[$fecha] as $libro): ?>
              <div class="box book-box">
                <h3 class="title is-5">📖 <?= htmlspecialchars($libro['titulo_libro']) ?></h3>
                <p><strong>📍 Salón:</strong> <?= htmlspecialchars($libro['salon']) ?> | 
                   <strong>⏰ Horario:</strong> <?= htmlspecialchars($libro['horario']) ?></p>
                <p><strong>🎤 Moderador:</strong> <?= htmlspecialchars($libro['moderador']) ?> 
                   (<?= htmlspecialchars($libro['afiliacion_moderador']) ?>)</p>
                <?php if (!empty($libro['comentaristas'])): ?>
                  <p><strong>💬 Comentaristas:</strong> <?= htmlspecialchars($libro['comentaristas']) ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

        </div>
      <?php endforeach; ?>

    <?php endif; ?>
  </div>

  <!-- 🔹 Modal Descripción -->
  <div id="modalDescripcion" class="modal">
    <div class="modal-background" onclick="cerrarModal()"></div>
    <div class="modal-card">
      <header class="modal-card-head">
        <p class="modal-card-title">📝 Descripción de la Ponencia</p>
        <button class="delete" aria-label="close" onclick="cerrarModal()"></button>
      </header>
      <section class="modal-card-body" id="contenidoDescripcion"></section>
    </div>
  </div>

  <script src="https://kit.fontawesome.com/a2e0b3f6b6.js" crossorigin="anonymous"></script>
  <script>
    // --- Descripciones ---
    const descripciones = <?= json_encode(
      array_reduce($paneles_por_fecha, function($acc, $fechaData) {
        foreach ($fechaData as $panel) {
          foreach ($panel['ponencias'] as $id => $ponencia) {
            $acc[$id] = nl2br(htmlspecialchars($ponencia['descripcion_ponencia'] ?? ''));
          }
        }
        return $acc;
      }, [])
    ); ?>;

    // --- Cambiar entre tabs ---
    function abrirTab(tabId, el) {
      document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
      document.querySelectorAll('.tabs ul li').forEach(li => li.classList.remove('is-active'));
      document.getElementById(tabId).style.display = '';
      el.parentElement.classList.add('is-active');
    }

    // --- Mostrar descripción ---
    function mostrarDescripcion(id) {
      const modal = document.getElementById('modalDescripcion');
      document.getElementById('contenidoDescripcion').innerHTML = descripciones[id] || 'Sin descripción disponible.';
      modal.classList.add('is-active');
    }

    function cerrarModal() {
      document.getElementById('modalDescripcion').classList.remove('is-active');
    }
  </script>
</body>
</html>
