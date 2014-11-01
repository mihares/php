<?php
include 'config.class.php';
/* Clase encargada de gestionar las conexiones a la base de datos */ 
Class Db{ 
   private $servidor; 
   private $usuario; 
   private $password; 
   private $base_datos;
   private $link; 
   static $_instance;
   Private $mensajeError;
   private $transaccion =false;
 
   /*La funci�n construct es privada para evitar que el objeto pueda ser creado mediante new*/ 
   private function __construct(){ 
      $this->setConexion(); 
      $this->conectar(); 
   } 
 
   /*M�todo para establecer los par�metros de la conexi�n*/ 
   private function setConexion(){ 
      $conf = Conf::getInstance(); 
      $this->servidor=$conf->getHostDB(); 
      $this->base_datos=$conf->getDB(); 
      $this->usuario=$conf->getUserDB(); 
      $this->password=$conf->getPassDB(); 
   } 
 
   /*Evitamos el clonaje del objeto. Patr�n Singleton*/ 
   private function __clone(){ } 
 
   /*Funci�n encargada de crear, si es necesario, el objeto. 
   Esta es la funci�n que debemos llamar desde fuera de la 
   clase para instanciar el objeto, y as�, poder utilizar 
   sus m�todos*/ 
   public static function getInstance(){ 
      if (!(self::$_instance instanceof self)){ 
         self::$_instance=new self(); 
      } 
         return self::$_instance; 
   } 
 
   /*Realiza la conexi�n a la base de datos.*/ 
   private function conectar(){ 
      $this->link=mysql_connect($this->servidor, $this->usuario, $this->password) or die ('Error al conectarse.');
      if  ($this->link)
      {
      	if ((mysql_select_db($this->base_datos,$this->link) or die ('Error al seleccionar Base de Datos..')) == true)
      	{
      		//mysql_set_charset("SET NAMES 'utf8'", $this->link);
      		@mysql_query("SET NAMES 'utf8_bin'");
      	}
      }
   } 
    /*M�todo para ejecutar una sentencia sql*/ 
   public function ejecutar($sql){
      //if (is_resource($this->stmt))	mysql_free_result($this->stmt);
      $stmt=mysql_query($sql,$this->link);
      $this->setMensajeError();
      return $stmt; 
      //return $this->query($sql);
   }
       /*M�todo para ejecutar un procedimiento*/ 
   	public function ejecutarProcedimiento($sql)
   	{
     	$stmt= mysql_query($sql,$this->link);
     	$this->setMensajeError();
     	$this->desconectar();
     	$this->conectar();
      	return $stmt; 
   }
     
    /*M�todo para obtener una fila de resultados de la sentencia sql*/ 
   public function obtenerFila($stmt,$fila){
   	  if ($fila==0){ 
         $array=mysql_fetch_array($stmt); 
      }else{ 
         mysql_data_seek($stmt,$fila); 
         $array=mysql_fetch_array($stmt); 
      } 
      return $array; 
   }
    /*M�todo para obtener una matriz de resultados de la sentencia sql*/ 
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
    //Devuelve el �ltimo id del insert introducido 
   public function lastID(){ 
      return mysql_insert_id($this->link); 
   } 
    //Devuelve la cantidad de filas
   public function cantidadFilas($stmt){ 
      return mysql_num_rows(($stmt)); 
   } 
    //Devuelve la cantidad de filas afectadas
   public function cantidadFilasAfectadas(){ 
      return mysql_affected_rows($this->link); 
   }
 	//obtiene nombre d elas columnas
   public function obtenerNombreColumnas($stmt,$nombrecolumna=True){ 
   		if ($stmt != false)
		{	 
			//obtengo la cantidad de filas
			$cantidad=mysql_num_fields($stmt);
			//$cantidad=$this->cantidadFilasConsulta();  
			if ($nombrecolumna)
			{
		   		/* get column metadata */
				for ($i=0;$i < $cantidad ;$i++) 
				{
					//obtengo ingormacion del campo
				    $meta = mysql_fetch_field($stmt, $i);
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
 		$this->mensajeError = mysql_errno($this->link);
 		if ($this->mensajeError == 0)
 		{
 			$this->mensajeError = '';
 		}
 		else 
 		{
 			$this->mensajeError .=  ' '.mysql_error($this->link);
 		}
 		print $this->mensajeError;
   	}
   	/*funcion desconectar*/
	private function desconectar() 
	{
		if (is_resource($this->link)) 
		{
			mysql_close($this->link);
		}	
	}

	
	//destructor de la clase
	function __destruct()
	{
		$this->desconectar();
	}
	
	/*transacciones*/
	public function startTransaction()
	{
		if (!$this->transaccion)
		{
			$this->ejecutar('start Transaction;');
                        $this->transaccion = true;
		}
	}
	
	/*transacciones*/
	public function commitTransaction()
	{
		if ($this->transaccion)
		{
			$this->ejecutar('COMMIT;');
		}
	}
	
	/*transacciones*/
	public function rollbackTransaction()
	{
		if ($this->transaccion)
		{
			$this->ejecutar('ROLLBACK;');
                        $this->transaccion = false;
		}
	}
}
?>