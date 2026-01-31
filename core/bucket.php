<?php

	namespace Carapace;

	class Bucket{


		// variable administrable
		// static private $coffre_path = "";

		// static public $coffre_ID 			= "";


		static public $current_bucket_path		= "";


		public static function init(){
			self::construct_vault();
		}


		static public function prepare_bucket() : bool{

			if( self::create_bucket() ){
				return true;
			}

			// gestion d'erreur

			return false;
		}


		static private function create_bucket() : bool {
			
			$filename = generate_unique_filename( Vault::get_vault_path() );

			// todo : add filter here

			if( create_directory( $filename ) ){
				self::$current_bucket_path = $filename;
				return true;
			}
 
			return false;

		}

	}
