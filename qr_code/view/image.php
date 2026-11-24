<?php
/**
 * VUE — rendu PNG d'un QR code. Reçoit $valeur et retourne l'image binaire.
 * @var string $valeur
 */

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use chillerlan\QRCode\QROptions;

$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    throw new RuntimeException('vendor/autoload.php introuvable : lancez `composer require chillerlan/php-qrcode`.');
}
require_once $autoload;

return (new ChillerlanQRCode(new QROptions([
    'outputType'    => QROutputInterface::GDIMAGE_PNG,
    'outputBase64'  => false,
    'eccLevel'      => EccLevel::M,
    'scale'         => 8,
    'quietzoneSize' => 2,
])))->render($valeur);