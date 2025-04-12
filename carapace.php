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

	require_once 'libs/utils.lib.php';
	require_once 'libs/crypto.lib.php';
	require_once 'core/bucket.php';
	require_once 'core/client.php';
	require_once 'core/storage.php';
	require_once 'admin/data_interface.php';
	require_once 'admin/plugin_option.php';

	//use Carapace\Bucket;

	class Carapace{

		public function __construct()
		{
			// interface Wordpress
			new DataInterface();

			add_action( 'init', array( $this, 'detect_submission' ) );
		}


		private function store_data( array $data )
		{

			if( !isset($data["email"]) ){
				die("error email not provided");
				return;
			}

			pre($data);



		}


		public function detect_submission()
		{

			if( isset($_GET["submission"]) ){

				$data = array(
					"prenom" 	=> "Sandrine",
					"nom" 		=> "Hoeffler",
					"email" 	=> "sandrine.hoeffler@gmail.com"
				);

				Storage::store( "from test URL", $data );

				die("submission");
			}

		}



	}


	new Carapace();