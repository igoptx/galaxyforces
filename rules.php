<?php
	$index = 'rules';
	$auth = true;

	require('include/header.php');
	locale('website/rules');

	tablebegin($Lang['RulesTitle'], 500);
	subbegin();

?>

<div align="center">
<br>
<?php echo $Lang['RulesText']; ?>
</div>

<?php

	subend();
	tableend('Galaxy Forces', 500);

	require('include/footer.php');
