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
	
	/**
	 * Given a set of lines like:
	 *
	 * 0 @P1@ INDI 
	 * 1 NAME John /Doe/
	 * 1 FAMS @F181@
	 * 1 FAMS @F182@
	 *
	 * call getRelatedEntries( 'FAMS' ) to get an array of the GEDCOM_Entry objects with IDs @F181@ and @F182@
	 *
	 * @param string $type
	 * @return array An array of GEDCOM_Entry objects.
	 */
	function getRelatedEntries( $type ) {
		global $entries;
		
		$type = strtoupper( $type );
		
		$rv = array();
		
		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line, 3 );
			
			if ( count( $line_parts ) > 2 ) {
				if ( strtoupper( $line_parts[1] ) == $type && $line_parts[2]{0} == '@' ) {
					$rv[] = $entries[ $line_parts[2] ];
				}
			}
		}
		
		return $rv;
	}
	
	/**
	 * Given a set of lines like:
	 *
	 * 0 @P1@ INDI
	 * 1 NAME John /Doe/
	 * 1 NAME Jonathan /Doe/
	 *
	 * call getEntryValues( 'NAME' ) to get array( 'John /Doe/', 'Jonathan /Doe/' )
	 *
	 * @param string $type
	 * @return array An array of string values.
	 */
	function getEntryValues( $type ) {
		$type = strtoupper( $type );

		$rv = array();
		
		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line, 3 );
			
			if ( count( $line_parts ) > 1 ) {
				if ( strtoupper( $line_parts[1] ) == $type ) {
					if ( count( $line_parts ) > 2 ) {
						$rv[] = $line_parts[2];
					}
					else {
						$rv[] = '';
					}
				}
			}
		}
		
		return $rv;
	}
	
	/**
	 * Given a set of lines like:
	 *
	 * 0 @P1@ INDI
	 * 1 BIRT
	 * 2 DATE 1901-01-01
	 * 1 NAME John /Doe/
	 *
	 * call getEntrySubValues( 'BIRT', 'DATE' ) to get array( '1901-01-01' )
	 *
	 * @param string $type
	 * @param string $subtype
	 * @return array An array of string values.
	 */
	function getEntrySubValues( $type, $subtype ) {
		$type = strtoupper( $type );
		$subtype = strtoupper( $subtype );
		
		$rv = array();
		
		$in_type = false;
		$level = null;
		
		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line, 3 );
			
			if ( count( $line_parts ) > 1 ) {
				if ( ! $in_type ) {
					if ( strtoupper( $line_parts[1] ) == $type ) {
						$in_type = true;
						$level = $line_parts[0];
					}
				}
				else {
					if ( $line_parts[0] <= $level ) {
						$in_type = false;
						$level = null;
					}
					else if ( $line_parts[0] == $level + 1 && strtoupper( $line_parts[1] ) == $subtype ) {
						$rv[] = $line_parts[2];
					}
				}
			}
		}
		
		return $rv;
	}
	
	/**
	 * Given a set of lines like this:
	 *
	 * 0 @F19@ FAM
	 * 1 HUSB @P5@
	 * 1 WIFE @P4@
	 * 1 CHIL @P151@
	 * 2 _FREL Natural
	 * 2 _MREL Natural
	 * 1 CHIL @P150@
	 * 2 _FREL Natural
	 * 2 _MREL Natural
	 * 1 MARR
	 * 2 DATE 19 Jun 1976
	 * 
	 * Calling getSubBlocks( 'CHIL' ) will return GEDCOM_Entry objects with the following $data variables.
	 *
	 * 1 CHIL @P151@
	 * 2 _FREL Natural
	 * 2 _MREL Natural
	 *
	 * and
	 *
	 * 1 CHIL @P150@
	 * 2 _FREL Natural
	 * 2 _MREL Natural
	 *
	 */
	function getSubBlocks( $type ) {
		$type = strtoupper( $type );
		
		$lines = array();
		$in_type = false;
		$level = null;
		
		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line, 3 );
			
			if ( count( $line_parts ) > 1 ) {
				if ( $in_type && (int) $line_parts[0] <= $level ) {
					$blocks[] = new GEDCOM_Entry( "0 @_@ PARTIAL\n" . implode( "\n", $lines ) );
					$lines = array();
					$in_type = false;
					$level = null;
				}

				if ( ! $in_type ) {
					if ( strtoupper( $line_parts[1] ) == $type ) {
						$in_type = true;
						$level = $line_parts[0];
						$lines[] = $line;
					}
				}
				else {
					if ( (int) $line_parts[0] > $level ) {
						$lines[] = $line;
					}
				}
			}
			else {
				$in_type = false;
				$level = null;
			}
		}
		
		if ( ! empty( $lines ) ) {
			$blocks[] = new GEDCOM_Entry( "0 @_@ PARTIAL\n" . implode( "\n", $lines ) );
		}
		
		return $blocks;
	}
	
	/**
	 * Given an associative array of entries, remove references to any entries not in the list.
	 * Useful for stripping a GEDCOM_Entry of references to entries that were removed during
	 * tree manipulation.
	 */
	function removeMissingReferences( $existing_references ) {
		$new_data = array();
		
		$in_removal = false;
		$level = null;

		foreach ( $this->data as $line ) {
			$line_parts = explode( " ", $line );
			
			if ( $in_removal && $line_parts[0] > $level ) {
				continue;
			}
			
			if ( $in_removal && $line_parts[0] <= $level ) {
				$in_removal = false;
				$level = null;
			}
			
			if ( count( $line_parts ) == 3 && $line_parts[2]{0} == '@' && substr( $line_parts[2], -1 ) == '@' && ! isset( $existing_references[ $line_parts[2] ] ) ) {
				$in_removal = true;
				$level = $line_parts[0];
			}
			else {
				$new_data[] = $line;
			}
		}
		
		$this->data = $new_data;
		
		return;
	}
}

function build_gedcom_array( $gedcom_file_path ) {
	$handle = fopen( $gedcom_file_path, "r" );
	
	if ( ! $handle ) {
		return false;
	}

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
			echo "Did you mean this person?\n\t" . implode( "\n\t", $all_people[ $possible_person ]->data ) . "\n[y/n] ";
			
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

function remove_missing_references( $list ) {
	foreach ( $list as $idx => $entry ) {
		$entry->removeMissingReferences( $list );
		
		$list[$idx] = $entry;
	}
	
	return $list;
}