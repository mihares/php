<?php

/*constante que define si se usa o no memcache*/
define('_USAR_MEMCACHE', true);

/*datos conexion memcache*/
define('_HOST_MEMCACHE', '127.0.0.1');
define('_HOST_MEMCACHE_PUERTO', 11211);

/*declaración de la variable memcached*/
$memcache=false;

/*Preguntamos si existe para instanciar*/
if(defined('_USAR_MEMCACHE')){
    $memcache = new Memcache();    
}

/*conectarse*/
conectarMemcache($memcache);

/*funcion que cachea*/
function cachearMemcache($arrayDatos, $archivo, $segundos = 0) {
    if (!defined('_USAR_MEMCACHE')) {
        cachearArray($arrayDatos, $archivo);
        return;
    }

    $resultado = true;
    //llamar al objeto global
    global $memcache;

    //verificar memcache
    if ($memcache !== false) {
        //cachear
        $resultado = $memcache->set($archivo, $arrayDatos, false, $segundos);
        //cachear en disco cada una hora
        if (date('i') == '00' || date('i') == '30') {
            cachearArray($arrayDatos, $archivo);
        }
    } else {
        cachearArray($arrayDatos, $archivo);
        $resultado = false;
    }
    
    return $resultado;
}

/*
 * conectar a memcache
 */
function conectarMemcache(&$memcache) {
    if (!defined('_USAR_MEMCACHE'))
        return;
    //global $memcache;
    $estado = @$memcache->connect(_HOST_MEMCACHE, _HOST_MEMCACHE_PUERTO);
    if ($estado === false)
        $memcache = false;
}

/*
 * Obtener
 * 
 * el $tiempoExpirar es en caso de que no funcione el memcache y el cachee en disco 
 */
function obtenerMemcache($archivo, $tiempoExpirar = 0) {
    if (!defined('_USAR_MEMCACHE')) {
        if (expiroCache($archivo, $tiempoExpirar) === false) {
            $resultado = unserialize(@file_get_contents($archivo));
        } else {
            $resultado = false;
            //si es cero que devuelva si existe
            if ($tiempoExpirar == 0 && file_exists($archivo) === true) {
                $resultado = unserialize(@file_get_contents($archivo));
            }
        }
        return $resultado;
    }

    global $memcache;
    if ($memcache !== false) {
        //$memcache->flush();
        $resultado = $memcache->get($archivo);
        //
        if($resultado==false){
            if (expiroCache($archivo, $tiempoExpirar) === false) {
                $resultado = unserialize(@file_get_contents($archivo));
            }
        }
    } else {
        if (expiroCache($archivo, $tiempoExpirar) === false) {
            $resultado = unserialize(@file_get_contents($archivo));
        } else {
            $resultado = false;
            //si es 0 y existe el archivo
            if ($tiempoExpirar == 0 && file_exists($archivo) === true) {
                $resultado = unserialize(@file_get_contents($archivo));
            }
        }
    }
    return $resultado;
}

/*desconectar*/
function desconectarMemcached() {
    if (!defined('_USAR_MEMCACHE'))
        return;
    global $memcache;
    if ($memcache !== false) {
        $memcache->close();
    }
}

/*
 * funcion que agregar al memcache
 */
function agregarItemMemcache($arrayDatos,$archivo,$tiempo=0){
    //obtengo
    $resultado=obtenerMemcache($archivo,$tiempo);
   //agrego 
    $resultado[]=$arrayDatos;
    //cacheo
    cachearMemcache($resultado,$archivo,$tiempo);
    //borrar
    unset($resultado);
}

/*
 * borrar memcache
 */
function borrarMemcache($archivo){
    if (!defined('_USAR_MEMCACHE')){
        cachearArray(array(), $archivo);
        return;
    }
    global $memcache;
    $memcache->delete($archivo);
    return;
}

/*cache en disco*/
function cachearArray($arrayDatos, $archivo) {
    $fp = @fopen($archivo, 'w'); //w+
    @fwrite($fp, serialize($arrayDatos)); #escribimos en el archivo el array serializado
    @fclose($fp);
}


/*verifica si existe*/
function expiroCache($archivo, $tiempoSegundos) {
    global $modoDBError;

    // Si no tenemos conexcion, no expira
    if ($modoDBError == true) {
        return false;
    }

    // Si no existe el archivo, tb expiro
    if (file_exists($archivo) == false) {
        return true;
    }

    //Obtenemos la fecha de modificacion del archivo cacheado
    $fechaArchivoCache = date('YmdHis', @filemtime($archivo));
    //Obtenemos la fecha actual, formato numero como para poder restar
    $fechaActual = date('YmdHis');
    // Comparamos la fecha del archivo mas los segundos de expiracion si es superior a la fecha actual
    if ((date('YmdHis') >= $fechaArchivoCache + $tiempoSegundos)) {
        return true;
    }
    return false;
}

?>