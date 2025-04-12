<?php

	namespace Carapace;

	class Bucket{


		// variable administrable
		// static private $coffre_path = "";

		// static public $coffre_ID 			= "";

		static public $vault_path_meta_name 	= "carapace_storage_path";
		static public $defaut_coffre_path 		= "./SECUREDATA";

		static public $current_bucket_path		= "";


		public static function init()
		{
			self::construct_vault();
		}


		/*
		 * Fonction qui permet d'obtenir l'emplacement du coffre fort
		*/
		public static function get_vault_path() : string
		{
			return get_option( self::$vault_path_meta_name );
		}


		/*
		 * Construction du coffre fort de base
		*/
		static private function construct_vault() : void{

			$vault_path = self::get_vault_path();

			if( create_directory($vault_path) == false ){
				// error vault construction
			}

		}


		static public function prepare_bucket() : bool
		{

			if( self::create_bucket() )
			{
				return true;
			}

			// gestion d'erreur

			return false;
		}


		static private function create_bucket() : bool {
			
			$filename = generate_unique_filename( self::get_vault_path() );
			
			if( create_directory( $filename ) ){
				self::$current_bucket_path = $filename;
				return true;
			}
 
			return false;

		}

	}
