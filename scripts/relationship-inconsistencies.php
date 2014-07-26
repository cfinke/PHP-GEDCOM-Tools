#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:a:", array( "gedcom:", ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( empty( $cli_options['gedcom'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged\n" );
	die;
}

if ( $cli_options['gedcom']{0} != '/' ) {
	file_put_contents( 'php://stderr', "Please use absolute file paths.\n" );
	die;
}

$entries = build_gedcom_array( $cli_options['gedcom'] );

if ( false === $entries ) {
	file_put_contents( 'php://stderr', "Couldn't read GEDCOM file: " . $cli_options['gedcom'] . "\n" );
	die;	
}

if ( empty( $cli_options['age'] ) ) {
	$cli_options['age'] = 120;
}

$max_age_in_seconds = $cli_options['age'] * 365 * 24 * 60 * 60;

$missing_deaths = array();

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}
	
	$children = getRelatedChildren( $entry );
	
	$birth_timestamp = date_to_timestamp( $entry->getEntrySubValue( 'BIRT', 'DATE' ) );
	$death_timestamp = date_to_timestamp( $entry->getEntrySubValue( 'DEAT', 'DATE' ) );
	
	foreach ( $children as $child ) {
		$child_birth_timestamp = date_to_timestamp( $child->getEntrySubValue( 'BIRT', 'DATE' ) );
		
		if ( $child_birth_timestamp ) {
			if ( $birth_timestamp && ( $child_birth_timestamp < $birth_timestamp ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born before their parent, " . $entry->getEntryValue( 'NAME' ) . " (" . $entry->getEntrySubValue( 'BIRT', 'DATE' ) . ")\n";
			}
			else if ( $death_timestamp && ( $entry->getEntryValue( 'SEX' ) == 'F' && $child_birth_timestamp > $death_timestamp ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born after their mother, " . $entry->getEntryValue( 'NAME' ) . ", died (" . $entry->getEntrySubValue( 'DEAT', 'DATE' ) . ")\n";
			}
			else if ( $death_timestamp && ( $entry->getEntryValue( 'SEX' ) == 'M' && $child_birth_timestamp > ( $death_timestamp + ( 9 * 30 * 24 * 60 * 60 ) ) ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born more than 9 months after their father, " . $entry->getEntryValue( 'NAME' ) . ", died (" . $entry->getEntrySubValue( 'DEAT', 'DATE' ) . ")\n";
			}
			else if ( $birth_timestamp && ( $child_birth_timestamp < ( $birth_timestamp + ( 15 * 365 * 24 * 60 * 60 ) ) ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born before their parent, " . $entry->getEntryValue( 'NAME' ) . ", turned 15 (born " . $entry->getEntrySubValue( 'BIRT', 'DATE' ) . ")\n";
			}
			else if ( $entry->getEntryValue( 'SEX' ) == 'F' && $birth_timestamp && ( $child_birth_timestamp > ( $birth_timestamp + ( 50 * 365 * 24 * 60 * 60 ) ) ) ) {
				echo $child->getEntryValue( 'NAME' ) . " (" . $child->getEntrySubValue( 'BIRT', 'DATE' ) . ") was born after their mother, " . $entry->getEntryValue( 'NAME' ) . ", turned 50 (born " . $entry->getEntrySubValue( 'BIRT', 'DATE' ) . ")\n";
			}
		}
	}
}

function getRelatedChildren( $person ) {
	global $entries;
	
	// Get the families where this person is a spouse.
	$families = $person->getRelatedEntries( 'FAMS' );

	$rv = array();

	foreach ( $families as $family ) {
		$export[ $family->id ] = $family;
		
		$child_relation = null;
		
		foreach ( array( 'WIFE', 'HUSB' ) as $spouse_type ) {
			$spouses = $family->getRelatedEntries( $spouse_type );
			
			foreach ( $spouses as $spouse ) {
				if ( $spouse->id == $person->id ) {
					$child_relation = ( $spouse_type == 'WIFE' ? '_FREL' : '_MREL' );
					break 2;
				}
			}
		}
		
		$children = $family->getRelatedEntries( 'CHIL' );
		
		foreach ( $children as $child ) {
			// If this child has no adoption event...
			if ( count( $child->getEntryValues( 'ADOP' ) ) == 0 ) {
				// Check if there is a parent/child relationship (adopted, biological, guardian, etc.)
				if ( $child_relation ) {
					$child_blocks = $family->getSubBlocks( 'CHIL' );
				
					foreach ( $child_blocks as $child_block ) {
						// Check this child's subblock.
						if ( in_array( $child->id, $child_block->getEntryValues( 'CHIL' ) ) ) {
							// If any of the child-to-parent relationships are 'Adopted', don't add this person.
							$relationships = $child_block->getEntrySubValues( 'CHIL', $child_relation );
						
							foreach ( $relationships as $relationship ) {
								if ( strtolower( $relationship ) == 'adopted' ) {
									continue 3;
								}
							}
						}
					}
				}
				
				$rv[] = $child;
			}
		}
	}
	
	return $rv;
}