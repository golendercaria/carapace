<?php

	namespace Carapace;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use Carapace\crypto_helper;
	

	class Vault{

		static public $vault_path_meta_name 					= "carapace_storage_path";
		//private static $password_option_name 					= "carapace_encrypted_password";
		private static $automatic_lock_vault_delay_option_name 	= "carapace_automatic_lock_vault_delay";
		public static $carapace_password 						= null;
		public static $error_on_vault							= null;
		private static $remaining_time_to_autolock_vault 		= null;

		public function __construct(){

			if (session_status() == PHP_SESSION_NONE) {
				session_start();
			}

			self::check_for_auto_lock();
			
			add_action('wp_before_admin_bar_render', array( $this, 'status_lock_vault_on_admin_bar' ) );

			add_action('wp_ajax_carapace_unlock_vault', array( $this, 'unlock_vault_for_session' ) );
			add_action('wp_ajax_carapace_lock_vault', array( $this, 'lock_vault_for_session' ) );

		}


		private static function check_for_auto_lock(){
			if( isset($_SESSION["carapace_time_for_autolock_vault"]) ){
				self::$remaining_time_to_autolock_vault = $_SESSION["carapace_time_for_autolock_vault"] - time();
				if( self::$remaining_time_to_autolock_vault < 0 ){
					self::lock_vault();
				}
			}
		}


		public static function check_is_vault_folder_can_be_create( string $path ){
		
			if( file_exists($path) ){
				self::$error_on_vault = "Impossible de créé le coffre fort à cet emplacement, un fichier sous ce nom existe.";
				return false;
			}
		
			$parent_directory = dirname($path);
			if (!is_dir($parent_directory) || !is_writable($parent_directory)) {
				self::$error_on_vault = "Impossible de créé le coffre fort à l'emplacement défini, droit d'écriture invalide.";
				return false;
			}

			return true;

		}


		/*
		 * Fonction qui permet d'obtenir l'emplacement du coffre fort
		*/
		public static function get_vault_path() : string{
			return get_option( self::$vault_path_meta_name );
		}


		/*
		 * Fonction qui permet d'obtenir l'emplacement du coffre fort
		*/
		public static function get_automatic_lock_vault_delay() : int{
			return get_option( self::$automatic_lock_vault_delay_option_name );
		}


		/*
		 * Construction du coffre fort de base
		*/
		static public function construct_vault( $vault_path, $automatic_lock_vault_delay ) : bool{

			update_option( self::$automatic_lock_vault_delay_option_name, $automatic_lock_vault_delay );

			// TODO make .htaccess for secure folder
			return create_directory($vault_path);
		}




		/**
		 * Obtenir le status de création du coffre fort
		 */
		public static function vault_has_initiazed() : bool{

			return ( 
				Client::get_public_key() != ""
				&& Client::get_encrypted_private_key() != ""
				&& Vault::get_vault_path() != ""
				&& Vault::get_automatic_lock_vault_delay() != ""
			);

		}


		// public static function get_encrypted_vault_password(){
		// 	return get_option( self::$password_option_name );
		// }


		public function status_lock_vault_on_admin_bar() : void{


			global $wp_admin_bar;

			if( isset($_SESSION["carapace_rsa_key"]) ){

				$title_information = '';
				if( self::$remaining_time_to_autolock_vault !== null ){
					$title_information .= ' (fermeture du coffre fort dans ' . carapace_display_sec_for_human(self::$remaining_time_to_autolock_vault) . ')';
				}

				$wp_admin_bar->add_node(array(
					'id'    => 'vault_status',
					'title' => '<div class="title">Accès à la carapace' . $title_information . '</div>
								<button>Verouiller la carapace</button>',
					'meta'  => array(
						'class' => 'unlock'
					)
				));
			}else{
				$wp_admin_bar->add_node(array(
					'id'    => 'vault_status',
					'title' => '<div class="title">Dévérouiller la carapace</div>
								<input type="password" value="" />',
					'meta'  => array(
						'class' => 'lock'
					)
				));
			}

		}


		// public static function init_carapace(){
			
		// 	self::$carapace_password = Client::init_client_password();

		// 	Client::init_client( self::$carapace_password );

		// }


		public function unlock_vault_for_session(){

			if (isset($_POST['password']) ) {

				$_SESSION["carapace_client_password"] = $_POST['password'];

				$private_crypted_rsa_key 	= get_option( Client::$rsa_private_option_name );

				$private_rsa_key 			= crypto_helper::symetric_decrypt( $private_crypted_rsa_key, $_SESSION["carapace_client_password"] );

				preg_match('/-----BEGIN PRIVATE KEY-----.*-----END PRIVATE KEY-----/s', $private_rsa_key, $match);

				if( isset($match[0]) ){
					self::set_time_for_autolock();
					Monitor::tracking_action_on_carapace('Dévérouillage de la Carapace');
					$_SESSION["carapace_rsa_key"] = $private_rsa_key;
					wp_send_json_success(true);
				}else{
					Monitor::tracking_action_on_carapace('Tentative de dévérouillage de la Carapace (echec)');
					wp_send_json_error('invalid password');
				}

			}

		}


		public static function lock_vault_for_session(){
			self::lock_vault();
			wp_send_json_success(true);
		}


		private static function set_time_for_autolock(){
			$carapace_automatic_lock_vault_delay = self::carapace_automatic_lock_vault_delay();
			if( $carapace_automatic_lock_vault_delay > 0 ){
				$_SESSION["carapace_time_for_autolock_vault"] = time() + $carapace_automatic_lock_vault_delay;
			}
		}


		public static function carapace_automatic_lock_vault_delay(){
			return get_option( self::$automatic_lock_vault_delay_option_name );
		}


		private static function lock_vault(){
			unset($_SESSION["carapace_client_password"]);
			unset($_SESSION["carapace_rsa_key"]);
			unset($_SESSION["carapace_time_for_autolock_vault"]);
		}
	}