<?php

	namespace Carapace;

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	use Carapace\Storage;

	class DataInterface{

		private static $current_bucket_ID 		= null;

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
					'singular_name'	=> 'Bucket'
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
				),
				'capabilities' => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap' => true,
			));

			register_taxonomy(
				'origin',
				array('bucket'),
				array(
					'label' 			=> "Origine de la sauvegarde",
					'hierarchical' 		=> true,
					'show_in_menu' 		=> true,
					'show_admin_column' => false,
					'show_in_rest' 		=> true,
					'show_admin_column' => true
				)
			);

		}


		public function meta_box_for_bucket( $screen ) : void{

			if( $screen != "bucket" ){
				return;
			}

			// récupération du post courant
			$bucket = get_post();
			if( !isset($bucket->ID) ){
				return;
			}
			self::$current_bucket_ID = $bucket->ID;

			// ajout des meta box
			add_meta_box(
				'carapace_bucket_data',
				'Données principale',
				array($this, 'display_bucket_data'),
				$screen
			);

			add_meta_box(
				'bucket_meta_data',
				'Meta données du bucket',
				array($this, 'display_bucket_meta_data'),
				$screen
			);
		}


		public function display_bucket_data() : void{

			$bucket_path 	= get_post_meta(self::$current_bucket_ID, self::$data_bucket_path, true );
			$data 			= Storage::extract_data_from_bucket_path( $bucket_path );
			$data_structure = get_post_meta(self::$current_bucket_ID, self::$data_structure_meta_name, true );

			if( !empty($data_structure["no_secure"]) ){
				$no_secure_data = json_decode($data["no_secure"], true);
				$this->display_data($data_structure["no_secure"], $no_secure_data);
			}

			if( !empty($data_structure["secure"]) ){
				$this->display_data($data_structure["secure"], $data["secure"]);
			}

		}


		public function display_data( array $structure, $data ){
	
			if( !empty($structure) ){
				?>
				<table class="data-group">
					<?php
						foreach($structure as $key ){

							if( isset($data[$key]) ){
								?>
								<tr>
									<th><?php echo esc_html( $key ); ?></th>
									<td><?php echo esc_html( $data[$key] ); ?></td>
								</tr>
								<?php
							}else{
								?>
								<tr>
									<th><?php echo esc_html( $key ); ?></th>
									<td class="crypted_data">&nbsp;</td>
								</tr>
								<?php
							}

						}
					?>
				</table>
				<?php
			}
		}


		public function display_bucket_meta_data() : void{

			$bucket_path 	= get_post_meta(self::$current_bucket_ID, self::$data_bucket_path, true );
			$data_structure = get_post_meta(self::$current_bucket_ID, self::$data_structure_meta_name, true );
			$shamail 		= get_post_meta(self::$current_bucket_ID, self::$data_shamail_meta_name, true );

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