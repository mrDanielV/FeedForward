<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>FF Net - Sex by Weight & Height</title>
<style>
	html {
		margin: 0;
		padding: 0;
		font-family: verdana;
		font-size: 12px;	
	}
	body {
		background-color: #DDE;
		padding: 20px;
	}
	form {
		padding: 20px;
		border: 2px solid #EAEAF7;
		border-radius: 7px;
		box-shadow: #888 1px 2px 7px -1px;
		background-color: #EAF5F4;
	}
	.formline {
		margin-bottom: 7px;
	}
	label {
		width: 60px;
		display: inline-flex;
	}
	input {
		border-radius: 4px;
		border: 2px solid lightsteelblue;
		padding: 4px;
	}
	#predict {
		width: 213px;
		background-color: aliceblue;
		box-shadow: #666 1px 1px 2px;
	}
</style>
</head>

<?php
	require_once '../includer.php';
	
	$results = '';
	if(isset($_POST['predict'])){
		// Получение параметры
		$w = isset($_POST['weight'])?$_POST['weight']:null;
		$h = isset($_POST['height'])?$_POST['height']:null;
		$w = floatval($w);
		$h = floatval($h);

		// Валидация параметров
		$valid = true;
		if(!$w || !$h){
			$results = 'Enter weight and height to predict sex!';
			$valid = false;
		}
		
		if($valid){
			// Инициализация нейросети
			$net = new FF('SexWH');
			if($net->isErrors()){
				$results = $net->printErrors();
			}
			else{
				// Предсказание нейросети
				$res = $net->predict([$h, $w]);

				if($net->isErrors()){
					$results = $net->printErrors();
				}else{
					// Обработка результата для визуализации
					$sexP = $res['result'][0];
					
					$sex = 'MALE';
					$s = 1;
					if($sexP < 0.5){
						$sex = 'FEMALE';
						$s = 0;
					}
					
					// Уверенность в результате
					$p = 1 - $net->MSE($sexP, $s);
					$p = round($p, 4) * 100;

					$results = '<b>'.$sex.'</b> (certainty '.$p.'%)';
				}
			}
		}
	}
?>

<body>
	<div>
    <form action="" method="post" enctype="application/x-www-form-urlencoded" name="form">
   	  	<div class="formline">
       	  <label>Weight</label>
    	    <input name="weight" type="text" id="weight" value="<?=@$_POST['weight']?>" />
    	</div>
   	  	<div class="formline">
       	  <label>Height</label>
    	    <input type="text" name="height" id="height"  value="<?=@$_POST['height']?>" />
    	</div>
        <div class="formline">
          <input type="submit" name="predict" id="predict" value="Predict Sex" />
        </div>
    </form>
    </div>
    <div style="height:30px;"></div>
    <div id="results"><?=$results?></div>
</body>
</html>