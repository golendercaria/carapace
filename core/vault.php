<?php

	namespace Carapace;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use Carapace\crypto_helper;
	

	class Vault{

		private static $password_option_name 	= 'carapace_encrypted_password';
		public static $carapace_password 		= null;

		public function __construct(){

			if (session_status() == PHP_SESSION_NONE) {
				session_start();
			}
			
			add_action('wp_before_admin_bar_render', array( $this, 'status_lock_vault_on_admin_bar' ) );

			add_action('wp_ajax_carapace_unlock_vault', array( $this, 'unlock_vault_for_session' ) );
			add_action('wp_ajax_carapace_lock_vault', array( $this, 'lock_vault_for_session' ) );

		}



		/**
		 * Obtenir le status de création du coffre fort
		 */
		public static function vault_has_initiazed() : bool{
			$encrypted_vault_password = self::get_encrypted_vault_password();
			if( $encrypted_vault_password && $encrypted_vault_password !== ""){
				return true;
			}else{
				return false;
			}
		}


		public static function get_encrypted_vault_password(){
			return get_option( self::$password_option_name );
		}


		public function status_lock_vault_on_admin_bar() : void
		{
			global $wp_admin_bar;

			if( isset($_SESSION["carapace_rsa_key"]) ){
				$wp_admin_bar->add_node(array(
					'id'    => 'vault_status',
					'title' => '<div class="title">Accès à la carapace</div>
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


		public static function init_carapace(){
			
			self::$carapace_password = Client::init_client_password();

			Client::init_client( self::$carapace_password );

		}


		public function unlock_vault_for_session()
		{
			if (isset($_POST['password']) ) {

				$_SESSION["carapace_client_password"] = $_POST['password'];

				// Kk7yBbHZS20oTZHdZfj9mmA6Ng+OrfWHqMA/VgXW6OQ=
				// Kk7yBbHZS20oTZHdZfj9mmA6Ng+OrfWHqMA/VgXW6OQ=
				// pre($_SESSION["carapace_client_password"]);
				// die();

				// tentative de décryptage de la clé privé
				$private_crypted_rsa_key 	= get_option( Client::$rsa_private_option_name );



				$private_rsa_key 			= crypto_helper::symetric_decrypt( $private_crypted_rsa_key, $_SESSION["carapace_client_password"] );

				preg_match('/-----BEGIN PRIVATE KEY-----.*-----END PRIVATE KEY-----/s', $private_rsa_key, $match);

				if( isset($match[0]) ){
					Monitor::tracking_action_on_carapace('Dévérouillage de la Carapace');
					$_SESSION["carapace_rsa_key"] = $private_rsa_key;
					wp_send_json_success(true);
				}else{
					Monitor::tracking_action_on_carapace('Tentative de dévérouillage de la Carapace (echec)');
					wp_send_json_error('invalid password');
				}

			}

		}


		public function lock_vault_for_session(){
			unset($_SESSION["carapace_client_password"]);
			unset($_SESSION["carapace_rsa_key"]);
			wp_send_json_success(true);
		}

	}