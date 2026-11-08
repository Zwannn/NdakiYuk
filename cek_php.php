<?php
echo "imagecreatefromjpeg: ";
var_dump(function_exists('imagecreatefromjpeg'));

echo "<br>imagecreatefrompng: ";
var_dump(function_exists('imagecreatefrompng'));

echo "<br>imagewebp: ";
var_dump(function_exists('imagewebp'));

echo "<br><br>Loaded php.ini: ";
echo php_ini_loaded_file();
?>