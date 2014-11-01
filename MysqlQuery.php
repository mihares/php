<?php

function ejecutarSQL($sql,$conexion,&$mensaje){
	//realiza la consulta
	$datos = mysql_query($sql, _CONEXION) or die(mysql_error());
	//cuenta las filas
	$total = mysql_num_rows($datos);
	//cargar el array
	$resultado=array();
	while ($fila = mysql_fetch_assoc($datos)) {
   $resultado[]=$fila;
 }	
	//$mensaje= $total;
 return $resultado;
}


function ejecutarSQLNoValor($sql,$conexion,&$mensaje){
  //realiza la consulta
  $datos = mysql_query($sql, _CONEXION) or die(mysql_error());
  return $datos;
}



?>