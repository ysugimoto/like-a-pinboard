<?php echo $this->partial("partial/header");?>

<?php foreach ( $pins as $pin ):?>
    <div>
        <p>title: <?php echo prep_str($pin->title);?></p>
        <p>url: <?php echo prep_str($pin->url);?></p>
    </div>
<hr>
<?php endforeach;?>

<?php echo $this->partial("partial/footer");?>
