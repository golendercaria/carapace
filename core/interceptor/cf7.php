<?php

	namespace Carapace;

	use Carapace\Storage;

	/*
	 * Système qui permet d'intercepter les demandes emis depuis
	 * les formulaires CF7 et de stocker les données de manière chiffré
	*/
	class CF7Interceptor{

		public $origin = 'CF7';

		public function __construct()
		{
			add_action( 'wpcf7_before_send_mail', array( $this, 'intercept_cf7_form' ), 10, 2 );
		}

		public function intercept_cf7_form( $contact_form, $abort )
		{

			$submission = \WPCF7_Submission::get_instance();

			$this->contact_form = $contact_form;

			$tags_need_to_be_encrypt = $this->find_encrypted_tag( $contact_form );


			//$this->treat_file( $submission );
			if ($submission) {
				$submission_data = $submission->get_posted_data();

				// prepare data
				$data = $this->prepare_data($tags_need_to_be_encrypt, $submission_data);
				
				//$this->generate_title( $contact_form, $submission_data );
				Storage::store( array($this, 'generate_title'), $data, $this->origin );

			}


		}


		public function prepare_data($tags_need_to_be_encrypt, $submission_data){
			
			$data = array(
				"no_secure" => array(),
				"secure" 	=> array()
			);

			// si pas de tag spécifique à chiffré on chiffre tout !
			$encrypt_all = empty($tags_need_to_be_encrypt) ? true : false;

			foreach($submission_data as $key => $value){
				if( in_array($key, $tags_need_to_be_encrypt) || $encrypt_all === true ){
					$data["secure"][ $key ] = $value;
				}else{
					$data["no_secure"][ $key ] = $value;
				}
			}
			
			return $data;

		}

		// [textarea your-message encrypt]
		public function find_encrypted_tag( &$contact_form ){
			
			$tags_need_to_be_encrypt = array();

			$tags = $contact_form->scan_form_tags();
			foreach($tags as $tag){
				if( !empty($tag["options"]) ){
					foreach($tag["options"] as $option){
						if( $option === "encrypt" ){
							$tags_need_to_be_encrypt[] = $tag["name"];
						}
					}
				}
			}

			return $tags_need_to_be_encrypt;

		}

		public function generate_title()
		{
			return sprintf("Soumission de %s depuis le formulaire de contact \"%s\"", Storage::$email, $this->contact_form->name() );
		}

		public function treat_file( $submission )
		{

			// récupération des fichiers
			$files            = $submission->uploaded_files();

			// $uploaded_files   = array();
			// foreach ($_FILES as $file_key => $file) {
			// 	array_push($uploaded_files, $file_key);
			// }
			foreach ($files as $file_key => $file) {

				echo $file_key;
				pre($file);
				// $file = is_array( $file ) ? reset( $file ) : $file;
				// if( empty($file) ) continue;
				// copy($file, $cfdb7_dirname.'/'.$time_now.'-'.$file_key.'-'.basename($file));

				// récupération des fichier

					// dépot dans le répertoire de chiffrement
						// ex : files/...
					// renommage du fichier
					// pour chaque fichier générer un chiffrement distinct
					// enregistrement des liens dans le post type
					// suppression des fichiers d'origines
			}

			pre($files);
			die();
		}

	}