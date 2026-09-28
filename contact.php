<?php
	require('include/header.php');
	locale('website/contact');

	tablebegin('Galaxy Forces', 500);

?>	<br />
	<b><?php echo $Lang['ContactTitle']; ?></b><br />
<?php
	subbegin();

	echo $Lang['ContactText'];

	subend();
	tableend('Galaxy Forces', 500);

	require('include/footer.php');
