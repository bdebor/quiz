<?php
/*
Plugin Name: Quiz
*/

class Quiz_Plugin
{
	public function __construct()
	{
		include_once plugin_dir_path(__FILE__) . '/mcq.php';
		new Quiz_Mcq();

		register_activation_hook(__FILE__, array('Quiz_Mcq', 'install'));

		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
	}

	public function add_admin_menu()
	{
		add_menu_page('Notre premier plugin', 'Quiz', 'manage_options', 'quiz', array($this, 'menu_html'));
		add_submenu_page('quiz', 'Aperçu', 'Aperçu', 'manage_options', 'quiz', array($this, 'menu_html'));
	}

	public function menu_html()
	{
		echo '<h1>'.get_admin_page_title().'</h1>';
		echo '<p>Bienvenue sur la page d\'accueil du plugin</p>';
	}

	public function admin_styles()
	{
		wp_register_style('quiz', plugins_url('quiz/css/admin.css') );
		wp_enqueue_style('quiz');
	}
}

new Quiz_Plugin();