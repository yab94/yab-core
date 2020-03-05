<?php

$response->setHeader('Content-Type: application/json'); 

foreach($datas as $model) {
    $response->send();
    echo json_encode($model->getAttributes());
}