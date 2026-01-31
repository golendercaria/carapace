<?php

	namespace Carapace;


	class PluginInterface{

		private $configuration_error = array();

		public function __construct(){

			add_action( 'admin_menu', array($this, 'add_carapace_menu') );
			add_action('admin_post_carapace_save_settings', 'carapace_save_settings');
			
			wp_enqueue_style('carapace-admin-style',plugin_dir_url(__FILE__) . 'css/admin.css',	array(),'1.0','all');
			wp_enqueue_script('carapace-admin-js',plugin_dir_url(__FILE__) . 'js/admin.js',array(),'1.0',true);
			
			wp_localize_script('carapace-admin-js', 'carapace_js_data', array(
				'ajax_URL' => admin_url('admin-ajax.php')
			));
		}

		public function add_carapace_menu() {
			add_menu_page(
				'Carapace',
				'carapace',
				'manage_options',
				'carapace',
				array($this, 'carapace_plugin_page'),
				'dashicons-shield',
				100
			);

			add_submenu_page(
				'carapace',
				'Activités',
				'Activités',
				'manage_options',
				'edit.php?post_type=carapace_monitor'
			);
		}


		// Fonction pour afficher la page d'options
		public function carapace_plugin_page() {

			if ( ! current_user_can( 'manage_options' ) ){
				return;
			}

			$this->init_carapace_configuration();

			?>
			<div class="wrap" id="carapace_wrapper">

				<h1>Carapace</h1>
				<?php
					if( Vault::vault_has_initiazed() === false || ( isset($_GET["reconfigure"]) && $_GET["reconfigure"] == 'true' ) ){
						$this->display_carapace_configurator();
					}else{
						$this->display_carapace_status();
					}
				?>

			</div>
			<?php
		}


		public function display_carapace_status(){

			$link_to_reconfigure = add_query_arg(
				array(
					'reconfigure' => 'true'
				),
				admin_url('admin.php?page=carapace')
			);

			?>
			<h2>Configuration cryptographique</h2>
			<p>La carapace est initialisé.</p>
			<div class="configuration">
				<div class="config">
					<h3>Emplacement du coffre fort</h3>
					<input type="text" value="<?php echo Vault::get_vault_path(); ?>" class="config_value" readonly />
				</div>
				<div class="config">
					<h3>Délai de vérouillage automatique du coffre fort</h3>
					<input type="text" value="<?php echo Vault::get_automatic_lock_vault_delay(); ?>" class="config_value" readonly />
				</div>
				<div class="config">
					<h3>Clé asymétrique</h3>
					<textarea class="config_value" readonly><?php echo Client::get_public_key(); ?></textarea>
				</div>
				<div class="config">
					<h3>Clé privée chiffré</h3>
					<textarea class="config_value" readonly><?php echo Client::get_encrypted_private_key(); ?></textarea>
				</div>

		
				<a href="<?php echo $link_to_reconfigure; ?>" class="for-submit">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg>
					Reconfigurer la Carapace
				</a>
	
			</div>
			<?php
		}


		public function init_carapace_configuration(){

			if( 
				!empty($_POST) 
				&& isset($_POST["carapace_init_configuration"])
			){

				// contrôle du password
				if( !isset($_POST["carapace_password"]) || $_POST["carapace_password"] == "" ){
					$this->configuration_error = "Veuillez saisir un mot de passe.";
					return;
				}

				// check du vault directory
				$vault_directory = CARAPACE_PLUGIN_PATH . '/.SECUREDATA';
				if( !isset($_POST["vault_directory"]) || $_POST["vault_directory"] !== "" ){
					$vault_directory = $_POST["vault_directory"];
				}

				if( Vault::check_is_vault_folder_can_be_create($vault_directory) === false ){
					$this->configuration_error = Vault::$error_on_vault;
					return;
				}

				$init_carapace_response = Carapace::init_carapace(
					$_POST["carapace_password"],
					$vault_directory,
					(int) $_POST["automatic_lock_vault_delay"]
				);
				
				if(	$init_carapace_response !== true ){
					$this->configuration_error = $init_carapace_response;
					return;
				}

			}

		}


		public function display_carapace_configurator(){

			?>
			<form id="carapace-configuration-form" method="POST">
				
				<input type="hidden" name="carapace_init_configuration" value="1" />

				<h2>Configuration cryptographique</h2>
				<p>Le système fonctionne comme un coffre-fort numérique : lors de l'activation, une paire de clés est créée pour l'utilisateur. Chaque donnée est chiffrée avec une clé secrète très robuste (AES-256), puis cette clé est elle-même verrouillée à l'aide de la clé publique, ce qui permet au système de chiffrer les données sans jamais pouvoir les lire. Seul le mot de passe de l'utilisateur permet de déverrouiller la clé privée et donc d'accéder aux données, garantissant que personne d'autre ne peut les consulter.</p>
				
				<?php
					if( !empty($this->configuration_error) ){
						?>
						<div class="carapace_error">
							<?php echo $this->configuration_error; ?>
						</div>
						<?php
					}
				?>

				<div class="step" data-value="1">
					<h3>Mot de passe maître</h3>
					<p>Mot de passe permettant l'accès aux données chiffrées. Veuillez à ne surtout pas le divulgé et à bien conserver votre mot de passe en lieu sûr.</p>

					<div class="field">
						<label for="">Créez votre mot de passe maître</label>
						<div class="wrapper-input">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-key-round absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-muted-foreground"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"></path><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>
							<input type="text" name="carapace_password" value="<?php echo isset($_POST['carapace_password']) ? htmlspecialchars($_POST['carapace_password']) : 'golendercaria'; ?>" placeholder="••••••••••" />
						</div>
					</div>
				</div>

				<div class="step" data-value="2">
					<h3>Emplacement du coffre fort</h3>
					<p>Où stocker vos données chiffrées</p>
					
					<div class="field">
						<label for="">Chemin par défaut</label>
						<p>Laisser vide pour créer le vault au seins du dossier du plugin.</p>
						<div class="wrapper-input">
							<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-lock"><rect width="8" height="5" x="14" y="17" rx="1"></rect><path d="M10 20H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H20a2 2 0 0 1 2 2v2.5"></path><path d="M20 17v-2a2 2 0 1 0-4 0v2"></path></svg>
							<input type="text" name="vault_directory" value="<?php echo isset($_POST['vault_directory']) ? htmlspecialchars($_POST['vault_directory']) : ''; ?>" placeholder="<?php echo CARAPACE_PLUGIN_PATH . '/.SECUREDATA'; ?>" />
						</div>
					</div>
				</div>

				<div class="step" data-value="3">
					<h3>Verrouillage automatique</h3>
					<p>Délai avant fermeture de la carapace</p>
					
					<div class="field">

						<div class="wrapper-input">
					
							<label>
								<input type="radio" name="automatic_lock_vault_delay" value="">
								<div class="description">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" x2="9" y1="12" y2="12"></line></svg>
									<p>Manuel</p>
									<p>Verrouillage manuel</p>
								</div>
							</label>
							<label>
								<input type="radio" name="automatic_lock_vault_delay" value="3600" checked>
								<div class="description">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-zap"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"></path></svg>
									<p>Après 1 heure</p>
									<p>Sécurité maximale - Recommandé</p>
								</div>
							</label>

							<label>
								<input type="radio" name="automatic_lock_vault_delay" value="14400">
								<div class="description">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-timer"><line x1="10" x2="14" y1="2" y2="2"></line><line x1="12" x2="15" y1="14" y2="11"></line><circle cx="12" cy="14" r="8"></circle></svg>
									<p>Après 4 heure</p>
									<p>Sécurité élevée</p>
								</div>
							</label>

							<label>
								<input type="radio" name="automatic_lock_vault_delay" value="28800">
								<div class="description">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
									<p>Après 8 heure</p>
									<p>Une journée de travail</p>
								</div>
							</label>

							<label>
								<input type="radio" name="automatic_lock_vault_delay" value="86400">
								<div class="description">
									<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
									<p>Après 8 heure</p>
									<p>Sécurité minimale</p>
								</div>
							</label>
						</div>
					</div>
				</div>


				<button class="for-submit">
					<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg>
					Activer la Carapace
				</button>

			</form>


			<?php 
		
			/*
			// get vault status

				// need init config


				

				Configuration sur système cryptographique
				1) Choissiez un mot de passe
					- création d'une paire de clé RSA
					- chiffre RSA privé via password => AES
				
				2) Choissir un emplacement pour le coffre fort
					- chemin par défaut
					OU
					- plugin_dir/SECUREDATA

				3) Préférence de sécurité
					- Vérouiller automatiquement la carapace
						- jamais (vérouillage manuel ou lors de la déconnexion)
						- après 24H
						- après 8H
						- après 4H
						- après 60min
			<?php

			*/

		}
	}