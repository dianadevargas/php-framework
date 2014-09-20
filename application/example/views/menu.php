<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>Example Website</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?php echo PUBLIC_URL; ?>css/basic.css">
    <script src="<?php echo PUBLIC_URL; ?>js/vendor/jquery.min.js"></script>
</head>
<body>
    <section>
        <div class="center">
            <h2>Examples</h2>
            <ul>
                <li><a href="<?php echo PUBLIC_URL; ?>example/showDatabase">Show Database Structure</a></li>
                <li><a href="<?php echo PUBLIC_URL; ?>example/splitReg">Split a CamelCase string</a></li>
                <li><a href="<?php echo PUBLIC_URL; ?>example/displayDate">Convert Unix time to Date and Time</a></li>
                <li><a href="<?php echo PUBLIC_URL; ?>example/displayUnix">Convert Date and Time to Unix time</a></li>
                <li><a href="<?php echo PUBLIC_URL; ?>example/convertJson">Convert Json string to Array</a></li>
                <li><a href="<?php echo PUBLIC_URL; ?>example/unserializeText">Unserialize Text</a></li>
                <li><a href="<?php echo PUBLIC_URL; ?>example/showRequest?var1=one&var2=two">Test the controller object</a></li>
            </ul>
            <button onclick="window.location = '<?php echo PUBLIC_URL; ?>'">Back to menu</button>
        </div>
    </section>
    <?php $msgs = $vars->getMessages();
    if (!empty($msgs)) : ?>
    <section>
        <div class="center">
        	<ul>
	       <?php foreach ($msgs as $msg) : ?>
	       		<li><?php echo $msg; ?></li>
	       <?php endforeach; ?>
	       </ul>
	    </div>
	</section>
	<?php endif; ?>
</body>
</html>
