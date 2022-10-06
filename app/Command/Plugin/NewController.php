<?php

namespace App\Command\Plugin;

use Minicli\Command\CommandController;

use Minicli\Input;

class NewController extends CommandController
{

	public $plugin_name, $plugin_slug, $plugin_class, $namespace, $abbreviation;
	
	public function handle(): void
	{
		$this->handle_input();

		if ( ! $this->hasFlag( 'skip-clone' ) ) {
			$this->clone_repo();
		}

		if ( ! $this->hasFlag( 'skip-rename' ) ) {
			$this->rename_files();
		}

		if ( ! $this->hasFlag( 'skip-npm' ) ) {
			$this->run_npm();
		}

		$printer = $this->getPrinter();
		$printer->newline();
		$printer->success( 'Your plugin is ready to be enabled. Have a nice day :)' );
	}

	public function handle_input()
	{
		$printer = $this->getPrinter();
		$confirm = false;

		do {
			// Ask for plugin name
			do {
				$this->plugin_name = $this->hasParam( 'name' ) ? $this->getParam( 'name' ) : $this->prompt_input( '(1/5) Choose a name for the plugin:' );
			} while ( empty( $this->plugin_name ) );

			$default_plugin_slug  = str_replace( ' ', '-', strtolower( $this->plugin_name ) );
			$default_plugin_class = str_replace( ' ', '_', ucwords( strtolower( $this->plugin_name ) ) );
			$default_namespace    = ucfirst( str_replace( ' ', '_', strtolower( $this->plugin_name ) ) );
			$default_abbreviation = strtoupper( str_replace( ' ', '_', $this->plugin_name ) );

			// Ask for plugin slug
			$this->plugin_slug  = $this->hasParam( 'slug' ) ? $this->getParam( 'slug' ) : $this->prompt_input( '(2/5) Choose a slug for the plugin (default: ' . $default_plugin_slug . '):' );
			$this->plugin_class = $this->hasParam( 'class' ) ? $this->getParam( 'class' ) : $this->prompt_input( '(3/5) Choose a main class for the plugin (default: ' . $default_plugin_class . '):' );
			$this->namespace    = $this->hasParam( 'namespace' ) ? $this->getParam( 'namespace' ) : $this->prompt_input( '(4/5) Set a namespace: (default: ' . $default_namespace . ')' );
			$this->abbreviation = $this->hasParam( 'abbr' ) ? $this->getParam( 'abbr' ) : $this->prompt_input( '(5/5) Set an abbreviation: (default: ' . $default_abbreviation . ')' );
			
			if ( empty( $this->plugin_slug ) ) {
				$this->plugin_slug = $default_plugin_slug;
			}

			if ( empty( $this->plugin_class ) ) {
				$this->plugin_class = $default_plugin_class;
			}
	
			if ( empty( $this->namespace ) ) {
				$this->namespace = $default_namespace;
			}

			if ( empty( $this->abbreviation ) ) {
				$this->abbreviation = $default_abbreviation;
			}

			$printer->newline();
			$printer->out( 'Name: ', 'display' );
			$printer->out( $this->plugin_name . "\r\n", 'info' );
			$printer->out( 'Slug: ', 'display' );
			$printer->out( $this->plugin_slug . "\r\n", 'info' );
			$printer->out( 'Main class: ', 'display' );
			$printer->out( $this->plugin_class . "\r\n", 'info' );
			$printer->out( 'Namespace: ', 'display' );
			$printer->out( $this->namespace . "\r\n", 'info' );
			$printer->out( 'Abbreviation: ', 'display' );
			$printer->out( $this->abbreviation . "\r\n", 'info' );

			do {
				$confirm_input = $this->prompt_input( 'Is this correct? (y/n)' );
			} while ( ! in_array( $confirm_input, array( 'y', 'Y', 'yes', 'Yes', 'n', 'N', 'no', 'No' ), true ) );
			
			if ( in_array( $confirm_input, array( 'y', 'Y', 'yes', 'Yes' ), true ) ) {
				$confirm = true;
			}

		} while ( ! $confirm );
	}

	public function clone_repo()
	{
		$printer = $this->getPrinter();

		$printer->newline();
		$printer->display( 'Creating plugin...' );
		exec( 'git clone https://github.com/DevriX/dx-plugin-boilerplate ' . $this->plugin_slug . ' && cd ' . $this->plugin_slug . ' && rm -rf .git', $output, $return_var );

		if ( $return_var !== 0 ) {
			$printer->newline();
			$printer->error( 'Error cloning the boilerplate. Please check the output above.' );
			exit;
		}

		$printer->out( 'Done.', 'success' );
	}

	public function run_npm()
	{

		$printer = $this->getPrinter();

		$printer->newline();
		$printer->display( 'Running NPM...' );

		exec( 'cd ' . $this->plugin_slug . ' && npm i && npm run prod', $output, $return_var );

		if ( $return_var !== 0 ) {
			$printer->newline();
			$printer->error( 'Error running NPM. Please check the output above.' );
			exit;
		}

		$printer->out( 'Done.', 'success' );
	}

	public function rename_files()
	{
		$printer = $this->getPrinter();

		$php_files = $this->get_php_files( $this->plugin_slug );

		if ( empty( $php_files ) ) {
			$printer->newline();
			$printer->error( 'No PHP files found in the plugin directory. Please check the output above.' );
			exit;
		}

		if ( ! empty( $php_files ) && is_array( $php_files ) ) {

			$printer->newline();
			$printer->newline();
			$printer->out( 'Replacing boilerplate strings.', 'display' );

			foreach ( $php_files as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}
				$printer->out( '.', 'display' );

				$file_content = file_get_contents( $file );

				$file_content = $this->replace_plugin_name( $file_content, $this->plugin_name );
				$file_content = $this->replace_namespace( $file_content, $this->namespace );
				$file_content = $this->replace_abbreviation( $file_content, $this->abbreviation );
				$file_content = $this->replace_slug( $file_content, $this->plugin_slug );
				$file_content = $this->replace_functions( $file_content, $this->plugin_slug );
				$file_content = $this->replace_package( $file_content, $this->plugin_class );

				file_put_contents( $file, $file_content );

				if ( strpos( $file, 'plugin-name' ) !== false ) {
					$new_file = str_replace( 'plugin-name', $this->plugin_slug, $file );
					rename( $file, $new_file );
				}
			}
			$printer->out( 'done.', 'success' );
		}
	}

	public function replace_plugin_name( string $file_content, string $plugin_name ) : string
	{
		return str_replace( 'DX Plugin Name', $plugin_name, $file_content );
	}

	public function replace_namespace( string $file_content, string $namespace ) : string
	{
		$file_content = str_replace( 'namespace PLUGIN_NAME', 'namespace ' . $namespace, $file_content );
		$file_content = str_replace( 'PLUGIN_NAME\\', $namespace . '\\', $file_content );

		return $file_content;
	}

	public function replace_abbreviation( string $file_content, string $abbreviation ) : string
	{
		return str_replace( 'PLUGIN_NAME', strtoupper( $abbreviation ), $file_content );
	}

	public function replace_slug( string $file_content, string $plugin_slug ) : string
	{
		$file_content = str_replace( '\'plugin-name\'', '\'' . $plugin_slug . '\'', $file_content );
		$file_content = str_replace( '"plugin-name"', '"' . $plugin_slug . '"', $file_content );
		$file_content = str_replace( '-plugin-name', '-' . $plugin_slug, $file_content );
		$file_content = str_replace( 'plugin-name-', $plugin_slug . '-', $file_content );

		return $file_content;
	}

	public function replace_functions( string $file_content, string $function_name ) : string
	{
		$function_name = str_replace( '-', '_', $function_name );

		$file_content = str_replace( '_plugin_name', '_' . $function_name, $file_content );
		$file_content = str_replace( 'plugin_name_', $function_name . '_', $file_content );
		$file_content = str_replace( 'get_' . $function_name, 'get_plugin_name' . '_', $file_content );

		return $file_content;
	}
	
	public function replace_package( string $file_content, string $plugin_main_class ) : string
	{
		return str_replace( 'Plugin_Name', $plugin_main_class, $file_content );
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

			if ( in_array( pathinfo( $path, PATHINFO_EXTENSION ), array( 'php', 'scss', 'js', 'pot' ,'json' ) ) ) {
				$results[] = $path;
			} else if ( $value != "." && $value != ".." ) {
				$this->get_php_files( $path, $results );
			}
		}
		return $results;
	}
}