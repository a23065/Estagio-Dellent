<?php
require_once 'PodioAPI.php';
require_once 'config-podio.php';
include 'setup_podio.php';


setup(CLIENT_ID, CLIENT_SECRET);
auth_app(APP_ID, APP_TOKEN);
Podio::authenticate_with_password(USER, PASS);

/******************
CRIAÇÃO DE VIEWS
******************/

//Nome da app
$application = PodioApp::get( APP_ID);
$app_name = $application->config['name'];

//Construção da mensagem a ser colocada na notificação Status
$msg = "Criadas views em ".$app_name." : \n";

//Criar uma view + de 48h para avaliação
//
PodioView::create( APP_ID, $attributes = array(
	'name' => '+48h para avaliação',
	'private' => false,										//vista pública
	'filters' => array(
				'applicant-stage' 	=> array(null, 14),		//sem applicant stage definido (por avaliar)
				'created_on'		=> array (				//data de criação
										'to' => '+0dr',		//sem data de ínicio mas com data de fim (2 dias antes)
										'from' => '-2dr',	//sem data de ínicio mas com data de fim (2 dias antes)
										)
				),
	"grouping" => array (									//corresponde ao Split no interface
				"type" => "field",
				"sub_value" => null,
				"value"=> 119290949 ) 						//Total shocolarship (119290949)							
				));

$msg .= " + de 48h para avaliação [".date('Y-m-d H:i:s')."]\n";

//Criar uma view + de 48h para ser contactado
//
PodioView::create( APP_ID, $attributes = array(
	'name' => '+48h para contacto',
	'private' => false,										//vista pública
	'filters' => array(
				'applicant-stage' 	=> 6,					//option 6: To be contacted
				'last_edit_on'		=> array (				//data de criação
										'to' => '+0dr',		//sem data de ínicio mas com data de fim (2 dias antes)
										'from' => '-2dr',	//sem data de ínicio mas com data de fim (2 dias antes)
										)
				),
	"grouping" => array (									//corresponde ao Split no interface
				"type" 		=> "field",
				"sub_value" => null,
				"value"		=> 119290955 ) 					//main-activity (119290955)							
				));
				

$msg .= " + de 48h para ser contactado [".date('Y-m-d H:i:s')."]\n";	
							
//Criar uma view + de 5 dias em Interview
//
PodioView::create( APP_ID, $attributes = array(
		'name' => '+5 dias em entrevista',
		'private' => false,										//vista pública
		'filters' => array(
					'applicant-stage' 	=> 7,					//option 7: Interviewing
					'last_edit_on'		=> array (				//data de edição
											'to' => '+0dr',		//sem data de ínicio mas com data de fim (2 dias antes)
											'from' => '-5dr',	//sem data de ínicio mas com data de fim (2 dias antes)
											)
					),
		"grouping" => array (									//corresponde ao Split no interface
					"type" 		=> "field",
					"sub_value" => null,
					"value"		=> 119290956 ) 					//Expertise areas (119290956)								
					));

$msg .= " + de 5 dias em Interviewing [".date('Y-m-d H:i:s')."]\n";	
							

//Mensagem da Status para indicar onde/quando e quais as views criadas
//
PodioStatus::create( 4467105, $attributes = array(
					'value' => $msg,					
		) );
print_r($msg);