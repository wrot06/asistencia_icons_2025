<?php
session_start();

function renderTemplate($title, $content) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
      <link rel="stylesheet" href="css/icons2025.css">
    </head>
    <body>
      <div class="certificado-total">
        <?php echo $content; ?>
      </div>
      <!-- FOOTER: Slider de patrocinadores y firma del desarrollador -->
      <footer>        
        <div class="developer-role">         
          <img src="img/Proyecto.png" style="width: 80px;">
        </div>
      </footer>
      <!-- Carga única del JavaScript; la ruta es relativa a /app -->
     
    </body>
    </html>
    <?php
    exit;
}




if (filter_has_var(INPUT_GET, 'identificacion') && trim($_GET['identificacion']) !== '') {
    $idInput = trim(filter_input(INPUT_GET, 'identificacion', FILTER_SANITIZE_NUMBER_INT));
    if (!ctype_digit($idInput) || (int)$idInput < 0) exit("Número de identificación inválido.");
    $document_number = $idInput;
    $_SESSION["idencargado"] = "B";
    $_SESSION['document_number'] = $document_number;

    require_once 'rene/conexion5.php';
    if (!isset($conec) || !is_object($conec) || $conec->connect_error) exit("Error al conectar con la base de datos: " . (is_object($conec) ? $conec->connect_error : "Conexión no inicializada."));

$stmt = $conec->prepare("
    SELECT 
        a.full_name,
        a.document_type,
        a.document_number
    FROM attendees a
    WHERE a.document_number = ?
    LIMIT 1
");


    if (!$stmt) exit("Error en la preparación de la consulta: " . $conec->error);
    $stmt->bind_param("s", $document_number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $datos = $result->fetch_assoc();

        // Determinar texto del documento
        switch (mb_strtoupper($datos['document_type'])) {
            case 'TEMP':
                $larguito = "número temporal ";
                break;
            case 'PASAPORTE':
                $larguito = "Pasaporte número ";
                break;
            case 'CC':
                $larguito = "Cédula de Ciudadanía número ";
                break;
            case 'TI':
                $larguito = "Tarjeta de Identidad número ";
                break;
            case 'CEDULA DE EXTRANJERIA':
            case 'CÉDULA DE EXTRANJERÍA':
                $larguito = "Cédula de Extranjería número ";
                break;
            default:
                $larguito = "documento número ";
                break;
        }

        $dia      = date("d");
        $mesLargo = date("F");
        $meses    = [
            "January"=>"enero","February"=>"febrero","March"=>"marzo","April"=>"abril",
            "May"=>"mayo","June"=>"junio","July"=>"julio","August"=>"agosto",
            "September"=>"septiembre","October"=>"octubre","November"=>"noviembre","December"=>"diciembre"
        ];
        $mesLargo = ucfirst($meses[$mesLargo] ?? $mesLargo);
        $ano      = date("Y");

        // Como ya no hay roles en esta tabla, se deja fijo
        $role = "ASISTENTE";
        $role2 = $role;

        ob_start();
        ?>
        <div class="hero">          
          <div class="certificate-container">
            <h4 class="certificate-title mb-4"><strong>CERTIFICACIÓN</strong></h4>
            <p class="certificate-text">
              <?php
              echo "El Centro de Investigaciones y Estudios Socio-Jurídicos (CIESJU) de la Universidad de Nariño hace constar que <strong>" .
                   htmlspecialchars($datos['full_name'], ENT_QUOTES, 'UTF-8') . "</strong>, quien se identifica con " . 
                   $larguito . htmlspecialchars($datos['document_number'], ENT_QUOTES, 'UTF-8') .
                   ", asistió al <em>VII Seminario Nacional IconS 'Democracia, Derechos Humanos e Inteligencia Artificial'</em> en calidad de " . $role .
                   ", realizado los días 22, 23 y 24 de octubre de 2025, con una intensidad
                   de 30 horas académicas, el cual se desarrolló en las instalaciones de la
                   Universidad de Nariño, en la ciudad de Pasto, República de Colombia.";
              ?>
            </p>
            <p class="certificate-text">
              <?php echo "En constancia de lo anterior, se firma en San Juan de Pasto, $dia de $mesLargo de $ano."; ?>
            </p>
            <p class="certificate-text">
              <strong>LEONARDO A. ENRÍQUEZ MARTÍNEZ</strong><br>
              Decano<br>
              Facultad de Derecho y Ciencias Políticas<br>
              Universidad de Nariño
            </p>
            <p class="certificate-text">
              <strong>CRISTHIAN ALEXANDER PEREIRA OTERO</strong><br>
              Director<br>
              Centro de Investigación y Estudios Socio-Jurídicos<br>
              Universidad de Nariño
            </p>

            <div class="buttons has-addons is-justify-content-flex-end">  
              <a href="icons2025.php" class="button">ATRÁS</a>
              <form method="POST" action="pdf/icons2025.php" target="_blank" class="control">
                <input type="hidden" name="identificacion" value="<?= htmlspecialchars($document_number, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="rol" value="<?= htmlspecialchars($role2, ENT_QUOTES, 'UTF-8') ?>">
                <button class="button is-info is-selected" type="submit">
                  CERTIFICADO <?= strtoupper(htmlspecialchars($role2, ENT_QUOTES, 'UTF-8')) ?>
                </button>
              </form>
            </div>
          </div>
        </div>
        <?php
        $content = ob_get_clean();
        renderTemplate("Certificado", $content);
    } else {
        ob_start();
        ?>
        <div class="hero">
          <div class="overlay"></div>
          <div class="content" id="main-content">
            <p class="info-text">Este número de identificación no aparece en nuestra base de datos.</p>
            <p class="info-text"><a href="icons2025.php">Volver</a></p>
          </div>
        </div>
        <?php
        $content = ob_get_clean();
        renderTemplate("Consulta Certificado", $content);
    }

    $stmt->close();
    $conec->close();
    exit;
}


ob_start();
?>
<div class="hero">
  <div class="overlay"></div>
  <div class="content" id="main-content">
    
    <div class="input-container">

      <div id="slider">
        <div id="mini-slider">
          <img src="img/ICONS2025/1.jpg" alt="1" class="active">
          <img src="img/ICONS2025/2.jpg" alt="2">
          <img src="img/ICONS2025/3.jpg" alt="3">
          <img src="img/ICONS2025/4.jpg" alt="4">
          <img src="img/ICONS2025/5.jpg" alt="5">
        </div>
      </div>

      <form method="GET" action="icons2025.php">
        <p class="info-text" style="font-size: 11px; margin-top: -5px; margin-bottom: 8px;">
          Para generar tu certificado digital, ingresa tu<br>número de identificación y haz clic en "Consultar".
        </p>
        <input type="text" name="identificacion" id="identificacion" 
               class="numeric-input" placeholder="" 
               style="display: block; margin: 0 auto 10px auto; text-align: center;" 
               pattern="\d+">
        <button type="submit" class="generate-btn" id="generarCertificado" 
                style="display: block; margin: 0 auto; width: 200px;">
          Consultar
        </button>
      </form>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
renderTemplate("Generar Certificado", $content);
?>
