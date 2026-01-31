<?php

	/*
	 * 
	 * 
	*/
	// génération d'un conteneur

	// génération d'un mot de passe random

	// chiffrement du contenu avec le mot de passe random

	// chiffrement du mot de passe avec la clé publique RSA

	// stockage des données chiffrés et du mot de passe chiffré
	
	namespace Carapace;

	use Carapace\crypto_helper;
	use Carapace\DataInterface;

	class Storage{

		private static $aes_password			= null;
		private static $encrypted_aes_password	= null;
		private static $encrypted_data 			= array(
			"data" => null,
			"files"	=> null
		);
		private static $no_encrypted_data		= array(
			"data" => null,
			"files"	=> null
		);

		public static $shamail_from_data		= null;
		public static $data_structure_from_data = null;
		public static $email					= null;


		public static function store( string|callable $title, array $data, $origin = null ){

			// todo : add to documentation
			//$data 	= apply_filters( 'carapace_modulate_data_for_submission', $data );
			
			Bucket::prepare_bucket();

			self::prepare_meta_data( $data );

			// préparation des data et convertion sous forme de chaine de caractère
			if( isset($data["secure"]) && is_array( $data["secure"]) ){

				// génération d'un mot de passe random
				self::generate_password();
				
				$base64_data = self::prepare_data_for_encrypt( $data["secure"] );

				// chiffrement de la données de manière symétrique
				self::encrypt_data( $base64_data );

			}

			// récupération des données non sécurisé
			if( isset($data["no_secure"]) && is_array( $data["no_secure"]) ){
				self::prepare_no_secure_data( $data["no_secure"] );
			}

			// suppression de data
			$data = null;

			// chiffrement du mot de passe asymétriquement
			self::encrypt_aes_password();

			// stockage dans le bucket
			self::save_data();

			if(is_callable($title)){
				$title = call_user_func( $title );
			}
			
			// todo : add to documentation
			//$title 	= apply_filters( 'carapace_modulate_title_for_submission', array($this, 'generate_title'), $data );

			self::register_data( $title, $origin );

		}



		public static function register_data( string $title, $origin = null ){

			$post_data = array(
				'post_title'   => $title,
				'post_status'  => 'private',
				'post_type'    => 'bucket'
			);
	

			$post_id = wp_insert_post($post_data);

			// if ( ! is_wp_error($post_id) ) {
			//     // Ajouter un terme (ou plusieurs) à une taxonomie
			//     wp_set_object_terms($post_id, 'mon-terme', 'bucket_category');
			// }

			if( $post_id ){
				
				add_post_meta($post_id, DataInterface::$data_bucket_path, Bucket::$current_bucket_path );
				add_post_meta($post_id, DataInterface::$data_structure_meta_name, Storage::$data_structure_from_data );
				add_post_meta($post_id, DataInterface::$data_shamail_meta_name, Storage::$shamail_from_data );

				//pre( get_post_meta($post_id) );

				if( $origin != null ){
					wp_set_object_terms( $post_id, $origin, 'origin' );
				}

			}else{

				// gestion d'erreur
				// - post type n'existe pas
				// - titre absent
				// - ..
			}

		}


		private static function prepare_no_secure_data( $data ){
			$json_data 							= json_encode($data);
			self::$no_encrypted_data["data"] 	= $json_data;
		}


		/*
		 * Fonction qui permet de chiffrer une image qui est glissé depuis les uploads de Wordpress
		 * 
		 * 
		*/ 
		public static function storeImage( $upload ){

			Bucket::prepare_bucket();

			// ouverture du fichier
			$file_path 		= $upload["file"];
			$file 			= file_get_contents( $file_path );

			// // génération d'un hash
			// $sha_file 		= hash('sha256', $file);

			// génération d'un password de chiffrement AES
			self::generate_password();

			// transformation de l'image en base64
			$file_to_base64 = base64_encode( $file );

			// chiffrement en AES
			self::encrypt_data( $file_to_base64 );

			// chiffrement du mot de passe asymétriquement
			self::encrypt_aes_password();

			//$parent_folder = dirname( $file_path );

			// write encrypted file
			self::write_encrypted_upload();

			// suppression fichier
			unlink($file_path);

			return $upload;

		}


		private static function prepare_meta_data( array $data ) : void
		{
			self::$email = self::extract_email_from_data( $data );
			self::extract_data_struct_from_data( $data );

			// hashé le mail
			if( self::$email != "" ){
				self::$shamail_from_data = crypto_helper::get_sha_mail( self::$email );
			}

		}


		private static function generate_password() : void{
			self::$aes_password = wp_generate_password(32, true, false);
		}


		/*
		 * Transformation d'un tableau de données en base64
		 * Array > Json > Base64
		*/
		private static function prepare_data_for_encrypt( array $data ) : string
		{
			$json_data 		= json_encode($data);
			$base64_data 	= base64_encode($json_data);
			return $base64_data;
		}
	

		/*
		 * Chiffrement des données en AES256
		*/
		private static function encrypt_data( string $base64_data ) : void{

			$encrypted_data = crypto_helper::symetric_encrypt($base64_data, self::$aes_password);

			self::$encrypted_data["data"] = $encrypted_data;

		}


		/*
		 * Chiffreement de la clé AES en asymétrique avec la clé publique
		*/
		private static function encrypt_aes_password() : void
		{

			$public_rsa_key = get_option( Client::$rsa_public_option_name );

			if( $public_rsa_key == ""){
				// gestion d'erreur si pas de clé publique
			}

			self::$encrypted_aes_password = crypto_helper::asymetric_encrypt( self::$aes_password, $public_rsa_key );
		
			self::$aes_password = null;

		}


		/*
		 * Ecriture des données dans le bucket
		*/
		private static function save_data() : void
		{

			// écriture des données non chiffré
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . "no_secure_data.json",
				self::$no_encrypted_data["data"],
				LOCK_EX
			);


			// écriture des données chiffré
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . "data.txt",
				self::$encrypted_data["data"],
				LOCK_EX
			);

			// écriture du mot de passe chiffré
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . "password.txt",
				self::$encrypted_aes_password,
				LOCK_EX
			);

		}



		private static function write_encrypted_upload() : void
		{

			// ecriture des données de base
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . 'data.txt',
				self::$encrypted_data["data"],
				LOCK_EX
			);

			// ecriture du mot de passe chiffré
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . 'password.txt',
				self::$encrypted_aes_password,
				LOCK_EX
			);


		}


		/*
		 * Extraction de l'email pour le registre
		*/
		public static function extract_email_from_data( array $data ) : string
		{

			if( empty($data) ){
				return "";
			}

			foreach( $data as $key => $value){
				//if( $key === "email" ){
					if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
						return $value;
					}
				//}
			}

			return "";

		}


		/*
		 * Extraction de la structure des données
		 * => peut-être isolé ça dans RGPD plus tard ?
		 * 
		*/
		private static function extract_data_struct_from_data( array $data ) : void
		{
			self::$data_structure_from_data = array_keys( $data );
		}


		public static function extract_data_from_bucket_path( string $bucket_path ) : string|array{

			$data = array(
				"no_secure" => array(),
				"secure" => array()
			);

			$no_secure_data_file_path 	= $bucket_path . DIRECTORY_SEPARATOR . 'no_secure_data.json';
			$secure_data_file_path 		= $bucket_path . DIRECTORY_SEPARATOR . 'data.txt';
			$password_file_path 		= $bucket_path . DIRECTORY_SEPARATOR . 'password.txt';

			// extraction des données non sécurisé
			if( 
				file_exists($no_secure_data_file_path)
			){
				$data["no_secure"] = file_get_contents($no_secure_data_file_path);
			}

			// extraction des données sécurisé
			if( 
				file_exists($secure_data_file_path)
			){

				$data["secure"] = file_get_contents($secure_data_file_path);
				if(
					// si pas de données de rsa key en session
					isset($_SESSION["carapace_rsa_key"]) 
					&& $_SESSION["carapace_rsa_key"] != "" 
					&& file_exists($password_file_path)
				){

					// TODO : faire en sorte de bien tester que le déchiffrement à été possible
					Monitor::tracking_action_on_carapace('Opération de déchiffrement des données du bucket ' . basename($bucket_path));
	
					$password 	= file_get_contents($password_file_path);

					try {
						// code
						$decrypted_password = crypto_helper::asymetric_decrypt( $password, $_SESSION["carapace_rsa_key"]);
					} catch (\Exception $e) {
						echo $e->getMessage();
					}

					if( isset($decrypted_password) && $decrypted_password !== null && $decrypted_password != "" ){						
						// decrypted data
						try {
							$decrypted_data = crypto_helper::symetric_decrypt($data["secure"], $decrypted_password);
							$data["secure"] = json_decode( base64_decode($decrypted_data), true );
						} catch (\Exception $e) {
							echo $e->getMessage();
						}
					}

				}
			}


			return $data;



			if( 
				// si les fichiers n'existe pas
				!file_exists($data_file_path)
				&& !file_exists($password_file_path)
			)
			{
				return "Aucune données";
			}
			else
			{

				$data 		= file_get_contents($data_file_path);
				if(
					// si pas de données de rsa key en session
					!isset($_SESSION["carapace_rsa_key"]) 
					|| $_SESSION["carapace_rsa_key"] == "" 
				
				)
				{
					return $data;
				}
				else
				{
					$password 	= file_get_contents($password_file_path);

					// decrypt password
					$decrypted_password = crypto_helper::asymetric_decrypt( $password, $_SESSION["carapace_rsa_key"]);
					
					// decrypted data
					$decrypted_data = crypto_helper::symetric_decrypt($data, $decrypted_password);

					return json_decode( base64_decode($decrypted_data), true );
				}

			}


		}

	}