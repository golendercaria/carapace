<?php

	namespace Carapace;

	class Monitor{
			
		//public static $logged_one_thing = null;
		
		public function __construct(){
			add_action( 'init', array($this, 'register_custom_post_type'), 10 );
			add_action( 'tracking_action_on_carapace', array($this, 'tracking_action_on_carapace'), 10 );
	
			add_action( 'admin_init', array($this, 'log_view_archives_data') );
			add_action( 'current_screen', array($this, 'log_read_bucket') );
		}


		public function register_custom_post_type(){

			register_post_type( 'carapace_monitor', array(
				'labels'              => array(
					'name'      	=> 'Activités',
					'singular_name'	=> 'Activité',
					'add_new'		=> 'Ajouter une action',
					'add_new_item'	=> 'Ajouter une action'
				),
				'public'              	=> false,
				'show_ui'             	=> true,
				'show_in_menu'        	=> false,
				'show_in_admin_bar'   	=> true,
				'show_in_nav_menus'   	=> true,
				'publicly_queryable'  	=> false,
				'exclude_from_search' 	=> false,
				'has_archive'         	=> false,
				'query_var'           	=> false,
				'can_export'          	=> false,
				'capability_type'     	=> 'post',
				'menu_icon'				=> 'dashicons-welcome-view-site',
				'supports'            	=> array(
					'title'
				)
			));
		}



		// methode de log
		public static function tracking_action_on_carapace( string $message ) : void{

			$current_user = wp_get_current_user();

			$date = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
			
			$args = array(
				'post_title'    => $message . " pour l'utilisateur " . $current_user->display_name . " (ID " . $current_user->ID . ") le " . $date->format('d/m/Y H\hi'),
				'post_content'  => '',
				'post_status'   => 'private',
				'post_author'   => 1,
				'post_type'     => 'carapace_monitor'
			);

			$post_id = wp_insert_post($args);

		}

		public function log_view_archives_data(){
		
			if( isset($_GET['post_type']) && $_GET['post_type'] === 'bucket' && is_admin() ){
				Monitor::tracking_action_on_carapace('Accès à la liste des buckets');
			}

		}

		public function log_read_bucket( $screen ){
			if( $screen->id === 'bucket' && $screen->base === 'post'){
				$post_id = intval($_GET['post']);
				$vault_is_unlock = isset($_SESSION["carapace_rsa_key"]) && $_SESSION["carapace_rsa_key"] != "";
				if( $vault_is_unlock ){
					Monitor::tracking_action_on_carapace('Accès aux bucket ' . $post_id . ' en mode lecture des données sécurisés');
				}else{
					Monitor::tracking_action_on_carapace('Accès aux bucket ' . $post_id . ' avec une restriction d\'accès aux données sécurisés');
				}
			}
		}


	


	}