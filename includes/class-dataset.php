<?php
namespace ABB\WCFE_BD;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dataset {

	public static function get_geo(): array {
		$file = ABB_WCFE_BD_DIR . 'assets/data/bd_geo.json';
		if ( file_exists( $file ) ) {
			$json = file_get_contents( $file );
			$data = json_decode( $json, true );
			if ( is_array( $data ) ) return $data;
		}
		return [
			"Dhaka"       => ["Dhamrai","Dohar","Keraniganj","Nawabganj","Savar","Dhaka City"],
			"Chattogram"  => ["Anwara","Banshkhali","Boalkhali","Fatikchhari","Hathazari","Lohagara","Mirsharai","Patiya","Rangunia","Raozan","Satkania","Sitakunda"],
			"Barishal"    => ["Agailjhara","Babuganj","Bakerganj","Banaripara","Gournadi","Hizla","Mehendiganj","Muladi","Wazirpur"],
		];
	}

	public static function get_districts(): array {
		return array_keys( self::get_geo() );
	}

	public static function get_districts_assoc(): array {
		$out = [ '' => __( 'Select District', 'abb-checkout-field-editor-bd' ) ];
		foreach ( self::get_districts() as $d ) $out[ $d ] = $d;
		return $out;
	}

	public static function get_subdistricts( string $district ): array {
		$geo = self::get_geo();
		return isset( $geo[ $district ] ) && is_array( $geo[ $district ] ) ? $geo[ $district ] : [];
	}
}
