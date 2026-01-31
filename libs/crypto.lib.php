<?php

	namespace Carapace;

	class crypto_helper{

		static $version			= "2.0.0";
		static $symetric_cypher	= "aes-256-cbc";

		/**
		* Chiffre un message en utilisant l'algorithme AES-256.
		*
		* $encrypted = crypto_helper::symetric_encrypt("Mon message secret", "ma_cle_secrete_32_octets");
		*
		* @param string $message_need_to_be_encrypt Message en clair à chiffrer
		* @param string $key Clé secrète (16 ou 32 octets)
		*
		* @return string Message chiffré
		*
		*/
		static function symetric_encrypt( string $message_need_to_be_encrypt, string $key ){

			// Génére le vecteur d'initialisation (IV) via le nom du cypher
			$ivlen 	= openssl_cipher_iv_length( self::$symetric_cypher );
			$iv 	= openssl_random_pseudo_bytes($ivlen);

			$encrypted = openssl_encrypt( $message_need_to_be_encrypt, self::$symetric_cypher, $key, 0, $iv);

			return base64_encode($iv . $encrypted);

		}

		/**
		* déchiffre un message en utilisant l'algorithme AES-256.
		*
		* $encrypted = crypto_helper::symetric_decrypt("Mon message secret", "ma_cle_secrete_32_octets");
		*
		* @param string $message_need_to_be_encrypt Message chiffré à déchiffrer
		* @param string $key Clé secrète (16 ou 32 octets)
		*
		* @return string Message déchiffré
		*
		*/
		static function symetric_decrypt( string $message_need_to_be_decrypt, string $key ) {

			$data = base64_decode($message_need_to_be_decrypt);

			// Récupérer l'IV et les données chiffrées
			$ivlen 		= openssl_cipher_iv_length(self::$symetric_cypher);
			$iv 		= substr($data, 0, $ivlen);
			$encrypted 	= substr($data, $ivlen);

			return openssl_decrypt($encrypted, self::$symetric_cypher, $key, 0, $iv);

		}


		static function check_supported_cypher_method( string $cypher_method ) : bool{
			return in_array($cypher_method,openssl_get_cipher_methods());
		}


		/**
		* Chiffre un message en utilisant une clé publique RSA.
		*
		* $crypted_message = crypto_helper::asymetric_encrypt("Mon message", "-----BEGIN PUBLIC KEY-----");
		*
		* @param string $message_need_to_be_encrypt Message en clair à chiffrer
		* @param string $public_rsa_key Clé public RSA
		*
		* @return string Message chiffré
		*
		*/
		static function asymetric_encrypt( string $message_need_to_be_encrypt, string $public_rsa_key ) : string{

			$encrypted_content = '';
			if (!openssl_public_encrypt($message_need_to_be_encrypt, $encrypted_content, $public_rsa_key)) {
				$openssl_error = openssl_error_string();
				throw new \Exception("Erreur lors du chiffrement avec la clé publique.".$openssl_error);
			}

			return base64_encode($encrypted_content);

		}


		/**
		* Déchiffre un message en utilisant une clé privé RSA.
		*
		* $decrypted_message = crypto_helper::asymetric_decrypt("Mon message", "-----BEGIN PRIVATE KEY-----");
		*
		* @param string $message_need_to_be_decrypt Message en clair à chiffrer
		* @param string $public_rsa_key Clé public RSA
		*
		* @return string Message déchiffré
		*
		*/
		static function asymetric_decrypt( string $message_need_to_be_decrypt, string $private_key ) : string{

			$decrypted_data = '';
			if (!openssl_private_decrypt(base64_decode($message_need_to_be_decrypt), $decrypted_data, $private_key)) {
				$openssl_error = openssl_error_string();
				throw new \Exception("Erreur lors du déchiffrement avec la clé privée: " . $openssl_error);
			}

			return $decrypted_data;

		}


		/**
		* Génère une clé RSA 4096 bits
		*
		* crypto_helper::generate_rsa_key();
		*
		* @return array{
		* public: string,
		*     private: string
		* }|string
		*/
		static function generate_rsa_key() : array|string{
			
			$config = array(
				"private_key_bits" => 4096,
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


		public static function get_sha_mail( string $email ) : string{
			return hash('sha256', $email);
		}

		
		public static function generate_aes_password(): string{
			return random_bytes(32);
		}


	}