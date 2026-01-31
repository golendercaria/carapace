<?php

	namespace Carapace;

	use Carapace\Storage;

	class ImageInterceptor{

		public static $endpoint_for_decrypt_image = 'carapace-render-encrypted-image';

		public function __construct(){

			// add_filter( 'upload_dir', array( $this, 'custom_upload_dir' ) );

			add_filter( 'wp_insert_attachment_data', array( $this, 'wp_insert_attachment_data' ) );
			
			add_action( 'wp_handle_upload', array($this, 'intercept_image' ) );

			// add_action( 'add_attachment', function(){

			// 	die("add_attchment");
			// });

			add_filter( 'wp_get_attachment_url', array( $this, 'generate_decrypt_image_url' ) );
			
			//add_action( 'init', array($this, 'decrypt_image' ), 20 );
			

			// add_action('init', function () {
			// 	add_rewrite_rule('^' . ImageInterceptor::$endpoint_for_decrypt_image . '/?', 'index.php?' . ImageInterceptor::$endpoint_for_decrypt_image . '=1', 'top');
			// }, 10);
			// add_filter('query_vars', function ($vars) {
			// 	$vars[] = ImageInterceptor::$endpoint_for_decrypt_image;
			// 	return $vars;
			// });



		}


		public function wp_insert_attachment_data( $data )
		{

			if( $data["post_type"] === "attachment" )
			{
				$data["guid"] = WP_PLUGIN_URL . str_replace(WP_PLUGIN_DIR, '', Bucket::$current_bucket_path);
				error_log($data["guid"]);
			}

			return $data;
		}

		public function custom_upload_dir( $dir )
		{

			Bucket::prepare_bucket();

			/*
				avant

				[path] => /var/www/html/wp-content/uploads/2025/06
				[url] => https://localhost:44350/wp-content/uploads/2025/06
				[subdir] => /2025/06
				[basedir] => /var/www/html/wp-content/uploads
				[baseurl] => https://localhost:44350/wp-content/uploads
				[error] => 

				après

				[path] => /var/www/html/wp-content/plugins/carapace/SECUREDATA/68da30191676cfcbf7db9e28c793a3307da2a3e1c2024c8c56d36cf35d2c470f
				[url] => https://localhost:44350/wp-content/plugins/carapace/SECUREDATA/68da30191676cfcbf7db9e28c793a3307da2a3e1c2024c8c56d36cf35d2c470f
				[subdir] => 68da30191676cfcbf7db9e28c793a3307da2a3e1c2024c8c56d36cf35d2c470f
				[basedir] => /var/www/html/wp-content/plugins/carapace/SECUREDATA
				[baseurl] => https://localhost:44350/wp-content/plugins/carapace
				[error] => 
				*/

			$vault_path = Bucket::get_vault_path();

			$baseurl = str_replace( ABSPATH, site_url() . DIRECTORY_SEPARATOR, WP_PLUGIN_URL . DIRECTORY_SEPARATOR . 'carapace' );

			$dir["path"] 	= Bucket::$current_bucket_path;
			$dir["url"]  	= WP_PLUGIN_URL . str_replace(WP_PLUGIN_DIR, '', Bucket::$current_bucket_path);
			$dir["subdir"] 	= basename(Bucket::$current_bucket_path);
			$dir["basedir"] = Bucket::get_vault_path();
			$dir["baseurl"] = $baseurl;

			return $dir;

		}


		private function write_encrypted_file( string $file_path, $sha_file, $encrypted_data )
		{
			$parent_folder 		= dirname( $file_path );
			$finale_file_path 	= $parent_folder . '/' . $sha_file . '.txt';

			// écriture du fichier encrypté
			file_put_contents( $finale_file_path, $encrypted_data );

		}


		public function encrypt_upload( $upload )
		{
			// ouverture du fichier
			$file_path 		= $upload["file"];
			$file 			= file_get_contents( $file_path );

			// génération d'un hash
			$sha_file 		= hash('sha256', $file);

			// génération d'un password de chiffrement AES
			$aes_password 	= wp_generate_password(32, true, false);

			// transformation de l'image en base64
			$file_to_base64 = base64_encode( $file );

			// chiffrement en AES
			$encrypted_data = crypto_helper::symetric_encrypt($file_to_base64, $aes_password );

			// write encrypted file
			$this->write_encrypted_file( $file_path, $sha_file, $encrypted_data );

			// suppression fichier
			unlink($file_path);

			// chiffrement du mot de passe AES

			file_put_contents($upload["file"], $encrypted_data);


			pre($upload);
			pre($sha_image);
			// hash de l'image
			error_log("test");

			/**		private static function encrypt_data( string $base64_data ) : void
		

			$encrypted_data = crypto_helper::symetric_encrypt($base64_data, self::$aes_password);

			self::$encrypted_data["data"] = $encrypted_data;

			 */
		}

		public function intercept_image( $upload ){

			return Storage::storeImage( $upload );




			die();

			$this->encrypt_upload( $upload );

			// if( isset( $_SESSION["carapace_client_password"] ) ){
			// 	$symetric_password = $_SESSION["carapace_client_password"];
			// }else{
			// 	return;
			// }

			die();


			// création d'un répertoire de hash dans le repertoire courant

			// structuture du dossier
			// hash/data.txt
			// hash/password.txt


			die();


			$file 			= file_get_contents( $upload["file"] );
			$base64_data 	= base64_encode($file);

			error_log(serialize($base64_data));

			$encrypted_data = crypto_helper::symetric_encrypt($base64_data, $symetric_password);

			file_put_contents($upload["file"], $encrypted_data);

			return $upload;
		}

		
		public function generate_decrypt_image_url( $url ) {

			$attachment_id = attachment_url_to_postid($url);

			$guid = get_post_field('guid', $attachment_id);

			return $guid;

			// error_log($guid);
			// if (!$attachment_id) return $url;
			// return site_url('/' . self::$endpoint_for_decrypt_image . '/?attachment_id=' . $attachment_id);
		}

		public function decrypt_image(){

			$vault_path = Bucket::get_vault_path();

			echo $vault_path;

			$crypted_image_signature_url = str_replace(WP_PLUGIN_DIR, "", $vault_path);


			pre($crypted_image_signature_url);

			die();
			
			//[REQUEST_URI] => /carapace-render-encrypted-image/?attachment_id=15
			if( !preg_match('/' . ImageInterceptor::$endpoint_for_decrypt_image . '/', $_SERVER["REQUEST_URI"]) ){
				return;
			}

			if( !isset( $_SESSION["carapace_client_password"] ) ){
				return;
			}

			// decryptage
			$symetric_password 	= $_SESSION["carapace_client_password"];

			/*
			$msg = "sucret";
			$base64_data 	= base64_encode($msg);
			$encrypted_data = crypto_helper::symetric_encrypt($base64_data, $symetric_password);
		
			$decrypted_data 	= crypto_helper::symetric_decrypt($encrypted_data, $symetric_password);
			$decrypted_data = base64_decode($decrypted_data);
			pre($decrypted_data);
			die();*/


			// Lire le contenu encodé en Base64 depuis le fichier
// $base64_data = file_get_contents($file_path);

// // Déchiffrer si nécessaire
// if (isset($_SESSION["carapace_client_password"])) {
//     $symetric_password = $_SESSION["carapace_client_password"];
//     $base64_data = crypto_helper::symetric_decrypt($base64_data, $symetric_password);
// }

// // Décoder le Base64 pour obtenir les données binaires
// $binary_data = base64_decode($base64_data);

			// récupération du chemin de l'image
			$id 		= intval($_GET["attachment_id"] );
			$file_path 	= get_attached_file($id);

			// récupération du contenu crypté
			$encrypted_data	= file_get_contents($file_path);

			// pre($encrypted_data);
			// $encrypted_data = base64_encode($encrypted_data);
		
	
			$decrypted_image 	= crypto_helper::symetric_decrypt($encrypted_data, $symetric_password);
			$decrypted_image	= base64_decode($decrypted_image, true);

			if( $decrypted_image ){
				$mime = get_post_mime_type($id);
				header('Content-Type: ' . $mime);
				echo $decrypted_image;
				exit;
			}else{
				die("error on image");
			}

		
		}


	}