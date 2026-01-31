<?php

	/*
	Plugin Name: Carapace
	Plugin URI: https://binsfeld.lu
	Description: Un plugin générique de secu pour wordpress
	Version: 1.0
	Author: Votre Nom
	Author URI: http://votre-site.com
	License: GPL2
	*/

	namespace Carapace;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	define('CARAPACE_PLUGIN_PATH', __DIR__);

	require_once 'libs/crypto.lib.php';
	require_once 'libs/utils.lib.php';
	require_once 'core/vault.php';
	require_once 'core/client.php';
	require_once 'core/monitor.php';
	
	/*
	require_once 'core/bucket.php';
	require_once 'core/storage.php';
	require_once 'admin/data_interface.php';
	require_once 'admin/plugin_option.php';

	require_once 'core/interceptor.php';
	*/
	require_once 'interface/interface_plugin.php';

	//use Carapace\Bucket;
	
	class Carapace{
		
		

		public function __construct(){

			new Monitor();
			new Vault();
			new PluginInterface();

		}

		public static function init_carapace( string $password, string $vault_path, int $automatic_lock_vault_delay ) : bool|string{

			// création de la pair de clé RSA
			$carapace_password = Client::init_client( $password );

			// création du vault
			if( Vault::construct_vault( $vault_path, $automatic_lock_vault_delay ) === false ){
				return "Erreur lors de la création du coffre fort.";
			}else{
				update_option( Vault::$vault_path_meta_name , $vault_path );
			}

			return true;

		}



	}

	new Carapace();



/*

		
			// new DataInterface();
			// new Vault();
			// new Monitor();
			// new Interceptor();

			// add_action( 'init', array( $this, 'detect_submission' ) );
			*/