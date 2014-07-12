#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";

$cli_options = getopt( "g:p:o:", array( "gedcom:", "person:", "out:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['p'] ) ) {
	$cli_options['person'] = $cli_options['p'];
}

if ( isset( $cli_options['o'] ) ) {
	$cli_options['out'] = $cli_options['o'];
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['person'] ) || empty( $cli_options['out'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --person='Johann /Tuchtenhagen/' --out=/path/to/new-tree.ged\n" );
	die;
}

if ( $cli_options['gedcom']{0} != '/' || $cli_options['out']{0} != '/' ) {
	file_put_contents( 'php://stderr', "Please use absolute file paths.\n" );
	die;
}

$entries = build_gedcom_array( $cli_options['gedcom'] );
$tree_base = find_person( $cli_options['person'], $entries );

if ( ! $tree_base ) {
	file_put_contents( 'php://stderr', "Couldn't find person: " . $cli_options['person'] . "\n" );
	die;
}

// Start buildin' the tree.
$export = array();
$export[ 'HEAD' ] = $entries[ 'HEAD' ];

// Add the person we're basing the new tree on.
addPerson( $entries[ $tree_base ] );

// Add this person's parents (and their parents, and their parents)
addRelatedParents( $entries[ $tree_base ] );

// For everyone we've added already, add their children (and their children...)
$all_related_parents = array_keys( $export );

foreach ( $all_related_parents as $related_parent_key ) {
	addRelatedChildren( $entries[ $related_parent_key ] );
}

// Remove references to anyone we didn't add.
$export = remove_missing_references( $export );

$output_handle = fopen( $cli_options['out'], 'w' );

if ( ! $output_handle ) {
	file_put_contents( 'php://stderr', "Couldn't open file: " . $cli_options['out'] . "\n" );
	die;
}

foreach ( $export as $entry ) {
	fwrite( $output_handle, implode( "\n", $entry->data ) . "\n" ); 
}

fclose( $output_handle );

echo "GEDCOM written to " . $cli_options['out'] . "\n";

function addPerson( $person ) {
	global $export, $entries;
	
	if ( isset( $export[ $person->id ] ) ) {
		return;
	}

	$export[ $person->id ] = $entries[ $person->id ];
	
	$sources = $person->getRelatedEntries( 'SOUR' );
	
	foreach ( $sources as $source ) {
		if ( ! isset( $export[ $source->id ] ) ) {
			$export[ $source->id ] = $entries[ $source->id ];
			
			$repos = $source->getRelatedEntries( 'REPO' );
			
			foreach ( $repos as $repo ) {
				if ( ! isset( $export[ $repo->id ] ) ) {
					$export[ $repo->id ] = $entries[ $repo->id ];
				}
			}
		}
	}
}

function addRelatedChildren( $person ) {
	global $export, $entries;
	
	// Get the families where this person is a spouse.
	$families = $person->getRelatedEntries( 'FAMS' );

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
				
				addPerson( $child );
				addRelatedChildren( $child );
			}
		}
	}
}

function addRelatedParents( $person ) {
	global $export, $entries;
	
	if ( count( $person->getEntryValues( 'ADOP' ) ) > 0 ) {
		return;
	}
	
	// Get the families where this person is a child.
	$families = $person->getRelatedEntries( 'FAMC' );

	foreach ( $families as $family ) {
		$child_blocks = $family->getSubBlocks( 'CHIL' );

		$export[ $family->id ] = $family;

		foreach ( $child_blocks as $child_block ) {
			// Check this child's subblock.
			if ( in_array( $person->id, $child_block->getEntryValues( 'CHIL' ) ) ) {
				$father_relationships = $child_block->getEntrySubValues( 'CHIL', '_FREL' );
				
				if ( count( $father_relationships ) == 0 || strtolower( $father_relationships[0] ) != 'adopted' ) {
					$husbands = $family->getEntryValues( 'HUSB' );
					
					if ( count( $husbands ) > 0 ) {
						addPerson( $entries[ $family->getEntryValues( 'HUSB' )[0] ] );
						addRelatedParents( $entries[ $family->getEntryValues( 'HUSB' )[0] ] );
					}
				}
				
				$mother_relationships = $child_block->getEntrySubValues( 'CHIL', '_MREL' );
				
				if ( count( $mother_relationships ) == 0 || strtolower( $mother_relationships[0] ) != 'adopted' ) {
					$wifes = $family->getEntryValues( 'WIFE' );
					
					if ( count( $wifes ) > 0 ) {
						addPerson( $entries[ $family->getEntryValues( 'WIFE' )[0] ] );
						addRelatedParents( $entries[ $family->getEntryValues( 'WIFE' )[0] ] );
					}
				}
			}
		}
	}
}