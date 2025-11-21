<?php
$host = "10.10.10.109";
$username = "icons_user";
$password = "Icons2025!";
$database = "icons2025";

// =============================
// 🔌 Conexión BD
// =============================
function conectarDB() {
    global $host, $username, $password, $database;
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(["error" => "Error de conexión: " . $e->getMessage()]));
    }
}

//
// =========================================================
//  🔹 PONENCIAS
// =========================================================
//
function buscarPonencias($termino, $tipo = 'todo') {
    $pdo = conectarDB();
    $sql = "SELECT id, nombre_apellido, titulo_ponencia FROM ponencias WHERE 
           (nombre_apellido LIKE ? OR titulo_ponencia LIKE ? OR descripcion_ponencia LIKE ?)
           ORDER BY nombre_apellido ASC";
    $params = ["%$termino%", "%$termino%", "%$termino%"];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ponencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($ponencias as $p) {
        $sqlCoaut = "SELECT nombre FROM coautores WHERE id_ponencia = ?";
        $stmtC = $pdo->prepare($sqlCoaut);
        $stmtC->execute([$p['id']]);
        $coautores = $stmtC->fetchAll(PDO::FETCH_COLUMN);

        $autores = $p['nombre_apellido'];
        if (!empty($coautores)) {
            $autores .= ', ' . implode(', ', $coautores);
        }

        $data[] = [
            'id' => $p['id'],
            'autores' => $autores,
            'titulo_ponencia' => $p['titulo_ponencia']
        ];
    }

    return $data;
}

function obtenerTodasPonencias() {
    $pdo = conectarDB();
    $sql = "SELECT id, nombre_apellido, titulo_ponencia FROM ponencias ORDER BY nombre_apellido ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ponencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($ponencias as &$p) {
        $sqlCoautores = "SELECT nombre FROM coautores WHERE id_ponencia = ?";
        $stmtC = $pdo->prepare($sqlCoautores);
        $stmtC->execute([$p['id']]);
        $coautores = $stmtC->fetchAll(PDO::FETCH_COLUMN);

        $todosAutores = [$p['nombre_apellido']];
        if (!empty($coautores)) {
            $todosAutores = array_merge($todosAutores, $coautores);
        }

        $p['autores'] = implode(', ', $todosAutores);
    }

    return $ponencias;
}

function obtenerPonenciasPorPanel($id_panel) {
    $pdo = conectarDB();
    $sql = "SELECT * FROM ponencias WHERE id_panel = ? ORDER BY nombre_apellido ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_panel]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPonenciaPorId($id) {
    $pdo = conectarDB();

    $sql = "SELECT * FROM ponencias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $ponencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ponencia) return null;

    $sqlCoautores = "SELECT nombre FROM coautores WHERE id_ponencia = ?";
    $stmtCoautores = $pdo->prepare($sqlCoautores);
    $stmtCoautores->execute([$id]);
    $coautores = $stmtCoautores->fetchAll(PDO::FETCH_COLUMN);

    $ponencia['coautores'] = $coautores;

    return $ponencia;
}

//
// =========================================================
//  🔹 PANELES
// =========================================================
//
function obtenerPaneles() {
    $pdo = conectarDB();
    $sql = "SELECT id, no, moderador FROM panel ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPanelPorId($id) {
    $pdo = conectarDB();
    $sql = "SELECT id, no, moderador FROM panel WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function buscarPaneles($termino) {
    $pdo = conectarDB();
    $sql = "SELECT id, no, moderador FROM panel WHERE moderador LIKE ? OR no LIKE ? ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termino%", "%$termino%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//
// =========================================================
//  🔹 LANZAMIENTOS
// =========================================================
//
function obtenerLanzamientos() {
    $pdo = conectarDB();
    $sql = "SELECT id, moderador, titulo_libro FROM lanzamientos_libros ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarLanzamientos($termino) {
    $pdo = conectarDB();
    $sql = "SELECT id, moderador, titulo_libro 
            FROM lanzamientos_libros 
            WHERE moderador LIKE ? OR titulo_libro LIKE ?
            ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termino%", "%$termino%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerLanzamientoPorId($id) {
    $pdo = conectarDB();
    $sql = "SELECT id, moderador, titulo_libro 
            FROM lanzamientos_libros
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

//
// =========================================================
//  🔹 COMENTARISTAS (FALTABA TODO ESTO)
// =========================================================
//
function obtenerComentaristas() {
    $pdo = conectarDB();

    $sql = "SELECT id, titulo_libro, comentaristas 
            FROM lanzamientos_libros
            ORDER BY titulo_libro ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultado = [];

    foreach ($rows as $r) {

        // Separar comentaristas por coma
        $coms = array_map('trim', explode(',', $r['comentaristas']));

        foreach ($coms as $nombre) {
            if ($nombre === '') continue;

            $resultado[] = [
                'id'           => $r['id'],           // en tu orden
                'comentarista' => $nombre,
                'titulo_libro' => $r['titulo_libro']
            ];
        }
    }

    return $resultado;
}



function buscarComentaristas($termino) {
    $pdo = conectarDB();
    $sql = "SELECT id, comentarista, titulo_libro
            FROM comentaristas_libros
            WHERE comentarista LIKE ? OR titulo_libro LIKE ?
            ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termino%", "%$termino%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerComentaristaPorId($id) {
    $pdo = conectarDB();
    $sql = "SELECT id, comentarista, titulo_libro
            FROM comentaristas_libros
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function ver_detalle_comentarista($id) {
    $pdo = conectarDB();

    // 1. Buscar el lanzamiento
    $sql = "SELECT id, titulo_libro, comentaristas 
            FROM lanzamientos_libros
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return ['success' => false, 'error' => 'No encontrado'];
    }

    // 2. Separar comentaristas
    $coms = array_map('trim', explode(',', $row['comentaristas']));

    // Como no hay índice, tomamos SOLO EL PRIMERO
    // (o si quieres manejar índice, te lo hago)
    $primer_comentarista = $coms[0] ?? '';

    return [
        'success' => true,
        'data' => [
            'id' => $row['id'],
            'comentarista' => $primer_comentarista,
            'titulo_libro' => $row['titulo_libro']
        ]
    ];
}


//
// =========================================================
//  🔻 CONTROLADOR PRINCIPAL (POST)
// =========================================================
//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    switch ($action) {

        // 🔹 Ponencias
        case 'buscar':
            $termino = $_POST['termino'] ?? '';
            if ($termino === '') {
                echo json_encode(['error' => 'Debe ingresar un término de búsqueda']);
                break;
            }
            echo json_encode(['success' => true, 'data' => buscarPonencias($termino)]);
            break;

        case 'ver_todas':
            echo json_encode(['success' => true, 'data' => obtenerTodasPonencias()]);
            break;

        case 'por_panel':
            $id_panel = $_POST['id_panel'] ?? 0;
            if (!$id_panel) {
                echo json_encode(['error' => 'Debe indicar el ID del panel']);
                break;
            }
            echo json_encode(['success' => true, 'data' => obtenerPonenciasPorPanel($id_panel)]);
            break;

        case 'ver_detalle':
            $id = $_POST['id'] ?? 0;
            echo json_encode($id ? ['success' => true, 'data' => obtenerPonenciaPorId($id)] : ['error' => 'Debe indicar el ID']);
            break;

        // 🔹 Paneles
        case 'ver_panel':
            echo json_encode(['success' => true, 'data' => obtenerPaneles()]);
            break;

        case 'ver_detalle_panel':
            $id = $_POST['id'] ?? 0;
            echo json_encode($id ? ['success' => true, 'data' => obtenerPanelPorId($id)] : ['error' => 'Debe indicar el ID']);
            break;

        case 'buscar_panel':
            $termino = trim($_POST['termino'] ?? '');
            echo json_encode(['success' => true, 'data' => buscarPaneles($termino)]);
            break;

        // 🔹 Lanzamientos
        case 'ver_lanzamientos':
            echo json_encode(['success' => true, 'data' => obtenerLanzamientos()]);
            break;

        case 'buscar_lanzamientos':
            $termino = trim($_POST['termino'] ?? '');
            echo json_encode(['success' => true, 'data' => buscarLanzamientos($termino)]);
            break;

        case 'ver_detalle_lanzamiento':
            $id = $_POST['id'] ?? 0;
            echo json_encode($id ? ['success' => true, 'data' => obtenerLanzamientoPorId($id)] : ['error' => 'Debe indicar el ID']);
            break;

        // 🔹 Comentaristas (LOS QUE FALTABAN)
        case 'ver_comentaristas':
            $resultados = obtenerComentaristas();
            echo json_encode(['success' => true, 'data' => $resultados]);
            break;


        case 'buscar_comentaristas':
            $termino = trim($_POST['termino'] ?? '');
            echo json_encode(['success' => true, 'data' => buscarComentaristas($termino)]);
            break;

        case "ver_detalle_comentarista":
            echo json_encode(ver_detalle_comentarista($_POST['id']));
            break;


        // ❌ Acción desconocida
        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }

    exit;
}
?>
