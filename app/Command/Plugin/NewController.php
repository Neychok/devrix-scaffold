<?php

namespace App\Command\Plugin;

use Minicli\Command\CommandController;

use Minicli\Input;

class NewController extends CommandController
{
	public function handle(): void
	{
		$printer = $this->getPrinter();
		$confirm = false;

		do {

			// Ask for plugin name
			do {
				$plugin_name = strtolower( $this->prompt_input( '(1/5) Choose a name for the plugin:' ) );
			} while ( empty( $plugin_name ) );


			// Ask for plugin slug
			$default_plugin_slug = str_replace( ' ', '-', $plugin_name );
			$plugin_slug         = $this->prompt_input( '(2/5) Choose a slug for the plugin (default: ' . $default_plugin_slug . '):' );
			if ( empty( $plugin_slug ) ) {
				$plugin_slug = $default_plugin_slug;
			}

			// Ask for plugin main class
			$default_plugin_class = str_replace( ' ', '_', ucwords( $plugin_name ) );
			$plugin_class         = $this->prompt_input( '(3/5) Choose a main class for the plugin (default: ' . $default_plugin_class . '):' );
			if ( empty( $plugin_class ) ) {
				$plugin_class = $default_plugin_class;
			}
	
			// Ask for plugin namespace
			$default_namespace = ucfirst( str_replace( ' ', '_', $plugin_name ) );
			$namespace         = $this->prompt_input( '(4/5) Set a namespace: (default: DX\\' . $default_namespace . ')' );
			if ( empty( $namespace ) ) {
				$namespace = 'DX\\' . $default_namespace;
			}

			// Ask for plugin abberivation
			$default_abbreviation = strtoupper( str_replace( ' ', '_', $plugin_name ) );
			$abbreviation = $this->prompt_input( '(5/5) Set an abbreviation: (default: ' . $default_abbreviation . ')' );
			if ( empty( $abbreviation ) ) {
				$abbreviation = $default_abbreviation;
			}

			$printer->newline();
			$printer->out( 'Name: ', 'display' );
			$printer->out( $plugin_name . "\r\n", 'info' );
			$printer->out( 'Slug: ', 'display' );
			$printer->out( $plugin_slug . "\r\n", 'info' );
			$printer->out( 'Main class: ', 'display' );
			$printer->out( $plugin_class . "\r\n", 'info' );
			$printer->out( 'Namespace: ', 'display' );
			$printer->out( $namespace . "\r\n", 'info' );
			$printer->out( 'Abbreviation: ', 'display' );
			$printer->out( $abbreviation . "\r\n", 'info' );

			do {
				$confirm = $this->prompt_input( 'Is this correct? (y/n)' );
			} while ( ! in_array( $confirm, array( 'y', 'Y', 'yes', 'Yes', 'n', 'N', 'no', 'No' ), true ) );
			
			if ( in_array( $confirm, array( 'y', 'Y', 'yes', 'Yes' ), true ) ) {
				$confirm = true;
			}

		} while ( ! $confirm );

		exec( 'git clone https://github.com/DevriX/dx-plugin-boilerplate ' . $plugin_slug . ' && cd ' . $plugin_slug . ' && rm -rf .git', $output, $return_var );

		if ( $return_var !== 0 ) {
			$printer->newline();
			$printer->error( 'Error cloning the boilerplate. Please check the output above.' );
			exit;
		}

		$php_files = $this->get_php_files( $plugin_slug );

		if ( empty( $php_files ) ) {
			$printer->newline();
			$printer->error( 'No PHP files found in the plugin directory. Please check the output above.' );
			exit;
		}

		if ( ! empty( $php_files ) && is_array( $php_files ) ) {

			$printer->newline();
			$printer->out( 'Replacing boilerplate strings.', 'info' );

			foreach ( $php_files as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}
				$printer->out( '.', 'info' );

				$file_content = file_get_contents( $file );

				$file_content = $this->replace_plugin_name( $file_content, $plugin_name );
				$file_content = $this->replace_namespace( $file_content, $namespace );
				$file_content = $this->replace_abbreviation( $file_content, $abbreviation );
				$file_content = $this->replace_functions( $file_content, $plugin_slug );
				$file_content = $this->replace_package( $file_content, $plugin_class );

				file_put_contents( $file, $file_content );
			}
			$printer->out( 'done.', 'info' );

			$printer->newline();
			$printer->success( 'Your plugin is ready to be enabled. Have a nice day :)' );
		}
	}

	public function prompt_input( string $text ) : string
	{
		$input   = new Input();
		$printer = $this->getPrinter();

		$printer->display( $text );
		$printer->newline();
		return $input->read();
	}

	public function get_php_files( string $dir, array &$results = array() ) : array
	{
		if ( ! is_dir( $dir ) ) {
			return $results;
		}
		
		$files = scandir( $dir );
	
		foreach ( $files as $value ) {
			
			$path = realpath( $dir . DIRECTORY_SEPARATOR . $value );
			
			if ( pathinfo($path, PATHINFO_EXTENSION) === 'php' ) {
				$results[] = $path;
			} else if ( $value != "." && $value != ".." ) {
				$this->get_php_files( $path, $results );
			}
		}
		return $results;
	}

	public function replace_plugin_name( string $file_content, string $plugin_name ) : string
	{
		return str_replace( 'DX Plugin Name', $plugin_name, $file_content );
	}

	public function replace_namespace( string $file_content, string $namespace ) : string
	{
		return str_replace( 'namespace PLUGIN_NAME', 'namespace ' . $namespace, $file_content );
	}

	public function replace_abbreviation( string $file_content, string $abbreviation ) : string
	{
		return str_replace( 'PLUGIN_NAME', strtoupper( $abbreviation ), $file_content );
	}

	public function replace_functions( string $file_content, string $function_name ) : string
	{
		$function_name = strtolower( $function_name );

		$file_content = str_replace( '_plugin_name', '_' . $function_name, $file_content );
		$file_content = str_replace( 'plugin_name_', $function_name . '_', $file_content );

		return $file_content;
	}
	
	public function replace_package( string $file_content, string $plugin_main_class ) : string
	{
		return str_replace( 'Plugin_Name', $plugin_main_class, $file_content );
	}
}