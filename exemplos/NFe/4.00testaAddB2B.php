<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');
include_once '../../bootstrap.php';

use NFe\ToolsNFe;

$nfe = new ToolsNFe('../../config/config.json');
$aResposta = array();

$pathNFefile = '';
$pathB2Bfile = '';
$tagB2B = '';
$retorno = $nfe->addB2B($pathNFefile, $pathB2Bfile, $tagB2B);
echo '<br><br><PRE>';
echo htmlspecialchars($nfe->soapDebug);
echo '</PRE><BR>';
print_r($aResposta);
echo "<br>";