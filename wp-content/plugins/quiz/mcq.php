<?php

class Quiz_Mcq
{
	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'), 20);
		add_shortcode('mcq', array($this, 'mcq_shortcode')); // ex : [mcq id=2]
		add_action( 'wp_ajax_my_ajax', array($this, 'my_ajax') );
	}

	public static function install()
	{
		global $wpdb;

		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quiz_mcq (id INT AUTO_INCREMENT PRIMARY KEY, content TEXT NOT NULL);"
		);
		$wpdb->query(
			"CREATE TABLE IF NOT EXISTS {$wpdb->prefix}quiz_mcq_result (id INT AUTO_INCREMENT PRIMARY KEY, mcq_id INT NOT NULL, user_id INT NOT NULL, score INT NOT NULL, user_checks TEXT NOT NULL);"
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

		add_submenu_page('quiz', 'Les résultats', 'Les résultats', 'manage_options', 'quiz_mcq_results', array($this, 'displayResultsAction'));
	}

	public function displayResultsAction()
	{
		global $wpdb;

		$results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quiz_mcq_result");
		?>

		<h1>Les résultats :</h1>
		<table>
			<tr>
				<th>id</th>
				<th>user_id</th>
				<th>mcq_id</th>
				<th>score</th>
			</tr>
			<?php foreach($results as $result): ?>
				<tr>
					<td><?= $result->id ?></td>
					<td><?= $result->user_id ?></td>
					<td><?= $result->mcq_id ?></td>
					<td><?= $result->score ?></td>
				</tr>
			<?php endforeach ?>
		</table>

		<?php
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
		global $current_user;
		$user_id = $current_user->ID;
		$atts = shortcode_atts(array('id' => ''), $atts, 'qcm');

		$mcq = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}quiz_mcq WHERE id={$atts['id']}" );
		$contentArray = json_decode($mcq->content);

		$result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}quiz_mcq_result WHERE user_id=$user_id AND mcq_id={$atts['id']}");
		$user_checks = json_decode($result->user_checks);


		// $result->score

		if($result){ // le test a déjà été fait, on montre la correction
			$html = "
				<div id='mcq'>
				<h2>Correction du QCM</h2>
				<p style='color: red;'>Score: {$result->score}</p>
				";


			//for($i = 1, $max = count($contentArray); $i <= $max; $i++){
			foreach($contentArray as $i => $contentItem){
				$i2 = $i + 1;
				$html .= "
					<div style='border: 1px solid #000; padding: 5px;'>
						<p>Question $i2 : {$contentItem->question}</p>
						";
				foreach($contentItem->responses as $j => $response){
					$j2 = $j + 1;
					$html .= "<p style='position: relative;'>";

					// Mettre en vert si correct, en rouge la correction
					if($user_checks[$i][$j] === '1'){
						if($user_checks[$i][$j] === $contentItem->checks[$j]){
							$html .= "<span style='color: green; position: absolute; left: -20px;'>v</span>";
						}else{
							$html .= "<span style='color: red; position: absolute; left: -20px;'>x</span>";
						}
					}else{
						if($contentItem->checks[$j] === '1'){
							$html .= "<span style='color: red; position: absolute; left: -20px;'>x</span>";
						}
					}

					$html .= "<div style='border: 1px solid #000; width: 10px; height: 7px; display: inline-block; line-height: 10px; font-size: 10px; text-align: center;'>";
					if($user_checks[$i][$j] === '1') {
						$html .= "<span>x</span>";
					}
					$html .= "</div>
							<span>$response</span>
						</p>
						";
				}
				$html .= "</div>";
			}
			$html .= "</div>";

		}else{ // Le test n'a déjà pas été fait

			$html = "
				<div id='mcq'>
				<h2>QCM</h2>
				";
			//for($i = 1, $max = count($contentArray); $i <= $max; $i++){
			foreach($contentArray as $i => $contentItem){
				$i2 = $i + 1;
				$html .= "
					<div style='border: 1px solid #000; padding: 5px;'>
						<p>Question $i2 : {$contentItem->question}</p>
						";
				foreach($contentItem->responses as $j => $response){
					$j2 = $j + 1;
					$html .= "
						<p>
							<input type='checkbox' id='c{$i2}_{$j2}' value='1'>
							<span>$response</span>
						</p>
						";
				}
				$html .= "</div>";
			}
			$html .= "
					<button id='validate'>Valider</button>
				</div>
				";

			$path = admin_url('admin-ajax.php').'?mcq_id='.$atts['id'];

			$html .= "
				<script>
					function onClickValidate(){
						var checkboxes = jQuery('#mcq input');
						var post = {};
						for (var i = 0, max = checkboxes.length; i < max; i++){
							var jqCheckbox = jQuery(checkboxes[i]);
							var id = jqCheckbox.attr('id');
	//							var pos = id.indexOf('_');
	//							var question_id = id.slice(0, pos);
	//							var response_id = id.slice(pos);

							post[id] = (jqCheckbox.is(':checked')?'1':'');
						}

						var userChecksArray = [];
						for(var i = 1; i <= 10; i++){
							if(post['c'+i+'_1'] !== undefined){
								var userChecksItem = [];
								for(var j = 1; j <= 5; j++){
									if(post['c'+i+'_'+j] !== undefined){
										userChecksItem.push((post['c'+i+'_'+j]?'1':''));
									}else{
										break;
									}
								}
								userChecksArray[i-1] = userChecksItem;
							}else{
								break;
							}
						}

						console.log(userChecksArray);

						jQuery.ajax ({
							type:'POST',
							dataType:'json',
							url:'$path',
							data:{
								action:'my_ajax',
								userChecksArray: userChecksArray
							},
							success:function(msg){
								console.log(msg);
							},
							error : function(XMLHttpRequest, textStatus, errorThrown){

							}
						});
					}

					jQuery('#validate').click(onClickValidate);
				</script>
			";
		}

		return $html;
	}

	public function my_ajax() {
		global $wpdb;
		global $current_user;
		$user_id = $current_user->ID;
		$mcq_id = $_GET['mcq_id'];
		$userChecksArray = $_POST['userChecksArray'];

		/**/ // ne pas pouvoir re-enregister le test
		$result = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}quiz_mcq_result WHERE user_id=$user_id AND mcq_id=$mcq_id" );

		if($result){
			echo('already done');
			exit;
		}
		/*/*/

		$mcq = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}quiz_mcq WHERE id=$mcq_id" );
		$contentArray = json_decode($mcq->content);

		/**/ // Calculer le score
		$errorsNb = 0;
		foreach($userChecksArray as $i => $userChecksItem){
			$checks = $contentArray[$i]->checks;
			foreach($userChecksItem as $j => $userCheck){
				if($userCheck !== $checks[$j]){
					$errorsNb++;
					break;
				}
			}
		}
		$questionsNb = count($userChecksArray);
		$score = $questionsNb - $errorsNb;
		/*/*/

		/**/ // Enregistrer dans la table quiz_mcq_result
		$wpdb->insert("{$wpdb->prefix}quiz_mcq_result", array(
			'user_id' => $user_id,
			'mcq_id' => $mcq_id,
			'score' => $score,
			'user_checks' => json_encode($userChecksArray)
		));
		/*/*/

		echo($score);
		exit;
	}

	private function generateContent() {
		$contentArray = [];
		for ($i = 1; $i <= 10; $i++) {
			if ($_POST['q' . $i] !== null) {
				$contentItem             = [];
				$contentItem['question'] = $_POST['q' . $i];
				for ($j = 1; $j <= 5; $j++) {
					if ($_POST['r' . $i . '_' . $j] !== null) {
						$contentItem['checks'][]    = ($_POST['c' . $i . '_' . $j] ?: '');
						$contentItem['responses'][] = $_POST['r' . $i . '_' . $j];
					} else {
						break;
					}
				}
				$contentArray[] = $contentItem;
			} else {
				break;
			}
		}

		return json_encode($contentArray);
	}
}