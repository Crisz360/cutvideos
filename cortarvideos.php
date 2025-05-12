<?php
header('Content-Type: application/json');
/**
 * @author CrisZ360
 * 
 * **CORTAR VIDEO CRISZ360***
 * 
 * Esta es una herramienta para cortar videos en partes de 5, 10, 15, 20 minutos.
 * Subes el video, seleccionas los minutos y las parte consecutivas para que se cree el video en partes.
 * 
 * @version 1.0
 * 
 * @link https://github.com/Crisz360
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { //Valido que el metodo sea POST
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) { //Valido que el archivo se haya subido.
    echo json_encode([
        'success' => false, 
        'error' => 'Error al subir el archivo.',
        'error_code' => $_FILES['video']['error']
    ]);
    exit;
}

if(!isset($_POST['minutos']) || empty($_POST['minutos'])){ //Valido que se haya seleccionado los minutos
    echo json_encode([
        'success' => false, 
        'error' => 'Error al seleccionar el tiempo.',
        'error_code' => $_POST['minutos']
    ]);
    exit;
}

if(!isset($_POST['partes']) || empty($_POST['partes'])){ //Valido que se haya seleccionado las partes
    echo json_encode([
        'success' => false, 
        'error' => 'Error al seleccionar las partes.',
        'error_code' => $_POST['partes']
    ]);
    exit;
}

$videoTmp = $_FILES['video']['tmp_name'];//Ruta temporal

$nombreOriginal = $_FILES['video']['name'];
$info = pathinfo($nombreOriginal);
$nombre = $info['filename'];
$ext = $info['extension'];

$rutaDestino = __DIR__ . '/shutube/';//Creo el directorio donde se almacenarán los videos
if (!is_dir($rutaDestino)) {
    mkdir($rutaDestino, 0777, true);
}

$rutaFinal = $rutaDestino . $nombreOriginal;//Ruta donde se moverán los archivos subidos
if (!move_uploaded_file($videoTmp, $rutaFinal)) {
    echo json_encode(['success' => false, 'error' => 'No se pudo guardar el archivo subido.']);
    exit;
}

$cmdDuracion = "ffmpeg -i \"$rutaFinal\" 2>&1";//Obtengo la duración del video
$output = shell_exec($cmdDuracion);

if (!preg_match('/Duration: (\d+):(\d+):(\d+.\d+)/', $output, $matches)) { //Extraigo la duración del video
    echo json_encode(['success' => false, 'error' => 'No se pudo obtener la duración del video.']);
    exit;
}

$horas = (int)$matches[1]; //Se extraen las horas, minutos y segundos
$minutos = (int)$matches[2];
$segundos = (float)$matches[3];
$duracionTotal = $horas * 3600 + $minutos * 60 + $segundos;

$segmento = $_POST['minutos'];//Recibo los minutos a cortar
$inicio = 0;//Inicia en 0
$contador = $_POST['partes'];//Contador de las partes

while ($inicio < $duracionTotal) {//Recorro el video para cortarlo en partes de 5, 10, 15, 20 minutos
    $archivoSalida = $rutaDestino . "{$nombre}_{$contador}." . $ext;//Ruta donde se guardan los videos
    $cmdCorte = "ffmpeg -i \"$rutaFinal\" -ss $inicio -t $segmento -c copy \"$archivoSalida\"";
    shell_exec($cmdCorte);
    $inicio += $segmento;
    $contador++;
}

echo json_encode(['success' => true, 'message' => 'Video cortado con éxito.']);