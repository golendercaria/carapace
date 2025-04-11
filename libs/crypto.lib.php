<?php

	namespace carapace;

	// commande pour créer une paire de clé ed25519

	class crypto_helper{

		static $version			= "1.0.0";
		static $symetric_cypher	= "aes-256-cbc";

		/*
		 * Fonction utile pour crypter une données en AES256
		 * 
		 * Exemple crypto_helper::symetric_encrypt("mon message", "ma clé de 16 ou 32 octets");
		 * 
		*/
		static function symetric_encrypt( string $message_need_to_be_encrypt, string $key ) 
		{

			// Génére le vecteur d'initialisation (IV) via le nom du cypher
			$ivlen 	= openssl_cipher_iv_length( self::$symetric_cypher );
			$iv 	= openssl_random_pseudo_bytes($ivlen);

			// Chiffrer les données
			$encrypted = openssl_encrypt( $message_need_to_be_encrypt, self::$symetric_cypher, $key, 0, $iv);

			return base64_encode($iv . $encrypted);

		}


		/*
		 * Fonction pour décrypter une données en AES256
		 * 
		 * Exemple crypto_helper::symetric_decrypt("mon message", "ma clé de 16 ou 32 octets");
		 * 
		*/
		function symetric_decrypt( string $message_need_to_be_encrypt, string $key ) {

			$data = base64_decode($message_need_to_be_encrypt);

			// Récupérer l'IV et les données chiffrées
			$ivlen 		= openssl_cipher_iv_length(self::$symetric_cypher);
			$iv 		= substr($data, 0, $ivlen);
			$encrypted 	= substr($data, $ivlen);

			// Déchiffrer les données
			return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
			
		}


		static function symetric_encrypt_hmac( string $message_need_to_be_encrypt = "", array $no_crypted_asymetric_key = [] ) : string{

			if( self::check_supported_cypher_method( self::$symetric_cypher ) === false ){
				throw 'error';
			}

			if( empty($no_crypted_asymetric_key) ){
				throw 'missing asymetrique key';
			}

			$ivlen 				= openssl_cipher_iv_length(self::$symetric_cypher);
			$iv 				= openssl_random_pseudo_bytes($ivlen);
			$first_encrypted	= openssl_encrypt($message_need_to_be_encrypt, self::$symetric_cypher, $no_crypted_asymetric_key[0], OPENSSL_RAW_DATA, $iv);
			$second_encrypted 	= hash_hmac('sha3-512', $first_encrypted, $no_crypted_asymetric_key[1], true);
			$ciphertext 		= base64_encode( $iv.$second_encrypted.$first_encrypted );

			// echo "ivlength = " . $ivlen . "\r\n";
			// echo "iv = " . $iv . "\r\n";
			// echo "key1 = " . $no_crypted_asymetric_key[0] . "\r\n";
			// echo "key2 = " . $no_crypted_asymetric_key[1] . "\r\n";
			// echo "second = " . $second_encrypted . "\r\n";
			// echo "first = " . $first_encrypted . "\r\n";

			return $ciphertext;
		}

		
		static function symetric_decrypt_hmac( array $payload = array() ) : string {

			$key1 = self::asymetric_decrypt( $payload["key1"], "./private_key_extracted.pem" );
			$key2 = self::asymetric_decrypt( $payload["key2"], "./private_key_extracted.pem" );

			if( $payload["encrypted_string"] == "" ){
				throw 'error string to decrypt is empty';
			}

			if( self::check_supported_cypher_method( self::$symetric_cypher ) === false ){
				throw 'error';
			}

			$string_to_decrypt_decoded 	= base64_decode($payload["encrypted_string"]);
			
			$iv_length 					= openssl_cipher_iv_length( self::$symetric_cypher );
			$iv 						= substr($string_to_decrypt_decoded,0,$iv_length);


			$second_encrypted 			= substr($string_to_decrypt_decoded,$iv_length,64);
			$first_encrypted 			= substr($string_to_decrypt_decoded,$iv_length+64);
			
			// echo "ivlength = " . $iv_length . "\r\n";
			// echo "iv = " . $iv . "\r\n";
			// echo "second = " . $second_encrypted . "\r\n";
			// echo "first = " . $first_encrypted . "\r\n";
			// echo "key1 = " . $key1 . "\r\n";
			// echo "key2 = " . $key2 . "\r\n";

			$decrypted_string			= openssl_decrypt($first_encrypted,self::$symetric_cypher, $key1, OPENSSL_RAW_DATA,$iv);
			$second_encrypted_new 		= hash_hmac('sha3-512', $first_encrypted, $key2, true);
		
			if (hash_equals($second_encrypted,$second_encrypted_new)){
				return $decrypted_string;
			}else{
				return "error hash no equal";
			}
		
		}

		static function check_supported_cypher_method( string $cypher_method ) : bool{
			return in_array($cypher_method,openssl_get_cipher_methods());
		}

		static function asymetric_encrypt( $content, $path_public_key ){

			$public_key = self::get_key($path_public_key);

			//echo $content . "\r\n";

			// Chiffrer le contenu avec la clé publique RSA
			$encrypted_content = '';
			if (!openssl_public_encrypt($content, $encrypted_content, $public_key)) {
				$openssl_error = openssl_error_string();
				throw new \Exception("Erreur lors du chiffrement avec la clé publique.".$openssl_error);
			}

			return base64_encode($encrypted_content);

		}

		static function asymetric_decrypt( $crypted_content, $path_private_key ){

			$private_key = self::get_key($path_private_key);

			// Déchiffrer les données
			$decrypted_data = '';
			if (!openssl_private_decrypt(base64_decode($crypted_content), $decrypted_data, $private_key)) {
				$openssl_error = openssl_error_string();
				throw new \Exception("Erreur lors du déchiffrement avec la clé privée: " . $openssl_error);
			}

			return $decrypted_data;

		}

		static function get_key( $key_path ){

			return file_get_contents($key_path);

		}


		static function generate_rsa_key() : array|string{
			
			$config = array(
				"private_key_bits" => 2048,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
			);
		
			$resource = openssl_pkey_new( $config );

			if ($resource === false) {
				return "Erreur lors de la génération de la clé RSA.";
			}

			$key = array(
				"public" 	=> null,
				"private" 	=> null
			);

			// Extraction de la clé privée
			openssl_pkey_export($resource, $key["private"]);

			// Extraction de la clé publique
			$details = openssl_pkey_get_details($resource);
			$key["public"] = $details['key'];

			return $key;

		}

	}