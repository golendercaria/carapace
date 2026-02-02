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
				'Données',
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
				$this->display_data("no_secure", $data_structure, $no_secure_data);
			}

			if( !empty($data_structure["secure"]) ){
				$this->display_data("secure", $data_structure, $data["secure"]);
			}

		}


		public function display_data( string $type, array $structure, $data ){
	
			if( !empty($structure[ $type ]) ){

				?>
				<div class="carapace_data">
					<?php
						if( $type === "no_secure" ){
							?>
							<div class="title">
								<div class="ico">
									<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-lock"><rect width="8" height="5" x="14" y="17" rx="1"></rect><path d="M10 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v2.5"></path><path d="M20 17v-2a2 2 0 1 0-4 0v2"></path></svg>
								</div>
								<h3>Donnés privées</h3>
								<p>Informations accéssibles via un accès Wordpress</p>
							</div>
							<?php
						}elseif( $type === "secure" ){
							?>
							<div class="title">
								<div class="ico">
									<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"></path><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>
								</div>
								<h3>Donnés chiffrées</h3>
								<p>Informations accéssibles via un accès Wordpress et une clé de déchiffrement</p>
							</div>
							<?php
						}

						foreach($structure[ $type ] as $key ){

							if( isset($data[$key]) ){
								?>
								<label><?php echo esc_html( $key ); ?></label>
								<input type="text" value="<?php echo esc_html( $data[$key] ); ?>" readonly />
								<?php
							}else{
								?>
								<label><?php echo esc_html( $key ); ?></label>
								<input type="text" value="••••••••••••••••••••••••••••••••" readonly class="crypted" />
								<?php
							}

						}
					?>
				</div>
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