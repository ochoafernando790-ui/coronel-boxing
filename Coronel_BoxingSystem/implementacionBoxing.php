<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
session_regenerate_id(true);

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


if(isset($_GET['exit'])){
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}


if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
    exit();
}


require_once("claseBoxing.php");


$tabla = $_POST['tabla'] ?? $_GET['tabla'] ?? '';

//------------------------------------------------------------------------------------------
$busqueda = $_POST['buscar'] ?? '';
$objetoConexion = new claseConexionCRUD();
$miembro = $objetoConexion->mostrarSwitch("CONCAT(nombre,' ',apellido) AS nombre", "id_miembro", "miembros")->fetch_all(MYSQLI_ASSOC);
$entrenador = $objetoConexion->mostrarSwitch("CONCAT(nombre,' ',apellido) AS nombre","id_entrenador","entrenadores")->fetch_all(MYSQLI_ASSOC);
$membresia = $objetoConexion->mostrarSwitch("tipo, precio", "id_membresia","membresia")->fetch_all(MYSQLI_ASSOC);




$tablas = [

"miembros" => [
    "tabla" => "miembros",
    "id" => "id_miembro",
    "campos" => [
        "nombre",
        "apellido",
        "telefono",
        "fecha_nacimiento",
        "puntos_box",
        "estado"
    ]
],

"pagos" => [
    "tabla" => "pagos",
    "id" => "id_pago",
    "campos" => [
        "total",
        "id_miembro",
        "descripcion",
        "id_membresia"
    ]
],

    "entrenadores" => [
        "tabla" => "entrenadores",
        "id"=> "id_entrenador",
        "campos"=>[
        "nombre",
        "apellido",
        "telefono",
        "especialidad",
        "hora_inicio",
        "hora_fin",
        "estado"
        ]
],

   "membresia" => [
        "tabla" => "membresia",
        "id"=> "id_membresia",
        "campos"=>[
        "tipo",
        "precio",
        "duracion_dias"
        ]
],

  "inscripciones" => [
        "tabla" => "inscripciones",
        "id"=> "id_inscripcion",
        "campos"=>[
        "id_miembro",
        "id_membresia"
        ]

],

 "asistencia" => [
        "tabla" => "asistencia",
        "id"=> "id_asistencia",
        "campos"=>[
        "id_miembro",
        "id_entrenador",
        "turno",
        "dia_semana"
        ]
        
],

 "users" => [
        "tabla" => "users",
        "id"=> "id_user",
        "campos"=>[
        "nombre",
        "contrasena",
        "rol"
        ]
        
],

];

if(!isset($tablas[$tabla])){
    $tabla = '';
}

if(isset($_POST["guardar"])){

    $tablaActual = $tablas[$tabla];
    $datos = [];

    foreach($tablaActual["campos"] as $campo){
        $valor = $_POST[$campo] ?? null;

        if($campo == "contrasena" && !empty($valor)){
            $valor = password_hash($valor, PASSWORD_DEFAULT);
        }

        $datos[$campo] = $valor;
    }

  
if($tabla == "pagos"){
    $tipoPago = $_POST['tipo_pago'] ?? '';

if($tipoPago == "producto"){
    $datos['id_membresia'] = null;
    $datos['descripcion'] = $_POST['descripcion'] ?? "";
} else {
    if(empty($_POST['id_membresia'])){
        die("Error: no se seleccionó una membresía válida");
    }

    $datos['id_membresia'] = (int)$_POST['id_membresia'];
    $datos['descripcion'] = null; 
}
}

  
    $objetoConexion->insertar($tablaActual["tabla"], $datos);
}

if(isset($_POST["modificar"])){

$tablaActual = $tablas[$tabla];
$datos = [];

foreach($tablaActual["campos"] as $campo){

    $valor = $_POST[$campo] ?? null;

    if($campo == "contrasena"){
        if(!empty($valor)){
            $valor = password_hash($valor, PASSWORD_DEFAULT);
        } else {
            continue; 
        }
    }

    $datos[$campo] = $valor;
}

$objetoConexion->modificar(
    $tablaActual["tabla"],
    $datos,
    $tablaActual["id"],
    $_POST["id"]
);

header("Location: ".$_SERVER["PHP_SELF"]."?tabla=$tabla");
exit();
}
if(isset($_GET["eliminar"])){

$tablaActual = $tablas[$tabla];

$objetoConexion->eliminar(
    $tablaActual["tabla"],
    $tablaActual["id"],
    $_GET["eliminar"]
);

}


$registroEditar = null;

if(isset($_GET["editar"])){

$tablaActual = $tablas[$tabla];

$resultado = $objetoConexion->seleccionarUno( $tablaActual["tabla"], $tablaActual["id"], $_GET["editar"] );

$registroEditar = $resultado->fetch_assoc();

}

//---------------------------------------------------------------------------------------
$recibeResultados = null;

if($tabla != '' && isset($tablas[$tabla])){

    $tablaActual = $tablas[$tabla];

    $recibeResultados = $objetoConexion->obtenerDatosConBusqueda(
        $tablaActual["tabla"],
        $busqueda,
        $tablaActual["campos"]
    );
}

$recibeResultadosPagos = $objetoConexion->obtenerConJoin(
    "pagos",
    [
        [
            "tabla" => "miembros",
            "condicion" => "pagos.id_miembro = miembros.id_miembro"
        ],
        [
            "tabla" => "membresia",
            "condicion" => "pagos.id_membresia = membresia.id_membresia"
        ],
    ],
    "pagos.*, 
    concat(miembros.nombre,' ', miembros.apellido) AS miembro,
    membresia.tipo AS membresia"
);

$recibeResultadosInscripciones = $objetoConexion->obtenerConJoin(
        "inscripciones",
    [
        [
            "tabla" => "miembros",
            "condicion" => "inscripciones.id_miembro = miembros.id_miembro"
        ],
        [
            "tabla" => "membresia",
            "condicion" => "inscripciones.id_membresia = membresia.id_membresia"
        ],
    ],
"inscripciones.*, 
concat(miembros.nombre,' ', miembros.apellido) AS miembro,
membresia.tipo AS membresia,
membresia.duracion_dias"

);

$recibeResultadosAsistencia = $objetoConexion->obtenerConJoin(
        "asistencia",
    [
        [
            "tabla" => "miembros",
            "condicion" => "asistencia.id_miembro = miembros.id_miembro"
        ],
        [
            "tabla" => "entrenadores",
            "condicion" => "asistencia.id_entrenador = entrenadores.id_entrenador"
        ],
    ],
    "asistencia.*, 
    concat(miembros.nombre,' ', miembros.apellido) AS miembro,
    concat(entrenadores.nombre,' ',entrenadores.apellido) AS entrenador"

);



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Conexion.css">
    <title>Coronelbox_System</title>
</head>

<body>
 <section>  
        <article>
            <form name="tabla" id="tabla" action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
               
                <label for="tabla">Consultar Tablas:</label>
                
                <div>
                    <div>
                        <input type="radio" name="tabla" value="miembros"
                        <?php if($tabla == "miembros") echo "checked"; ?>>
                        <label> Miembros </label>
                    </div>

                    <div>
                        <input type="radio" name="tabla" value="pagos"
                        <?php if($tabla == "pagos") echo "checked"; ?>>
                        <label> Pagos que se realizaron </label>
                    </div>
                    <div>
                        <input type="radio" name="tabla" value="entrenadores"
                        <?php if($tabla == "entrenadores") echo "checked"; ?>>
                        <label> Entrenadores </label>
                    </div>
                        <div>
                        <input type="radio" name="tabla" value="membresia"
                        <?php if($tabla == "membresia") echo "checked"; ?>>
                        <label> Membresias </label>
                    </div>
                    <div>
                        <input type="radio" name="tabla" value="inscripciones"
                        <?php if($tabla == "inscripciones") echo "checked"; ?>>
                        <label> Inscripciones </label>
                    </div>
            
                    <div>
                        <input type="radio" name="tabla" value="asistencia"
                        <?php if($tabla == "asistencia") echo "checked"; ?>>
                        <label> Asistencia </label>
                    </div>
                    <?php if(isset($_SESSION['rol']) && $_SESSION['rol'] == 'administrador'){ ?>
                    <div>
                        <input type="radio" name="tabla" value="users"
                        <?php if($tabla == "users") echo "checked"; ?>>
                        <label> Usuarios y administradores </label>
                    </div>
                    <?php } ?>
                </div>
                <input type="submit" name="consultar" value="Consultar datos de la tabla">
                <a href="implementacionBoxing.php?exit=1"  class="btn-logout">Cerrar sesión</a>

            </form>
        </article>
    </section>
    <?php 
  

    
    if ($tabla != '') {

        switch ($tabla) {


//Miembros case-----------------------------------------------------------------------------------------
case 'miembros':
?>



<form method="POST">
<input type="hidden" name="tabla" value="miembros">
<input type="hidden" name="id" value="<?= $registroEditar['id_miembro'] ?? '' ?>">

<label>Nombre del miembro</label>
<input type="text" name="nombre" required
value="<?= $registroEditar['nombre'] ?? '' ?>">

<label>Apellido del miembro</label>
<input type="text" name="apellido" required
value="<?= $registroEditar['apellido'] ?? '' ?>">

<label>Número telefónico en formato 0000-0000</label>
<input type="tel" pattern="\d{4}-\d{4}" name="telefono" required
value="<?= $registroEditar['telefono'] ?? '' ?>">

<label>Fecha de nacimiento</label>
<input type="date" name="fecha_nacimiento" required
value="<?= $registroEditar['fecha_nacimiento'] ?? '' ?>">

<label>Puntos box</label>
<input type="text" name="puntos_box" required
value="<?= $registroEditar['puntos_box'] ?? '' ?>">

<label>Estado</label>
<select name="estado" required>
<option disabled selected>Seleccione estado</option>

<option value="1" <?= (isset($registroEditar) && $registroEditar['estado'] == 1) ? 'selected' : '' ?>>
Activo
</option>

<option value="0" <?= (isset($registroEditar) && $registroEditar['estado'] == 0) ? 'selected' : '' ?>>
Inactivo
</option>

</select>

        <a href="?tabla=miembros">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
<form method="POST">
    <input type="hidden" name="tabla" value="miembros">
    <input type="text" name="buscar" placeholder="Buscar miembro">
    <button type="submit">Buscar</button>
</form>
  <table>
        <tr>
            <th>ID</th>
              <th>Nombre</th>
                <th>Apellido</th>
                  <th>Numero telefonico</th>
                    <th>Fecha de nacimiento</th>
                       <th>Fecha de registro</th>
                            <th>Puntos box</th>
                                <th>Estado</th>
                                     <th>Edicion</th>
        </tr>
            <?php

        
            while ($registroMiembro = $recibeResultados->fetch_assoc())
            {
                echo "<tr>";
                echo "<td>" . $registroMiembro['id_miembro'] . "</td>";
                echo "<td>" . $registroMiembro['nombre'] . "</td>";
                echo "<td>" . $registroMiembro['apellido'] . "</td>";
                echo "<td>" . $registroMiembro['telefono'] . "</td>";
                echo "<td>" . $registroMiembro['fecha_nacimiento'] . "</td>";
                echo "<td>" . $registroMiembro['fecha_registro'] . "</td>";        
                echo "<td>" . $registroMiembro['puntos_box'] . "</td>";
                echo "<td>" . ($registroMiembro['estado'] ? "Activo" : "Inactivo") . "</td>";
              
                 ?>
                 <td>
                    <a href="?tabla=miembros&editar=<?= $registroMiembro['id_miembro'] ?>">Editar</a>
                     <a href="?tabla=miembros&eliminar=<?= $registroMiembro['id_miembro'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;
        
    
    //Pagos case----------------------------------------------------------------------------------------- 
case 'pagos':
    ?>
 <form method="POST">
<input type="hidden" name="tabla" value="pagos">
<input type="hidden" name="id" value="<?= $registroEditar['id_pago'] ?? '' ?>">

<label>Tipo de pago</label>
<select id="tipo_pago" name="tipo_pago" required>
    <option value="">Seleccione</option>
<option value="membresia"
<?= (isset($registroEditar) && $registroEditar['id_membresia']) ? 'selected' : '' ?>>
Membresía
</option>

<option value="producto"
<?= (isset($registroEditar) && !$registroEditar['id_membresia']) ? 'selected' : '' ?>>
Producto / Otro
</option>
</select>

<label>Membresía</label>
<select id="id_membresia" name="id_membresia">
<option value="">Seleccione membresía</option>

<?php 

foreach ($membresia as $fila) {  ?>
<option value="<?= $fila['id_membresia'] ?>"
<?= (isset($registroEditar) && $registroEditar['id_membresia'] == $fila['id_membresia']) ? 'selected' : '' ?>>
<?= $fila['tipo'] ?>
</option>
<?php } ?>
</select>

<label>Descripción</label>
<input type="text" id="descripcion" name="descripcion"
value="<?= $registroEditar['descripcion'] ?? '' ?>">

<label>Total</label>
<input type="number" step="0.01" id="total" name="total" required
value="<?= $registroEditar['total'] ?? '' ?>">

<label>Miembro que realiza el pago</label>
<select name="id_miembro" required>
<?php 

foreach ($miembro as $fila) { ?>

<option value="<?= $fila['id_miembro'] ?>"
<?= (isset($registroEditar) && $registroEditar['id_miembro'] == $fila['id_miembro']) ? 'selected' : '' ?>>
<?= $fila['nombre'] ?>
</option>
<?php } ?>
</select>

        <a href="?tabla=pagos">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
  <table>
  <tr>
    <th>ID</th>
    <th>Total</th>
    <th>Descripción</th>
    <th>Tipo</th>
    <th>Membresía</th>
    <th>Fecha</th>
    <th>Miembro</th>
    <th>Edición</th>
</tr>
            <?php

        
while ($registroPago = $recibeResultadosPagos->fetch_assoc())
{
    $tipo = $registroPago['membresia'] ? "Membresía" : "Producto";

    echo "<tr>";
    echo "<td>" . $registroPago['id_pago'] . "</td>";
    echo "<td>" . $registroPago['total'] . "</td>";
    echo "<td>" . ($registroPago['descripcion'] ?? "-") . "</td>";
    echo "<td>" . $tipo . "</td>";
    echo "<td>" . ($registroPago['membresia'] ?? "-") . "</td>";
    echo "<td>" . $registroPago['fecha_pago'] . "</td>";
    echo "<td>" . $registroPago['miembro'] . "</td>";
              
                 ?>
                 <td>
                    <a href="?tabla=pagos&editar=<?= $registroPago['id_pago'] ?>">Editar</a>
                     <a href="?tabla=pagos&eliminar=<?= $registroPago['id_pago'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;


//Entrenadores case----------------------------------------------------------------------------------------
case 'entrenadores':
?>
<form method="POST">
<input type="hidden" name="tabla" value="entrenadores">
<input type="hidden" name="id" value="<?= $registroEditar['id_entrenador'] ?? '' ?>">

<label>Nombre del entrenador</label>
<input type="text" name="nombre" required
value="<?= $registroEditar['nombre'] ?? '' ?>">

<label>Apellido del entrenador</label>
<input type="text" name="apellido" required
value="<?= $registroEditar['apellido'] ?? '' ?>">

<label>Número telefónico</label>
<input type="text" name="telefono" required
value="<?= $registroEditar['telefono'] ?? '' ?>">

<label>Especialidad del entrenador</label>
<input type="text" name="especialidad" required
value="<?= $registroEditar['especialidad'] ?? '' ?>">

<label>Hora de inicio de turno (HH:MM)</label>
<input type="text" pattern="^([01]\d|2[0-3]):([0-5]\d)$"
name="hora_inicio" required 
value="<?= $registroEditar['hora_inicio'] ?? '' ?>">

<label>Hora de final de turno (HH:MM)</label>
<input type="text" pattern="^([01]\d|2[0-3]):([0-5]\d)$"
name="hora_fin" required
value="<?= $registroEditar['hora_fin'] ?? '' ?>">

<label>Estado</label>
<select name="estado" required>
<option disabled selected>Seleccione estado</option>

<option value="1" <?= (isset($registroEditar) && $registroEditar['estado'] == 1) ? 'selected' : '' ?>>
Activo
</option>

<option value="0" <?= (isset($registroEditar) && $registroEditar['estado'] == 0) ? 'selected' : '' ?>>
Inactivo
</option>

</select>

        <a href="?tabla=entrenadores">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
  <table>
        <tr>
            <th>ID</th>
              <th>Nombre</th>
                <th>Apellido</th>
                  <th>Numero telefonico</th>
                  <th>Especialidad</th>
                    <th>Hora de inicio de turno</th>
                       <th>Hora de fin de turno</th>
                            <th>Estado</th>
                                <th>Edicion</th>
        </tr>
            <?php

        
            while ($registroEntrenador = $recibeResultados->fetch_assoc())
            {
                echo "<tr>";
                echo "<td>" . $registroEntrenador['id_entrenador'] . "</td>";
                echo "<td>" . $registroEntrenador['nombre'] . "</td>";
                echo "<td>" . $registroEntrenador['apellido'] . "</td>";
                echo "<td>" . $registroEntrenador['telefono'] . "</td>";
                echo "<td>" . $registroEntrenador['especialidad'] . "</td>";
                echo "<td>" . $registroEntrenador['hora_inicio'] . "</td>";        
                echo "<td>" . $registroEntrenador['hora_fin'] . "</td>";
                echo "<td>" . ($registroEntrenador['estado'] ? "Activo" : "Inactivo") . "</td>";
              
                 ?>
                 <td>
                    <a href="?tabla=entrenadores&editar=<?= $registroEntrenador['id_entrenador'] ?>">Editar</a>
                     <a href="?tabla=entrenadores&eliminar=<?= $registroEntrenador['id_entrenador'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;


// Membresias case-------------------------------------------------------------------------------------------
case 'membresia':
?>
<form method="POST">
<input type="hidden" name="tabla" value="membresia">
<input type="hidden" name="id" value="<?= $registroEditar['id_membresia'] ?? '' ?>">

<label>Tipo de membresia</label>
<input type="text" name="tipo" required
value="<?= $registroEditar['tipo'] ?? '' ?>">

<label>Precio</label>
<input type="number" step="0.1" name="precio" required
value="<?= $registroEditar['precio'] ?? '' ?>">

<label>Duracion de dias</label>
<input type="number" name="duracion_dias" required
value="<?= $registroEditar['duracion_dias'] ?? '' ?>">

    <a href="?tabla=membresia">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
  <table>
        <tr>
            <th>ID</th>
              <th>Tipo de membresia</th>
                  <th>Precio</th>
                    <th>Duracion de dias</th>
                        <th>Edicion</th>
        </tr>
            <?php

        
            while ($registroMembresia = $recibeResultados->fetch_assoc())
            {
                echo "<tr>";
                echo "<td>" . $registroMembresia['id_membresia'] . "</td>";
                echo "<td>" . $registroMembresia['tipo'] . "</td>";
                echo "<td>" . $registroMembresia['precio'] . "</td>";
                echo "<td>" . $registroMembresia['duracion_dias'] . "</td>";
              
                 ?>
                 <td>
                    <a href="?tabla=membresia&editar=<?= $registroMembresia['id_membresia'] ?>">Editar</a>
                     <a href="?tabla=membresia&eliminar=<?= $registroMembresia['id_membresia'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;

//Inscripciones case---------------------------------------------------------------------------------------------
case 'inscripciones':
?>
<form method="POST">
<input type="hidden" name="tabla" value="inscripciones">
<input type="hidden" name="id" value="<?= $registroEditar['id_inscripcion'] ?? '' ?>">

    <label>Miembro</label>
    <select name="id_miembro" required>
    <option value="" disabled selected>Seleccione el miembro</option>

    <?php 
   
    foreach($miembro as $fila) { ?>
        
        <option value="<?= $fila['id_miembro'] ?>"
            <?= (isset($registroEditar) && $registroEditar['id_miembro'] == $fila['id_miembro']) ? 'selected' : '' ?>>
            <?= $fila['nombre'] ?></option>

    <?php } ?>

</select>

    <label>Membresia</label>
    <select name="id_membresia" required>
    <option value="" disabled selected>Seleccione la membresia</option>

    <?php 
 
    foreach($membresia as $fila) { ?>
        
        <option value="<?= $fila['id_membresia'] ?>" 
        <?= (isset($registroEditar) && $registroEditar['id_membresia'] == $fila['id_membresia']) ? 'selected' : '' ?>>
        <?= $fila['tipo'] ?></option>

    <?php } ?>

</select>


    <a href="?tabla=inscripciones">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
<table>
        <tr>
            <th>ID</th>
              <th>Miembro</th>
                <th>Membresia</th>
                  <th>Fecha de inicio</th>
                      <th>Dias restantes</th>
                        <th>Edicion</th>
        </tr>
            <?php

        
while ($registroInscripciones = $recibeResultadosInscripciones->fetch_assoc())
{
$fechaInicio = $registroInscripciones['fecha_inicio'];
$duracion = $registroInscripciones['duracion_dias'];

$fechaFin = date("Y-m-d", strtotime($fechaInicio . " + $duracion days"));
$hoy = date("Y-m-d");

$diasRestantes = floor((strtotime($fechaFin) - strtotime($hoy)) / 86400);

// colores
$color = "";

if($diasRestantes <= 0){
    $color = "background-color: #ff4d4d;";
}
elseif($diasRestantes <= 3){
    $color = "background-color: #ffd24d;";
}
else{
    $color = "background-color: #4dff88;";
}
                echo "<tr style='$color'>";
                echo "<td>" . $registroInscripciones['id_inscripcion'] . "</td>";
                echo "<td>" . $registroInscripciones['miembro'] . "</td>";
                echo "<td>" . $registroInscripciones['membresia'] . "</td>";
                echo "<td>" . $registroInscripciones['fecha_inicio'] . "</td>";
                echo "<td>";

if($diasRestantes <= 0){
    echo "Vencido";
}
elseif($diasRestantes <= 3){
    echo "Por vencer ($diasRestantes días)";
}
else{
    echo "Activo ($diasRestantes días)";
}

echo "</td>";
              
                 ?>
                 <td>
                    <a href="?tabla=inscripciones&editar=<?= $registroInscripciones['id_inscripcion'] ?>">Editar</a>
                     <a href="?tabla=inscripciones&eliminar=<?= $registroInscripciones['id_inscripcion'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;

//Asistencia case---------------------------------------------------------------------------------------------
case 'asistencia':
?>
<form method="POST">
<input type="hidden" name="tabla" value="asistencia">
<input type="hidden" name="id" value="<?= $registroEditar['id_asistencia'] ?? '' ?>">

    <label>Miembro</label>
    <select name="id_miembro" required>
    <option value="" selected>Seleccione el miembro</option>

    <?php 
   
    foreach($miembro as $fila) { ?>
        
        <option value="<?= $fila['id_miembro'] ?>"
            <?= (isset($registroEditar) && $registroEditar['id_miembro'] == $fila['id_miembro']) ? 'selected' : '' ?>>
            <?= $fila['nombre'] ?></option>

    <?php } ?>

</select>

    <label>Entrenador a cargo</label>
    <select name="id_entrenador" required>
    <option value="" disabled selected>Seleccione el entrenador</option>

    <?php 
   
    foreach($entrenador as $fila) { ?>
        
        <option value="<?= $fila['id_entrenador'] ?>" 
        <?= (isset($registroEditar) && $registroEditar['id_entrenador'] == $fila['id_entrenador']) ? 'selected' : '' ?>>
        <?= $fila['nombre'] ?></option>

    <?php } ?>

</select>

<label>Turno de la clase</label>
<select name="turno" required>
<option value="" disabled selected>Seleccione el turno</option>

<option value="Matutino" <?= (isset($registroEditar) && $registroEditar['turno'] == "Matutino") ? 'selected' : '' ?>>
Matutino
</option>

<option value="Vespertino" <?= (isset($registroEditar) && $registroEditar['turno'] == "Vespertino") ? 'selected' : '' ?>>
Vespertino
</option>

<option value="Nocturno" <?= (isset($registroEditar) && $registroEditar['turno'] == "Nocturno") ? 'selected' : '' ?>>
Nocturno
</option>

</select>

<label>Dia de la semana</label>
<select name="dia_semana" required>
    <option value="" disabled selected>Seleccione el dia de la semana</option>

<option value="Lunes" <?= (isset($registroEditar) && $registroEditar['dia_semana'] == "Lunes") ? 'selected' : '' ?>>
Lunes
</option>

<option value="Martes" <?= (isset($registroEditar) && $registroEditar['dia_semana'] == "Martes") ? 'selected' : '' ?>>
Martes
</option>

<option value="Miercoles" <?= (isset($registroEditar) && $registroEditar['dia_semana'] == "Miercoles") ? 'selected' : '' ?>>
Miercoles
</option>

<option value="Jueves" <?= (isset($registroEditar) && $registroEditar['dia_semana'] == "Jueves") ? 'selected' : '' ?>>
Jueves
</option>

<option value="Viernes" <?= (isset($registroEditar) && $registroEditar['dia_semana'] == "Viernes") ? 'selected' : '' ?>>
Viernes
</option>

<option value="Sabado" <?= (isset($registroEditar) && $registroEditar['dia_semana'] == "Sabado") ? 'selected' : '' ?>>
Sabado
</option>

</select>


    <a href="?tabla=asistencia">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
  <table>
        <tr>
            <th>ID</th>
              <th>Miembro</th>
                <th>Entrenador a cargo</th>
                  <th>Turno</th>
                    <th>Dia de la semana</th>
                        <th>Hora y fecha exacta de la asistencia</th>
                            <th>Edicion</th>
        </tr>
            <?php

        
            while ($registroAsistencia = $recibeResultadosAsistencia->fetch_assoc())
            {
                echo "<tr>";
                echo "<td>" . $registroAsistencia['id_asistencia'] . "</td>";
                echo "<td>" . $registroAsistencia['miembro'] . "</td>";
                echo "<td>" . $registroAsistencia['entrenador'] . "</td>";
                echo "<td>" . $registroAsistencia['turno'] . "</td>";
                echo "<td>" . $registroAsistencia['dia_semana'] . "</td>";
                echo "<td>" . $registroAsistencia['fecha'] . "</td>";
              
              
                 ?>
                 <td>
                    <a href="?tabla=asistencia&editar=<?= $registroAsistencia['id_asistencia'] ?>">Editar</a>
                     <a href="?tabla=asistencia&eliminar=<?= $registroAsistencia['id_asistencia'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;


//Users case-------------------------------------------------------------------------------------------------------
case 'users':
?>
<form method="POST">
<input type="hidden" name="tabla" value="users">
<input type="hidden" name="id" value="<?= $registroEditar['id_user'] ?? '' ?>">

    <label>Nombre</label>
    <input type="text" name="nombre" required
    value="<?= $registroEditar['nombre'] ?? '' ?>">

    <label>Contraseña</label>
    <input type="password" name="contrasena" <?= $registroEditar ? '' : 'required' ?>>

    <label>Rol</label>
    <select name="rol" required>
    <option value="" disabled selected>Seleccione el rol del usuario</option>
    <option value="empleado" <?=  (isset($registroEditar) && $registroEditar['rol'] == "empleado") ? 'selected' : '' ?>>
    Empleado
    </option>
    <option value="administrador" <?=  (isset($registroEditar) && $registroEditar['rol'] == "administrador") ? 'selected' : ''?>>
    Administrador
    </option>
</select>

    <a href="?tabla=users">Cancelar</a>

    <?php if ($registroEditar) { ?>

        <button type="submit" name="modificar">Modificar</button>

    <?php } else { ?>

        <button type="submit" name="guardar">Guardar</button>

    <?php } ?>

</form>
  <table>
        <tr>
            <th>ID</th>
              <th>Nombre</th>
                <th>Rol</th>
                    <th>Edicion</th>
        </tr>
            <?php

        
            while ($registroUsuarios = $recibeResultados->fetch_assoc())
            {
                echo "<tr>";
                echo "<td>" . $registroUsuarios['id_user'] . "</td>";
                echo "<td>" . $registroUsuarios['nombre'] . "</td>";
                echo "<td>" . $registroUsuarios['rol'] . "</td>";
              
              
                 ?>
                 <td>
                    <a href="?tabla=users&editar=<?= $registroUsuarios['id_user'] ?>">Editar</a>
                     <a href="?tabla=users&eliminar=<?= $registroUsuarios['id_user'] ?>"
                        onclick="return confirm('Desea eliminar el registro?')">Eliminar</a>
                 </td>
           
            <?php 
                echo "</tr>";
             }
            ?>
           
</table>
<?php  
                break;
        }
    }
?>  

<script>
const membresias = <?php


$datos = [];

foreach($membresia as $fila) {
    $datos[$fila['id_membresia']] = [
        "precio" => $fila['precio'],
        "nombre" => $fila['tipo']
    ];
}

echo json_encode($datos, JSON_UNESCAPED_UNICODE);
?>;


const selectMembresia = document.getElementById("id_membresia");
const descripcion = document.getElementById("descripcion");
const total = document.getElementById("total");

if(selectMembresia){
selectMembresia.addEventListener("change", function() {
    const id = this.value;

    if(membresias[id]){
        total.value = membresias[id].precio;
    }
});
}
const tipoPago = document.getElementById("tipo_pago");
if(tipoPago){
tipoPago.addEventListener("change", function() {
    const esMembresia = this.value === "membresia";

    if(selectMembresia){
        selectMembresia.disabled = !esMembresia;
        selectMembresia.required = esMembresia;
    }

    if(descripcion){
        descripcion.required = !esMembresia;
        descripcion.readOnly = esMembresia;
    }

    if(esMembresia && selectMembresia.value && membresias[selectMembresia.value]){
        descripcion.value = membresias[selectMembresia.value].nombre;
    }
else {
    if(selectMembresia) selectMembresia.value = "";
    if(total && !total.value) total.value = "";
}
});
}

window.addEventListener("load", function() {
    if(tipoPago){
        tipoPago.dispatchEvent(new Event("change"));
    }
});
</script>
</body>

</html>