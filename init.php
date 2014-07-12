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

function build_gedcom_array( $gedcom_file_path ) {
	$handle = fopen( $gedcom_file_path, "r" );

	$entries = array();

	$last_entry = array();
	$last_entry_id = null;

	while ( ! feof( $handle ) && $line = fgets( $handle ) ) {
		$line = trim( $line );
		if ( substr( $line, 0, 2 ) == '0 ' ) {
			if ( $last_entry_id ) {
				$entries[ $last_entry_id ] = new GEDCOM_Entry( implode( "\n", $last_entry ) );
				$last_entry = array();
			}
		
			$line_parts = explode( " ", $line, 3 );
			$last_entry_id = $line_parts[1];
		}
	
		$last_entry[] = $line;
	}

	if ( $last_entry_id ) {
		$entries[ $last_entry_id ] = new GEDCOM_Entry( implode( "\n", $last_entry ) );
	}
	
	fclose( $handle );
	
	return $entries;
}

function find_person( $name, $all_people ) {
	$possible_people = array();

	foreach ( $all_people as $person_id => $person ) {
		if ( in_array( $name, str_replace( '/', '', $person->getEntryValues( "NAME" ) ) ) ) {
			$possible_people[] = $person_id;
		}
	}

	if ( count( $possible_people ) == 1 ) {
		return $possible_people[0];
	}
	else if ( count( $possible_people ) > 1 ) {
		echo count( $possible_people ) . " possible matches found.\n";
	
		foreach ( $possible_people as $possible_person ) {
			echo "Did you mean this individual?\n\t" . implode( "\n\t", $all_people[ $possible_person ]->data ) . "\n[y/n] ";
			
			$input = get_input();
		
			if ( strtolower( $input ) == "y" ) {
				return $possible_person;
			}
		}
	}
	
	return false;
}

function get_input() {
	$handle = fopen( "php://stdin","r" );
	
	$line = fgets($handle);
	
	return trim( $line ); 
}