<?php

// A simple (emphasis on simple) representation of an entry in a GEDCOM file.
// This was built to be as simple as possible but still useful enough for the 
// functions I needed it for. Probably not a good idea to build anything 
// full-fledged off of this.

class GEDCOM_Entry {
	var $id = null;
	var $block_type = null;
	var $data = array();
	
	function __construct( $text_block ) {
		$lines = array_map( 'trim', explode( "\n", $text_block ) );
		
		$header_parts = explode( " ", $lines[0], 3 );
		
		$this->id = $header_parts[1];
		
		if ( count( $header_parts ) > 2 ) {
			$this->block_type = $header_parts[2];
		}
		
		$this->data = $lines;
	}
	
	function getRelatedEntries( $type ) {
		global $entries;
		
		$rv = array();
		
		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line, 3 );
			
			if ( count( $line_parts ) > 2 ) {
				if ( $line_parts[1] == $type && $line_parts[2]{0} == '@' ) {
					$rv[] = $entries[ $line_parts[2] ];
				}
			}
		}
		
		return $rv;
	}
	
	function getEntryValues( $type ) {
		global $entries;
		
		$rv = array();
		
		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line, 3 );
			
			if ( count( $line_parts ) > 2 ) {
				if ( $line_parts[1] == $type ) {
					$rv[] = $line_parts[2];
				}
			}
		}
		
		return $rv;
	}
}

function get_input() {
	$handle = fopen( "php://stdin","r" );
	
	$line = fgets($handle);
	
	return trim( $line ); 
}