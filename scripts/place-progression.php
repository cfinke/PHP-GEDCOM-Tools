#!/usr/bin/env php
<?php

require dirname( dirname( __FILE__ ) ) . "/init.php";
require dirname( dirname( __FILE__ ) ) . "/lib/AnimGif.php";

$cli_options = getopt( "g:t:o:i:k:", array( "gedcom:", "type:", "out:", "icon:", "key:" ) );

if ( isset( $cli_options['g'] ) ) {
	$cli_options['gedcom'] = $cli_options['g'];
}

if ( isset( $cli_options['o'] ) ) {
	$cli_options['out'] = $cli_options['o'];
}

if ( isset( $cli_options['t'] ) ) {
	$cli_options['type'] = $cli_options['t'];
}

if ( isset( $cli_options['i'] ) ) {
	$cli_options['icon'] = $cli_options['i'];
}

if ( isset( $cli_options['k'] ) ) {
	$cli_options['key'] = $cli_options['k'];
}

if ( empty( $cli_options['gedcom'] ) || empty( $cli_options['type'] ) || empty( $cli_options['out'] ) ) {
	file_put_contents( 'php://stderr', "Usage: " . basename( __FILE__ ) . " --gedcom=/path/to/tree.ged --type=[BIRT|DEAT] --icon='http://example.com/custom-icon.png' --key='optional-google-api-key' --out=/path/to/output.gif\n" );
	die;
}

if ( $cli_options['gedcom']{0} != '/' || $cli_options['out']{0} != '/' ) {
	file_put_contents( 'php://stderr', "Please use absolute file paths.\n" );
	die;
}

$entries = build_gedcom_array( $cli_options['gedcom'] );

if ( false === $entries ) {
	file_put_contents( 'php://stderr', "Couldn't read GEDCOM file: " . $cli_options['gedcom'] . "\n" );
	die;	
}

$places = array();

foreach ( $entries as $entry ) {
	if ( $entry->block_type != 'INDI' ) {
		continue;
	}

	$date = $entry->getEntrySubValue( $cli_options['type'], 'DATE' );
	$place = $entry->getEntrySubValue( $cli_options['type'], 'PLAC' );
	
	if ( ! $date || ! $place ) {
		continue;
	}
	
	$timestamp = strtotime( $date );
	
	if ( $timestamp === false ) {
		continue;
	}
	
	$year = date( 'Y', $timestamp );
	
	if ( $year === date( 'Y' ) ) {
		continue;
	}
	
	$places[ $year ][] = $place;
}

ksort( $places );

$first_year = key( $places );
end( $places );
$last_year = key( $places );
reset( $places );

$all_years = array();

for ( $i = $first_year; $i <= $last_year; $i++ ) {
	$all_years[ $i ] = isset( $places[ $i ] ) ? $places[$i] : '';
}

$base_url = "http://maps.googleapis.com/maps/api/staticmap?size=400x256&maptype=roadmap&scale=2&center=United+States&zoom=3&format=gif";

if ( ! empty( $cli_options['key'] ) ) {
	$base_url .= '&key=' . urlencode( $cli_options['key'] );
}

$image_files = array();
$image_cache = array();

$image_cache[ $base_url ] = file_get_contents( $base_url );

$image_dir = tempdir();

$tmp_filename = tempnam( $image_dir, "php-gedcom" );
$blank_map = $tmp_filename . ".gif";

$tmp_file = fopen( $tmp_filename, "w" );
fwrite( $tmp_file, $image_cache[ $base_url ] );
fclose( $tmp_file );

copy( $tmp_filename, $tmp_filename . ".gif" );
unlink( $tmp_filename );

$image_files[] = $tmp_filename . ".gif";

foreach ( $all_years as $year => $places ) {
	$image_url = $base_url;
	
	if ( is_array( $places ) ) {
		$image_url .= '&markers=';
		
		if ( ! empty( $cli_options['icon'] ) ) {
			$image_url .= 'icon:' . urlencode( $cli_options['icon'] ) . '|';
		}
		
		$image_url .= implode( "|", array_map( 'urlencode', $places ) );
	}
	
	echo "Getting image for $year...\n";
	
	if ( ! isset( $image_cache[ $image_url ] ) ) {
		$image_cache[ $image_url ] = file_get_contents( $image_url );
		
		if ( ! $image_cache[ $image_url ] ) {
			sleep( 10 );
			
			$image_cache[ $image_url ] = file_get_contents( $image_url );
		
			if ( ! $image_cache[ $image_url ] ) {
				file_put_contents( 'php://stderr', "Error retrieving remote file: " . $image_url );
				break;
			}
		}
		
		sleep( 1 );
	}

	$this_year = $image_cache[ $image_url ];
	
	$tmp_filename = tempnam( $image_dir, "php-gedcom" );
	
	$tmp_file = fopen( $tmp_filename, "w" );
	fwrite( $tmp_file, $this_year );
	fclose( $tmp_file );
	
	$diffed_filename = tempnam( $image_dir, "php-gedcom" );
	
	shell_exec( "compare " . escapeshellarg( $tmp_filename ) . " "  . escapeshellarg( $blank_map ) . " -lowlight-color transparent -highlight-color '#f55b53' -compose src -fuzz '3%' " . escapeshellarg( $diffed_filename ) . ".gif" );
	
	unlink( $tmp_filename );
	unlink( $diffed_filename );
	
	$image_files[] = $diffed_filename . ".gif";
}

shell_exec( "gifsicle --careful --loop -d10 -Okeep-empty " . join( " ", array_map( 'escapeshellarg', $image_files ) ) . " " . str_repeat( escapeshellarg( $image_files[ count( $image_files ) - 1 ] ) . " ", 20 ) . " > " . escapeshellarg( $cli_options['out'] ) );

foreach ( $image_files as $image_file ) {
	unlink( $image_file );
}

rmdir( $image_dir );

echo "Animation written to: " . $cli_options['out'] . "\n";