<?php
	require_once './config.php';
    require __DIR__ . '/vendor/autoload.php';

	$uuid = $_GET['uuid'];
	$client = new MongoDB\Client($dbConnectionStr);
	$databaseManager = $client->admama;
    $qrUUid = $databaseManager->qr_uuid;
    $issetUUid = $qrUUid->findOne(['uuid' => $uuid, 'isLogin' => 0]);
    if(!empty($issetUUid)){
        $updateResult = $qrUUid->updateOne(
            [ 'uuid' => $uuid ],
            [ '$set' => [ 'isLogin' => 1 ]]
        );
    }
    header("content:application/json;chartset=uft-8");

    echo json_encode(['code' => 200]);
?>