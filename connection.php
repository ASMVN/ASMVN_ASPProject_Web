<?php
header('Content-Type: text/html; charset=utf-8');
    $serverName = "14.241.201.7,1537";
    $connectionOption = [
        "database" => "ASP",
        "UID" => "sa",
        "PWD" => "L@cH0ng#@!2026$",
        "CharacterSet" => "UTF-8"
    ];

    $conn = sqlsrv_connect($serverName, $connectionOption);
    // if ($conn){
    //     echo "success";
    // }else{
    //     echo "fail";
    //     die(print_r(sqlsrv_errors(),true));
    // }
    