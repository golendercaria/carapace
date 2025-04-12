<?php

	namespace Carapace;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use Carapace\Storage;

	class DataInterface{

		public static $data_structure_meta_name = "carapace_bucket_data_structure";
		public static $data_shamail_meta_name 	= "carapace_bucket_shamail";
		public static $data_bucket_path			= "carapace_bucket_path";


		public function __construct(){
			
			add_action( 'init', array($this, 'register_custom_post_type'), 10 );
			
			add_action('add_meta_boxes', array( $this, 'meta_box_for_bucket' ) );

		}


		public function register_custom_post_type() : void{
			
			register_post_type( 'bucket', array(
				'labels'              => array(
					'name'      	=> 'Bucket',
					'singular_name'	=> 'Bucket',
					'add_new'		=> 'Ajouter un bucket',
					'add_new_item'	=> 'Ajouter un bucket'
				),
				'public'              	=> false,
				'show_ui'             	=> true,
				'show_in_menu'        	=> true,
				'show_in_admin_bar'   	=> true,
				'show_in_nav_menus'   	=> true,
				'publicly_queryable'  	=> false,
				'exclude_from_search' 	=> false,
				'has_archive'         	=> false,
				'query_var'           	=> false,
				'can_export'          	=> false,
				'capability_type'     	=> 'post',
				'menu_icon'				=> 'dashicons-database',
				'supports'            	=> array(
					'title'
				)
			));

		}


		public static function register_data( string $title )
		{

			$post_data = array(
				'post_title'   => $title,
				'post_status'  => 'private',
				'post_type'    => 'bucket'
			);
	

			$post_id = wp_insert_post($post_data);

			if( $post_id ){
				
				add_post_meta($post_id, self::$data_bucket_path, Bucket::$current_bucket_path );
				add_post_meta($post_id, self::$data_structure_meta_name, Storage::$data_structure_from_data );
				add_post_meta($post_id, self::$data_shamail_meta_name, Storage::$shamail_from_data );

				pre( get_post_meta($post_id) );

			}else{

				// gestion d'erreur
				// - post type n'existe pas
				// - titre absent
				// - ..
			}

		}


		public function meta_box_for_bucket( $screen ) : void
		{

			if( $screen != "bucket" ){
				return;
			}

			add_meta_box(
				'bucket_meta_data',
				'Meta données du bucket',
				array($this, 'display_bucket_meta_data'),
				$screen
			);
		}


		public function display_bucket_meta_data() : void
		{

			$bucket = get_post();

			if( !isset($bucket->ID) ){
				return;
			}

			$bucket_path 	= get_post_meta($bucket->ID, self::$data_bucket_path, true );
			$data_structure = get_post_meta($bucket->ID, self::$data_structure_meta_name, true );
			$shamail 		= get_post_meta($bucket->ID, self::$data_shamail_meta_name, true );

			?>
			<table class="acf-table" style="text-align:left;">
				<tr>
					<th class="acf-th">Bucket Path</th>
					<td><?php echo $bucket_path ?></td>
				</tr>
				<tr>
					<th class="acf-th">Structure de données</th>
					<td><?php pre($data_structure); ?></td>
				</tr>
				<tr>
					<th class="acf-th">Email (sha256)</th>
					<td><?php echo $shamail ?></td>
				</tr>
			</table>
			<?php
		}

	}