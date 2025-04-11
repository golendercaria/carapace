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
	// public static function generate_unique_file_name( string $path ) : void{

		
	// 	// $folder_name 	= substr($sha_mail, 0,2);
	// 	// $file_name 		= substr($sha_mail, 2, strlen($sha_mail) - 2) . '.' . self::$extension;

	// 	// self::$data_folder_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $folder_name;
	// 	// self::$data_file_path 	= self::$data_folder_path . DIRECTORY_SEPARATOR . $file_name;

	// }