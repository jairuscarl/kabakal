<?php

    $isOn = True;
    echo "hello tnangina mo";

    $counter = 0;
    while ($isOn) {
        echo "<h1>admin</h1>";
        $counter++;
        if ($counter == 2){
            $isOn = false;
        }
    }


?>