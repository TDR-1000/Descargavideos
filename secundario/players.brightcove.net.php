<?php

class PlayersBrightcove extends cadena{

function calcula(){
/*
$proxy = '189.174.63.36:8080';

$ch=curl_init();
curl_setopt($ch,CURLOPT_URL,'http://tvnpod.tvolucion.com/indom/delivery/6dd74f0d-18d9-412a-bd59-5071182ffdbd/indom-c120.mp4_970k.mp4');
curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch,CURLOPT_PROXY,$proxy);
curl_setopt($ch,CURLOPT_HEADER,1);
curl_setopt($ch,CURLOPT_RANGE,'1-200');

$t=curl_exec($ch);
if(curl_errno($ch))dbug('Curl error: '.curl_error($ch));

curl_close($ch);
dbug_($t);
exit;
*/

if(enString($this->web, '//m.')){
	$this->web = str_replace('//m.', '//www.', $this->web);
	dbug('Movil -> escritorio');
	$this->web_descargada=CargaWebCurl($this->web,'',0,'',array('Referer: '.$this->web));
	
	if(!enString($this->web_descargada,'<html'))
		$this->web_descargada=CargaWebCurl($this->web);
}



//usarse a sí mismo como réferer
if(!enString($this->web_descargada,'<html'))
	$this->web_descargada=CargaWebCurl($this->web,'',0,'',array('Referer: '.$this->web));

if(!enString($this->web_descargada,'<html'))
	$this->web_descargada=CargaWebCurl($this->web);

$web_original = $this->web_descargada;

//dbug_($this->web_descargada);

$obtenido=array('enlaces' => array());

$titulo = entre1y2($this->web_descargada, '<title>', '<');
dbug_($titulo);

include_once 'brightcove-funciones.php';
dbug($this->web);
BrightCove_Api($this->web.'"', $obtenido);
finalCadena($obtenido);
return;


// para lasestrellas
if (stringContains($this->web_descargada,array('iniPlayer({'))){
	if (preg_match('@iniPlayer\((\{[\s\S]+\})\);@m', $this->web_descargada, $matches)) {
		dbug_r($matches);
		$json = json_decode(utf8_encode($matches[1]), true);
		dbug_r($json);
		
		$obtenido['titulo'] = $titulo;
		$obtenido['imagen'] = $json['thumbnail'];
		$obtenido['enlaces'][] = array(
			'url'  => $url = $this->makeValidLink($json['streaming_url']),
			'url_txt' => 'Descargar',
			'tipo' => 'http'
		);
		$obtenido['alerta_especifica'] = 'Puede ser necesario usar un proxy.';
		finalCadena($obtenido,false);
		return;
	}
	return;

//para televisa.com/novelas
} else if(stringContains($this->web_descargada,array('showVideo(','data-idvideo="','data-video-id="','embed.php?id='))){
	if(enString($this->web_descargada,'showVideo(')){
		dbug('-1-');
		preg_match('@showVideo\(([0-9]+)\)@',$this->web_descargada,$match);
	}
	elseif(enString($this->web_descargada,'data-id="')){
		dbug('-2-');
		preg_match('@data-id="([0-9]+)"@',$this->web_descargada,$match);
	}
	elseif(enString($this->web_descargada,'data-idvideo="')){
		dbug('-2.5-');
		preg_match('@data-idvideo="([0-9]+)"@',$this->web_descargada,$match);
	}
	elseif(enString($this->web_descargada,'data-video-id="')){
		dbug('-2.6-');
		preg_match('@data-video-id="([0-9]+)"@',$this->web_descargada,$match);
	}
	elseif(enString($this->web_descargada,'embed.php?id=')){
		dbug('-3-');
		preg_match('@embed.php\?id=([0-9]+)@',$this->web_descargada,$match);
	}

	if(isset($match[1])){
		$idVideo=$match[1];
		dbug($idVideo);
		// $this->web='http://amp.televisa.com/embed/embed.php?id='.$idVideo.'&w=620&h=345';
		$this->web='http://tvolucion.esmas.com/embed/embed.php?id='.$idVideo.'&w=620&h=345';
		$this->web_descargada=CargaWebCurl($this->web,'',0,'',array('Referer: '.$this->web));
		if(enString($this->web_descargada,'ya no se encuentra disponible')){
			setErrorWebIntera('Éste vídeo ya no se encuentra disponible.');
			return;
		}
		//dbug_($this->web_descargada);
	}
}



if(enString($this->web_descargada, 'params_dvr.json')){
	if (!isset($idVideo)) {
		preg_match('#/([0-9]+?)/params_dvr.json#', $this->web_descargada, $matches);
		$idVideo = $matches[1];
	}
	$hostname = 'tvolucion.esmas.com';
	$json = "http://{$hostname}/tvenvivofiles/{$idVideo}/params_dvr.json";
	$json = CargaWebCurl($json);
	$json = utf8_encode($json);
	$json = json_decode($json, true);
	dbug_r($json);
	
	$titulo = $json['channel']['item']['title'];
	if ($titulo == '') $titulo = entre1y2($web_original, '<meta property="og:title" content="', '"');
	
	$imagen = $json['channel']['item']['media-group']['media-thumbnail']['@attributes']['url'];

	$i = 1;
	foreach($json['channel']['item']['media-group']['media-content'] as &$elem){
		if(enString($elem['@attributes']['url'], '.f4m')){
			$obtenido['enlaces'][] = array(
				'titulo' => 'opción ' . ($i++),
				'url'  => $elem['@attributes']['url'],
				'nombre_archivo' => generaNombreWindowsValido($titulo),
				'tipo' => 'f4m'
			);
		}
		elseif(enString($elem['@attributes']['url'], '.m3u8')){
			$obtenido['enlaces'][] = array(
				'titulo' => 'opción ' . ($i++),
				'url'  => $elem['@attributes']['url'],
				'tipo' => 'm3u8'
			);
		}
		elseif(enString($elem['@attributes']['url'], '.mp4')){
			$obtenido['enlaces'][] = array(
				'titulo' => 'opción ' . ($i++),
				'url'  => $elem['@attributes']['url'],
				'url_txt' => 'Descargar',
				'tipo' => 'http'
			);
		}
	}
	
	$obtenido['alerta_especifica'] = 'Es necesario usar un proxy de México o estar en México. El programa F4M-Downloader permite indicar un proxy.';
}
else {



//http://c.brightcove.com/services/messagebroker/amf?playerKey=AQ~~,AAAAEUA28vk~,ZZqXLYtFw-ADB2SpeHfBR3cyrCkvIrAe
if(enString($this->web_descargada,'playerKey:"'))
	$playerKey=entre1y2($this->web_descargada,'playerKey:"','"');
elseif(enString($this->web_descargada,'<param name="playerKey"'))
	$playerKey=entre1y2($this->web_descargada,'<param name="playerKey" value="','"');
if(!isset($playerKey)){
	setErrorWebIntera('No se ha encontrado ningún vídeo.');
	return;
}
dbug('playerKey -> '.$playerKey);
$messagebroker='http://c.brightcove.com/services/messagebroker/amf?playerKey='.$playerKey;


if(enString($this->web_descargada,'playerID:"'))
	$experienceID=entre1y2($this->web_descargada,'playerID:"','"');
elseif(enString($this->web_descargada,'<param name="playerID"'))
	$experienceID=entre1y2($this->web_descargada,'<param name="playerID" value="','"');
if(!isset($experienceID)){
	setErrorWebIntera('No se ha encontrado ningún vídeo.');
	return;
}
dbug('experienceID -> '.$experienceID);
	
if(enString($this->web_descargada,'videoId:"'))
	$contentId=entre1y2($this->web_descargada,'videoId:"','"');
elseif(enString($this->web_descargada,'<param name="videoId"'))
	$contentId=entre1y2($this->web_descargada,'<param name="videoId" value="','"');
if(!isset($contentId)){
	setErrorWebIntera('No se ha encontrado ningún vídeo.');
	return;
}
dbug('contentId -> '.$contentId);


include_once 'brightcove-funciones.php';

//$aa = 'AAMAAAABAEZjb20uYnJpZ2h0Y292ZS5leHBlcmllbmNlLkV4cGVyaWVuY2VSdW50aW1lRmFjYWRlLmdldERhdGFGb3JFeHBlcmllbmNlAAIvMQAAAe8KAAAAAgIAKDcyOTBiYTVlOTQzZGM0MmI3ZDY4NmE1NjJmOTZkNWI0MGI0ZjE3OTIRCmNjY29tLmJyaWdodGNvdmUuZXhwZXJpZW5jZS5WaWV3ZXJFeHBlcmllbmNlUmVxdWVzdBlleHBlcmllbmNlSWQhY29udGVudE92ZXJyaWRlcxFUVExUb2tlbhlkZWxpdmVyeVR5cGUTcGxheWVyS2V5B1VSTAVCYrdWAacgAAkDAQqBA1Njb20uYnJpZ2h0Y292ZS5leHBlcmllbmNlLkNvbnRlbnRPdmVycmlkZRtjb250ZW50UmVmSWRzDXRhcmdldBVmZWF0dXJlZElkE2NvbnRlbnRJZBdjb250ZW50VHlwZRtmZWF0dXJlZFJlZklkFWNvbnRlbnRJZHMZY29udGVudFJlZklkAQYXdmlkZW9QbGF5ZXIFf////+AAAAAFQoo2IT7sSAAEAAEBAQYBBX/////gAAAABmVBUX5+LEFBQUFFVUEyOHZrfixaWnFYTFl0RnctQURCMlNwZUhmQlIzY3lyQ2t2SXJBZQaBGWh0dHA6Ly9ub3RpY2llcm9zLnRlbGV2aXNhLmNvbS9wcm9ncmFtYXMtbm90aWNpZXJvLWNvbi1qb2FxdWluLWxvcGV6LWRvcmlnYS8=';
//dbug_r(brightcove_decode(base64_decode($aa)));

$a_encodear = array
(
	'target'	=> 'com.brightcove.experience.ExperienceRuntimeFacade.getDataForExperience',
	'response'	=> '/1',
	'data'		=> array
	(
		'0' => '7290ba5e943dc42b7d686a562f96d5b40b4f1792',
		'1' => new SabreAMF_AMF3_Wrapper
		(
			new SabreAMF_TypedObject
			(
				'com.brightcove.experience.ViewerExperienceRequest',
				array
				(
					'TTLToken' => null,
					'deliveryType' => NAN,
					'URL' => $this->web, //Innecesario
					'experienceId' => $experienceID,
					'playerKey' => $playerKey,
					'contentOverrides' => array
					(
						new SabreAMF_TypedObject
						(
							'com.brightcove.experience.ContentOverride',
							array
							(
								'contentRefId' => null,
								'contentIds' => null,
								'featuredRefId' => null,
								'contentRefIds' => null,
								'contentType' => 0,
								'target' => 'videoPlayer',
								'featuredId' => NAN,
								'contentId' => $contentId
							)
						)
					)
				)
			)
		)
	)
);


// FALLAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
$post = brightcove_encode($a_encodear);
dbug(base64_encode($post));
//dbug_r(brightcove_decode($post));

//dbug_r(brightcove_decode(base64_decode('AAMAAAABAEZjb20uYnJpZ2h0Y292ZS5leHBlcmllbmNlLkV4cGVyaWVuY2VSdW50aW1lRmFjYWRlLmdldERhdGFGb3JFeHBlcmllbmNlAAIvMQAAAhYKAAAAAgIAKDcyOTBiYTVlOTQzZGM0MmI3ZDY4NmE1NjJmOTZkNWI0MGI0ZjE3OTIRCmNjY29tLmJyaWdodGNvdmUuZXhwZXJpZW5jZS5WaWV3ZXJFeHBlcmllbmNlUmVxdWVzdBlkZWxpdmVyeVR5cGUHVVJMGWV4cGVyaWVuY2VJZCFjb250ZW50T3ZlcnJpZGVzE3BsYXllcktleRFUVExUb2tlbgV/////4AAAAAaBZ2h0dHA6Ly90dm9sdWNpb24uZXNtYXMuY29tL3RlbGVub3ZlbGFzL2RyYW1hL2xhLXJvc2EtZGUtZ3VhZGFsdXBlLzIzNjM0OS9yb3NhLWd1YWRhbHVwZS1wZXF1ZW5hLWdyYW4taGlzdG9yaWEtYW1vci8FQmK3VgGnIAAJAwEKgQNTY29tLmJyaWdodGNvdmUuZXhwZXJpZW5jZS5Db250ZW50T3ZlcnJpZGUNdGFyZ2V0FWZlYXR1cmVkSWQTY29udGVudElkG2ZlYXR1cmVkUmVmSWQVY29udGVudElkcxljb250ZW50UmVmSWQbY29udGVudFJlZklkcxdjb250ZW50VHlwZQYXdmlkZW9QbGF5ZXIFf////+AAAAAFQoNbrQiDiAABAQEBBAAGZUFRfn4sQUFBQUVVQTI4dmt+LFpacVhMWXRGdy1BREIyU3BlSGZCUjNjeXJDa3ZJckFlBgE=')));





dbug('a descargar: '.$messagebroker);
$t = brightcove_curl_web($messagebroker, $post);
$res_decoded=brightcove_decode($t);
dbug_r($res_decoded);

$titulo1=$res_decoded['data']->getAMFData();
$titulo2=$titulo1['programmedContent']['videoPlayer']->getAMFData();

dbug_r($titulo2);



$titulo3=$titulo2['mediaDTO']->getAMFData();

$titulo=$titulo3['displayName'];



$renditions = $res_decoded['data']->getAMFData();
$renditions = $renditions['programmedContent']['videoPlayer']->getAMFData();
$renditions = $renditions['mediaDTO']->getAMFData();
$imagen = $renditions['videoStillURL'];
$renditions = $renditions['renditions'];
dbug_r($renditions);

$urls = array();
for($i = 0, $i_t = count($renditions); $i < $i_t; $i++)
	$urls[] = $renditions[$i]->getAMFData();
usort($urls, function ($a, $b) {
	return $a['frameWidth'] < $b['frameWidth'];
});
dbug_r($urls);


for($i = 0, $i_t = count($urls); $i < $i_t; $i++){
	$video = $urls[$i];
	$url = $video['defaultURL'];
	if (enString($url, '?'))
		$url = entre1y2($url, 0, '?');
	$url = $this->makeValidLink($url);
	$obtenido['enlaces'][] = array(
		'url_txt' => 'Tamaño: ' . $video['frameWidth'] . 'x' . $video['frameHeight'],
		'url'  => $url,
		'tipo' => 'http'
	);
}

$obtenido['alerta_especifica'] = "Puede ser necesario usar un Proxy ya que los vídeos pueden tener GeoBloqueo (restricción por país).";

}



$obtenido['titulo']=$titulo;
$obtenido['imagen']=$imagen;

finalCadena($obtenido,false);
}

function makeValidLink($url) {
	return preg_replace('@[a-zA-Z0-9]+\.tvolucion\.com/z@', 'apps.tvolucion.com', $url);
}

}
