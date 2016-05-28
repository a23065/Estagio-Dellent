<?php
session_start();//inicio sessão
require_once realpath(dirname(__FILE__) . '/src/Google/autoload.php');
require_once 'podio-php-4.3.0/PodioAPI.php';
include 'config-podio.php';//credenciais//


//PODIO//
//PODIO - setup cliente//
Podio::setup(CLIENT, SECRET);
Podio::authenticate_with_app(APP, APP_TOKEN);
Podio::authenticate_with_password(USER, PASS);//necessária esta autenticação para ser possível update/delete/create


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

//webhook//
// Big switch statement to handle the different events
// Ficheiros de Logs
$file = "./logs/webhook.log";
$fich = "./logs/lasan.log";
$fich2 = "./logs/File_id.log";
$fich3 = "./logs/GD_files.txt";
switch ($_POST['type']) {

  // Validate the webhook. This is a special case where we verify newly created webhooks.
  case 'hook.verify':
    PodioHook::validate($_POST['hook_id'], array('code' => $_POST['code']));

  // An item was created
  case 'item.create':
    $string = gmdate('Y-m-d H:i:s') . " item.create webhook received. ";
    $string .= "Post params: ".print_r($_POST, true) . "\n";
    file_put_contents($file, $string, FILE_APPEND | LOCK_EX);

  // An item was updated
  case 'item.update':
    $string = gmdate('Y-m-d H:i:s') . " item.update webhook received. ";
    $string .= "Post params: ".print_r($_POST, true) . "\n";
    file_put_contents($file, $string, FILE_APPEND | LOCK_EX);
		
		$item_obj= PodioItem::get( $_POST['item_id'] );
		//numero de ficheiros attached to item
		file_put_contents($fich, print_r(count($item_obj->files), true), FILE_APPEND | LOCK_EX);
		
			//Verifica se item tem ficheiros
			if (count($item_obj->files)>0){

				//percorrer cada ficheiro
				foreach ($item_obj->files as $fil){
					$fils = "File id: ".$fil->file_id;
					file_put_contents($fich2, print_r ($fils ,true), FILE_APPEND | LOCK_EX);					
					
					$ficheiro = PodioFile::get($fil->file_id);															
			
					//inserir ficheiro em pasta Candidates -- google Drive
					$folderId = CANDIDATES;
					//renomear ficheiro
					$nome_fich = $item_obj->app_item_id."_";//número sequencial da APP
					$nome_fich .= $ficheiro->name;//nome de ficheiro original
					
					$fileMetadata = new Google_Service_Drive_DriveFile(array(
					  'name' => $nome_fich,
					  'parents' => array($folderId)
					));
					$content = $ficheiro->get_raw();
					$file = $service->files->create($fileMetadata, array(
					  'data' => $content,
					  'mimeType' => 'application/octet-stream',
					  'uploadType' => 'multipart',
					  'fields' => 'id'));
					printf("File ID: %s\n", $file->id);	
					
					//Inserir comentários no item indicando que ficheiros foram guardados					
					$ref_type = 'item';
					$ref_id = $_POST['item_id'];
					$comment = "Ficheiro guardado:".$nome_fich." Data:".gmdate('Y-m-d H:i:s');
					
					PodioComment::create( $ref_type, $ref_id, $attributes = array(
						'value' => $comment,
						
					) );
					
					//log de ficheiros guardados
					$string = "Ficheiro guardado->".gmdate('Y-m-d H:i:s') . " [";
					$string .= print_r($nome_fich, true) . "] \n";					
					file_put_contents($fich3, $string, FILE_APPEND | LOCK_EX);

				}
			} else {
				echo "Sem ficheiros";
			}

  // An item was deleted
  case 'item.delete':
    $string = gmdate('Y-m-d H:i:s') . " item.delete webhook received. ";
    $string .= "Post params: ".print_r($_POST, true) . "\n";
    file_put_contents($file, $string, FILE_APPEND | LOCK_EX);

}
