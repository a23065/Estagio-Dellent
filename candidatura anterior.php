<?php
require_once 'PodioAPI.php';
include 'config-podio.php';

/*
$client_id = "teste-z3138q";
$client_secret = "LNAK8RAa1ZxI2J121hdwqAAEcHwuu9hpxSg6yQboPZzGzWbHMgGkhtZj2UtWFrPw";
$app_id = "15268294"; //oportunidades
$app_token = "0cae452286954ee9974b13aced979a48";
$item_id = "398895764"; //php
*/
$client_id = "teste-z3138q";
$client_secret = "LNAK8RAa1ZxI2J121hdwqAAEcHwuu9hpxSg6yQboPZzGzWbHMgGkhtZj2UtWFrPw";
$app_id = "15464701"; //site da dellent - candidatos
$app_token = "6c789e9759fc49d8a8ece1adb399a731";

Podio::setup($client_id, $client_secret);
Podio::authenticate_with_app($app_id, $app_token);

//necessária esta autenticação para ser possível update/delete/create
Podio::authenticate_with_password(USER, PASS);

//////////////////////////////////////////////////////////////////////
//   HTML - FORMULÁRIO  //

?>
<html>
   
   <head>
      <title>Formulário candidatura</title>
   </head>
   
   <body>
      <?php
         
         // define variables and set to empty values
         $name = $nascimento = $telefone = $email = $skype= $linkedin= $skype = $anosexperiencia = $backgroundacademico = "";
         
         if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = test_input($_POST["name"]);
            $email = test_input($_POST["email"]);
            $telefone = test_input($_POST["telefone"]);
			$skype = test_input($_POST["skype"]);
			$nascimento = test_input($_POST["nascimento"]);
			$job_apply = test_input($_POST["job_apply"]);
			$linkedin = test_input($_POST["linkedin"]);
			$work_at = test_input($_POST["work_at"]);
			$exp_yrs = test_input($_POST["exp_yrs"]);
			$academic = test_input($_POST["academic"]);
			$school = test_input($_POST["school"]);
            $company = test_input($_POST["company"]);
         }
         
         function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
         }
      ?>
   
      <h2>Formulário de candidatura</h2>
      
      <form method = "post" action = "<?php $_PHP_SELF ?>">
         <table>
            <tr>
               <td>Name:</td> 
               <td><input type = "text" name = "name"></td>
            </tr>
            
            <tr>
               <td>E-mail:</td>
               <td><input type = "text" name = "email"></td>
            </tr>
            
            <tr>
               <td>telefone:</td>
               <td><input type = "text" name = "telefone"></td>
            </tr>
                        
            <tr>
               <td>skype:</td>
               <td><input type = "text" name = "skype"></td>
            </tr>
                                       
            <tr>
               <td>data de nascimento:</td>
               <td><input type = "text" name = "nascimento"></td>
            </tr>
                        
            <tr>
               <td>job_apply:</td>
               <td><input type = "text" name = "job_apply"></td>
            </tr>
                        
            <tr>
               <td>linkedin:</td>
               <td><input type = "text" name = "linkedin"></td>
            </tr>
                                       
            <tr>
               <td>work-at:</td>
               <td><input type = "text" name = "work_at"></td>
            </tr>
                                           
            <tr>
               <td>exp_yrs:</td>
               <td><input type = "text" name = "exp_yrs"></td>
            </tr>
                                           
            <tr>
               <td>academic:</td>
               <td><input type = "text" name = "academic"></td>
            </tr>
                                    
            <tr>
               <td>company:</td>
               <td><input type = "text" name = "company"></td>
            </tr>
            
            <tr>
               <td>school:</td>
               <td>
                  <input type = "radio" name = "school" value = "1">Secundário
				  <input type = "radio" name = "school" value = "2">Licenciatura                  
				  <input type = "radio" name = "school" value = "3">Mestrado
				  <input type = "radio" name = "school" value = "4">Doutoramento
               </td>
            </tr>
            
            <tr>
               <td>
                  <input type = "submit" name = "submit" value = "Submit"> 
               </td>
            </tr>
         </table>
      </form>
      
      <?php
	  //antes de criar item no podio, verificar número de ocorrências de emails no PODIO
	  //Pode ser feito através do calculate ou do filter (filtered)
		/*
		$calculate = PodioItem::calculate($app_id,array(				
			'aggregation' => 'count',
			'filters' => array('email-address' => $email),
		));	
		*/	
		
		$filter = PodioItem::filter($app_id,array(				
			'filters' => array('email-address' => $email),
		));
		
		$count_filtered = $filter->filtered;
		
		print_r ($count_filtered."<br><br><br>");
	  //se calculate diferente de vazio então $ray_items = ''
	  $ray_items = NULL; //inicializar a NULL - para limpar variável
		 
		 //conta os que itens que obedecem ao criterio de filtragem
		 if($count_filtered>0){
			
			//filtrar por todos os items na app que tenham email igual ao submetido, por ordem descendente de data de criação
			$cand = PodioItem::filter ($app_id, array(
					'filters' => array(
						'email-address' => $email),
					'sort_by' => 'created_on',
					'sort_desc' => true,
					'limit' => 3, //só está a mostrar as últimas 3
			));
			// Para cada item encontrado construir um array com o seus item_id
			foreach ($cand as $it) {
			  print $it->created_on->format('d-m-Y H:i:s')."<br>";
			  print $it->item_id."<br>";
			  $ray_items [] = $it->item_id;
			  
			}
		} else {
			print "sem candidaturas anteriores";
			$ray_items = NULL;
		}

	  //criar item no Podio
		$create_item = PodioItem::create( $app_id, $attributes = array('fields' => array(
								"applicants-name" 				=> $name,
								"candidatura-anterior"			=> $ray_items,
								"contact-phone-number"			=> "teledone",
								"email-address" 				=> $email,
								"skype" 						=> "skype",
								"text" 							=> "nascimento",
								"job-applying-for" 				=> 1,
								"availability-to-work-at"		=> 1,
								"experience-years"				=> 123,
								"academic-background"			=> "academic-background",
								"total-scholarship"				=> 1,
								"company" 						=> 2	


		)));
			  
	  
	  
	  
	  
         echo "<h2>Detalhes de submissão :</h2>";
         echo $name;
         echo "<br>";
         
         echo $email;
         echo "<br>";
         
         echo $telefone;
         echo "<br>";
         
         echo $skype;
         echo "<br>";
                  
         echo $nascimento;
         echo "<br>";
                  
         echo $job_apply;
         echo "<br>";
                  
         echo $linkedin;
         echo "<br>";
                  
         echo $work_at;
         echo "<br>";
                    
         echo $exp_yrs;
         echo "<br>";
                    
         echo $school;
         echo "<br>";
                       
         echo $company;
         echo "<br>";
         
         echo $academic;
      ?>
      
   </body>
</html>
