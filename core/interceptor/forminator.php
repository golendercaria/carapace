<?php

namespace Carapace;
use Carapace\Storage;

class ForminatorInterceptor {

	public $origin = 'Forminator';
	protected $form;

	public function __construct() {
		// Le hook fournit 3 args → on met 3
		add_action('forminator_custom_form_submit_before_set_fields', [$this,'intercept'],10,3);

	}


	public function intercept($entry,$form_id,$fields_posted){

		$this->form = \Forminator_API::get_form($form_id);
		$fields 	= $this->form->get_fields();       // structure du formulaire
		$submission = $fields_posted;               // valeurs envoyées

		// $this->contact_form = $contact_form;

		if ($submission) {

			// // Récupération des tags du formulaire
			// $tags = $this->contact_form->scan_form_tags();

			// prepare data
			$data = $this->prepare_data(array(), $submission);

			//$this->generate_title( $contact_form, $submission_data );
			Storage::store( array($this, 'generate_title'), $data, $this->origin );

		}

	}

	
	public function prepare_data($tags_need_to_be_encrypt, $submission_data){
		
		$data = array(
			"no_secure" => array(),
			"secure" 	=> array()
		);


		foreach($submission_data as $key => $value){
			$data["secure"][ $value["name"] ] = $value["value"];

			// if( in_array($key, $tags_need_to_be_encrypt) ){
			// 	$data["secure"][ $key ] = $value;
			// }else{
			// 	$data["no_secure"][ $key ] = $value;
			// }
		}

		return $data;

	}


	public function generate_title(){
		return sprintf("Soumission de %s depuis le formulaire de contact \"%s\"", Storage::$email, $this->form->name );
	}
}
