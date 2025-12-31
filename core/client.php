<?php

	namespace Carapace;

	use Carapace\crypto_helper;

	class Client{

		//static public $storage_path_meta_name 	= "carapace_storage_path";
		//static public $client_meta_name 	= "carapace_storage_path";

		static public $rsa_public_option_name 	= "carapace_client_rsa_public_key";
		static public $rsa_private_option_name 	= "carapace_client_encrypted_rsa_private_key";

		/*
			# activation
			- Un client fourni un mot de passe
			- On génère une clé symétrique (AES)
			- gnérer une clé RSA

			On chiffre la clé privé RAS en AES via le mot de passe (kevin123)

			A ce stade on à que la clé RSA publique qui elle ne peut chiffré que de petite partie.

			On générè une nouvelle clé AES256 
			- on chiffre le fichier texte en AES256 avec un mot de passe random
			- on prend le mot de passe et on le chiffre avec avec la clé publique

			L'admin se connecte
			- avec la partie privée RSA il peut obtenir toute les mots de passe AES qui on servis à chiffré les contenu
		*/


		static public function init_client( string $password )
		{

			// génération de la pair de clé RSA
			$rsa_key 					= crypto_helper::generate_rsa_key();
			
			// chiffreement de la clé RSA
			$encrypted_rsa_private_key 	= crypto_helper::symetric_encrypt($rsa_key["private"], $password);

			// stockage des données dans les options du wordpress
			update_option( self::$rsa_public_option_name, $rsa_key["public"] );
			update_option( self::$rsa_private_option_name, $encrypted_rsa_private_key );

			Bucket::init();

		}


		static public function get_private_rsa_key( string $password ){



		}


		static public function r(){}

	}
