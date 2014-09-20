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
            <?php if (isset($vars->title) && !empty($vars->title)) echo "<h1>{$vars->title}</h1>"; ?>
            <?php foreach ($vars->tables as $name => $data) : ?>
            <h2><a href="<?php echo PUBLIC_URL."example/showRows?{$name}"; ?>"><?php echo $name; ?></a></h2>
            <table style="border">
                <?php foreach ($data['fields'] as $n => $row) :
                        if ($n == 0) {
                            $keys = array_keys($row);
                            echo "<tr>";
                            foreach ($keys as $th) {
                                echo "<th>$th</th>";
                            }
                            echo "</tr>";
                        }
                        echo "<tr>";
                        foreach ($row as $k => $td) {
                            if ($k == 'tsi' || $k == 'tsu')
                                echo '<td>'.date('Y-m-d',strtotime($td)).'</td>';
                            else
                                echo "<td>$td</td>";
                        }
                        echo "</tr>";
                    ?>
                <?php endforeach; ?>
                <?php if (isset($data['rows'])) : ?>
                <tr>
                <td>Total Rows</td>
                <td><?php echo $data['rows']; ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php endforeach; ?>
	        <button onclick="window.location = '<?php echo PUBLIC_URL; ?>example/'">Back to menu</button>
        </div>
    </section>
</body>
</html>
