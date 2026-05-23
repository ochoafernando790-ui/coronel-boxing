<?php
class claseConexionCRUD {

    private $host = "localhost";
    private $usuario = "root";
    private $password = "chapulin53";
    private $bd = "coronelbox_db";
    private $puerto = 3306;
    private $conexion;

 
    public function __construct()
    {
        $this->conexion = new mysqli(
            $this->host,
            $this->usuario,
            $this->password,
            $this->bd,
            $this->puerto
        );
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        if ($this->conexion->connect_error) {
            die("Error de conexión: " . $this->conexion->connect_error);
        }
    }
public function login($usuario){

    $sql = "SELECT * FROM users WHERE nombre='$usuario'";
    return $this->conexion->query($sql);
}
    

public function obtenerConJoin($tablaPrincipal, $joins = [], $campos = "*")
{
    $sql = "SELECT $campos FROM $tablaPrincipal";

    foreach ($joins as $join) {
        $sql .= " LEFT JOIN {$join['tabla']} 
                  ON {$join['condicion']}";
    }

    return $this->conexion->query($sql);
}



    public function eliminar($tabla, $campoId, $id)
    {
        $sql = "DELETE FROM $tabla WHERE $campoId = $id";
        return $this->conexion->query($sql);
    }

  
    public function seleccionarUno($tabla, $campoId, $id)
    {
        $sql = "SELECT * FROM $tabla WHERE $campoId = $id";
        return $this->conexion->query($sql);
    }

    public function insertar($tabla, $datos){

    $columnas = implode(", ", array_keys($datos));
    $valores = [];

    foreach($datos as $valor){
        if(is_null($valor)){
            $valores[] = "NULL";
        } else {
            $valor = $this->conexion->real_escape_string($valor);
            $valores[] = "'$valor'";
        }
    }

    $valoresSQL = implode(", ", $valores);
    $sql = "INSERT INTO $tabla ($columnas) VALUES ($valoresSQL)";

    if(!$this->conexion->query($sql)){
        die("ERROR MYSQL: " . $this->conexion->error);
    }

    return true;
}

  public function modificar($tabla, $datos, $campoId, $id)
{
    $set = "";

    foreach ($datos as $campo => $valor) {
        if (is_null($valor)) {
            $set .= "$campo = NULL,";
        } else {
            $valor = $this->conexion->real_escape_string($valor);
            $set .= "$campo = '$valor',";
        }
    }

    $set = rtrim($set, ",");

    $id = $this->conexion->real_escape_string($id);

    $sql = "UPDATE $tabla SET $set WHERE $campoId = '$id'";

    return $this->conexion->query($sql);
}

public function mostrarSwitch($campoId, $campoMostrar, $tabla)
{
    $sql = "SELECT $campoId, $campoMostrar FROM $tabla";
    return $this->conexion->query($sql);
}
//-------------------------------------------------------------------------------------------------
public function obtenerDatosConBusqueda($tabla, $busqueda, $campos){
    
    $sql = "SELECT * FROM $tabla";

    if($busqueda != ''){
        $condiciones = [];

        foreach($campos as $campo){
            $condiciones[] = "$campo LIKE '%$busqueda%'";
        }

        $sql .= " WHERE " . implode(" OR ", $condiciones);
    }

    return $this->conexion->query($sql);
}


}
?>