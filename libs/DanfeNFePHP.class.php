<?php
/**
 * Este arquivo é parte do projeto NFePHP - Nota Fiscal eletrônica em PHP.
 *
 * Este programa é um software livre: você pode redistribuir e/ou modificá-lo
 * sob os termos da Licença Pública Geral GNU como é publicada pela Fundação
 * para o Software Livre, na versão 3 da licença, ou qualquer versão posterior.
 *
 * Este programa é distribuído na esperança que será útil, mas SEM NENHUMA
 * GARANTIA; sem mesmo a garantia explícita do VALOR COMERCIAL ou ADEQUAÇÃO PARA
 * UM PROPÓSITO EM PARTICULAR, veja a Licença Pública Geral GNU para mais
 * detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Publica GNU junto com este
 * programa. Caso contrário consulte <http://www.fsfla.org/svnwiki/trad/GPLv3>.
 *
 * @package     NFePHP
 * @name        DanfeNFePHP.class.php
 * @version     1.8.5
 * @license     http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @copyright   2010 &copy; NFePHP
 * @link        http://www.nfephp.org/
 * @author      Roberto L. Machado <linux.rlm at gmail dot com>
 *
 *        CONTRIBUIDORES :
 *              André Ferreira de Morais <andrefmoraes at gmail dot com>
 *              Leandro C. Lopez <leandro.castoldi at gmail dot com>
 *              Marcos Diez <marcos at unitron dot com dot br>
 *              Abdenego Santos <abdenego at gmail dot com>
 *              Djalma Fadel Junior <dfadel at ferasoft dot com dot br>
 *              Felipe Bonato <montanhats at gmail dot com>
 *              Renato Zaccaron Gonzaga <renato at zaccaron dot com dot br>
 *              Paulo Gabriel Coghi < paulocoghi at gmail dot com>
 *
 * @todo Formatação Paisagem
 * @todo Adaptação para a nova versão 2.0 do manual SEFAZ
 */

//comente a linha abaixo para nao permitir qualquer aviso no codigo pdf, a linha abaixo é utilizada para debug
//error_reporting(E_ALL);
//ajuste do tempo limite de resposta do processo
set_time_limit(1800);
//definição do caminho para o diretorio com as fontes do FDPF
define('FPDF_FONTPATH','font/');
//classe extendida da classe FPDF para montagem do arquivo pfd
require_once('FPDF/code128.php');
//require_once('LoadClasses.class.php');

class DanfeNFePHP {

    private $pdf; // objeto fpdf()
    private $xml; // string XML NFe
    private $logomarca=''; // path para logomarca em jpg
    private $errMsg=''; // mesagens de erro
    private $errStatus=FALSE;// status de erro TRUE um erro ocorreu FALSE sem erros
    private $orientacao='P'; //orientação da DANFE P-Retrato ou L-Paisagem
    private $papel='A4'; //formato do papel
    private $destino = 'I'; //destivo do arquivo pdf I-borwser, S-retorna o arquivo, D-força download, F-salva em arquivo local
    private $pdfDir=''; //diretorio para salvar o pdf com a opção de destino = F
    private $fontePadrao='Times'; //Nome da Fonte para gerar o DANFE	

    //objetos DOM da NFe
    private $dom;
    private $infNFe;
    private $ide;
    private $entrega;
    private $retirada;
    private $emit;
    private $dest;
    private $enderEmit;
    private $enderDest;
    private $det;
    private $cobr;
    private $dup;
    private $ICMSTot;
    private $ISSQNtot;
    private $transp;
    private $transporta;
    private $veicTransp;
    private $infAdic;
    private $wPrint; //largura imprimivel
    private $hPrint; //comprimento imprimivel
    //alinhamento do logo
    public $logoAlign='C';
    public $yDados=0;
    private $version = '1.8.5';
    private $textoAdic = '';
    private $wAdic = 0;

    /**
     *__construct
     * @package NFePHP
     * @name __construct
     * @version 1.0
     * @param string $docXML Arquivo XML da NFe (com ou sem a tag nfeProc)
     * @param string $sOrientacao Orientação da impressão P-retrato L-Paisagem
     * @param string $sPapel Tamanho do papel (Ex. A4)
     * @param string $sPathLogo Caminho para o arquivo do logo
     * @param string $sDestino Estabelece a direção do envio do documento PDF I-browser D-browser com download S-
     * @param string $sDirPDF Caminho para o diretorio de armazenamento dos arquivos PDF
     */
    function __construct($docXML='', $sOrientacao="P",$sPapel='A4',$sPathLogo='', $sDestino='I',$sDirPDF='',$fonteDANFE='') {
        $this->orientacao  = $sOrientacao;
        $this->papel    = $sPapel;
        $this->pdf      = '';
        $this->xml      = $docXML;
        $this->logomarca= $sPathLogo;
        $this->destino  = $sDestino;
        $this->pdfDir   = $sDirPDF;
	// verifica se foi passa a fonte a ser usada
        if (empty($fonteDANFE)) {
            $this->fontePadrao = 'Times';
	} else {
            $this->fontePadrao = $fonteDANFE;
	}   
        //se for passado o xml
        if ( !empty($this->xml) ) {
            $this->dom = new DomDocument;
            $this->dom->loadXML($this->xml);
            $this->nfeProc    = $this->dom->getElementsByTagName("nfeProc")->item(0);
            $this->infNFe     = $this->dom->getElementsByTagName("infNFe")->item(0);
            $this->ide        = $this->dom->getElementsByTagName("ide")->item(0);
            $this->entrega    = $this->dom->getElementsByTagName("entrega")->item(0);
            $this->retirada   = $this->dom->getElementsByTagName("retirada")->item(0);
            $this->emit       = $this->dom->getElementsByTagName("emit")->item(0);
            $this->dest       = $this->dom->getElementsByTagName("dest")->item(0);
            $this->enderEmit  = $this->dom->getElementsByTagName("enderEmit")->item(0);
            $this->enderDest  = $this->dom->getElementsByTagName("enderDest")->item(0);
            $this->det        = $this->dom->getElementsByTagName("det");
            $this->cobr       = $this->dom->getElementsByTagName("cobr")->item(0);
            $this->dup        = $this->dom->getElementsByTagName('dup');
            $this->ICMSTot    = $this->dom->getElementsByTagName("ICMSTot")->item(0);
            $this->ISSQNtot   = $this->dom->getElementsByTagName("ISSQNtot")->item(0);			
            $this->transp     = $this->dom->getElementsByTagName("transp")->item(0);
            $this->transporta = $this->dom->getElementsByTagName("transporta")->item(0);
            $this->veicTransp = $this->dom->getElementsByTagName("veicTransp")->item(0);
            $this->infAdic    = $this->dom->getElementsByTagName("infAdic")->item(0);
        }
    } //fim construct

    /**
     * montaDANFE
     * Esta função monta a DANFE conforme as informações fornecidas para a classe
     * durante sua construção.
     * Esta função constroi DANFE's com até 3 páginas podendo conter até 56 itens.
     * A definição de margens e posições iniciais para a impressão são estabelecidas no
     * pelo conteúdo da funçao e podem ser modificados.
     * @package NFePHP
     * @name montaDANFE
     * @version 1.3
     * @param string $orientacao (Opcional) Estabelece a orientação da impressão (ex. P-retrato)
     * @param string $papel (Opcional) Estabelece o tamanho do papel (ex. A4)
     * @return string O ID da NFe numero de 44 digitos extraido do arquivo XML
     * @todo Impressão paisagem
     * @todo Inclusão de campos de NFe de serviços
     */
    public function montaDANFE($orientacao='P',$papel='A4',$logoAlign='C'){
        $this->orientacao = $orientacao;
        $this->papel = $papel;
        $this->logoAlign = $logoAlign;
        //instancia a classe pdf
        $this->pdf = new PDF_Code128($this->orientacao, 'mm', $this->papel);
        // margens do PDF
        $margSup = 2;
        $margEsq = 2;
        $margDir = 2;
        // posição inicial do relatorio
        $xInic = 1;
        $yInic = 1;
        if($papel =='A4'){ //A4 210x297mm
            $maxW = 210;
            $maxH = 297;
        }
        //total inicial de paginas
        $totPag = 1;
        //largura imprimivel em mm
        $this->wPrint = $maxW-($margEsq+$xInic);
        //comprimento imprimivel em mm
        $this->hPrint = $maxH-($margSup+$yInic);
        // estabelece contagem de paginas
        $this->pdf->AliasNbPages();
        // fixa as margens
        $this->pdf->SetMargins($margEsq,$margSup,$margDir);
        $this->pdf->SetDrawColor(0,0,0);
        $this->pdf->SetFillColor(255,255,255);
        // inicia o documento
        $this->pdf->Open();
        // adiciona a primeira página
        $this->pdf->AddPage($this->orientacao, $this->papel);
        $this->pdf->SetLineWidth(0.1);
        $this->pdf->SetTextColor(0,0,0);

	//##################################################################
        // CALCULO DO NUMERO DE PAGINAS A SEREM IMPRESSAS
        //##################################################################

	//Verificando quantas linhas serão usadas para impressão das duplicatas
        $linhasDup = 0;
        if ( ($this->dup->length > 0) && ($this->dup->length <= 7) ) {
            $linhasDup = 1;
	} elseif ( ($this->dup->length > 7) && ($this->dup->length <= 14) ) {
            $linhasDup = 2;
	} elseif ( ($this->dup->length > 14) && ($this->dup->length <= 21) ) {
            $linhasDup = 3;
	} elseif ($this->dup->length > 21) {
            $linhasDup = 3;
	} else{
            $linhasDup = 0;
        }
        //verifica se será impresso a linha dos serviços ISSQN
	$linhaISSQN = 0;
        if ( isset($this->ISSQNtot) ){
            if ($this->ISSQNtot->getElementsByTagName("vServ")->item(0)->nodeValue > 0 ) {
                $linhaISSQN = 1;
            }
        }
        //calcular a altura necessária para os dados adicionais
        $this->wAdic = round($this->wPrint*0.66,0);
        $fontProduto = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        $this->textoAdic = '';
        if( isset($this->retirada) ){
            $txRetCNPJ = !empty($this->retirada->getElementsByTagName("CNPJ")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("CNPJ")->item(0)->nodeValue : '';
            $txRetxLgr = !empty($this->retirada->getElementsByTagName("xLgr")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xLgr")->item(0)->nodeValue : '';
            $txRetnro = !empty($this->retirada->getElementsByTagName("nro")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("nro")->item(0)->nodeValue : 's/n';
            $txRetxCpl = !empty($this->retirada->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xCpl")->item(0)->nodeValue : '';
            $txRetxBairro = !empty($this->retirada->getElementsByTagName("xBairro")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xBairro")->item(0)->nodeValue : '';
            $txRetxMun = !empty($this->retirada->getElementsByTagName("xMun")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xMun")->item(0)->nodeValue : '';
            $txRetUF = !empty($this->retirada->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("UF")->item(0)->nodeValue : '';
            $this->textoAdic .= "LOCAL DA RETIRADA DA MERCADORIA : " . $txRetxLgr . ',' . $txRetnro . ' ' . $txRetxCpl . ' - ' . $txRetxBairro . ' ' .$txRetxMun . ' - ' .$txRetUF . "\r\n";
        }
        //dados do local de entrega da mercadoria
        if( isset($this->entrega) ){
            $txRetCNPJ = !empty($this->entrega->getElementsByTagName("CNPJ")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("CNPJ")->item(0)->nodeValue : '';
            $txRetxLgr = !empty($this->entrega->getElementsByTagName("xLgr")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xLgr")->item(0)->nodeValue : '';
            $txRetnro = !empty($this->entrega->getElementsByTagName("nro")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("nro")->item(0)->nodeValue : 's/n';
            $txRetxCpl = !empty($this->entrega->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xCpl")->item(0)->nodeValue : '';
            $txRetxBairro = !empty($this->entrega->getElementsByTagName("xBairro")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xBairro")->item(0)->nodeValue : '';
            $txRetxMun = !empty($this->entrega->getElementsByTagName("xMun")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xMun")->item(0)->nodeValue : '';
            $txRetUF = !empty($this->entrega->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("UF")->item(0)->nodeValue : '';
            if( $this->textoAdic != '' ){
                $this->textoAdic .= ". \r\n";
            }
            $this->textoAdic .= "LOCAL DA ENTREGA DA MERCADORIA : " . $txRetxLgr . ',' . $txRetnro . ' ' . $txRetxCpl . ' - ' . $txRetxBairro . ' ' .$txRetxMun . ' - ' .$txRetUF . "\r\n";
        }
        //informações adicionais
        if (isset($this->infAdic)){
            $i = 0;
            if( $this->textoAdic != '' ){
                $this->textoAdic .= ". \r\n";
            }
            $this->textoAdic .= !empty($this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue) ? 'Inf. Contribuinte: ' . trim($this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue) : '';
            $this->textoAdic .= !empty($this->infAdic->getElementsByTagName("infAdFisco")->item(0)->nodeValue) ? "\r\n Inf. fisco: " . trim($this->infAdic->getElementsByTagName("infAdFisco")->item(0)->nodeValue) : '';
            $obsCont = $this->infAdic->getElementsByTagName("obsCont");
            if (isset($obsCont)){
                foreach ($obsCont as $obs){
                    $campo =  $obsCont->item($i)->getAttribute("xCampo");
                    $xTexto = !empty($obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue) ? $obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue : '';
                    $this->textoAdic .= "\r\n" . $campo . ':  ' . trim($xTexto);
                    $i++;
                }
            }
        } else {
            $this->textoAdic = '';
        }
        $alinhas = explode("\n",$this->textoAdic);
        $numlinhasdados = 0;
        foreach ($alinhas as $linha){
            $numlinhasdados += $this->__getNumLines($linha,$this->wAdic,$fontProduto);
        }
        $hdadosadic = round(($numlinhasdados+3) * $this->pdf->FontSize,0);
        if ($hdadosadic < 10 ){
            $hdadosadic = 10;
        }
        
        //altura disponivel para os campos da DANFE
        $hcanhoto = 23;//para canhoto
        $hcabecalho = 47;//para cabeçalho
        $hdestinatario = 25;//para destinatario
        $hduplicatas = 12;//para cada grupo de 7 duplicatas
        $himposto = 18;// para imposto
        $htransporte = 25;// para transporte
        $hissqn = 11;// para issqn
        $hfooter = 5;// para rodape
        $hCabecItens = 4;
        //alturas disponiveis para os dados
        $hDispo1 = $this->hPrint - ($hcanhoto + $hcabecalho + $hdestinatario + ($linhasDup * $hduplicatas) + $himposto + $htransporte + ($linhaISSQN * $hissqn) + $hdadosadic + $hfooter + $hCabecItens);
        $hDispo2 = $this->hPrint - ($hcabecalho + $hfooter + $hCabecItens);
        //Contagem da altura ocupada para impressão dos itens
	$fontProduto = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        $i = 0;
        $numlinhas = 0;
        $hUsado = 0;
        $w2 = round($this->wPrint*0.31,0)-1;
	while ($i < $this->det->length){
  	    $texto = $this->__descricaoProduto( $this->det->item($i) ) ;
	    $numlinhas = $this->__getNumLines($texto,$w2,$fontProduto);
            $hUsado += round(($numlinhas * $this->pdf->FontSize)+1,0);
            $i += 1;
        } //fim da soma das areas de itens usadas
	
        //calculo do numero de paginas necessarias
        if($hUsado > $hDispo1){
            //serão necessárias mais paginas
            $hOutras = $hUsado - $hDispo1;
            $totPag = 1 + ceil($hOutras / $hDispo2);
        } else {
            //sera necessaria apenas uma pagina
            $totPag = 1;
        }

        //montagem da primeira página
        $pag = 1;
        $x = $xInic;
        $y = $yInic;
        //coloca o canhoto da NFe
        $y = $this->__canhotoDANFE($x,$y);		
        //coloca o cabeçalho
        $y = $this->__cabecalhoDANFE($x,$y,$pag,$totPag);
        //coloca os dados do destinatário
        $y = $this->__destinatarioDANFE($x,$y+1);
        //coloca os dados das faturas
        $y = $this->__faturaDANFE($x,$y+1);
        //coloca os dados dos impostos e totais da NFe
        $y = $this->__impostoDANFE($x,$y+1);
        //coloca os dados do trasnporte
        $y = $this->__transporteDANFE($x,$y+1);
        //itens da DANFE
	$nInicial = 0;
        $y = $this->__itensDANFE($x,$y+1,$nInicial,$hDispo1,$pag,$totPag);
        //coloca os dados do ISSQN
	if ($linhaISSQN == 1) {
            $y = $this->__issqnDANFE($x,$y+4);
	} else {
            $y += 4;
        }
        //coloca os dados adicionais da NFe
        $y = $this->__dadosAdicionaisDANFE($x,$y,$pag,$hdadosadic);
        //coloca o rodapé da página
        $this->__rodapeDANFE();
        
        //loop para páginas seguintes
        for ( $n = 2; $n <= $totPag; $n++ ) {
            // fixa as margens
            $this->pdf->SetMargins($margEsq,$margSup,$margDir);
            //adiciona nova página
            $this->pdf->AddPage($this->orientacao, $this->papel);
            //ajusta espessura das linhas
            $this->pdf->SetLineWidth(0.1);
            //seta a cor do texto para petro
            $this->pdf->SetTextColor(0,0,0);
            // posição inicial do relatorio
            $x = $xInic;
            $y = $yInic;
            //coloca o cabeçalho na página adicional
            $y = $this->__cabecalhoDANFE($x,$y,$n,$totPag);
            //coloca os itens na página adicional
            $y = $this->__itensDANFE($x,$y+1,$nInicial,$hDispo2,$pag,$totPag);
            //coloca o rodapé da página
            $this->__rodapeDANFE();
        }
        //retorna o ID na NFe
        return str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
    }//fim da função montaDANFE

    /**
     * printDANFE
     * Esta função envia a DANFE em PDF criada para o dispositivo informado.
     * O destino da impressão pode ser :
     * I-browser
     * D-browser com download
     * F-salva em um arquivo local com o nome informado
     * S-retorna o documento como uma string e o nome é ignorado.
     * Para enviar o pdf diretamente para uma impressora indique o
     * nome da impressora e o destino deve ser 'S'.
     * @package NFePHP
     * @name printDANFE
     * @version 1.0
     * @param string $nome Path completo com o nome do arquivo pdf
     * @param string $destino Direção do envio do PDF
     * @param string $printer Identificação da impressora no sistema
     * @return string Caso o destino seja S o pdf é retornado como uma string
     * @todo Rotina de impressão direta do arquivo pdf criado
     */
    public function printDANFE($nome='',$destino='I',$printer=''){
        $arq = $this->pdf->Output($nome,$destino);
        if ( $destino == 'S' ){
            //aqui pode entrar a rotina de impressão direta
        }
        return $arq;

        /*
           Opção 1 - exemplo de script shell usando acroread
             #!/bin/sh
            if ( $# == 2 ) then
                set printer=$2
            else
                set printer=$PRINTER
            fi
            if( $1 != "" ) then
                cat ${1} | acroread -toPostScript | lpr -P $printer
                echo ${1} sent to $printer ... OK!
            else
                echo PDF Print: No filename defined!
            fi

            Opção 2 -
            salvar pdf em arquivo temporario
            converter pdf para ps usando pdf2ps do linux
            imprimir ps para printer usando lp ou lpr
            remover os arquivos temporarios pdf e ps

            Opção 3 -
            salvar pdf em arquivo temporario
            imprimir para printer usando lp ou lpr com system do php
            remover os arquivos temporarios pdf


        */
    } //fim função printDANFE

    /**
     *__cabecalhoDANFE
     * Monta o cabelhalho da DANFE
     * @package NFePHP
     * @name __cabecalhoDANFE
     * @version 1.1
     * @param number $x Posição horizontal inicial, canto esquerdo
     * @param number $y Posição vertical inicial, canto superior
     * @param number $pag Número da Página
     * @param number$totPag Total de páginas
     * @return number Posição vertical final
     */
    private function __cabecalhoDANFE($x=0,$y=0,$pag='1',$totPag='1'){
        $oldX = $x;
        $oldY = $y;
        //####################################################################################
        //coluna esquerda identificação do emitente
        $w=round($this->wPrint*0.41,0);// 80;
        $w1 = $w;
        $h=32;
        $oldY += $h;
        $this->__textBox($x,$y,$w,$h);
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'I');
        $texto = 'IDENTIFICAÇÃO DO EMITENTE';
        $this->__textBox($x,$y,$w,5,$texto,$aFont,'T','C',0,'');
        //estabelecer o alinhamento
        //pode ser left L , center C , right R
        //se for left separar 1/3 da largura para o tamanho da imagem
        //os outros 2/3 serão usados para os dados do emitente
        //se for center separar 1/2 da altura para o logo e 1/2 para os dados
        //se for right separa 2/3 para os dados e o terço seguinte para o logo
        //se não houver logo centraliza dos dados do emitente
        // coloca o logo
        if (is_file($this->logomarca)){
            $logoInfo=getimagesize($this->logomarca);
            //largura da imagem em mm
            $logoWmm = ($logoInfo[0]/72)*25.4;
            //altura da imagem em mm
            $logoHmm = ($logoInfo[1]/72)*25.4;
            if ($this->logoAlign=='L'){
                $nImgW = round($w/3,0);
                $nImgH = round($logoHmm * ($nImgW/$logoWmm),0);
                $xImg = $x+1;
                $yImg = round(($h-$nImgH)/2,0)+$y;
                //estabelecer posições do texto
                $x1 = round($xImg + $nImgW +1,0);
                $y1 = round($h/3+$y,0);
                $tw = round(2*$w/3,0);
            }
            if ($this->logoAlign=='C'){
                $nImgH = round($h/2,0);
                $nImgW = round($logoWmm * ($nImgH/$logoHmm),0);
                $xImg = round(($w-$nImgW)/2+$x,0);
                $yImg = $y+3;
                $x1 = $x;
                $y1 = round($yImg + $nImgH + 1,0);
                $tw = $w;
            }
            if($this->logoAlign=='R'){
                $nImgW = round($w/3,0);
                $nImgH = round($logoHmm * ($nImgW/$logoWmm),0);
                $xImg = round($x+($w-(1+$nImgW)),0);
                $yImg = round(($h-$nImgH)/2,0)+$y;
                $x1 = $x;
                $y1 = round($h/3+$y,0);
                $tw = round(2*$w/3,0);
            }
            $this->pdf->Image($this->logomarca, $xImg, $yImg, $nImgW, $nImgH, 'jpeg');
        } else {
            $x1 = $x;
            $y1 = round($h/3+$y,0);
            $tw = $w;
        }
        //Nome emitente
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'B');
        $texto = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue;
        $this->__textBox($x1,$y1,$tw,8,$texto,$aFont,'T','C',0,'');
        //endereço
        $y1 = $y1+3;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        $fone = !empty($this->enderEmit->getElementsByTagName("fone")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("fone")->item(0)->nodeValue : '';
        $foneLen = strlen($fone);
        if ($foneLen > 0 ){
            $fone2 = substr($fone,0,$foneLen-4);
            $fone1 = substr($fone,0,$foneLen-8);
            $fone = '(' . $fone1 . ') ' . substr($fone2,-4) . '-' . substr($fone,-4);
        } else {
            $fone = '';
        }
        $lgr = !empty($this->enderEmit->getElementsByTagName("xLgr")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("xLgr")->item(0)->nodeValue : '';
	$nro = !empty($this->enderEmit->getElementsByTagName("nro")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("nro")->item(0)->nodeValue : '';
	$cpl = !empty($this->enderEmit->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("xCpl")->item(0)->nodeValue : '';
	$bairro = !empty($this->enderEmit->getElementsByTagName("xBairro")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("xBairro")->item(0)->nodeValue : '';
	$CEP = !empty($this->enderEmit->getElementsByTagName("CEP")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("CEP")->item(0)->nodeValue : ' ';
	$CEP = $this->__format($CEP,"#####-###");
	$mun = !empty($this->enderEmit->getElementsByTagName("xMun")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("xMun")->item(0)->nodeValue : '';
	$UF = !empty($this->enderEmit->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->enderEmit->getElementsByTagName("UF")->item(0)->nodeValue : '';
	$texto = $lgr . "," . $nro . "  " . $cpl . "\n" . $bairro . " - " . $CEP . "\n" . $mun . " - " . $UF . " " . "Fone/Fax: " . $fone;
        $this->__textBox($x1,$y1,$tw,8,$texto,$aFont,'T','C',0,'');

        //####################################################################################
        //coluna central Danfe
        $x += $w;
        $w=round($this->wPrint * 0.17,0);//35;
        $w2 = $w;
        $h = 32;
        $this->__textBox($x,$y,$w,$h);
        $texto = "DANFE";
        $aFont = array('font'=>$this->fontePadrao,'size'=>14,'style'=>'B');
        $this->__textBox($x,$y+1,$w,$h,$texto,$aFont,'T','C',0,'');
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'');
        $texto = 'Documento Auxiliar da Nota Fiscal Eletrônica';
        $h = 20;
	$this->__textBox($x,$y+6,$w,$h,$texto,$aFont,'T','C',0,'',FALSE);
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'');
        $texto = '0 - ENTRADA';
        $y1 = $y + 14;
        $h = 8;
        $this->__textBox($x+2,$y1,$w,$h,$texto,$aFont,'T','L',0,'');
        $texto = '1 - SAÍDA';
        $y1 = $y + 17;
        $this->__textBox($x+2,$y1,$w,$h,$texto,$aFont,'T','L',0,'');
        //tipo de nF
        $aFont = array('font'=>$this->fontePadrao,'size'=>12,'style'=>'B');
        $y1 = $y + 13;
        $h = 7;
        $texto = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        $this->__textBox($x+27,$y1,5,$h,$texto,$aFont,'C','C',1,'');
        //numero da NF
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $y1 = $y + 20;
        $numNF = str_pad($this->ide->getElementsByTagName('nNF')->item(0)->nodeValue, 9, "0", STR_PAD_LEFT);
        $numNF = $this->__format($numNF,"###.###.###");
        $texto = "Nº. " . $numNF;
        $this->__textBox($x,$y1,$w,$h,$texto,$aFont,'C','C',0,'');
        //Série
        $y1 = $y + 23;
        $serie = str_pad($this->ide->getElementsByTagName('serie')->item(0)->nodeValue, 3, "0", STR_PAD_LEFT);
        $texto = "Série " . $serie;
        $this->__textBox($x,$y1,$w,$h,$texto,$aFont,'C','C',0,'');
        //numero paginas
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'I');
        $y1 = $y + 26;
        $texto = "Folha " . $pag . "/" . $totPag;
        $this->__textBox($x,$y1,$w,$h,$texto,$aFont,'C','C',0,'');

        //####################################################################################
        //coluna codigo de barras
        $x += $w;
        $w = ($this->wPrint-$w1-$w2);//85;
        $w3 = $w;
        $h = 32;
        $this->__textBox($x,$y,$w,$h);
        $this->pdf->SetFillColor(0,0,0);
        $chave_acesso = str_replace('NFe', '', $this->infNFe->getAttribute("Id"));
        $bW = 75;
        $bH = 12;
        //codigo de barras
        $this->pdf->Code128($x+(($w-$bW)/2),$y+2,$chave_acesso,$bW,$bH);
        //linhas divisorias
        $this->pdf->Line($x,$y+4+$bH,$x+$w,$y+4+$bH);
        $this->pdf->Line($x,$y+12+$bH,$x+$w,$y+12+$bH);
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $y1 = $y+4+$bH;
        $h = 7;
        $texto = 'CHAVE DE ACESSO';
        $this->__textBox($x,$y1,$w,$h,$texto,$aFont,'T','L',0,'');
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'B');
        $y1 = $y+8+$bH;
        $texto = $this->__format( $chave_acesso,"####-####-####-####-####-####-####-####-####-####-####");
        //$texto = $chave_acesso;
        $this->__textBox($x+2,$y1,$w-2,$h,$texto,$aFont,'T','C',0,'');
        $texto = 'Consulta de autenticidade no portal nacional da NF-e';
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'');
        $y1 = $y+12+$bH;
        $this->__textBox($x+2,$y1,$w-2,$h,$texto,$aFont,'T','C',0,'');
        $texto = 'www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora';
        $aFont = array('font'=>$this->fontePadrao,'size'=>8,'style'=>'');
        $y1 = $y+16+$bH;
        $this->__textBox($x+2,$y1,$w-2,$h,$texto,$aFont,'T','C',0,'http://www.nfe.fazenda.gov.br/portal ou no site da Sefaz Autorizadora');

        //####################################################################################
        //Dados da NF do cabeçalho
        //natureza da operação
        $texto = 'NATUREZA DA OPERAÇÃO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $w = $w1+$w2;
        $y = $oldY;
        $oldY += $h;
        $x = $oldX;
        $h = 7;
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->ide->getElementsByTagName("natOp")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //PROTOCOLO DE AUTORIZAÇÃO DE USO
        $texto = 'PROTOCOLO DE AUTORIZAÇÃO DE USO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $x += $w;
        $w = $w3;
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        // algumas NFe podem estar sem o protocolo de uso portanto sua existencia deve ser
        // testada antes de tentar obter a informação.
        // NOTA : DANFE sem protocolo deve existir somente no caso de contingência !!!
        if( isset( $this->nfeProc ) ) {
            $texto = !empty($this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue) ? $this->nfeProc->getElementsByTagName("nProt")->item(0)->nodeValue : '';
            $tsHora = $this->__convertTime($this->nfeProc->getElementsByTagName("dhRecbto")->item(0)->nodeValue);
            if ($texto != ''){
                $texto .= "  -  " . date('d/m/Y   H:i:s',$tsHora);
            }
            $cStat = $this->nfeProc->getElementsByTagName("cStat")->item(0)->nodeValue;
        } else {
            $texto = '';
            $cStat = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');

        //####################################################################################
        //INSCRIÇÃO ESTADUAL
        $w = round($this->wPrint * 0.333,0);
        $y += $h;
        $oldY += $h;
        $x = $oldX;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->emit->getElementsByTagName("IE")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //INSCRIÇÃO ESTADUAL DO SUBST. TRIBUT.
        $x += $w;
        $texto = 'INSCRIÇÃO ESTADUAL DO SUBST. TRIBUT.';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->emit->getElementsByTagName("IEST")->item(0)->nodeValue) ? $this->emit->getElementsByTagName("IEST")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //CNPJ
        $x += $w;
        $w = ($this->wPrint-(2*$w));
        $texto = 'CNPJ';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->emit->getElementsByTagName("CNPJ")->item(0)->nodeValue;
        $texto = $this->__format($texto,"##.###.###/####-##");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');

        //####################################################################################
        //Indicação de NF Homologação
        $tpAmb = $this->ide->getElementsByTagName('tpAmb')->item(0)->nodeValue;
        //indicar cancelamento
        if ( $cStat == '101') {
            //101 Cancelamento
            $x = 10;
            $y = $this->hPrint-130;
            $h = 25;
            $w = $this->wPrint-(2*$x);
            $this->pdf->SetTextColor(90,90,90);
            $texto = "NFe CANCELADA";
            $aFont = array('font'=>$this->fontePadrao,'size'=>48,'style'=>'B');
            $this->__textBox($x,$y,$w,$h,$texto,$aFont,'C','C',0,'');
            $this->pdf->SetTextColor(0,0,0);
        }
        //indicar sem valor
        if ( $tpAmb != 1 ) {
            $x = 10;
            $y = round($this->hPrint/2,0);
            $h = 5;
            $w = $this->wPrint-(2*$x);
            $this->pdf->SetTextColor(90,90,90);
            $texto = "SEM VALOR FISCAL";
            $aFont = array('font'=>$this->fontePadrao,'size'=>48,'style'=>'B');
            $this->__textBox($x,$y,$w,$h,$texto,$aFont,'C','C',0,'');
            $aFont = array('font'=>$this->fontePadrao,'size'=>30,'style'=>'B');
            $texto = "AMBIENTE DE HOMOLOGAÇÃO";
            $this->__textBox($x,$y+12,$w,$h,$texto,$aFont,'C','C',0,'');
            $this->pdf->SetTextColor(0,0,0);
        }
        return $oldY;
    } //fim __cabecalhoDANFE

    /**
     * __destinatarioDANFE
     * Monta o campo com os dados do destinatário na DANFE.
     * @package NFePHP
     * @name __destinatarioDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    private function __destinatarioDANFE($x=0,$y=0){
        
        //####################################################################################
        //DESTINATÁRIO / REMETENTE
        $oldX = $x;
        $oldY = $y;
        $w = $this->wPrint;
        $h = 7;
        $texto = 'DESTINATÁRIO / REMETENTE';
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',0,'');
        //NOME / RAZÃO SOCIAL
        $w = round($this->wPrint*0.61,0);
        $w1 = $w;
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','L',0,'');
        //CNPJ / CPF
        $x += $w;
        $w = round($this->wPrint*0.23,0);
        $w2 = $w;
        $texto = 'CNPJ / CPF';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        if ( !empty($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue) ) {
            $texto = $this->__format($this->dest->getElementsByTagName("CNPJ")->item(0)->nodeValue,"###.###.###/####-##");
        } else {
            $texto = !empty($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue) ? $this->__format($this->dest->getElementsByTagName("CPF")->item(0)->nodeValue,"###.###.###-##") : '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //DATA DA EMISSÃO
        $x += $w;
        $w = $this->wPrint-($w1+$w2);
        $wx = $w;
        $texto = 'DATA DA EMISSÃO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->__ymd2dmy($this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue);
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //ENDEREÇO
        $w = round($this->wPrint*0.47,0);
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $texto = 'ENDEREÇO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->dest->getElementsByTagName("xLgr")->item(0)->nodeValue;
        $texto .= ', ' . $this->dest->getElementsByTagName("nro")->item(0)->nodeValue;
	$texto .= " " . $this->__simpleGetValue( $this->dest , "xCpl" , "" );
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','L',0,'',TRUE);
        //BAIRRO / DISTRITO
        $x += $w;
        $w = round($this->wPrint*0.21,0);
        $w2 = $w;
        $texto = 'BAIRRO / DISTRITO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->dest->getElementsByTagName("xBairro")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //CEP
        $x += $w;
        $w = $this->wPrint-$w1-$w2-$wx;
        $w2 = $w;
        $texto = 'CEP';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
	$texto = !empty($this->dest->getElementsByTagName("CEP")->item(0)->nodeValue) ? $this->dest->getElementsByTagName("CEP")->item(0)->nodeValue : '';
        $texto = $this->__format($texto,"#####-###");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //DATA DA SAÍDA
        $x += $w;
        $w = $wx;
        $texto = 'DATA DA SAÍDA';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ide->getElementsByTagName("dSaiEnt")->item(0)->nodeValue) ? $this->ide->getElementsByTagName("dSaiEnt")->item(0)->nodeValue:"";
        $texto = $this->__ymd2dmy($texto);
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //MUNICÍPIO
        $w = $w1;
        $y += $h;
        $x = $oldX;
        $texto = 'MUNICÍPIO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->dest->getElementsByTagName("xMun")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','L',0,'');
        //UF
        $x += $w;
        $w = 8;
        $texto = 'UF';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->dest->getElementsByTagName("UF")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //FONE / FAX
        $x += $w;
        $w = round(($this->wPrint -$w1-$wx-8)/2,0);
        $w3 = $w;
        $texto = 'FONE / FAX';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->dest->getElementsByTagName("fone")->item(0)->nodeValue) ? $this->__format($this->dest->getElementsByTagName("fone")->item(0)->nodeValue,'(##) ####-####') : '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //INSCRIÇÃO ESTADUAL
        $x += $w;
        $w = $this->wPrint -$w1-$wx-8-$w3;
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = $this->dest->getElementsByTagName("IE")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //HORA DA SAÍDA
        $x += $w;
        $w = $wx;
        $texto = 'HORA DA SAÍDA';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');

        return ($y + $h);
    } //fim da função __destinatarioDANFE

    /**
     * __faturaDANFE
     * Monta o campo de duplicatas da DANFE
     * @package NFePHP
     * @name __faturaDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    private function __faturaDANFE($x,$y){
        
	$linha = 1;
        $h = 8+3;
	$oldx = $x;
        //verificar se existem duplicatas
        if ( $this->dup->length > 0 ) {
            //#####################################################################
            //FATURA / DUPLICATA
            $texto = "FATURA / DUPLICATA";
            $texto = $texto;
            $w = $this->wPrint;
            $h = 8;
            $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
            $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',0,'');
            $y += 3;
            $dups = "";
            $dupcont = 0;
            $nFat = $this->dup->length;
            $w = round($this->wPrint/7.018,0)-1;
            $increm = 1;
            foreach ($this->dup as $k => $d) {
                $nDup = $this->dup->item($k)->getElementsByTagName('nDup')->item(0)->nodeValue;
                $dDup = $this->__ymd2dmy($this->dup->item($k)->getElementsByTagName('dVenc')->item(0)->nodeValue);
                $vDup = 'R$ ' . number_format($this->dup->item($k)->getElementsByTagName('vDup')->item(0)->nodeValue, 2, ",", ".");
                $h = 8;
                $texto = '';
                $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
                $this->__textBox($x,$y,$w,$h,'Num.',$aFont,'T','L',1,'');
                $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
                $this->__textBox($x,$y,$w,$h,$nDup,$aFont,'T','R',0,'');
                $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
                $this->__textBox($x,$y,$w,$h,'Venc.',$aFont,'C','L',0,'');
                $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
                $this->__textBox($x,$y,$w,$h,$dDup,$aFont,'C','R',0,'');
                $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
                $this->__textBox($x,$y,$w,$h,'Valor',$aFont,'B','L',0,'');
                $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
                $this->__textBox($x,$y,$w,$h,$vDup,$aFont,'B','R',0,'');
                $x += $w+$increm;
                $dupcont += 1;
                if ($dupcont > 6) {
                    $y += 9;
                    $x = $oldx;
                    $dupcont = 0;
                    $linha += 1;
                }
                if ($linha == 4){
                    $linha = 3;
                    break;
                }
            }
            if ($dupcont == 0){
                $y = $y - 9;
                $linha = $linha -1;
            }
            return ($y+$h);
        } else {
            $linha = 0;
            return ($y-2);
	}	
    } //fim da função __faturaDANFE

    /**
     * __impostoDANFE
     * Monta o campo de impostos e totais da DANFE
     * @package NFePHP
     * @name __impostoDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    private function __impostoDANFE($x,$y){
        $oldX = $x;
        //#####################################################################
        $texto = "CÁLCULO DO IMPOSTO";
        $w = $this->wPrint;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,8,$texto,$aFont,'T','L',0,'');
        //BASE DE CÁLCULO DO ICMS
        $w = round($this->wPrint*0.21,0);
        $w1 = $w;
        $y += 3;
        $h = 7;
        $texto = 'BASE DE CÁLCULO DO ICMS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = number_format($this->ICMSTot->getElementsByTagName("vBC")->item(0)->nodeValue, 2, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR DO ICMS
        $x += $w;
        $w = round($this->wPrint*0.18,0);
        $w2 = $w;
        $texto = 'VALOR DO ICMS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = number_format($this->ICMSTot->getElementsByTagName("vICMS")->item(0)->nodeValue, 2, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //BASE DE CÁLCULO DO ICMS S.T.
        $x += $w;
        $w = $w2;
        $texto = 'BASE DE CÁLCULO DO ICMS S.T.';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ICMSTot->getElementsByTagName("vBCST")->item(0)->nodeValue) ? number_format($this->ICMSTot->getElementsByTagName("vBCST")->item(0)->nodeValue, 2, ",", ".") : '0,00';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR DO ICMS SUBSTITUIÇÃO
        $x += $w;
        $w = $w2;
        $texto = 'VALOR DO ICMS SUBSTITUIÇÃO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ICMSTot->getElementsByTagName("vST")->item(0)->nodeValue) ? number_format($this->ICMSTot->getElementsByTagName("vST")->item(0)->nodeValue, 2, ",", ".") : '0,00';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR TOTAL DOS PRODUTOS
        $x += $w;
        $w = $this->wPrint-($w1+3*$w2);
        $wx = $w;
        $texto = 'VALOR TOTAL DOS PRODUTOS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = number_format($this->ICMSTot->getElementsByTagName("vProd")->item(0)->nodeValue, 2, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //#####################################################################
        //VALOR DO FRETE
        $w = round($this->wPrint*0.15,0);
        $w1 = $w;
        $y += $h;
        $x = $oldX;
        $h = 7;
        $texto = 'VALOR DO FRETE';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = number_format($this->ICMSTot->getElementsByTagName("vFrete")->item(0)->nodeValue, 2, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR DO SEGURO
        $x += $w;
        $w = $w1;//31;
        $texto = 'VALOR DO SEGURO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ICMSTot->getElementsByTagName("vSeg")->item(0)->nodeValue) ? number_format($this->ICMSTot->getElementsByTagName("vSeg")->item(0)->nodeValue, 2, ",", ".") : '0,00';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //DESCONTO
        $x += $w;
        $w = $w1;
        $texto = 'DESCONTO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ICMSTot->getElementsByTagName("vDesc")->item(0)->nodeValue) ? number_format($this->ICMSTot->getElementsByTagName("vDesc")->item(0)->nodeValue, 2, ",", ".") : '0,00';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //OUTRAS DESPESAS
        $x += $w;
        $w = $w1;
        $texto = 'OUTRAS DESPESAS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ICMSTot->getElementsByTagName("vOutro")->item(0)->nodeValue) ? number_format($this->ICMSTot->getElementsByTagName("vOutro")->item(0)->nodeValue, 2, ",", ".") : '0,00';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR TOTAL DO IPI
        $x += $w;
        $w = $this->wPrint-($wx+4*$w1);
        $texto = 'VALOR TOTAL DO IPI';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->ICMSTot->getElementsByTagName("vIPI")->item(0)->nodeValue) ? number_format($this->ICMSTot->getElementsByTagName("vIPI")->item(0)->nodeValue, 2, ",", ".") : '0,00';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR TOTAL DA NOTA
        $x += $w;
        $w = $wx;
        $texto = 'VALOR TOTAL DA NOTA';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        $texto = number_format($this->ICMSTot->getElementsByTagName("vNF")->item(0)->nodeValue, 2, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');

        return ($y+$h);
    } //fim __impostoDANFE

    /**
     * __transporteDANFE
     * Monta o campo de transportes da DANFE
     * @package NFePHP
     * @name __transporteDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    private function __transporteDANFE($x,$y){
        $oldX = $x;
        //#####################################################################
        //TRANSPORTADOR / VOLUMES TRANSPORTADOS
        $texto = "TRANSPORTADOR / VOLUMES TRANSPORTADOS";
        $w = $this->wPrint;
        $h = 7;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',0,'');
        //NOME / RAZÃO SOCIAL
        $w1 = round($this->wPrint*0.29,0);
        $y += 3;
        $texto = 'NOME / RAZÃO SOCIAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->transporta) ) {
            $texto = !empty($this->transporta->getElementsByTagName("xNome")->item(0)->nodeValue) ? $this->transporta->getElementsByTagName("xNome")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'B','L',0,'');
        //FRETE POR CONTA
        $x += $w1;
        $w2 = round($this->wPrint*0.15,0);
        $texto = 'FRETE POR CONTA';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        $tipoFrete = !empty($this->transp->getElementsByTagName("modFrete")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("modFrete")->item(0)->nodeValue : '0';
        switch( $tipoFrete ){
            case 0:
                default:
                $texto = "(0) Emitente";
                break;
            case 1:
                $texto = "(1) Dest/Rem";
                break;
            case 2:
                $texto = "(2) Terceiros";
                break;
            case 9:
                $texto = "(9) Sem Frete";
                break;
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'C','C',1,'');
        //CÓDIGO ANTT
        $x += $w2;
        $texto = 'CÓDIGO ANTT';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->veicTransp) ){
            $texto = !empty($this->veicTransp->getElementsByTagName("RNTC")->item(0)->nodeValue) ? $this->veicTransp->getElementsByTagName("RNTC")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'B','C',0,'');
        //PLACA DO VEÍC
        $x += $w2;
        $texto = 'PLACA DO VEÍCULO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->veicTransp) ){
            $texto = !empty($this->veicTransp->getElementsByTagName("placa")->item(0)->nodeValue) ? $this->veicTransp->getElementsByTagName("placa")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'B','C',0,'');
        //UF
        $x += $w2;
        $w3 = round($this->wPrint*0.04,0);
        $texto = 'UF';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->veicTransp) ){
            $texto = !empty($this->veicTransp->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->veicTransp->getElementsByTagName("UF")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'B','C',0,'');
        //CNPJ / CPF
        $x += $w3;
        $w = $this->wPrint-($w1+3*$w2+$w3);
        $texto = 'CNPJ / CPF';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->transporta) ){
            $texto = !empty($this->transporta->getElementsByTagName("CNPJ")->item(0)->nodeValue) ? $this->__format($this->transporta->getElementsByTagName("CNPJ")->item(0)->nodeValue,"##.###.###/####-##") : '';
            if ($texto == ''){
                $texto = !empty($this->transporta->getElementsByTagName("CPF")->item(0)->nodeValue) ? $this->__format($this->transporta->getElementsByTagName("CPF")->item(0)->nodeValue,"###.###.###-##") : '';
            }
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //#####################################################################
        //ENDEREÇO
        $y += $h;
        $x = $oldX;
        $h = 7;
        $w1 = round($this->wPrint*0.44,0);
        $texto = 'ENDEREÇO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->transporta) ){
            $texto = !empty($this->transporta->getElementsByTagName("xEnder")->item(0)->nodeValue) ? $this->transporta->getElementsByTagName("xEnder")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'B','L',0,'');
        //MUNICÍPIO
        $x += $w1;
        $w2 = round($this->wPrint*0.30,0);
        $texto = 'MUNICÍPIO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->transporta) ){
            $texto = !empty($this->transporta->getElementsByTagName("xMun")->item(0)->nodeValue) ? $this->transporta->getElementsByTagName("xMun")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'B','C',0,'');
        //UF
        $x += $w2;
        $w3 = round($this->wPrint*0.04,0);
        $texto = 'UF';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->transporta) ){
            $texto = !empty($this->transporta->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->transporta->getElementsByTagName("UF")->item(0)->nodeValue : '';
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'B','C',0,'');
        //INSCRIÇÃO ESTADUAL
        $x += $w3;
        $w = $this->wPrint-($w1+$w2+$w3);
        $texto = 'INSCRIÇÃO ESTADUAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->transporta) ){
            $texto = $this->transporta->getElementsByTagName("IE")->item(0)->nodeValue;
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','C',0,'');
        //#####################################################################
        //QUANTIDADE
        $y += $h;
        $x = $oldX;
        $h = 7;
        $w1 = round($this->wPrint*0.10,0);
        $texto = 'QUANTIDADE';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->transp->getElementsByTagName("qVol")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("qVol")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'B','C',0,'');
        //ESPÉCIE
        $x += $w1;
        $w2 = round($this->wPrint*0.17,0);
        $texto = 'ESPÉCIE';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->transp->getElementsByTagName("esp")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("esp")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'B','C',0,'');
        //MARCA
        $x += $w2;
        $texto = 'MARCA';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->transp->getElementsByTagName("marca")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("marca")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'B','C',0,'');
        //NÚMERO
        $x += $w2;
        $texto = 'NÚMERO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'T','L',1,'');
        $texto = !empty($this->transp->getElementsByTagName("nVol")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("nVol")->item(0)->nodeValue : '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'B','C',0,'');
        //PESO BRUTO
        $x += $w2;
        $w3 = round($this->wPrint*0.20,0);
        $texto = 'PESO BRUTO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'T','L',1,'');
	$texto = !empty($this->transp->getElementsByTagName("pesoB")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("pesoB")->item(0)->nodeValue : '0.0';
        $texto = number_format($texto, 3, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'B','R',0,'');
        //PESO LÍQUIDO
        $x += $w3;
        $w = $this->wPrint -($w1+3*$w2+$w3);
        $texto = 'PESO LÍQUIDO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
	$texto = !empty($this->transp->getElementsByTagName("pesoL")->item(0)->nodeValue) ? $this->transp->getElementsByTagName("pesoL")->item(0)->nodeValue : '0.0';
        $texto = number_format($texto, 3, ",", ".");
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');

        return ($y+$h);
    } //fim __transporteDANFE


    /**
     * __descricaoProduto
     * Monta a string de descrição de cada Produto
     * @package NFePHP
     * @name __descricaoProduto
     * @version 1.0
     * @author Marcos Diez
     * @param DOM itemProd
     * @return string String com a descricao do produto
     */
    private function __descricaoProduto( $itemProd ){
      $prod = $itemProd->getElementsByTagName('prod')->item(0);
      $infAdProd = substr(!empty($itemProd->getElementsByTagName('infAdProd')->item(0)->nodeValue) ? $itemProd->getElementsByTagName('infAdProd')->item(0)->nodeValue : '',0,120);
      if (!empty($infAdProd)){
        $infAdProd = trim($infAdProd);
	$infAdProd .= ' ';
      }
      $medTxt='';
      $med = $prod->getElementsByTagName("med")->item(0);
      if( isset( $med ) ){
	$medTxt .= $this->__simpleGetValue( $med , 'nLote' , ' Lote: ');
	$medTxt .= $this->__simpleGetValue( $med , 'qLote' , ' Quant: ' );
	$medTxt .= $this->__simpleGetDate( $med , 'dFab'  , ' Fab: ' );
	$medTxt .= $this->__simpleGetDate( $med , 'dVal'  , ' Val: ' );
	$medTxt .= $this->__simpleGetValue( $med , 'vPMC'  , ' PCM: ' );
	if( $medTxt != '' ){
	  $medTxt.= ' ';
	}
      }
      $texto = $prod->getElementsByTagName("xProd")->item(0)->nodeValue . ' ' . $infAdProd . $medTxt;
      return $texto;
    } //fim __descricaoProduto

    /**
     * __simpleGetValue
     * Extrai o valor do node DOM
     * @package NFePHP
     * @version 1.0
     * @author Marcos Diez
     * @param DOM $theObj
     * @param string $keyName identificador da TAG do xml
     * @param string $extraText prefixo do retorno
     * @return string
     */
    private function __simpleGetValue( $theObj , $keyName , $extraText ){
      $vct = $theObj->getElementsByTagName( $keyName )->item(0);
      if( isset( $vct ) ){
	return $extraText . trim($vct->nodeValue);
      }
      return "";
    } //fim __simpleGetValue

    /**
     * __simpleGetDate
     * Recupera e reformata a data do padrão da NFe para dd/mm/aaaa
     * @package NFePHP
     * @version 1.0
     * @author Marcos Diez
     * @param DOM $theObj
     * @param string $keyName identificador da TAG do xml
     * @param string $extraText prefixo do retorno
     * @return string
     */
    private function __simpleGetDate( $theObj , $keyName , $extraText ){
      $vct = $theObj->getElementsByTagName( $keyName )->item(0);
      if( isset( $vct ) ){
	$theDate = explode( "-" , $vct->nodeValue );
	return $extraText . $theDate[2] . "/" . $theDate[1] . "/" . $theDate[0];
      }
      return "";
    } //fim __simpleGetDate

    /**
     * __itensDANFE
     * Monta o campo de itens da DANFE
     * @package NFePHP
     * @name __itensDANFE
     * @version 1.3
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @param number $nInicio Número do item inicial
     * @param number $max Número do item final
     * @param number $hmax Haltura máxima do campo de itens em mm
     * @return number Posição vertical final
     */
    private function __itensDANFE($x,$y, &$nInicio,$hmax,$pag=0,$totpag=0) {
        $oldX = $x;
        $oldY = $y;
        //#####################################################################
        //DADOS DOS PRODUTOS / SERVIÇOS
        $texto = "DADOS DOS PRODUTOS / SERVIÇOS ";
        $w = $this->wPrint;
        $h = 4;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',0,'');
        $y += 3;
        $w = $this->wPrint;
        //desenha a caixa dos dados dos itens da NF
        $texto = '';
        $this->__textBox($x,$y,$w,$hmax);
        //##################################################################################
        // cabecalho LOOP COM OS DADOS DOS PRODUTOS
        //CÓDIGO PRODUTO
        $texto = "CÓDIGO PRODUTO";
        $w1 = round($this->wPrint*0.09,0);
        $h = 4;
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w1,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w1, $y, $x+$w1, $y+$hmax);
        //DESCRIÇÃO DO PRODUTO / SERVIÇO
        $x += $w1;
        $w2 = round($this->wPrint*0.31,0);
        $texto = 'DESCRIÇÃO DO PRODUTO / SERVIÇO';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w2, $y, $x+$w2, $y+$hmax);
        //NCM/SH
        $x += $w2;
        $w3 = round($this->wPrint*0.06,0);
        $texto = 'NCM/SH';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w3, $y, $x+$w3, $y+$hmax);
        //O/CST
        $x += $w3;
        $w4 = round($this->wPrint*0.04,0);
        $texto = 'O/CST';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w4,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w4, $y, $x+$w4, $y+$hmax);
        //CFOP
        $x += $w4;
        $w5 = round($this->wPrint*0.04,0);
        $texto = 'CFOP';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w5,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w5, $y, $x+$w5, $y+$hmax);
        //UN
        $x += $w5;
        $w6 = round($this->wPrint*0.06,0);
        $texto = 'UN';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w6,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w6, $y, $x+$w6, $y+$hmax);
        //QUANT
        $x += $w6;
        $w7 = round($this->wPrint*0.06,0);
        $texto = 'QUANT';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w7,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w7, $y, $x+$w7, $y+$hmax);
        //VALOR UNIT
        $x += $w7;
        $w8 = round($this->wPrint*0.06,0);
        $texto = 'VALOR UNIT';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w8,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w8, $y, $x+$w8, $y+$hmax);
        //VALOR TOTAL
        $x += $w8;
        $w9 = round($this->wPrint*0.06,0);
        $texto = 'VALOR TOTAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w9,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w9, $y, $x+$w9, $y+$hmax);
        //B.CÁLC ICMS
        $x += $w9;
        $w10 = round($this->wPrint*0.06,0);
        $texto = 'B.CÁLC ICMS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w10,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w10, $y, $x+$w10, $y+$hmax);
        //VALOR ICMS
        $x += $w10;
        $w11 = round($this->wPrint*0.06,0);
        $texto = 'VALOR ICMS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w11,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w11, $y, $x+$w11, $y+$hmax);
        //VALOR IPI
        $x += $w11;
        $w12 = round($this->wPrint*0.05,0);
        $texto = 'VALOR IPI';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w12,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w12, $y, $x+$w12, $y+$hmax);
        //ALÍQ. ICMS
        $x += $w12;
        $w13 = round($this->wPrint*0.035,0);
        $texto = 'ALÍQ. ICMS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w13,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($x+$w13, $y, $x+$w13, $y+$hmax);
        //ALÍQ. IPI
        $x += $w13;
        $w14 = $this->wPrint-($w1+$w2+$w3+$w4+$w5+$w6+$w7+$w8+$w9+$w10+$w11+$w12+$w13);
        $texto = 'ALÍQ. IPI';
        $this->__textBox($x,$y,$w14,$h,$texto,$aFont,'C','C',0,'',FALSE);
        $this->pdf->Line($oldX, $y+$h+1, $oldX + $this->wPrint, $y+$h+1);
        $y += 5;
        //##################################################################################
        // LOOP COM OS DADOS DOS PRODUTOS
        $i = 0;
        $hUsado = 4;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        foreach ($this->det as $d) {
            if ( $i >= $nInicio) {
	        $thisItem = $this->det->item($i); 
                $prod = $thisItem->getElementsByTagName("prod")->item(0); 
	        $textoProduto = $this->__descricaoProduto( $thisItem );
		$linhaDescr = $this->__getNumLines($textoProduto,$w2,$aFont);
                $h = round(($linhaDescr * $this->pdf->FontSize)+1,0);
                $hUsado += $h;
                if ($hUsado > $hmax){
                    //ultrapassa a capacidade para uma única página
                    //o restante dos dados serão usados nas proximas paginas
                    $nInicio = $i;
                    break;
                }
                //carrega as tags do item
                $imposto = $this->det->item($i)->getElementsByTagName("imposto")->item(0);
		$ICMS = $imposto->getElementsByTagName("ICMS")->item(0);
		$IPI  = $imposto->getElementsByTagName("IPI")->item(0);
                //corrige o x
                $x=$oldX;
                //codigo do produto				
                $texto = $prod->getElementsByTagName("cProd")->item(0)->nodeValue;
		$this->__textBox($x,$y,$w1,$h,$texto ,$aFont,'T','C',0,'');
                $x += $w1;
                //DESCRIÇÃO
		$this->__textBox($x,$y,$w2,$h,$textoProduto,$aFont,'T','L',0,'',FALSE);
                $x += $w2;
                //NCM
                $texto = !empty($prod->getElementsByTagName("NCM")->item(0)->nodeValue) ? $prod->getElementsByTagName("NCM")->item(0)->nodeValue : '';
                $this->__textBox($x,$y,$w3,$h,$texto,$aFont,'T','C',0,'');
                $x += $w3;
		if ( isset($ICMS) ){
                    $texto = $ICMS->getElementsByTagName("orig")->item(0)->nodeValue . $ICMS->getElementsByTagName("CST")->item(0)->nodeValue;
                    $this->__textBox($x,$y,$w4,$h,$texto,$aFont,'T','C',0,'');
                }
                $x += $w4;
                $texto = $prod->getElementsByTagName("CFOP")->item(0)->nodeValue;
                $this->__textBox($x,$y,$w5,$h,$texto,$aFont,'T','C',0,'');
                $x += $w5;
                $texto = $prod->getElementsByTagName("uCom")->item(0)->nodeValue;
                $this->__textBox($x,$y,$w6,$h,$texto,$aFont,'T','C',0,'');
                $x += $w6;
                $texto = number_format($prod->getElementsByTagName("qCom")->item(0)->nodeValue, 2, ",", ".");
                $this->__textBox($x,$y,$w7,$h,$texto,$aFont,'T','R',0,'');
                $x += $w7;
                $texto = number_format($prod->getElementsByTagName("vUnCom")->item(0)->nodeValue, 4, ",", ".");
                $this->__textBox($x,$y,$w8,$h,$texto,$aFont,'T','R',0,'');
                $x += $w8;
                $texto = number_format($prod->getElementsByTagName("vProd")->item(0)->nodeValue, 2, ",", ".");
                $this->__textBox($x,$y,$w9,$h,$texto,$aFont,'T','R',0,'');
                $x += $w9;
		if ( isset($ICMS) ){
                    $texto = !empty($ICMS->getElementsByTagName("vBC")->item(0)->nodeValue) ? number_format($ICMS->getElementsByTagName("vBC")->item(0)->nodeValue, 2, ",", ".") : '0,00';
                    $this->__textBox($x,$y,$w10,$h,$texto,$aFont,'T','R',0,'');
		}   
                $x += $w10;
		if (isset($ICMS)){				
                   $texto = !empty($ICMS->getElementsByTagName("vICMS")->item(0)->nodeValue) ? number_format($ICMS->getElementsByTagName("vICMS")->item(0)->nodeValue, 2, ",", ".") : '0,00';
                   $this->__textBox($x,$y,$w11,$h,$texto,$aFont,'T','R',0,'');
		}   
                $x += $w11;
                if ( isset($IPI) ){
                    $texto = !empty($IPI->getElementsByTagName("vIPI")->item(0)->nodeValue) ? number_format($IPI->getElementsByTagName("vIPI")->item(0)->nodeValue, 2, ",", ".") :'';
                } else {
                    $texto = '';
                }
                $this->__textBox($x,$y,$w12,$h,$texto,$aFont,'T','R',0,'');
                $x += $w12;
		if (isset($ICMS)){				
                   $texto = !empty($ICMS->getElementsByTagName("pICMS")->item(0)->nodeValue) ? number_format($ICMS->getElementsByTagName("pICMS")->item(0)->nodeValue, 0, ",", ".") : '0,00';
                   $this->__textBox($x,$y,$w13,$h,$texto,$aFont,'T','C',0,'');
		}   
                $x += $w13;
                if ( isset($IPI) ){
                    $texto = !empty($IPI->getElementsByTagName("pIPI")->item(0)->nodeValue) ? number_format($IPI->getElementsByTagName("pIPI")->item(0)->nodeValue, 0, ",", ".") : '';
                } else {
                    $texto = '';
                }
                $this->__textBox($x,$y,$w14,$h,$texto,$aFont,'T','C',0,'');
                $y += $h;
                $i++;
            } else{
                $i++;
            }
        }
        return $oldY+$hmax;
    } // fim __itensDANFE
	
    /**
     * __issqnDANFE
     * Monta o campo de serviços do DANFE
     * @package NFePHP
     * @name __issqnDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    private function __issqnDANFE($x,$y){
        $oldX = $x;
        //#####################################################################
        //CÁLCULO DO ISSQN
        $texto = "CÁLCULO DO ISSQN";
        $w = $this->wPrint;
        $h = 7;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',0,'');

        //INSCRIÇÃO MUNICIPAL
        $y += 3;
        $w = round($this->wPrint*0.23,0);
        $texto = 'INSCRIÇÃO MUNICIPAL';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        //inscrição municipal
	$texto = $this->emit->getElementsByTagName("im")->item(0)->nodeValue;
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','L',0,'');

        //VALOR TOTAL DOS SERVIÇOS
        $x += $w;
        $texto = 'VALOR TOTAL DOS SERVIÇOS';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->ISSQNtot) ){
            $texto = !empty($this->ISSQNtot->getElementsByTagName("vServ")->item(0)->nodeValue) ? $this->ISSQNtot->getElementsByTagName("vServ")->item(0)->nodeValue : '';
            $texto = number_format($texto, 2, ",", ".");
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //BASE DE CÁLCULO DO ISSQN
        $x += $w;
        $texto = 'BASE DE CÁLCULO DO ISSQN';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->ISSQNtot) ){
            $texto = !empty($this->ISSQNtot->getElementsByTagName("vBC")->item(0)->nodeValue) ? $this->ISSQNtot->getElementsByTagName("vBC")->item(0)->nodeValue : '';
            $texto = number_format($texto, 2, ",", ".");
        } else {
            $texto = '';
        }
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');
        //VALOR TOTAL DO ISSQN
        $x += $w;
        $w = $this->wPrint - (3 * $w);
        $texto = 'VALOR TOTAL DO ISSQN';
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        if ( isset($this->ISSQNtot) ){
            $texto = !empty($this->ISSQNtot->getElementsByTagName("vISS")->item(0)->nodeValue) ? $this->ISSQNtot->getElementsByTagName("vISS")->item(0)->nodeValue : '';
            $texto = number_format($texto, 2, ",", ".");
        } else {
            $texto = '';
        }		
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'B','R',0,'');

        return ($y+$h+1);
    } //fim __issqnDANFE
	

    /**
     *__dadosAdicionaisDANFE
     * Coloca o grupo de ados adicionais da NFe.
     * @package NFePHP
     * @name __dadosAdicionaisDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @param number $h altura do campo
     * @return number Posição vertical final
     */
    private function __dadosAdicionaisDANFE($x,$y,$pag,$h){
        $oldX = $x;
        //##################################################################################
        //DADOS ADICIONAIS
        $texto = "DADOS ADICIONAIS";
        $w = $this->wPrint;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,8,$texto,$aFont,'T','L',0,'');
        //INFORMAÇÕES COMPLEMENTARES
        $texto = "INFORMAÇÕES COMPLEMENTARES";
        $y += 3;
        //$w = round($this->wPrint*0.66,0);
        $w = $this->wAdic;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');

        /*
        //dados do local de retirada da mercadoria
        $texto = '';
        if( isset($this->retirada) ){
            $txRetCNPJ = !empty($this->retirada->getElementsByTagName("CNPJ")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("CNPJ")->item(0)->nodeValue : '';
            $txRetxLgr = !empty($this->retirada->getElementsByTagName("xLgr")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xLgr")->item(0)->nodeValue : '';
            $txRetnro = !empty($this->retirada->getElementsByTagName("nro")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("nro")->item(0)->nodeValue : 's/n';
            $txRetxCpl = !empty($this->retirada->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xCpl")->item(0)->nodeValue : '';
            $txRetxBairro = !empty($this->retirada->getElementsByTagName("xBairro")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xBairro")->item(0)->nodeValue : '';
            $txRetxMun = !empty($this->retirada->getElementsByTagName("xMun")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("xMun")->item(0)->nodeValue : '';
            $txRetUF = !empty($this->retirada->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->retirada->getElementsByTagName("UF")->item(0)->nodeValue : '';
            $texto .= "Local da Retirada da Mercadoria : " . $txRetxLgr . ',' . $txRetnro . ' ' . $txRetxCpl . ' - ' . $txRetxBairro . ' ' .$txRetxMun . ' - ' .$txRetUF . "\r\n";
        }
        //dados do local de entrega da mercadoria
        if( isset($this->entrega) ){
            $txRetCNPJ = !empty($this->entrega->getElementsByTagName("CNPJ")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("CNPJ")->item(0)->nodeValue : '';
            $txRetxLgr = !empty($this->entrega->getElementsByTagName("xLgr")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xLgr")->item(0)->nodeValue : '';
            $txRetnro = !empty($this->entrega->getElementsByTagName("nro")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("nro")->item(0)->nodeValue : 's/n';
            $txRetxCpl = !empty($this->entrega->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xCpl")->item(0)->nodeValue : '';
            $txRetxBairro = !empty($this->entrega->getElementsByTagName("xBairro")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xBairro")->item(0)->nodeValue : '';
            $txRetxMun = !empty($this->entrega->getElementsByTagName("xMun")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("xMun")->item(0)->nodeValue : '';
            $txRetUF = !empty($this->entrega->getElementsByTagName("UF")->item(0)->nodeValue) ? $this->entrega->getElementsByTagName("UF")->item(0)->nodeValue : '';
            if( $texto != '' ){
                $texto .= ". \r\n";
            }
            $texto .= "Local da Entrega da Mercadoria : " . $txRetxLgr . ',' . $txRetnro . ' ' . $txRetxCpl . ' - ' . $txRetxBairro . ' ' .$txRetxMun . ' - ' .$txRetUF . "\r\n";
        }
        //informações adicionais
        if (isset($this->infAdic)){
            $i = 0;
            if( $texto != '' ){
                $texto .= ". \r\n";
            }
            $texto .= !empty($this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue) ? 'Inf. Contribuinte: ' . $this->infAdic->getElementsByTagName("infCpl")->item(0)->nodeValue : '';
            $texto .= !empty($this->infAdic->getElementsByTagName("infAdFisco")->item(0)->nodeValue) ? "\r\n Inf. fisco: " . $this->infAdic->getElementsByTagName("infAdFisco")->item(0)->nodeValue : '';
            $obsCont = $this->infAdic->getElementsByTagName("obsCont");
            if (isset($obsCont)){
                foreach ($obsCont as $obs){
                    $campo =  $obsCont->item($i)->getAttribute("xCampo");
                    $xTexto = !empty($obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue) ?  $obsCont->item($i)->getElementsByTagName("xTexto")->item(0)->nodeValue : '';
                    $texto .= "\r\n" . $campo . ':  ' . $xTexto;
                    $i++;
                }
            }
        } else {
            $texto = '';
        }
        */
        //o texto com os dados adicionais foi obtido na função montaDANFE
        //e carregado em uma propriedade privada da classe
        //$this->wAdic com a largura do campo
        //$this->textoAdic com o texto completo do campo
        $y += 1;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        $this->__textBox($x,$y+2,$w-2,$h-3,$this->textoAdic,$aFont,'T','L',0,'',FALSE);
        //RESERVADO AO FISCO
        $texto = "RESERVADO AO FISCO";
        $x += $w;
        $y -= 1;
        $w = $this->wPrint-$w;
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'B');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'T','L',1,'');
        //inserir texto informando caso de contingência
        //1 – Normal – emissão normal;
        //2 – Contingência FS – emissão em contingência com impressão do DANFE em Formulário de Segurança;
        //3 – Contingência SCAN – emissão em contingência no Sistema de Contingência do Ambiente Nacional – SCAN;
        //4 – Contingência DPEC - emissão em contingência com envio da Declaração Prévia de Emissão em Contingência – DPEC;
        //5 – Contingência FS-DA - emissão em contingência com impressão do DANFE em Formulário de Segurança para Impressão de Documento Auxiliar de Documento Fiscal Eletrônico (FS-DA).
        $tpEmis = $this->ide->getElementsByTagName("tpEmis")->item(0)->nodeValue;
        $texto = '';
        switch($tpEmis){
            case 2:
                $texto = 'CONTINGÊNCIA FS emissão em contingência com impressão do DANFE em Formulário de Segurança';
                break;
            case 3:
                $texto = 'CONTINGÊNCIA SCAN';
                break;
            case 4:
                $texto = 'CONTINGÊNCIA DPEC';
                break;
            case 5:
                $texto = 'CONTINGÊNCIA FSDA emissão em contingência com impressão do DANFE em Formulário de Segurança para Impressão de Documento Auxiliar de Documento Fiscal Eletrônico (FS-DA)';
                break;
        }
        $y += 2;
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        $this->__textBox($x,$y,$w-2,$h-3,$texto,$aFont,'T','L',0,'',FALSE);
        return $y+$h;
    } //fim __dadosAdicionaisDANFE
    
    /**
     * __rodapeDANFE
     * Monta o rodape no final da DANFE
     * @package NFePHP
     * @name __rodapeDANFE
     * @version 1.1
     */
    private function __rodapeDANFE(){
        $x = 2;
        $y = $this->hPrint - 2;
        $texto = "Impresso em  ". date('d/m/Y   H:i:s');
        $w = $this->wPrint-4;
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'I');
        $this->__textBox($x,$y,$w,4,$texto,$aFont,'T','L',0,'');
        $texto = "DanfeNFePHP ver. " . $this->version .  "  Powered by NFePHP (GNU/GPLv3) © www.nfephp.org";
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'I');
        //$this->__textBox($x, $y, $w, $h, $text, $aFont, $vAlign, $hAlign, $border, $link, $force, $hmax, $hini)
        $this->__textBox($x,$y,$w,4,$texto,$aFont,'T','R',0,'http://www.nfephp.org');
    } //fim __rodapeDANFE


    /**
     * __canhotoDANFE
     * Monta o canhoto da DANFE
     * @package NFePHP
     * @name __canhotoDANFE
     * @version 1.1
     * @param number $x Posição horizontal canto esquerdo
     * @param number $y Posição vertical canto superior
     * @return number Posição vertical final
     */
    private function __canhotoDANFE($x,$y) {
        $oldX = $x;
        //#################################################################################
        //canhoto
        //identificação do tipo de nf entrada ou saida
        $tpNF = $this->ide->getElementsByTagName('tpNF')->item(0)->nodeValue;
        if($tpNF == '0'){
            //NFe de Entrada
            $emitente = '';
            $emitente .= $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue . " - ";
            $emitente .= $this->enderDest->getElementsByTagName("xLgr")->item(0)->nodeValue . ", ";
            $emitente .= $this->enderDest->getElementsByTagName("nro")->item(0)->nodeValue . " - ";
            $emitente .= !empty($this->enderDest->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->enderDest->getElementsByTagName("xCpl")->item(0)->nodeValue . " " : '';
            $emitente .= $this->enderDest->getElementsByTagName("xBairro")->item(0)->nodeValue . " ";
            $emitente .= $this->enderDest->getElementsByTagName("xMun")->item(0)->nodeValue . "-";
            $emitente .= $this->enderDest->getElementsByTagName("UF")->item(0)->nodeValue . "";
            $destinatario = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue . " ";
        } else {
            //NFe de Saída
            $emitente = $this->emit->getElementsByTagName("xNome")->item(0)->nodeValue . " ";
            $destinatario = '';
            $destinatario .= $this->dest->getElementsByTagName("xNome")->item(0)->nodeValue . " - ";
            $destinatario .= $this->enderDest->getElementsByTagName("xLgr")->item(0)->nodeValue . ", ";
            $destinatario .= $this->enderDest->getElementsByTagName("nro")->item(0)->nodeValue . " - ";
            $destinatario .= !empty($this->enderDest->getElementsByTagName("xCpl")->item(0)->nodeValue) ? $this->enderDest->getElementsByTagName("xCpl")->item(0)->nodeValue . " " : '';
            $destinatario .= $this->enderDest->getElementsByTagName("xBairro")->item(0)->nodeValue . " ";
            $destinatario .= $this->enderDest->getElementsByTagName("xMun")->item(0)->nodeValue . "-";
            $destinatario .= $this->enderDest->getElementsByTagName("UF")->item(0)->nodeValue . " ";
        }
        //identificação do sistema emissor
        //linha separadora do canhoto
        $w = round($this->wPrint * 0.81,0);
        $h = 10;
        //desenha caixa
        $texto = '';
        $aFont = array('font'=>$this->fontePadrao,'size'=>7,'style'=>'');
        $this->__textBox($x,$y,$w,$h,$texto,$aFont,'C','L',1,'',FALSE);
        $numNF = str_pad($this->ide->getElementsByTagName('nNF')->item(0)->nodeValue, 9, "0", STR_PAD_LEFT);
        $serie = str_pad($this->ide->getElementsByTagName('serie')->item(0)->nodeValue, 3, "0", STR_PAD_LEFT);
        $texto = "RECEBEMOS DE ";
        $texto .= $emitente;
        $texto .= " OS PRODUTOS E/OU SERVIÇOS CONSTANTES DA NOTA FISCAL ELETRÔNICA INDICADA AO LADO. EMISSÃO: ";
        $texto .= $this->__ymd2dmy($this->ide->getElementsByTagName("dEmi")->item(0)->nodeValue) ." ";
        $texto .= "VALOR TOTAL: R$ ";
        $texto .= number_format($this->ICMSTot->getElementsByTagName("vNF")->item(0)->nodeValue, 2, ",", ".") . " ";
        $texto .= "DESTINATÁRIO: ";
        $texto .= $destinatario;
        $this->__textBox($x,$y,$w-1,$h,$texto,$aFont,'C','L',0,'',FALSE);
        $x1 = $x + $w;
        $w1 = $this->wPrint - $w;
        $texto = "NF-e";
        $aFont = array('font'=>$this->fontePadrao,'size'=>14,'style'=>'B');
        $this->__textBox($x1,$y,$w1,18,$texto,$aFont,'T','C',0,'');
        $texto = "Nº. " . $this->__format($numNF,"###.###.###") . " \n";
        $texto .= "Série $serie";
        $aFont = array('font'=>$this->fontePadrao,'size'=>10,'style'=>'B');
        $this->__textBox($x1,$y,$w1,18,$texto,$aFont,'C','C',1,'');
        //DATA DO RECEBIMENTO
        $texto = "DATA DO RECEBIMENTO";
        $y += $h;
        $w2 = round($this->wPrint*0.17,0); //35;
        $aFont = array('font'=>$this->fontePadrao,'size'=>6,'style'=>'');
        $this->__textBox($x,$y,$w2,8,$texto,$aFont,'T','L',1,'');
        //IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR
        $x += $w2;
        $w3 = $w-$w2;
        $texto = "IDENTIFICAÇÃO E ASSINATURA DO RECEBEDOR";
        $this->__textBox($x,$y,$w3,8,$texto,$aFont,'T','L',1,'');
        $x = $oldX;
	$y += 9;
        $this->__hDashedLine($x,$y,$this->wPrint,0.1,80);
        $y += 2;
        return $y;
    } //fim __canhotoDANFE


    /**
     * __format
     * Função de formatação de strings.
     * @package NFePHP
     * @name __format
     * @version 1.0
     * @param string $campo String a ser formatada
     * @param string $mascara Regra de formatção da string (ex. ##.###.###/####-##)
     * @return string Retorna o campo formatado
     */
    private function __format($campo='',$mascara=''){
        //remove qualquer formatação que ainda exista
	$sLimpo = preg_replace("(/[' '-./ t]/)",'',$campo);
        // pega o tamanho da string e da mascara
        $tCampo = strlen($sLimpo);
        $tMask = strlen($mascara);
        if ( $tCampo > $tMask ) {
            $tMaior = $tCampo;
        } else {
            $tMaior = $tMask;
        }
	//contar o numero de cerquilhas da mascara
	$aMask = str_split($mascara);
	$z=0;
	$flag=FALSE;
	foreach ( $aMask as $letra ){
		if ($letra == '#'){
			$z++; 
		}	
	}
	if ( $z > $tCampo ) {
            //o campo é menor que esperado
            $flag=TRUE;
	}
        //cria uma variável grande o suficiente para conter os dados
        $sRetorno = '';
        $sRetorno = str_pad($sRetorno, $tCampo+$tMask, " ",STR_PAD_LEFT);
        //pega o tamanho da string de retorno
        $tRetorno = strlen($sRetorno);
        //se houve entrada de dados
        if( $sLimpo != '' && $mascara !='' ) {
            //inicia com a posição do ultimo digito da mascara
            $x = $tMask;
            $y = $tCampo;
            $cI = 0;
            for ( $i = $tMaior-1; $i >= 0; $i-- ) {
                if ($cI < $z){
                    // e o digito da mascara é # trocar pelo digito do campo
                    // se o inicio da string da mascara for atingido antes de terminar
                    // o campo considerar #
                    if ( $x > 0 ) {
                        $digMask = $mascara[--$x];
                    } else {
                        $digMask = '#';
                    }
                    //se o fim do campo for atingido antes do fim da mascara
                    //verificar se é ( se não for não use
                    if ( $digMask=='#' ) {
                        $cI++;
                        if ( $y > 0 ) {
                            $sRetorno[--$tRetorno] = $sLimpo[--$y];
                        } else {
                            //$sRetorno[--$tRetorno] = '';
                        }
                    } else {
                        if ( $y > 0 ) {
                            $sRetorno[--$tRetorno] = $mascara[$x];
                        } else {
                            if ($mascara[$x] =='('){
                                $sRetorno[--$tRetorno] = $mascara[$x];
                            }
                        }
                        $i++;
                    }
                }
            }
            if (!$flag){
                if ($mascara[0]!='#'){
                    $sRetorno = '(' . trim($sRetorno);
                }
            }
            return trim($sRetorno);
        } else {
            return '';
        }
    } //fim __format

    /**
     * __getNumLines
     * Obtem o numero de linhas usadas pelo texto usando a fonte especifidada
     * @package NFePHP
     * @name __getNumLines
     * @version 1.3
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param string $text
     * @param number $width
     * @param array $aFont
     * @return number numero de linhas
     */
    private function __getNumLines( $text , $width , $aFont=array('font'=>'Times','size'=>8,'style'=>'' ) ){
      $text=trim($text);
      $this->pdf->SetFont($aFont['font'],$aFont['style'],$aFont['size']);
      $n = $this->pdf->WordWrap($text,$width-0.2);
      return $n;
    } // fim __getNumLines


    /**
     *__textBox
     * Cria uma caixa de texto com ou sem bordas. Esta função perimite o alinhamento horizontal
     * ou vertical do texto dentro da caixa.
     * Atenção : Esta função é dependente de outras classes de FPDF
     * Ex. $this->__textBox(2,20,34,8,'Texto',array('fonte'=>$this->fontePadrao,'size'=>10,'style='B'),'C','L',FALSE,'http://www.nfephp.org')
     *
     * @package NFePHP
     * @name __textBox
     * @version 1.1
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param number $x Posição horizontal da caixa, canto esquerdo superior
     * @param number $y Posição vertical da caixa, canto esquerdo superior
     * @param number $w Largura da caixa
     * @param number $h Altura da caixa
     * @param string $text Conteúdo da caixa
     * @param array $aFont Matriz com as informações para formatação do texto com fonte, tamanho e estilo
     * @param string $vAlign Alinhamento vertical do texto, T-topo C-centro B-base
     * @param string $hAlign Alinhamento horizontal do texto, L-esquerda, C-centro, R-direita
     * @param boolean $border TRUE ou 1 desenha a borda, FALSE ou 0 Sem borda
     * @param string $link Insere um hiperlink
     * @param boolean $force Se for true força a caixa com uma unica linha e para isso atera o tamanho do fonte até caber no espaço, se falso mantem o tamanho do fonte e usa quantas linhas forem necessárias
     * @param number $hMax
     * @param number $vOffSet incremento forçado na na posição Y
     * @return number $height Qual a altura necessária para desenhar esta textBox
     */
    private function __textBox($x,$y,$w,$h,$text='',$aFont=array('font'=>'Times','size'=>8,'style'=>''),$vAlign='T',$hAlign='L',$border=1,$link='',$force=TRUE,$hMax=0,$vOffSet=0){
        $oldY = $y;
 	$temObs = FALSE;
 	$resetou = FALSE;
        if ($w < 0 ) {
            return $y;
        }
        //remover espaços desnecessários
        $text = trim($text);
        //converter o charset para o fpdf
        $text = utf8_decode($text);
        //desenhar a borda da caixa
        if ( $border ) {
            $this->pdf->RoundedRect($x,$y,$w,$h,0.8,'D');
        }
        //estabelecer o fonte
        $this->pdf->SetFont($aFont['font'],$aFont['style'],$aFont['size']);
        //calcular o incremento
        $incY = $this->pdf->FontSize; //tamanho da fonte na unidade definida
        if ( !$force ) {
            //verificar se o texto cabe no espaço
            $n = $this->pdf->WordWrap($text,$w);
        } else {
            $n = 1;
        }
        //calcular a altura do conjunto de texto
        $altText = $incY * $n;
        //separar o texto em linhas
        $lines = explode("\n", $text);
        //verificar o alinhamento vertical
        If ( $vAlign == 'T' ) {
            //alinhado ao topo
            $y1 = $y+$incY;
        }
        If ( $vAlign == 'C' ) {
            //alinhado ao centro
            $y1 = $y + $incY + (($h-$altText)/2);
        }
        If ( $vAlign == 'B' ) {
            //alinhado a base
            $y1 = ($y + $h)-0.5; 
        }
        //para cada linha
        foreach( $lines as $line ) {
            //verificar o comprimento da frase
            $texto = trim($line);
            $comp = $this->pdf->GetStringWidth($texto);
            if ( $force ) {
                $newSize = $aFont['size'];
                while ( $comp > $w ) {
                    //estabelecer novo fonte
                    $this->pdf->SetFont($aFont['font'],$aFont['style'],--$newSize);
                    $comp = $this->pdf->GetStringWidth($texto);
                }
            }
            //ajustar ao alinhamento horizontal
            if ( $hAlign == 'L' ) {
                $x1 = $x+0.5;
            }
            if ( $hAlign == 'C' ) {
                $x1 = $x + (($w - $comp)/2);
            }
            if ( $hAlign == 'R' ) {
                $x1 = $x + $w - ($comp+0.5);
            }

            //escrever o texto
            if ($hini >0){
                if ($y1 > ($oldY+$hini)){
                    if (!$resetou){
                        $y1 = oldY;
                        $resetou = TRUE;
                    }
                    $this->pdf->Text($x1, $y1, $texto);
		}  
            } else {
                $this->pdf->Text($x1, $y1, $texto);
            }
            //incrementar para escrever o proximo
            $y1 += $incY;
            if (($hmax > 0) && ($y1 > ($y+($hmax-1)))){
                $temObs = TRUE;
		break;
            }
        }
        return ($y1-$y)-$incY;
    } // fim função __textBox

    /**
     *__hDashedLine
     * Desenha uma linha horizontal tracejada com o FPDF
     * @package NFePHP
     * @name __hDashedLine
     * @version 1.0
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param number $x Posição horizontal inicial, em mm
     * @param number $y Posição vertical inicial, em mm
     * @param number $w Comprimento da linha, em mm
     * @param number $h Espessura da linha, em mm
     * @param number $n Numero de traços na seção da linha com o comprimento $w
     * @return none
     */
    private function __hDashedLine($x,$y,$w,$h,$n) {
        $this->pdf->SetLineWidth($h);
        $wDash=($w/$n)/2; // comprimento dos traços
        for( $i=$x; $i<=$x+$w; $i += $wDash+$wDash ) {
            for( $j=$i; $j<= ($i+$wDash); $j++ ) {
                if( $j <= ($x+$w-1) ) {
                    $this->pdf->Line($j,$y,$j+1,$y);
                }
            }
        }
    } //fim função __hDashedLine

    /**
     *__ymd2dmy
     * Converte datas no formato YMD (ex. 2009-11-02) para o formato brasileiro 02/11/2009)
     * @package NFePHP
     * @name __ymd2dmy
     * @version 1.0
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param string $data Parâmetro extraido da NFe
     * @return string Formatada para apresnetação da data no padrão brasileiro
     */
    private function __ymd2dmy($data) {
        if (!empty($data)) {
            $needle = "/";
            if (strstr($data, "-")) {
                $needle = "-";
            }
            $dt = explode($needle, $data);
            return "$dt[2]/$dt[1]/$dt[0]";
        }
    } // fim da função __ymd2dmy

    /**
     * __convertTime
     * Converte a imformação de data e tempo contida na NFe
     * @package NFePHP
     * @name __convertTime
     * @version 1.0
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param string $DH Informação de data e tempo extraida da NFe
     * @return timestamp UNIX Para uso com a funçao date do php
     */
    private function __convertTime($DH){
        if ($DH){
            $aDH = explode('T',$DH);
            $adDH = explode('-',$aDH[0]);
            $atDH = explode(':',$aDH[1]);
            $timestampDH = mktime($atDH[0],$atDH[1],$atDH[2],$adDH[1],$adDH[2],$adDH[0]);
            return $timestampDH;
        }
    } //fim da função __convertTime



} //fim da classe DANFENFe




?>