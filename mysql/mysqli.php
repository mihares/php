<?php
include 'config.class.php';
/* Clase encargada de gestionar las conexiones a la base de datos */ 
Class Db{ 
   private $servidor; 
   private $usuario; 
   private $password; 
   private $base_datos;
   private $link;
   private $matriz; 
   static $_instance;
   Private $mensajeError;
   public $conectado = false;
 
   /*La función construct es privada para evitar que el objeto pueda ser creado mediante new*/ 
   private function __construct(){ 
      $this->setConexion(); 
      $this->conectar(); 
   } 
 
   /*Método para establecer los parámetros de la conexión*/ 
   private function setConexion(){ 
      $conf = Conf::getInstance(); 
      $this->servidor=$conf->getHostDB(); 
      $this->base_datos=$conf->getDB(); 
      $this->usuario=$conf->getUserDB(); 
      $this->password=$conf->getPassDB(); 
   } 
 
   /*Evitamos el clonaje del objeto. Patrón Singleton*/ 
   private function __clone(){ } 
 
   /*Función encargada de crear, si es necesario, el objeto. 
   Esta es la función que debemos llamar desde fuera de la 
   clase para instanciar el objeto, y así, poder utilizar 
   sus métodos*/ 
   public static function getInstance(){ 
      if (!(self::$_instance instanceof self)){ 
         self::$_instance=new self(); 
      } 
         return self::$_instance; 
   } 
 
   /*Realiza la conexión a la base de datos.*/ 
   private function conectar(){ 
      $this->link=mysqli_connect($this->servidor, $this->usuario, $this->password) or die ('Error al conectarse.');
      if  ($this->link)
      {
      	if ((mysqli_select_db($this->link,$this->base_datos) or die ('Error al seleccionar Base de Datos..')) == true)
      	{
      		//mysql_set_charset("SET NAMES 'utf8'", $this->link);
      		@mysqli_query("SET NAMES 'utf8'");
      		$this->conectado = true;
      	}
      }
   } 
    /*Método para ejecutar una sentencia sql*/ 
   public function ejecutar($sql){
      //if (is_resource($this->stmt))	mysql_free_result($this->stmt);
      $stmt=mysqli_query($this->link,$sql);
      $this->setMensajeError();
      return $stmt; 
      //return $this->query($sql);
   }
  
    /*Método para ejecutar una sentencia sql*/ 
   	public function ejecutarProcedimiento($sql){
     	mysqli_multi_query($this->link,$sql);
     	$stmt = mysqli_store_result($this->link);
     	$this->reconeccion();
        $this->setMensajeError();
      	return $stmt; 
      //return $this->query($sql);
   }
   
   private function reconeccion()
   {
		mysqli_real_connect($this->link,$this->servidor,$this->usuario,$this->password,$this->base_datos);
   }
      
    /*Método para obtener una fila de resultados de la sentencia sql*/ 
   public function obtenerFila($stmt,$fila){
   	  if ($fila==0){ 
         $array=mysqli_fetch_array($stmt); 
      }else{ 
         mysqli_data_seek($stmt,$fila); 
         $array=mysqli_fetch_array($stmt); 
      } 
      return $array; 
   }
    /*Método para obtener una matriz de resultados de la sentencia sql*/ 
   public function obtenerTabla($stmt,$nombrecolumna=true){
   	//verificamos que alla algun resultado
   		$matriz='';	
   		if ($stmt != false)
		{	  
			//hallamos la cantidad de filas del objeto
			$cantidad=$this->cantidadFilas($stmt);
			if ($cantidad > 0)
			{
				//obtenemos los nombres de la columnas
				$columnas=$this->obtenerNombreColumnas($stmt,$nombrecolumna);
				if ($columnas == True);
				{
					//recorremos las filas
					for ($i=0;$i < $cantidad ;$i++) {
						$array= $this->obtenerFila($stmt,$i);
						//recorremos la fila
						for ($k=0;$k<count($columnas);$k++)
						{
							//asignamos a la matriz
							$matriz[$i][$columnas[$k]]=$array[$columnas[$k]];
						}	
					}
				}
			}
			else 
			{
				return false;
			}
		}
		else 
		{
			return false;
		}
      return $matriz; 
   }    
    //Devuelve el último id del insert introducido 
   public function lastID(){ 
      return mysqli_insert_id($this->link); 
   } 
    //Devuelve la cantidad de filas
   public function cantidadFilas($stmt){ 
      return mysqli_num_rows($stmt); 
   } 
    //Devuelve la cantidad de filas afectadas
   public function cantidadFilasAfectadas(){ 
      return mysqli_affected_rows($this->link); 
   } 

 	//obtiene nombre d elas columnas
   public function obtenerNombreColumnas($stmt,$nombrecolumna=True){ 
   		if ($stmt != false)
		{	 
			//obtengo la cantidad de filas
			$cantidad=mysqli_num_fields($stmt);
			//$cantidad=$this->cantidadFilasConsulta();  
			if ($nombrecolumna)
			{
		   		/* get column metadata */
				for ($i=0;$i < $cantidad ;$i++) 
				{
					//obtengo ingormacion del campo
				    $meta = mysqli_fetch_field($stmt);
				    $columnas[]= $meta->name;
				}
			}
			else 
			{
				//si no quiero con conmbres de columnas entonces le asigno la posicion
				for ($i=0;$i < $cantidad ;$i++) 
				{
					$columnas[$i]= $i;
				}
			}
		}
		else
		{
			return false;
		}
      return $columnas; 
   }
   /*emite siempre mensaje de error*/
	private function  setMensajeError()
	{
 		$this->mensajeError = mysqli_errno($this->link);
 		if ($this->mensajeError == 0)
 		{
 			$this->mensajeError = '';
 		}
 		else 
 		{
 			$this->mensajeError .=  ' '.mysqli_error($this->link);
 		}
 		print $this->mensajeError;
   	}
   	/*funcion desconectar*/
	private function desconectar() 
	{
		if (is_resource($this->link)) 
		{
			mysqli_close($this->link);
		}
		$this->conectado = false;	
	}
	//destructor de la clase
	function __destruct()
	{
		$this->desconectar();
	}
}
?>