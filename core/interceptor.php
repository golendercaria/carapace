<?php

	/*

		TODO :
		- capturer l'email ou le nom pour générer les titres
		- note sur forminator pour expliqeur concrètement comment ne pas stocker l'entry : option du formulaire
		- niveau d'accès
		- tracabilité
		- fichier

	*/

	namespace Carapace;

	class Interceptor{

		public function __construct()
		{

			$this->load_interceptor();

			//new ImageInterceptor();
			new CF7Interceptor();
			new ForminatorInterceptor();

		}

		public function load_interceptor() : void
		{

			//require_once 'interceptor/image.php';
			require_once 'interceptor/cf7.php';
			require_once 'interceptor/forminator.php';
		
		}

	}
