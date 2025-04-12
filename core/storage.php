<?php

	/*
	 * /-
	 *  |-
	 *  |
	 *  |
	 *  |
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

		public static $shamail_from_data		= null;
		public static $data_structure_from_data = null;


		public static function store( string $title, array $data )
		{

			Bucket::prepare_bucket();

			self::prepare_meta_data( $data );

			// génération d'un mot de passe random
			self::generate_password();

			// préparation des data et convertion sous forme de chaine de caractère
			$base64_data = self::prepare_data_for_encrypt( $data );

			// chiffrement de la données de manière asymétrique
			self::encrypt_data( $base64_data );

			// suppression de data
			$data = null;

			// chiffrement du mot de passe asymétriquement
			self::encrypt_aes_password();

			// stockage dans le bucket
			self::save_data();

			DataInterface::register_data( $title );

		}


		private static function prepare_meta_data( array $data ) : void
		{
			$email = self::extract_email_from_data( $data );
			self::extract_data_struct_from_data( $data );

			// hashé le mail
			if( $email != "" ){
				self::$shamail_from_data = crypto_helper::get_sha_mail( $email );
			}

		}


		private static function generate_password() : void
		{
			// utilisation de Wordpress
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
		private static function encrypt_data( string $base64_data ) : void
		{

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

			// ecriture des données de base
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . "data.txt",
				self::$encrypted_data["data"],
				LOCK_EX
			);

			// ecriture du mot de passe chiffré
			file_put_contents( 
				Bucket::$current_bucket_path . DIRECTORY_SEPARATOR . "password.txt",
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
				if( $key === "email" ){
					if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
						return $value;
					}
				}
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

	}