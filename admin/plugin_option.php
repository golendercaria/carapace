<?php

use Carapace\Bucket;
use Carapace\Client;

add_action( 'wp_head', 'style_message_plugin' );
function style_message_plugin() {
    echo '
    <style>
        .message-plugin {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }
    </style>
    ';
}

add_action( 'admin_menu', 'mon_plugin_menu' );
function mon_plugin_menu() {
    add_menu_page(
        'Carapace',
        'carapace',
        'manage_options',
        'carapace',
        'mon_plugin_page_options',
        'dashicons-admin-generic',
        100
    );
}

// Fonction pour afficher la page d'options
function mon_plugin_page_options() {

	if ( ! current_user_can( 'manage_options' ) ) 
	{
		return;
	}

	// Enregistrer les options
	if ( isset( $_POST[ Carapace\Bucket::$vault_path_meta_name ] ) ) 
	{
		update_option( Carapace\Bucket::$vault_path_meta_name , sanitize_text_field( $_POST[ Carapace\Bucket::$vault_path_meta_name ] ) );
		echo '<div class="updated"><p>Les paramètres ont été sauvegardés !</p></div>';
	}

	// Enregistrer les options
	if ( isset( $_POST["rsa_password"]) ) 
	{

		Carapace\Client::init_client($_POST["rsa_password"]);

	}

	?>
	<div class="wrap">
		<h1>Paramètres de la carapace</h1>
		<form method="post" action="">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="mon_plugin_message">Chemin de stockage</label></th>
					<td>
						<input type="text" name="<?php echo Carapace\Bucket::$vault_path_meta_name; ?>" id="mon_plugin_message" 
							value="<?php echo esc_attr( get_option( Carapace\Bucket::$vault_path_meta_name, Carapace\Bucket::$defaut_coffre_path ) ); ?>" 
							class="regular-text" />
					</td>
				</tr>
			</table>
			<?php submit_button( 'Sauvegarder les paramètres' ); ?>
		</form>

		<h1>Initialiser le client</h1>
		<form method="post" action="">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="p">Mot de passe</label></th>
					<td>
						<input type="text" name="rsa_password" id="p" 
							value="yannVang_securitykeyEn32Octetsvp" 
							class="regular-text" 
							pattern=".{32}" 
							title="Le mot de passe doit contenir exactement 16 caractères" 
							required
							 />
					</td>
				</tr>
			</table>
			mdp de test : yannVang_securitykeyEn32Octetsvp
			<?php submit_button( 'Générer la clé' ); ?>
		</form>

		<h1>Debug</h1>

		<div>
			<h2>clé publique :</h2>
			<textarea cols="50" rows="10"><?php echo get_option( Carapace\Client::$rsa_public_option_name ); ?></textarea>

			<h2>clé privé crypté :</h2>
			<textarea cols="50" rows="10"><?php echo get_option( Carapace\Client::$rsa_private_option_name ); ?></textarea>
		
			<h2>Etat du bucket</h2>
			<?php
				$vault_path = Carapace\Bucket::get_vault_path();
				if( is_dir($vault_path) ){
					echo "Le vault est créé";
				}else{
					echo "Le vault n'existe pas !";
				}
			?>
		</div>


	</div>
	<?php
}