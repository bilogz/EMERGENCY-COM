<?php
mysqli_report(MYSQLI_REPORT_OFF);

function probe($host) {
    $link = mysqli_init();
    mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $start = microtime(true);
    $ok = @mysqli_real_connect($link, $host, 'root', '', null, 3306);
    $elapsed = round(microtime(true) - $start, 3);

    if (!$ok) {
        echo $host . ' => errno=' . mysqli_connect_errno() . ' err=' . mysqli_connect_error() . ' elapsed=' . $elapsed . "s\n";
        return;
    }

    echo $host . ' => OK elapsed=' . $elapsed . "s\n";
    $res = mysqli_query($link, 'SELECT 1 AS ok');
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        echo $host . ' => query ok=' . $row['ok'] . "\n";
        mysqli_free_result($res);
    } else {
        echo $host . ' => query_err=' . mysqli_error($link) . "\n";
    }
    mysqli_close($link);
}

probe('127.0.0.1');
probe('localhost');
?>
