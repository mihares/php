<?php

//calcular paginas
function calcularPagina($cantidadPorPagina,$totalItems,&$pagina=0,&$paginasTotales=0){
  //calcular
  $totalItems=($totalItems<1)?0:$totalItems;
  $paginasTotales = ceil($totalItems / $cantidadPorPagina);
  if($paginasTotales<1)
    $paginasTotales=1;
  //el número de la página actual no puede ser menor a 0
  if($pagina < 1){
    $pagina = 1;
    // tampoco mayor la cantidad de páginas totales
  }elseif($pagina > $paginasTotales){ 
    $pagina = $paginasTotales;
  }
  $limit=$cantidadPorPagina;
  $offset=($pagina - 1)*$cantidadPorPagina;
  return (array($limit,$offset));
}

?>