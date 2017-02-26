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
			$content = $this->generateContent();
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
					<?php for($j = 1; $j <= 5; $j++): ?>
						<input type="checkbox" name="c<?= $i ?>_<?= $j ?>" value="1">
						<span>Réponse <?= $j ?></span>
						<input type="text" name="r<?= $i ?>_<?= $j ?>"><br>
					<?php endfor ?>
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
			$content = $this->generateContent();
			$wpdb->update("{$wpdb->prefix}quiz_mcq", array('content' => $content), array( 'id' => $_GET['id'] ));

			wp_redirect('?page=quiz_mcq');
		}
		?>

		<h1>Modifier le QCM <?= $mcq->id ?></h1>
		<form method="post" action="?page=quiz_mcq_edit&id=<?= $mcq->id ?>">
			<?php for($i = 1; $i <= 10; $i++): ?>
				<?php $contentItem = $contentArray[$i-1]; ?>
				<div style="border: 1px solid #000; padding: 5px;">
					<span>Question <?= $i ?></span>
					<input type="text" name="q<?= $i ?>" value="<?= $contentItem->question ?>"><br>
					<?php for($j = 1; $j <= 5; $j++): ?>
						<input type="checkbox" name="c<?= $i ?>_<?= $j ?>" value="1" <?= ($contentItem->checks[$j-1]?'checked':'') ?>>
						<span>Réponse <?= $j ?></span>
						<input type="text" name="r<?= $i ?>_<?= $j ?>" value="<?= $contentItem->responses[$j-1] ?>"><br>
					<?php endfor ?>
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
		//for($i = 1, $max = count($contentArray); $i <= $max; $i++){
		foreach($contentArray as $i => $contentItem){
			$i2 = $i + 1;
			$html .= "
				<div style='border: 1px solid #000; padding: 5px;'>
					<p>Question $i2 : {$contentItem->question}</p>
					";
			foreach($contentItem->responses as $j => $response){
				$html .= "
					<p>
						<input type='checkbox' name='c{$i}_{$j}' value='1'>
						<span>$response</span>
					</p>
					";
			}
			$html .= "</div>";
		}
		$html .= "</div>";


		return $html;
	}

	private function generateContent()
	{
		$contentArray = [];
		for($i = 1; $i <= 10; $i++){
			if($_POST['q'.$i]){
				$contentItem = [];
				$contentItem['question'] = $_POST['q'.$i];
				for($j = 1; $j <= 5; $j++){
					if($_POST['r'.$i.'_'.$j]){
						$contentItem['checks'][] = ($_POST['c'.$i.'_'.$j]?:'');
						$contentItem['responses'][] = $_POST['r'.$i.'_'.$j];
					}else{
						break;
					}
				}
				$contentArray[] = $contentItem;
			}else{
				break;
			}
		}

		return json_encode($contentArray);
	}
}