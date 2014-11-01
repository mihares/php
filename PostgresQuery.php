<?php
/*consulta y obtener datos generico postgres*/

function ejecutarSQL($query, $conexion, &$mensaje='') {
    usarSchema($conexion);
    if (!(@$res = pg_query($conexion, $query))) {
        $mensaje = @pg_errormessage($conexion);
        $resultado = false;
    } else {
        $resultado = (@pg_num_rows($res) == 0) ? $resultado : pg_fetch_all($res);
    }
    return $resultado;
}


?>