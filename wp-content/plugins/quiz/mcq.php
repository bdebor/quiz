<?php

class Quiz_Mcq
{
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'), 20);
		add_shortcode('mcq', array($this, 'mcq_shortcode')); // ex : [mcq id=2]
	}

	public static function install()
	{
		global $wpdb;

		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quiz_mcq (id INT AUTO_INCREMENT PRIMARY KEY, content TEXT NOT NULL);"
		);
	}

	public static function uninstall()
	{
	}

	public function add_admin_menu()
	{
		add_submenu_page('quiz', 'Les QCM', 'Les QCM', 'manage_options', 'quiz_mcq', array($this, 'indexAction'));
		add_submenu_page('quiz', 'Ajouter un QCM', 'Ajouter un QCM', 'manage_options', 'quiz_mcq_new', array($this, 'newAction'));
		add_submenu_page('quiz', 'Modifier un QCM', 'Modifier un QCM', 'manage_options', 'quiz_mcq_edit', array($this, 'editAction')); // sous-menu masqué avec du css
		add_submenu_page('quiz', 'Supprimer un QCM', 'Supprimer un QCM', 'manage_options', 'quiz_mcq_delete', array($this, 'deleteAction')); // sous-menu masqué avec du css
	}

	public function indexAction()
	{
		global $wpdb;

		$mcqs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quiz_mcq");
		?>

		<h1>Les QCM :</h1>
		<ul>
			<?php foreach($mcqs as $mcq): ?>
				<li>
					<a href="?page=quiz_mcq_edit&id=<?= $mcq->id ?>">modifier</a>
					<a href="?page=quiz_mcq_delete&id=<?= $mcq->id ?>">supprimer</a>
					<span><?= $mcq->id ?></span> : <?= $mcq->content ?>
				</li>
			<?php endforeach ?>
		</ul>

		<?php
	}

	public function newAction()
	{
		global $wpdb;

		if(array_key_exists('q1', $_POST) == true) {
			$contentArray = [];
			for($i = 1; $i <= 10; $i++){
				if($_POST['q'.$i] != null){
					$contentArray[] = [$_POST['q'.$i], ($_POST['c'.$i.'_1']?:''), $_POST['r'.$i.'_1'], ($_POST['c'.$i.'_2']?:''), $_POST['r'.$i.'_2'], ($_POST['c'.$i.'_3']?:''), $_POST['r'.$i.'_3']];
				}else{
					break;
				}
			}

			$content = json_encode($contentArray);
			$wpdb->insert("{$wpdb->prefix}quiz_mcq", array('content' => $content));

			wp_redirect('?page=quiz_mcq');
		}
		?>

		<h1>Ajouter un QCM</h1>
		<form method="post" action="?page=quiz_mcq_new">
			<?php for($i = 1; $i <= 10; $i++): ?>
				<div style="border: 1px solid #000; padding: 5px;">
					<span>Question <?= $i ?></span>
					<input type="text" name="q<?= $i ?>"><br>

					<input type="checkbox" name="c<?= $i ?>_1" value="1">
					<span>Réponse 1</span>
					<input type="text" name="r<?= $i ?>_1"><br>

					<input type="checkbox" name="c<?= $i ?>_2" value="1">
					<span>Réponse 2</span>
					<input type="text" name="r<?= $i ?>_2"><br>

					<input type="checkbox" name="c<?= $i ?>_3" value="1">
					<span>Réponse 3</span>
					<input type="text" name="r<?= $i ?>_3"><br>
				</div>
			<?php endfor ?>
			<br>
			<input type="submit" value="Valider" class="button button-primary button-large">
		</form>
		<script>
			(function($){
				// console.log($('body'));
			})(jQuery)
		</script>

		<?php
	}

	public function editAction()
	{
		global $wpdb;

		$mcq = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}quiz_mcq WHERE id={$_GET['id']}" );
		$contentArray = json_decode($mcq->content);

		if(array_key_exists('q1', $_POST) == true) {
			$contentArray = [];
			for($i = 1; $i <= 10; $i++){
				if($_POST['q'.$i] != null){
					$contentArray[] = [$_POST['q'.$i], ($_POST['c'.$i.'_1']?:''), $_POST['r'.$i.'_1'], ($_POST['c'.$i.'_2']?:''), $_POST['r'.$i.'_2'], ($_POST['c'.$i.'_3']?:''), $_POST['r'.$i.'_3']];
				}else{
					break;
				}
			}

			$content = json_encode($contentArray);
			$wpdb->update("{$wpdb->prefix}quiz_mcq", array('content' => $content), array( 'id' => $_GET['id'] ));
		}
		?>

		<h1>Modifier le QCM <?= $mcq->id ?></h1>
		<form method="post" action="?page=quiz_mcq_edit&id=<?= $mcq->id ?>">
			<?php for($i = 1; $i <= 10; $i++): ?>
				<div style="border: 1px solid #000; padding: 5px;">
					<span>Question <?= $i ?></span>
					<input type="text" name="q<?= $i ?>" value="<?= $contentArray[$i-1][0] ?>"><br>

					<input type="checkbox" name="c<?= $i ?>_1" value="1" <?= ($contentArray[$i-1][1]?'checked':'') ?>>
					<span>Réponse 1</span>
					<input type="text" name="r<?= $i ?>_1" value="<?= $contentArray[$i-1][2] ?>"><br>

					<input type="checkbox" name="c<?= $i ?>_2" value="1" <?= ($contentArray[$i-1][3]? 'checked':'') ?>>
					<span>Réponse 2</span>
					<input type="text" name="r<?= $i ?>_2" value="<?= $contentArray[$i-1][4] ?>"><br>

					<input type="checkbox" name="c<?= $i ?>_3" value="1" <?= ($contentArray[$i-1][5]?'checked':'') ?>>
					<span>Réponse 3</span>
					<input type="text" name="r<?= $i ?>_3" value="<?= $contentArray[$i-1][6] ?>"><br>
				</div>
			<?php endfor ?>
			<br>
			<input type="submit" value="Valider" class="button button-primary button-large">
		</form>

		<?php
	}

	public function deleteAction()
	{
		global $wpdb;

		$wpdb->delete("{$wpdb->prefix}quiz_mcq", array( 'id' => $_GET['id'] ));

		wp_redirect('?page=quiz_mcq');
	}

	public static function mcq_shortcode($atts)
	{
		global $wpdb;

		$atts = shortcode_atts(array('id' => ''), $atts, 'qcm');
		$mcq = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}quiz_mcq WHERE id={$atts['id']}" );
		$contentArray = json_decode($mcq->content);

		$html = "<div>";
		for($i = 1, $max = count($contentArray); $i <= $max ; $i++){
			$html.= "
				<div style='border: 1px solid #000; padding: 5px;'>
					<p>Question $i : {$contentArray[$i-1][0]}</p>
					<p>
						<input type='checkbox' name='c{$i}_1' value='1'>
						<span>{$contentArray[$i-1][2]}</span>
					</p>
					<p>
						<input type='checkbox' name='c{$i}_2' value='1'>
						<span>{$contentArray[$i-1][4]}</span>
					</p>
					<p>
						<input type='checkbox' name='c{$i}_3' value='1'>
						<span>{$contentArray[$i-1][6]}</span>
					</p>
				</div>
			";
		}
		$html .= "</div>";

		return $html;
	}
}