<?php

	namespace Carapace;

	class Bucket{

		// variable administrable
		static private $coffre_path = "";

		static public $coffre_ID 			= "";

		static public $coffre_path_meta_name 	= "carapace_storage_path";
		static public $defaut_coffre_path 		= "./SECUREDATA";

		static public function load_client( array $client_information ) : bool
		{

			if( 
				!isset($client_information["email"]) 
				// + email condition
			)
			{
				return false;
			}

			$secret = $client_information["secret"] ?? "";

			return self::load_coffre_ID( $client_information["email"], $secret );

		}

		static private function load_coffre_ID( string $email, string $secret = "" ) : bool {
			
			self::$coffre_ID = get_sha_string( $secret . $email );
			
			if( self::$coffre_ID == "" ){
				return false;
			}

			// emplacement du coffre client
			if( self::$coffre_path == "" ){
				self::$coffre_path = self::$defaut_coffre_path;
			}

			self::$coffre_path .= DIRECTORY_SEPARATOR . self::$coffre_ID;

			self::construct_coffre_folder();
			
			return true;
			

		}


		static private function construct_coffre_folder() : bool{

			if( !is_dir( self::$coffre_path ) ){
				if ( mkdir(self::$coffre_path, 0755, true) ) {
					return true;
				}else{
					die("une erreur lors de la création du coffre");
				}
			}else{
				return true; // coffre exist
			}
		}

	}
