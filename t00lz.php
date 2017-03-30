<?php

//namespace t00lz; namespaces a partir de la versión 5.3 (intenta no llorar).

//php.ini customize.
//ini_set('date.timezone', 'Europe/Madrid');
//ini_set('display_errors','on');
//error_reporting(E_ALL);


/**
 * Clase contenedora no instanciable de herramientas y utilidades 
 * genéricas. AKA t00lz.
 * 
 * @waring: dependiendo de si estás en un server o en local tendrás que hacer 
 * el include de esta librería de un path u otro. Tenlo en cuenta. 
 * 
 * if (strstr( php_uname() , 'Windows')) //o cual quier otra condicion .
 * 		//_includeReal
 * 		include realpath($_SERVER["DOCUMENT_ROOT"]) . "/cantina/t00lz.php";
 * else
 * 		//includeReal
 * 	    include realpath($_SERVER["DOCUMENT_ROOT"]) . "/desarrollo/cantina/t00lz.php";
 * 
 * @version "0.1"
 * @author Pascual Muñoz <pascual.munoz@pccomponentes.com>
 * 
 */
abstract class t00lz
{

	const getDebugMode = "";

	public static $DEBUG_MODE = true;

	/**
	 * dumpea con var_export. 
	 * http://stackoverflow.com/questions/19816438/make-var-dump-look-pretty
	 * @param mixed $var
	 */
	static function dump($var)
	{
		if (self::getDebugMode())
			highlight_string("<?php\n\n" . var_export($var, true) . ";\n?>");
		self::console_log($var);
	}

	static function console_log($data)
	{

		if (self::getDebugMode())
		{

			echo '<script>';
			echo 'console.log(' . json_encode($data) . ')';
			echo '</script>';
		}
	}

	/**
	 * @return Resource (conn , stream, socket) from a single URI . 
	 *  
	 * 
	 * @param type $uri {ENCAPSULAMIENTO}://{USER}:{PWD}@{HOST}{PATH}
	 * ENCAPSULAMIENTO ::=> [ftp:// | sftp://]
	 * Por el poder de preg_match()!!!
	 * @see https://www.youtube.com/watch?v=ftYt2jT_Gw0
	 * @see http://php.net/manual/en/language.types.resource.php
	 * @see https://regex101.com/
	 */
	static function getResource($uri)
	{//TODO: IMPLEMENTAR getResource($uri)
		//paramcheck {ENCAPSULAMIENTO} 
		if (!preg_match("/(.*:\/\/)/i", $uri, $match))
		{
			t00lz::dump("URI INCORRECTA en t00lz::getResource(): " . "\n" .
					"{ENCAPSULAMIENTO}://{USER}:{PWD}@{HOST}{PATH}" . "\n" .
					"soportados: " . "\n" .
					"mysql://" . "\n" .
					"ftp://" . "\n" .
					"ssh_ftp://" . "\n"
			);
			die();
		}
		$encapsulacion = $match[1];

		$resource2Return = null;
		switch ($encapsulacion)
		{
			case "mysqli://":
			case "mysql://":
				$mysqlRegexp = t00lz::dump("VAMOS A POR UNA BASE DE DATOS");
				$resource2Return = self::getResourceMysql($uri);
				break;

			case "ftp://":
				t00lz::dump("SELECCIONANDO PROTOCOLO FTP");
				self::getResourceFtp($uri);
				break;

			case "ssh.ftp://":
			case "ssh2.ftp://":
				t00lz::dump("VAS POR SSH MAS SEGURO EFECTIVAMENTE");
				self::getResourceSFtp($uri);
				break;
			case "connecthub://":
				t00lz::dump("Y UN EXTRA");
				$resource2Return = self::getResourceConnectHub($uri);
				break;
			default :
				$resource2Return = false;
		}
		return $resource2Return;
	}

	static private function &getResourceMysql($uri)
	{
		preg_match("/(.*:\/\/)(.*):(.*)@(.*)\/(.*)/i", $uri, $param);

		/* un dump($param) del ejemplo usado para testear la clase:  
		  array(
		  0 => 'mysql://becario:XXXXXXXXXXXX@192.168.50.30/helpdesk',
		  1 => 'mysql://',
		  2 => 'becario',
		  3 => 'XXXXXXXXXX',
		  4 => '192.168.50.30',
		  5 => 'helpdesk',
		  );
		 */
		//$encapsulamiento = $param[1];
		$user = $param[2];
		$pass = $param[3];
		$host = $param[4];
		$ddbb = $param[5];

		t00lz::dump("mysql_connect($host, $user, $pass, $ddbb);");

		$conn = mysqli_connect($host, $user, $pass, $ddbb, '3306');
		if (!$conn)
		{
			t00lz::dump('getResourceMysql: ' . mysqli_connect_error());
			return false;
		} else
			return $conn;
	}

	//todo: IMPLEMENTAR getResourceFtp($uri)
	private static function &getResourceFtp($uri)
	{
		self::dump("stub:getResourceFtp\n");
		preg_match("/(ftp:\/\/)(.*?):(.*?)@(.*?)(\/.*)/i", $uri, $match);

		$encapsulacion = $match[1];
		$username = $match[2];
		$password = $match[3];
		$host = $match[4];
		$path = $match[5];

		if (!isset($path))
			$path = "/";

		t00lz::dump("conectando a $encapsulacion$host\n");
		$conn = ftp_connect($encapsulacion . $host) or die("getResourceFtp(): SIN CONEXION");

		// Login
		if (ftp_login($conn, $username, $password))
		{
			// Change the dir
			ftp_chdir($conn, $path);

			// Return the resource
			return $conn;
		} else
			return false;
	}

	//todo: IMPLEMENTAR getResourceSFtp($uri)
	private static function &getResourceSFtp($uri)
	{
		t00lz::dump("stub:getResourceSFtp");
	}

	/**
	 * 
	 * @param string $uri
	 * @return ConnectHubMySQLi
	 */
	private static function &getResourceConnectHub($uri)
	{
		//_includeReal
		include realpath($_SERVER["DOCUMENT_ROOT"]) . "/connecthub.php";
		/*
		  array (
		  0 => 'connecthub://slaveweb',
		  1 => 'connecthub://',
		  2 => 'slaveweb',
		  );
		 */
		preg_match("/(.*?:\/\/)(.*)/i", $uri, $match);

		self::dump($match);

		$ConnectHub = ConnectHub::instance();
		/* @var $conn ConnectHubMySQLi */
		$conn = $ConnectHub->getConnection($match[2]);

		return $conn;
	}

	/**
	 * Hace un ping TCP a un host en un puerto . 
	 * (bool)t00lz::ping("www.google.es"); 
	 * 
	 * http://stackoverflow.com/questions/9841635/how-can-i-ping-a-server-port-with-php
	 * @param ip $host
	 * @param puerto $port
	 * @return boolean pong!
	 * @version "2.0" Añadidos dumps automáticos, 
	 * se parametriza $waitTimeoutInSeconds
	 */
	static function ping($host, $port = null, $waitTimeoutInSeconds = null)
	{
		//por defecto al 80
		if (null === $port)
			$port = 80;
		if (null === $waitTimeoutInSeconds)
			$waitTimeoutInSeconds = 2;

		self::dump("ping : {$host}:{$port}\n");

		$waitTimeoutInSeconds = 1;
		if ($fp = fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds))
		{
			self::dump("PONG!! {$host} is alive\n");
			$pong = true;
			fclose($fp);
		} else
		{
			$pong = false;
			self::dump("{$host} No responde..mal rOllO.\n");
		}

		return $pong;
	}

	public static function setDebugMode($bool)
	{
		self::$DEBUG_MODE = $bool;
	}

	public static function getDebugMode()
	{
		return self::$DEBUG_MODE; 
		
	}

}

/**
 * @author Pascual Muñoz. 07-03-2017
 * Class SFtp2FtpCopyPaster
 *
 * Copia Archivos desde $SOURCE hasta $DEST .
 * Siendo $SOURCE un sftp y $DEST un ftp.
 *
 * Los formatos de las uris son del tipo :
 * {ENCAPSULAMIENTO}://{USER}:{PWD}@{HOST}{PATH}
 *
 *
 * @see http://php.net/manual/en/function.ftp-connect.php
 *
 */
class SFtp2FtpCopyPaster
{

	const SSH_PORT = 22;
	//INDICES de los *ParamContainers
	const URI = 0;
	const ENCAPSULATION = 1;
	const USERNAME = 2;
	const PASSWORD = 3;
	const HOST = 4;
	const PATH = 5;

	private $sourceUri;
	private $destUri;
	//parametros de uris en arrays (preg_match)
	private $sourceParamContainer = array();
	private $destParamContainer = array();
	private $connSSH;
	private $connFTP;

	/**
	 * @param $sourceUri {ENCAPSULAMIENTO}://{USER}:{PWD}@{HOST}{PATH}
	 * @param $destUri {ENCAPSULAMIENTO}://{USER}:{PWD}@{HOST}{PATH}
	 */
	public function __construct($sourceUri, $destUri)
	{
		//las uris.
		$this->sourceUri = $sourceUri;
		$this->destUri = $destUri;

		// Split SFTP URI into:
		// $match[0] = ssh2.sftp://username:password@sld.domain.tld/path1/path2/
		// $match[1] = ssh2.sftp://
		// $match[2] = username
		// $match[3] = password
		// $match[4] = sld.domain.tld
		// $match[5] = /path1/path2/
		preg_match(
				"/(ssh2.sftp:\/\/)(.*?):(.*?)@(.*?)(\/.*)/i", $this->sourceUri, $this->sourceParamContainer
		);

		// Split FTP URI into:
		// $match[0] = ftp://username:password@sld.domain.tld/path1/path2/
		// $match[1] = ftp://
		// $match[2] = username
		// $match[3] = password
		// $match[4] = sld.domain.tld
		// $match[5] = /path1/path2/
		preg_match(
				"/ftp:\/\/(.*?):(.*?)@(.*?)(\/.*)/i", $this->destUri, $this->destParamContainer
		);


		$this->connSSH = $this->getSSHftpConnection($this->sourceUri);
		$this->connFTP = $this->getFtpConnection($this->destUri);
	}

	public function __destruct()
	{
		//apaga y vamonos.
		fclose($this->connSSH);
		fclose($this->connFTP);
	}

	/**
	 * Copia Los Archivos que contengan en su nombre la Cadena $regExp .
	 *
	 * @param $regExp
	 */
	public function copyFilesWhereFilenameContains($regExp)
	{


		echo "<BR>copyFilesWhereFilenameLike($regExp):";


		//obtenemos un stream
		if (!$stream = ssh2_sftp($this->connSSH))
		{
			die('Error : no se puede obtener Stream ftp');
		} else
		{
			echo "<br>Abierto stream al  SFTP";
		}

		$sourceDir = $this->sourceParamContainer[self::ENCAPSULATION];
		$sourceDir .= $stream;
		$sourceDir .= $this->sourceParamContainer[self::PATH];


		//handler para el directorio.
		if (!$dir = opendir($sourceDir))
		{
			die('No se pudo abrir el directorio fuente :' . $sourceDir);
		} else
		{
			echo "<br>Abriendo el directorio : $sourceDir";
		}

		/* recorremos el directorio completo y cargamos en el array �nicamente los valores
		 * que coincidan con el $regExp.
		 */
		$files = array();
		$fileCounter = 0;
		while (false != ($file = readdir($dir)))
		{
			if ($file != "." && $file != ".." && strstr($file, $regExp))
			{
				$files[] = $file;
			}
			$fileCounter++;
		}
		echo "<br>$fileCounter Archivos Encontrados en el directorio.";
		echo "<br>Seleccionando: " . implode("8==D", $files);

		//    $uriSource1 = "ssh2.sftp://{$stream}/{$dirDesde}/{$file}";


		foreach ($files as $file)
		{
			$desdeFileUri = "{$this->sourceParamContainer[self::ENCAPSULATION]}{$stream}/{$this->sourceParamContainer[self::PATH]}/{$file}";

			if (copy($desdeFileUri, $this->destParamContainer[self::URI] . $file))
			{
				echo "<br>COPIADO $file";
			} else
			{
				echo "<br>NO SE HA PODIDO COPIAR : $file ";
			}
		}
	}

	/**
	 * Copia todos los Archivos del directorio . (excepto .. y . )
	 *
	 * WARNNING , SIN TESTEAR .
	 * alias de copyFilesWhereFilenameContains(*)
	 */
	public function copyAllFiles()
	{


		echo "<BR>copyAllFiles(): Copiando todos los archivos del directorio...";


		//obtenemos un stream
		if (!$stream = ssh2_sftp($this->connSSH))
		{
			die('Error : no se puede obtener Stream ftp');
		} else
		{
			echo "<br>Abierto stream al  SFTP";
		}

		$sourceDir = $this->sourceParamContainer[self::ENCAPSULATION];
		$sourceDir .= $stream;
		$sourceDir .= $this->sourceParamContainer[self::PATH];


		//handler para el directorio.
		if (!$dir = opendir($sourceDir))
		{
			die('No se pudo abrir el directorio fuente :' . $sourceDir);
		} else
		{
			echo "<br>Abriendo el directorio : $sourceDir";
		}

		/* recorremos el directorio completo y cargamos en el array �nicamente los valores
		 * que coincidan con el $regExp.
		 */
		$files = array();
		$fileCounter = 0;
		while (false != ($file = readdir($dir)))
		{
			if ($file != "." && $file != "..")
			{
				$files[] = $file;
			}
			$fileCounter++;
		}
		echo "<br>$fileCounter Archivos Encontrados en el directorio.";
		echo "<br>Seleccionando: " . implode("8==D", $files);

		foreach ($files as $file)
		{
			$desdeFileUri = "{$this->sourceParamContainer[self::ENCAPSULATION]}{$stream}/{$this->sourceParamContainer[self::PATH]}/{$file}";

			if (copy($desdeFileUri, $this->destParamContainer[self::URI] . $file))
			{
				echo "<br>COPIADO $file";
			} else
			{
				echo "<br>NO SE HA PODIDO COPIAR : $file ";
			}
		}
	}

	//todo: IMPLEMENTAR sin el parametro uri .

	/**
	 * Retorna la conn a un FTP directamente desde una URI.
	 * @param $uri
	 * @return null|resource
	 */
	private function getFtpConnection($uri)
	{
		// Split FTP URI into:
		// $match[0] = ftp://username:password@sld.domain.tld/path1/path2/
		// $match[1] = ftp://
		// $match[2] = username
		// $match[3] = password
		// $match[4] = sld.domain.tld
		// $match[5] = /path1/path2/
		preg_match("/ftp:\/\/(.*?):(.*?)@(.*?)(\/.*)/i", $uri, $match);
		$this->destParamContainer = $match;

		// Set up a connection
		$conn = ftp_connect($match[1] . $match[4] . $match[5]);

		// Login
		if (ftp_login($conn, $match[2], $match[3]))
		{
			// Change the dir
			ftp_chdir($conn, $match[5]);

			// Return the resource
			return $conn;
		}

		// Or retun null
		return null;
	}

	//todo: IMPLEMENTAR sin el parametro uri .

	/**
	 * Retorna la conn a un SFTP directamente desde la URI.
	 * @param $uri
	 * @return resource
	 */
	function getSSHftpConnection($uri)
	{

		// Split FTP URI into:
		// $match[0] = ssh2.sftp://username:password@sld.domain.tld/path1/path2/
		// $match[1] = ssh2.sftp://
		// $match[2] = username
		// $match[3] = password
		// $match[4] = sld.domain.tld
		// $match[5] = /path1/path2/
		preg_match("/(ssh2.sftp:\/\/)(.*?):(.*?)@(.*?)(\/.*)/i", $uri, $match);


		if (!function_exists("ssh2_connect"))
		{
			die('Error fatal: funcion ssh2_connect() no disponible, por favor instala el driver.');
		}

		//conectamos
		if (!$connSSH = ssh2_connect($match[4], self::SSH_PORT))
		{
			die('No se puede conectar al ssh server' . $match[4]);
		} else
		{
			echo "<br>conectado por SSH a [$match[4]]";
		}

		//autentificamos
		if (!ssh2_auth_password($connSSH, $match[2], $match[3]))
		{
			die('getSSHftpConnection()->ssh2_auth_password() : Imposible Autenticar');
		} else
		{
			echo "<br>Autentificacion correcta con user: [$match[2]]";
		}

		return $connSSH;
	}

	function log($data)
	{
		if (is_array($data))
		{
			$output = implode('8==D', $data);
		} else
		{
			$output = $data;
		}
		echo $output;
	}

}
