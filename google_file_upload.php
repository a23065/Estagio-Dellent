<?php
session_start();//inicio sessão
require_once realpath(dirname(__FILE__) . '/src/Google/autoload.php');
require_once 'podio-php-4.3.0/PodioAPI.php';
include 'config-podio.php';//credenciais//


/*PODIO*/
/************************************************
PODIO - setup cliente
 ************************************************/
Podio::setup(CLIENT, SECRET);
Podio::authenticate_with_app(APP, APP_TOKEN);
Podio::authenticate_with_password(USER, PASS);


/*GOOGLE*/
/************************************************
GOOGLE - setup cliente
 ************************************************/
$client = new Google_Client();
$client->setClientId(GD_CLIENT);
$client->setClientSecret(GD_SECRET);
$client->setRedirectUri(GD_REDIRECT);
$client->addScope("https://www.googleapis.com/auth/drive");
$client->setAccessType("offline"); //para permitir acesso sem utilizador logado
$client->setApprovalPrompt('auto');

/************************************************
GOOGLE - Serviço google drive
 ************************************************/
$service = new Google_Service_Drive($client);

/************************************************
GOOGLE - Autenticação OAuth2.0
 ************************************************/

//Código de autenticação -> access_token + refresh_token
//access_token = 3600sec 

if (isset($_GET['code'])) {
  
  $client->authenticate($_GET['code']);
  $_SESSION['upload_token'] = $client->getAccessToken(); 
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['upload_token']) && $_SESSION['upload_token']) {
	$client->setAccessToken($_SESSION['upload_token']);
 
} else {
	$client->refreshToken(GD_REFRESH);
	$_SESSION['upload_token'] = $client->getAccessToken();
}

/********************************************************************************************/

// Ficheiros de Logs
$file = "./logs/webhook.log"; //log das chamadas ao webhook
$fich2 = "./logs/File_id.log"; //log file_id dos ficheiros attached aos itens
$fich3 = "./logs/GD_files.log"; //log de ficheiros enviados para Google Drive
$err = "./logs/error.log"; //log de erros das exceções

//Webhook//
//Diferentes tipos de eventos dos Webhook: VERIFY/ITEM.CREATE/ITEM.UPDATE/ITEM.DELETE
//caso sejam necessário prever mais tipos basta colocar mais "case"
switch ($_POST['type']) {

  // Validar o webhook. São verificados os novos webhooks criados.
  case 'hook.verify':
    PodioHook::validate($_POST['hook_id'], array('code' => $_POST['code']));

  // Item foi criado
  case 'item.create':
    $string = date('Y-m-d H:i:s',time()-3600) . " item.create webhook received. ";
    $string .= "Post params: ".print_r($_POST, true) . "\n";
    file_put_contents($file, $string, FILE_APPEND | LOCK_EX);


	$item_obj= PodioItem::get( $_POST['item_id'] );		
	//Verifica se item tem ficheiros
	if (count($item_obj->files)>0){

		//percorrer cada ficheiro do objecto Item
		foreach ($item_obj->files as $fil){
			//log de cada ficheiro encontrado no Item
			$fils = "{".date('Y-m-d H:i:s',time()-3600)." Item[".$_POST['item_id']."] com File id:".$fil->file_id."}";
			file_put_contents($fich2, print_r ($fils ,true), FILE_APPEND | LOCK_EX);					
			
			try {
				$ficheiro = PodioFile::get($fil->file_id);															
		
				//pasta de destino dos ficheiros Candidates -- google Drive
				$folderId = CANDIDATES;
				
				//renomear ficheiro
				$nome_fich = $item_obj->app_item_id."_";//número sequencial da APP
				$nome_fich .= $ficheiro->name;//nome de ficheiro original
				
				//metadata do ficheiro
				$fileMetadata = new Google_Service_Drive_DriveFile(array(
				  'name' => $nome_fich,
				  'parents' => array($folderId)
				));					
				
				//Conteúdo do Object file do PODIO
				$content = $ficheiro->get_raw();
				
				//criar ficheiro
				$file = $service->files->create($fileMetadata, array(
				  'data' => $content,
				  'mimeType' => 'application/octet-stream',
				  'uploadType' => 'multipart',
				  'fields' => 'id'));
				  
				//Inserir comentários no item indicando que ficheiros foram guardados					
				$ref_type = 'item';
				$ref_id = $_POST['item_id'];
				$comment = "Ficheiro guardado:".$nome_fich." Data:".date('Y-m-d H:i:s',time()-3600);						
				PodioComment::create( $ref_type, $ref_id, $attributes = array(
					'value' => $comment,						
				) );
			
				//log de ficheiros guardados
				$string = "Ficheiro guardado->".date('Y-m-d H:i:s',time()-3600) . " [";
				$string .= print_r($nome_fich, true) . "] \n";					
				file_put_contents($fich3, $string, FILE_APPEND | LOCK_EX);
										
			} catch (Exception $e) {
				$msg_err = "An error occurred: " . $e->getMessage();
				file_put_contents($err, $msg_err, FILE_APPEND | LOCK_EX);
				}
		}
	} else {
		echo "Sem ficheiros";
		
		//Inserir comentários no item indicando que não tem CV					
		$ref_type = 'item';
		$ref_id = $_POST['item_id'];
		$comment = "Não tem currículo anexado!".$nome_fich." Data:".date('Y-m-d H:i:s',time()-3600);						
		PodioComment::create( $ref_type, $ref_id, $attributes = array(
			'value' => $comment,						
		) );
				
		//log de ficheiros guardados
		$fils = "{".date('Y-m-d H:i:s',time()-3600)." Item[".$_POST['item_id']."] sem ficheiros associados!}";
		file_put_contents($fich2, print_r ($fils ,true), FILE_APPEND | LOCK_EX);
	}
	

  // Item atualizado
  case 'item.update':
    $string = date('Y-m-d H:i:s',time()-3600) . " item.update webhook received. ";
    $string .= "Post params: ".print_r($_POST, true) . "\n";
    file_put_contents($file, $string, FILE_APPEND | LOCK_EX);
		
	$item_obj= PodioItem::get( $_POST['item_id'] );		
	//Verifica se item tem ficheiros
	if (count($item_obj->files)>0){

		//percorrer cada ficheiro
		foreach ($item_obj->files as $fil){
			//log de cada ficheiro encontrado no Item
			$fils = "{".date('Y-m-d H:i:s',time()-3600)." Item[".$_POST['item_id']."] com File id:".$fil->file_id."}";
			file_put_contents($fich2, print_r ($fils ,true), FILE_APPEND | LOCK_EX);					
			
			try {
				$ficheiro = PodioFile::get($fil->file_id);															
		
				//pasta de destino dos ficheiros Candidates -- google Drive
				$folderId = CANDIDATES;
				
				//renomear ficheiro
				$nome_fich = $item_obj->app_item_id."_";//número sequencial da APP
				$nome_fich .= $ficheiro->name;//nome de ficheiro original
				
				//metadata do ficheiro
				$fileMetadata = new Google_Service_Drive_DriveFile(array(
				  'name' => $nome_fich,
				  'parents' => array($folderId)
				));					
				
				//Conteúdo do Object file do PODIO
				$content = $ficheiro->get_raw();
				
				//criar ficheiro
				$file = $service->files->create($fileMetadata, array(
				  'data' => $content,
				  'mimeType' => 'application/octet-stream',
				  'uploadType' => 'multipart',
				  'fields' => 'id'));
				  
				//Inserir comentários no item indicando que ficheiros foram guardados					
				$ref_type = 'item';
				$ref_id = $_POST['item_id'];
				$comment = "Ficheiro guardado:".$nome_fich." Data:".date('Y-m-d H:i:s',time()-3600);						
				PodioComment::create( $ref_type, $ref_id, $attributes = array(
					'value' => $comment,						
				) );
			
				//log de ficheiros guardados
				$string = "Ficheiro guardado->".date('Y-m-d H:i:s',time()-3600) . " [";
				$string .= print_r($nome_fich, true) . "] \n";					
				file_put_contents($fich3, $string, FILE_APPEND | LOCK_EX);
										
			} catch (Exception $e) {
				$msg_err = "An error occurred: " . $e->getMessage();
				file_put_contents($err, $msg_err, FILE_APPEND | LOCK_EX);
				}
		}
	} else {
		echo "Sem ficheiros";				
		//log de ficheiros guardados
		$fils = "{".date('Y-m-d H:i:s',time()-3600)." Item[".$_POST['item_id']."] sem ficheiros associados!}";
		file_put_contents($fich2, print_r ($fils ,true), FILE_APPEND | LOCK_EX);
	}

  // Item eliminado
  case 'item.delete':
    $string = date('Y-m-d H:i:s',time()-3600) . " item.delete webhook received. ";
    $string .= "Post params: ".print_r($_POST, true) . "\n";
    file_put_contents($file, $string, FILE_APPEND | LOCK_EX);

}
