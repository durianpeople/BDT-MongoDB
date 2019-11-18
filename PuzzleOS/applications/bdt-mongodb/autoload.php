<?php

spl_autoload_register(function ($c) {
    if ((strtok($c, "\\")) == "BDT") {
        $path = my_dir("app/" . btfslash(strtok('')) . ".php");
        if (file_exists($path)) require $path;
    }
});

include "lib/vendor/autoload.php";
