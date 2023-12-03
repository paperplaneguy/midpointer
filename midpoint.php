<?php

function are_coordinates($c) {
    if(count($c) != 2)
        return false;
    foreach($c as &$d)
        if( !is_numeric($d) )
            return false;
    return true;
}

function arg_check($index, $option1, $option2) {
    global $argv;
    return ($argv[$index] == $option1 || $argv[$index] == $option2);
}

function error($string) {
    _print($string);
    exit();
}

function _print($string) {
    print $string . "\n";
    # new line before sentences which are printed after input.
}


# database initialization
if( !SQLite3::version() )
    error("Is SQLite3 enabled?");

$database = new SQLite3("midpoint.sqlite");
if( !$database->querySingle("select name from sqlite_master where type='table' and name='labels'") )
    $database->exec("create table labels(label text, coordinates text)");


# main -------------------------------------------------------------------------
$number_of_places = 2;

# if starting-up with command line arguments
if($argc > 1) {
    if( arg_check(1, "+", "--add_label") ) {
        if($argc != 5)
            error("Invalid label.");

        if( $database->querySingle("select label from labels where label='{$argv[2]}'") )
            error("Label already exists");
        
        $insertion_query = $database->prepare("insert into labels(label, coordinates) values('{$argv[2]}', :coordinates)");
        $insertion_query->bindValue(':coordinates', implode(" ", [$argv[3], $argv[4]]), SQLITE3_TEXT);
        $insertion_query->execute();

        exit();
    } if( arg_check(1, "-p", "--places") ) {
        if($argc != 3)
            error("Invalid input.");
        
        $number_of_places = $argv[2];
        
    } elseif( arg_check(1, "-v", "--version") ) {
        _print("Midpoint v0.3");

        exit();
    } elseif( arg_check(1, "-h", "--help") ) {
        _print("Usage:");
        _print(" php midpoint.php [options]");

        _print(" php midpoint.php +  | --add_label label coordinates -- Add a place label.\n");
        
        _print(" php midpoint.php -p  | --places   number            -- No. of places to average.\n");

        _print(" php midpoint.php -h | --help                        -- Print this page.\n");

        _print(" php midpoint.php -v | --version                     -- Displays version info.\n");

        exit();
    } else
        error("Invalid arguments.");

}

$average_x = 0;
$average_y = 0;
for($i = 1; $i < ($number_of_places + 1); $i++) {
    _print("\nInsert the coordinates for place #$i.");
    $response = readline(" > ");

    # check if its a coordinate or a label
    if( is_numeric($response[0]) ) {
        # separate x and y coordinates
        $response = explode(", ", $response);

        # check if the coordinates are valid
        if( !are_coordinates($response) )
            error("Invalid coordinates.");

    } else {
        # check if the label exists
        $coordinates = $database-> querySingle("select coordinates from labels where label='{$response}'");
        if( !$coordinates )
            error("Invalid label.");
        
        $response = explode(", ", $coordinates);
    }

    $average_x += (float) $response[0];
    $average_y += (float) $response[1];
}

$average_x /= $number_of_places;
$average_y /= $number_of_places;

_print("http://www.google.com/maps/place/$average_x,$average_y");