<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title>Page Not Found</title>

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


	#body{
		margin: 0 15px 0 15px;
	}
	
	h2 {
		color : #900;
		padding-bottom : 3px;
		padding: 14px 15px 10px 15px;
	}
	p{
		text-align : center;
	}
	</style>
</head>
<body>

<div id="box">
	<h1>Page Not Found</h1>

	<h2><?php echo ( ! empty($message) ) ? $message : 'Requested page is not found.';?></h2>
	<?php if ( ! empty($backLink) ):?>
	<p>
		<a href="<?php echo page_link($backLink);?>">Back</a>
	</p>
	<?php endif;?>
	
</div>

</body>
</html>