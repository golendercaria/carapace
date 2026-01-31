<?php

	function pre( $a = null ){
		echo "<pre>";
		print_r($a);
		echo "</pre>";
	}

	function get_sha_string( string $string ) : string{

		if( isset($string) && $string != "" ){
			return hash('sha256', $string);
		}

		return "";

	}

	function generate_unique_filename( string $path, string $extension = null ) : string
	{
		$name = hash("sha256", uniqid("", true) . random_bytes(64));
		$file_path = $path . DIRECTORY_SEPARATOR . $name;

		if( $extension != null ){
			$file_path .= $extension; 
		}

		if( file_exists($file_path) ) {
			return generate_unique_filename();
		} else {
			return $file_path;
		}
	}

	function create_directory( string $path ) : bool{
		if( $path == "" ){
			return false;
		}

		if( !is_dir( $path ) ){
			return mkdir($path, 0755, true);
		}else{
			return true;
		}
		
	}


	function carapace_display_sec_for_human($secondes) {
		if ($secondes < 60) {
			return "$secondes secondes";
		} elseif ($secondes < 3600) {
			$minutes = floor($secondes / 60);
			return "$minutes min";
		} elseif ($secondes < 86400) {
			$heures = floor($secondes / 3600);
			return "$heures h";
		} else {
			$jours = floor($secondes / 86400);
			return "$jours jour" . ($jours > 1 ? 's' : '');
		}
	}
