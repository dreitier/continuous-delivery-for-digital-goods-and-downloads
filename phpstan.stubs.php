<?php

if (!function_exists('edd_get_download')) {
    function edd_get_download($arg) {};
}

if (!class_exists('DLM_Logging')) {
	class DLM_Logging {
		public static function get_instance() {}
	}
}

if (!class_exists('DLM_File_Manager')) {
	class DLM_File_Manager {
		public function get_file_size($arg) {}

		public function json_encode_files($arg) {}

		public function get_file_hashes($arg) {}
	}
}
