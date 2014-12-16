<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Unhandled Exception</title>

	<style type="text/css">
	body {
		background-color: #fff;
		margin: 40px;
		font: 13px/20px normal Helvetica, Arial, sans-serif;
		color: #4F5155;
	}

	h1 {
		color: #444;
		background-color: transparent;
		border-bottom: 1px solid #D0D0D0;
		font-size: 19px;
		font-weight: bold;
		margin: 0 0 14px 0;
		padding: 14px 15px 10px 15px;
	}

	h2 {
		color : #900;
		border-bottom : solid 1px #900;
		padding-bottom : 3px;
		padding: 12px 10px 12px 10px;
		margin : 0 10px;
	}
	h3 {
		font-size : 18px;
		margin-left : 20px;
	}
	h4 {
		font-size : 14px;
		margin-left : 14px;
	}
	h3, h4{
		
	}
	em{
		font-size: 1.1em;
		margin : 0 5px;
		color: #231815;
	}
	samp, kbd{
		font-weight: bold;
	}
	table{
		border : solid 1px #ccc;
		border-collapse : collapse;
		width : 95%;
		margin : 0 auto;
	}
	table td, table th{
		border : solid 1px #ccc;
		border-top : none;
		width : 50%;
		list-style-position: inside;
		padding: 5px 10px 5px 20px;
	}	
	table th{
		background-color: #ddd;
	}
	table tr:hover td{
		background-color: #D3EDFB;
	}
	</style>
</head>
<body>
	
<div id="box">
	<h1>Unhandled Exception</h1>

	<h2><?php echo $message;?></h2>
	<?php if ( get_config('deploy_status') !== 'production') :?>
	<h3>in <?php echo $file?> at line <?php echo $line;?></h3>
	<h4>Stack Trace:</h4>
	<table>
		<tbody>
			<tr>
				<th>File / line</th>
				<th>Class -> Method</th>
			</tr>
			<?php foreach ( $stackTrace as $trace ):?>
			<tr>
				<td>
					<?php if ( isset($trace[0]['file']) && isset($trace[0]['line']) ):?>
					<em><?php echo ( isset($trace[0]['file']) ) ? $trace[0]['file'] : '';?></em>at line <samp><?php echo ( isset($trace[0]['line']) ) ? $trace[0]['line'] : '';?></samp>
					<?php endif;?>
				</td>
				<td>
					<kbd>
						<?php echo ( isset($trace[0]['class']) )    ? $trace[0]['class'] : '';?>
						<?php echo ( isset($trace[0]['type']) )     ? $trace[0]['type'] : '';?>
						<?php echo ( isset($trace[0]['function']) ) ? $trace[0]['function'] . '()' : '';?>
					</kbd>
					<?php if ( isset($trace[1]) ):?>
					Called from <kbd><?php echo @$trace[1]['class'] . @$trace[1]['type'] . $trace[1]['function'];?>()</kbd>
					<?php endif;?>
				</td>
			</tr>
			<?php endforeach;?>
		</tbody>
	</table>
	<?php endif;?>
</div>

</body>
</html>