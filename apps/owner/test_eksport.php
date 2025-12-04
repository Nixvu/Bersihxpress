<?php
require_once '../../vendor/autoload.php';

$mpdf = new \Mpdf\Mpdf();
$mpdf->WriteHTML('<h1>Hello World</h1>');
$mpdf->Output('hello_world.pdf', 'I');
