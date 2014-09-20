<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
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
            <h2><?php echo $vars->form['title']; ?></h2>
            <form action="<?php echo $vars->form['action']; ?>" method="<?php echo $vars->form['method']; ?>" enctype="multipart/form-data" name="<?php echo $vars->form['name']; ?>">
            <ul>
                <li>
                    <label for="<?php echo $vars->form['input']['name']; ?>"><?php echo $vars->form['input']['label']; ?></label>
                    <?php switch ($vars->form['input']['type']) { 
                        case 'text' : ?>
                        <input type="$vars->form['input']['type'];" name="<?php echo $vars->form['input']['name']; ?>" id="<?php echo $vars->form['input']['name']; ?>" value="<?php echo $vars->form['input']['value']; ?>">
                        <?php break;
                        case 'textarea': ?>
                        <textarea rows="10" cols="50" name="<?php echo $vars->form['input']['name']; ?>" id="<?php echo $vars->form['input']['name']; ?>">
                        <?php echo $vars->form['input']['value']; ?>
                        </textarea>
                        <?php break;
                    } ?>
                </li>
                <?php if (is_array($vars->form['output'])) : ?>
                    <li>Result : </li>
                    <?php foreach ($vars->form['output'] as $key => $data) : ?>
                    <li>
                        <label><?php echo $key; ?> :</label>
                         <?php if (is_array($data)) : ?>
                            <ul>
                            <?php foreach ($data as $k => $v) : ?>
                            <li>
                                <label><?php echo $k; ?> :</label>
                                <div>
                                <?php if (is_array($v)) {
                                		print_r ($v); 
                                	} else { 
                                		echo $v;
                                	} ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div><?php echo $data; ?></div>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>Result : <?php echo $vars->form['output']; ?></li>
                <?php endif; ?>
                <li><input type="submit" value="Send"></li>
            </ul>
        </form>
         <button onclick="window.location = '<?php echo PUBLIC_URL; ?>example/'">Back to menu</button>
    </section>
</body>
</html>
