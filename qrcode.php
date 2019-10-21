<?php
	require_once './config.php';
	require __DIR__ . '/vendor/autoload.php';

	$uuid = $_GET['uuid'];
	$qrCodeUrl = $qrCodeUrl . "?uuid={$uuid}";

	$qrCode = new Endroid\QrCode\QrCode($qrCodeUrl);        
    $qrCode->setSize(300);
    // Set advanced options
    $qrCode->setWriterByName('png');
    $qrCode->setMargin(10);
    $qrCode->setEncoding('UTF-8');
    $qrCode->setErrorCorrectionLevel(new Endroid\QrCode\ErrorCorrectionLevel(Endroid\QrCode\ErrorCorrectionLevel::HIGH));
    $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
    $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
    $qrCode->setLogoSize(150, 200);
    $qrCode->setRoundBlockSize(true);
    $qrCode->setValidateResult(false);
    $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

    header('Content-Type: '.$qrCode->getContentType());
    echo $qrCode->writeString();
    // Save it to a file
    $qrCode->writeFile(__DIR__.'/qrcode.png');
    // Create a response object
    new Endroid\QrCode\QrCodeResponse($qrCode);

?>



