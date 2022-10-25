<?php

/*
Formato de las respuestas:
array(
	'titulo'            => título principal de la respuesta,
	'descripcion'       => descripción que acompañe el título, // Optativo
	'imagen'            => url de la imagen,
	'alerta_especifica' => 'texto con html', // Optativo
	'enlaces' => array(
		n => array(
			'titulo'          => texto que acompaña el enlace como descripción,
			'url'             => direccion de la descarga,
			'tipo'            => rtmp/rtmpConcreto/rtmpConcretoHTTP/m3u8/f4m/js/jsFlash/srt/http,
			'url_txt'         => texto que se verá en el enlace en lugar de url
			'extension'       => mp4, avi, flv...
			'rtmpdump'        => comando rtmpdump
			'rtmpdumpHTTP'    => url http que genera el comando rtmpdump para rtmp-downloader
			'm3u8_pass'       => pass m3u8 para f4m-downloader
			'otros_datos_mp3' => resultados mp3 (duración, peso, etc)
			'nombre_archivo'  => para f4m y rtmp downloader
			'script'          => js
		)
	)
);

si está presente url_txt, no estará titulo, y viceversa (esto vendrá bien para el gestor de descargas)
*/

if(!defined('DEBUG')){
	if(isset($_GET['debug']) || isset($_COOKIE['debug'])){
		ini_set('display_errors',1);
		ini_set('display_startup_errors',1);
		error_reporting(-1);
		define('DEBUG',true);
	}
	else{
		error_reporting(0);
	}
}

include_once '../definiciones.php';
include_once '../funciones.php';


//NO CACHE. Los resultados no se deben cachear
header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
header('Pragma: no-cache');










//recogemos variables
if(!isset($web)){
	if(isset($_POST['web']))
		$web=$_POST['web'];
	else
		$web='';
}


define('BM', isset($_POST['bookmarklet']));
dbug(BM ? 'BM true' : 'BM false');

define('POST_BM', !BM && isset($_POST['bmgenerated']));
dbug(POST_BM ? 'POST_BM true' : 'POST_BM false');


//API. devolver SOLO el enlace (1/2)
if(!defined('MODO_API')){
	define('MODO_API', isset($_GET['modoApi']));
}


$fallourlinterna='';
$errorImprimible='';

//2012-5-25 -> ya no está
//junio
//agosto-movil
//septiembre-api
//diciembre-res
$plantillaDefault='diciembre-res';
$plantillaApi='septiembre-api';
$plantillaRes='diciembre-res';
$path_plantilla='';





// Peligroso
if(isset($_GET['plantilla']))
	$path_plantilla='plantillas/'.$_GET['plantilla'].'/';
elseif(MODO_API){
	//API. devolver SOLO el enlace (2/2)
	$path_plantilla='plantillas/'.$plantillaApi.'/';
	dbug('plantilla api');
}elseif($path_plantilla=='')
	$path_plantilla='plantillas/'.$plantillaDefault.'/';


/*
array(
	n => array(
		0 => array(
			n => A buscar en enString $web
		)
		1 => array(	phps a importar
			n => Include n
		)
		2 => Funcion a lanzar
	)
);
*/

/*
Dominio(s)
PHP(s) a incluir
Class a crear
Función del objeto de la class a llamar
*/
require_once 'cadenas.php';

//Siempre ocultar avisos rápidos
define('IGNORA_AVISO_RAPIDO', true);

//hora de descargar y mostrar el resultado
//AVERIGUAR SERVIDOR

	$R = array();
	
	while(preg_match('@descargavideos\.tv.+?web=(.+?)(?:$|&)@', $web, $matches)){
		dbug_r($matches);
		
		if($matches && $matches[1]){
			dbug('sacando la web de descargavideos.tv/?web=...');
			$web = urldecode($matches[1]);
			dbug('Nueva web: '.$web);
		} else {
			break;
		}
	}
	
	
	if(trim($web) === ''){
		setErrorWebIntera('Especifique la dirección de la web que contiene el vídeo.');
	}
	elseif(BM || validar_enlace($web)){
		//La función anterior, si es exitosa, finaliza la web. Si falla (url de un server no válido o la función del canal se acabó antes de lo previsto, se ejecuta lo próximo
		// A veces la gente quiere descargar enlaces de google que todavía no se han redireccionado. Resolverlos
		if (preg_match('@https?://(?:www\.)?google\.[a-zA-Z]+?/.*?(?:url|q)=(http.+?)[&$]@', $web, $matches)) {
			dbug_r($matches);
			$web = urldecode($matches[1]);
			dbug("Extraída url de un enlace de google: " . $web);
		}
		
		$cadena_elegida_arr = averiguaCadena($web);
		if($cadena_elegida_arr===false){
			//no es una url aceptada de una web permitida
			setErrorWebIntera('Has introducido un enlace de una página web no soportada. Puedes consultar el listado de webs soportadas en el siguiente enlace:<br/><a href="http://'.DOMINIO.'/faq#p_q_c_s_d">http://'.DOMINIO.'/faq#p_q_c_s_d</a>');
			define('IGNORA_AVISO_RAPIDO', true);
			//lanzaBusquedaGoogle();
		}
		else{
			$intentos = 3;
			$intento = 0;
			$exito = false || BM;
			
			// url_exists_full descarga la web para comprobar si es un enlace válido. De paso, guarda en web_descargada el resultado, para no tener que re-descargarlo inúltilmente
			
			while(!$exito &&
					$intento < $intentos &&
					dbug('Intento '.$intento) &&
					!($exito = url_exists_full($web, true, 4 + $intento * 3))){
				$web = str_replace(' ', "%20", $web);
				$web = str_replace("\t", "%09", $web);
				$intento++;
			}
			
			if($exito){
				if (BM) 
					dbug('enlace correcto (por BM)=>'.$web);
				else
					dbug('enlace correcto (se pudo abrir la URL)=>'.$web);
				
				if (!BM || isset($cadena_elegida_arr[4])) {
					// Includes
					dbug('Incluyendo PHPs');
					include 'cadena.class.php';
					
					for($k=0,$k_t=count($cadena_elegida_arr[1]);$k<$k_t;$k++){
						dbug('Incluyendo: '.$cadena_elegida_arr[1][$k]);
						include_once $cadena_elegida_arr[1][$k];
					}
					
					// Si la llamada es del bookmarklet usar los parámetros del mismo
					if (BM || POST_BM) {
						$web_descargada = $_POST['src'];
					}
					
					//Crear objeto
					$cadena = new $cadena_elegida_arr[2]();
					$cadena->init($web, $web_descargada, $web_descargada_headers);
					
					// Lanzar función cadena
					// Estas funciones pueden modificar el valor de web_descargada ya que se para por parámetro, pero no de web
					if (BM) {
						dbug('Lanzando función cadena: '.$cadena_elegida_arr[4]);
						dbug('--------------------------------------');
						$R['BM2_JS'] = $cadena->{$cadena_elegida_arr[4]}();
					} else {
						if (POST_BM) {
							$cadena->set_normal_desde_bookmarklet();
						}
						
						dbug('Lanzando función cadena: '.$cadena_elegida_arr[3]);
						dbug('--------------------------------------');
						$cadena->{$cadena_elegida_arr[3]}();
					
						if($fallourlinterna==''){
							if(!isset($resultado['enlaces']) || count($resultado['enlaces'])==0){
								//no es una url aceptada de una web permitida
								setErrorWebIntera('No se pudo encontrar ningún video o audio.');
								dbug('URL correcta, de server soportado, pero no debería de haber nada dentro');
							}
							else{
								// Tenemos resultado
								
								generaR();
								
								global $Cadena_elegida;
								saveDownload($Cadena_elegida, $web, $resultado['titulo']);
							}
						}
					}
				} else {
					$R['BM2_JS'] = 'bookmarklet_form();';
				}
			}
			else{
				dbug('fallo al abrir la url=>'.$web);
				// Concretar el tipo de fallo para evitar que, en caso de ser fallo del usuario, no cometa el mismo error.
				if(substr_count($web, 'http') > 1){
					setErrorWebIntera('Introduzca un solo enlace. No se permiten calcular varios resultados al mismo tiempo');
					define('IGNORA_AVISO_RAPIDO', true);
				}elseif($intento == $intentos - 1){
					setErrorWebIntera('La web especificada parece estar caída y no responde (la conexión hace timeout).');
				}else{
					setErrorWebIntera('No se ha podido abrir el enlace o no es un enlace válido');
				}
			}
		}
	}
	else{
		//setErrorWebIntera('URL no válida');
		lanzaBusquedaGoogle($web);
	}
	if(defined('DEBUG') && !BM){
		dbug('-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_-_');
		dbug('DEBUG en marcha, terminando.');
		exit;
	}




if($fallourlinterna!=''){
	if($fallourlinterna=='premium')
		$errorImprimible='Error. Los vídeos premium o de pago no están soportados.';

	elseif($fallourlinterna=='full')
		$errorImprimible='Error. Introduce los vídeos de uno en uno, y no la serie completa.';

	else
		//$errorImprimible='Error. Lo sentimos.';
		$errorImprimible='Error: '.$fallourlinterna;
}


// imprimir web
if($web=='' || $errorImprimible!='')
	generaF();


function lanzaBusquedaGoogle($web){
	generaB();
}



function generaR(){
	global $Cadena_elegida, $web, $R;

	$R['url_img_res'] = $R['BASE']['imagen'];
	$R['titulo_res'] = html_entity_decode($R['BASE']['titulo']);
	$R['descripcion_res'] = isset($R['BASE']['descripcion']) ? html_entity_decode($R['BASE']['descripcion']) : '';
	$R['contenido'] = array();
	$R['CANAL'] = $Cadena_elegida;
	$R['WEB'] = $web;
	
	$R['MODO'] = 'RESULTADO';
	
	if(isset($R['BASE']['alerta_especifica'])){
		$R['alerta_especifica'] = $R['BASE']['alerta_especifica'];
	}
	
	define('HAY_RESULTADO', true);
	dbug('HAY_RESULTADO generado en generaR');
}

function generaB(){
	global $web, $R;

	$R['WEB'] = $web;
	$R['busqueda'] = $web;
	
	$R['MODO'] = 'BUSQUEDA';
	
	define('HAY_RESULTADO', true);
	dbug('HAY_RESULTADO generado en generaB (Búsqueda)');
}

function generaF(){
	global $Cadena_elegida, $web, $R, $errorImprimible;

	$R['error_texto'] = $errorImprimible;
	$R['CANAL'] = $Cadena_elegida;
	$R['WEB'] = $web;
	
	$R['MODO'] = 'ERROR';
	
	define('HAY_RESULTADO', true);
	dbug('HAY_RESULTADO generado en generaF');
}




//Se seta $Cadena_elegida
function averiguaCadena($web){
	global $cadenas,$Cadena_elegida;
	dbug('averiguando cadena');
	for($i=0,$i_t=count($cadenas); $i<$i_t; $i++)
		for($j=0,$j_t=count($cadenas[$i][0]); $j<$j_t; $j++){
			$pattern="@^(?:https?:)?//(([^/^\.]+\.)*?".strtr($cadenas[$i][0][$j], array('.'=>'\\.')).")(/.*)?$@i";
			preg_match($pattern, $web, $matches);
			if($matches){
				//Cadena encontrada
				$Cadena_elegida=$cadenas[$i][0][$j];
				dbug($cadenas[$i][0][$j]);
				return $cadenas[$i];
			}
		}
	return false;
}

function validar_enlace($link){
	global $web;

	if(enString($link, 'http://www.descargavideos.tv')){
		return false;
	}

	$link = trim($link);

	// Quitar espacios y pasarlos a guiones (-) en rtpa.es
	if(enString($link, 'rtpa.es')){
		$link = strtr($link,' ','-');
	}

	// http://http//www....
	if(enString($link,'http//')){
		// http// esta en el enlace. Quitarlo
		$link = strtr($link, array('http//' => ''));
	}
	
	if(strpos($link, '//') === 0){
		$link = 'http:'.$link;
	}
	
	if(strpos($link, '/') === 0){
		$link = 'http:/'.$link;
	}
	
	// http://http://www....
	if(strpos($link,'http://http://') === 0){
		$link = substr($link, strlen('http://'));
	}
	
	// http:// está en el enlace. Si no, lo agregamos
	if(enString($link,'http://')||enString($link,'https://')){
		// Comprobar si estamos con un iframe
		if(enString($link,'<iframe')){
			dbug('Detectado iframe');
			preg_match('@src.*?=.*?["\'](.*?)["\']@', $link, $matches);
			dbug_r($matches);
			if(isset($matches[1])){
				$link = $matches[1];
				if(strpos($link, '//') === 0){
					$link = 'http:'.$link;
				}
			}
			else{
				return false;
			}
		}
		if(($i = strpos($link, 'http')) !== 0){
			$link = substr($link, $i);
			dbug('Quitado texto existente antes de http(s)://');
		}
		$enlace = $link;
	}
	else{
		$enlace = 'http://'.$link;
	}
	
	$enlace = trim($enlace);

	$amos='si';
	if(preg_match('@^https?://(([^/^\.]+\.)+?[^/^\.]+?)(/.*)?$@i', $enlace)){
		$web=$enlace;
		dbug('enlace bien escrito (estructura de un enlace)');

		$web = $enlace;
		dbug('enlace correcto (pregmatch válido)=>'.$enlace);
		return true;
	}
	else{
		dbug('fallo en pregmatch de la url (enlace mal construido. No es un enlace)');
		return false;
	}

	return false;
}


//$obtenido -> array con los resultados
//$asegurate -> boolean: verdadero=comprobar si los enlaces son válidos. Falso=no comprobar
function finalCadena($obtenido, $asegurate=true){
	global $resultado, $R;

	$ind=(!isset($obtenido['enlaces'][0]['url']))?0:1;
	if(isset($obtenido['enlaces'][$ind]['url']))
		$duda1=esVideoAudioAnon($obtenido['enlaces'][$ind]['url']);
	else
		$duda1=true;
	if(isset($obtenido['enlaces'][$ind]['tipo']))
		$duda2=$obtenido['enlaces'][$ind]['tipo']!='http';
	else
		$duda2=true;
	if(!$asegurate || $duda1 || $duda2){
		dbug('Obtenido!');
		dbug_r($obtenido);

		$resultado=$obtenido;
		$R['BASE'] = $obtenido;
	}
	else{
		dbug('Error!');
		setErrorWebIntera('Ha ocurrido un error.');
	}
}
?>