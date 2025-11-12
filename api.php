<?php
$host = "10.10.10.109";
$username = "icons_user";
$password = "Icons2025!";
$database = "icons2025";

// Función para conectar a la base de datos
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

// 🔍 Buscar ponencias
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


// 📋 Obtener todas las ponencias
function obtenerTodasPonencias() {
    $pdo = conectarDB();

    // Obtener todas las ponencias
    $sql = "SELECT id, nombre_apellido, titulo_ponencia FROM ponencias ORDER BY nombre_apellido ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $ponencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recorrer cada ponencia y añadir sus coautores
    foreach ($ponencias as &$p) {
        $sqlCoautores = "SELECT nombre FROM coautores WHERE id_ponencia = ?";
        $stmtC = $pdo->prepare($sqlCoautores);
        $stmtC->execute([$p['id']]);
        $coautores = $stmtC->fetchAll(PDO::FETCH_COLUMN);

        // Combinar autor principal + coautores
        $todosAutores = [$p['nombre_apellido']];
        if (!empty($coautores)) {
            $todosAutores = array_merge($todosAutores, $coautores);
        }

        // Crear campo 'autores' unificado
        $p['autores'] = implode(', ', $todosAutores);
    }

    return $ponencias;
}


// 🧩 Obtener ponencias por panel
function obtenerPonenciasPorPanel($id_panel) {
    $pdo = conectarDB();
    $sql = "SELECT * FROM ponencias WHERE id_panel = ? ORDER BY nombre_apellido ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_panel]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 🧾 Obtener una ponencia por su ID + coautores
function obtenerPonenciaPorId($id) {
    $pdo = conectarDB();

    // Obtener la ponencia
    $sql = "SELECT * FROM ponencias WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $ponencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ponencia) return null;

    // Obtener los coautores relacionados
    $sqlCoautores = "SELECT nombre FROM coautores WHERE id_ponencia = ?";
    $stmtCoautores = $pdo->prepare($sqlCoautores);
    $stmtCoautores->execute([$id]);
    $coautores = $stmtCoautores->fetchAll(PDO::FETCH_COLUMN); // devuelve solo los nombres

    // Añadir los coautores al arreglo
    $ponencia['coautores'] = $coautores;

    return $ponencia;
}

// 📋 Obtener todos los paneles
function obtenerPaneles() {
    $pdo = conectarDB();
    $sql = "SELECT id, no, moderador FROM panel ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 🧾 Obtener un panel por su ID
function obtenerPanelPorId($id) {
    $pdo = conectarDB();
    $sql = "SELECT id, no, moderador FROM panel WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 🔍 Buscar paneles por término (moderador o título/no)
function buscarPaneles($termino) {
    $pdo = conectarDB();
    $sql = "SELECT id, no, moderador FROM panel WHERE moderador LIKE ? OR no LIKE ? ORDER BY id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termino%", "%$termino%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



// 🔄 Controlador principal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'buscar':
            $termino = $_POST['termino'] ?? '';
            if (empty($termino)) {
                echo json_encode(['error' => 'Debe ingresar un término de búsqueda']);
                exit;
            }
            $resultados = buscarPonencias($termino, 'todo');
            echo json_encode(['success' => true, 'data' => $resultados]);
            break;

        case 'ver_todas':
            $resultados = obtenerTodasPonencias();
            echo json_encode(['success' => true, 'data' => $resultados]);
            break;

        case 'por_panel':
            $id_panel = $_POST['id_panel'] ?? 0;
            if (empty($id_panel)) {
                echo json_encode(['error' => 'Debe indicar el ID del panel']);
                exit;
            }
            $resultados = obtenerPonenciasPorPanel($id_panel);
            echo json_encode(['success' => true, 'data' => $resultados]);
            break;

        case 'ver_detalle':
            $id = $_POST['id'] ?? 0;
            if (empty($id)) {
                echo json_encode(['error' => 'Debe indicar el ID de la ponencia']);
                exit;
            }
            $ponencia = obtenerPonenciaPorId($id);
            if ($ponencia) {
                echo json_encode(['success' => true, 'data' => $ponencia]);
            } else {
                echo json_encode(['error' => 'Ponencia no encontrada']);
            }
            break;

        case 'ver_panel':
            $resultados = obtenerPaneles();
            echo json_encode(['success' => true, 'data' => $resultados]);
            break;
        
        case 'ver_detalle_panel':
            $id = $_POST['id'] ?? 0;
            if (empty($id)) {
                echo json_encode(['error' => 'Debe indicar el ID del panel']);
                exit;
            }

            $panel = obtenerPanelPorId($id);
            if ($panel) {
                echo json_encode(['success' => true, 'data' => $panel]);
            } else {
                echo json_encode(['error' => 'Panel no encontrado']);
            }
            break;
        
        case 'buscar_panel':
            $termino = trim($_POST['termino'] ?? '');
            if ($termino === '') {
                echo json_encode(['success' => true, 'data' => []]); // o error si prefieres
                break;
            }
            $resultados = buscarPaneles($termino);
            echo json_encode(['success' => true, 'data' => $resultados]);
            break;

        default:
            echo json_encode(['error' => 'Acción no válida']);
            break;
    }
    exit;
}
?>
