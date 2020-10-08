<?php

// Classe para repetir tentativa de emissão de NFSe PMF pendentes por Servidor Indisponível / Timeout

include_once '../objects/notaFiscal.php';
$notaFiscal = new NotaFiscal($db);
 
$stmt = $notaFiscal->readPendenteDiaMunic($data->dataEmissao, $data->codigoMunicipio);

//
// se não encontrou registros, encerra processamento
if($stmt->rowCount() == 0)
    exit;
 
include_once '../objects/notaFiscalItem.php';
include_once '../objects/itemVenda.php';
include_once '../objects/emitente.php';
include_once '../objects/tomador.php';
include_once '../objects/autorizacao.php';
include_once '../shared/utilities.php';
$utilities = new Utilities();

while ($rNF = $stmt->fetch(PDO::FETCH_ASSOC)){


    error_log($rNF["idEmitente"]." = ".$data->codigoMunicipio."\n"), 3, "../arquivosNFSe/apiErrors.log");


    $autorizacao = new Autorizacao($db);
    $autorizacao->idEmitente = $rNF["idEmitente"];
    $autorizacao->codigoMunicipio = $data->codigoMunicipio;
    $autorizacao->readOne();

    $notaFiscal = new NotaFiscal($db);
    $notaFiscal->idNotaFiscal = $rNF["idNotaFiscal"];
    $notaFiscal->readOne();

    $tomador = new Tomador($db);
    $tomador->idTomador = $notaFiscal->idTomador;
    $tomador->readOne();

    $municTomador = new Municipio($db);
    $municTomador->buscaMunicipioModerna($tomador->codigoMunicipio);

    $emitente = new Emitente($db);
    $emitente->idEmitente = $notaFiscal->idEmitente;
    $emitente->readOne();

    $municEmitente = new Municipio($db);
    $municEmitente->buscaMunicipioModerna($emitente->codigoMunicipio);

    $notaFiscalItem = new NotaFiscalItem($db);
    $arrayNotaFiscalItem = $notaFiscalItem->read($notaFiscal->idNotaFiscal);

    $totalItens = 0;
    $vlTotBC = 0; 
    $vlTotISS = 0; 
    $vlTotServ = 0; 
    $qtdServicos = 0;
    $descricaoServicos = "";
    foreach ( $arrayNotaFiscalItem as $notaFiscalItem ) {

        $notaFiscalItem->readItemVenda('MODERNA');
        $nuCnae = $notaFiscalItem->cnae;
        $txIss = $notaFiscalItem->taxaIss;
        $totalItens += floatval($notaFiscalItem->valorTotal);
        $vlTotServ += $notaFiscalItem->valorTotal;
        $vlTotISS += $notaFiscalItem->valorIss; 

        $qtdServicos++;
        $descricaoServicos .= "~".$notaFiscalItem->unidade.
                              "~".number_format($notaFiscalItem->quantidade,2,',','').
                              "~".$notaFiscalItem->descricaoItemVenda.
                              "~".number_format($notaFiscalItem->valorUnitario,2,',','').
                              "~".number_format($notaFiscalItem->valorTotal,2,',','');
    }
    $descricaoServicos .= "~@@";

    $numeroRps = 1;


    $tipoTomador = '01';
    if (strlen($tomador->documento)==14)
        $tipoTomador = '02';
    $linhaRps = '000000000000000'.  // número da nota
                '1'.  // status da nota
                date("d/m/Y", strtotime($notaFiscal->dataEmissao)).' 00:00:00'.  // data timestamp
                substr($notaFiscal->dataEmissao,0,4).substr($notaFiscal->dataEmissao,5,2).  // ano/mês
                '000000000000000'.  // número da nota substituta
                '01'.  // natureza da operação
                str_pad($numeroRps, 15, '0', STR_PAD_LEFT).  // número do RPS  ??????????????????????????????????????
                '00001'.  // série RPS
                '1'.  // tipo RPS
                date("d/m/Y", strtotime($notaFiscal->dataEmissao)).  // série RPS
                '1'.  // status RPS
                '000000000000000'.  // número do RPS substituído
                '00000'.  // número de série RPS substituído
                str_pad(number_format($vlTotServ,2,',',''), 16, '0', STR_PAD_LEFT).  // valor do serviço
                str_pad($nuCnae, 7, '0', STR_PAD_LEFT).  // cnae
                str_pad($notaFiscalItem->codigoServico, 15, '0', STR_PAD_LEFT).  // identificacao da Atividade
                str_pad(number_format($vlTotServ,2,',',''), 16, '0', STR_PAD_LEFT).  // valor base de cálculo
                str_pad(number_format($txIss,2,',',''), 16, '0', STR_PAD_LEFT).  // taxaIss
                str_pad(number_format($vlTotISS,2,',',''), 16, '0', STR_PAD_LEFT).  // valor do ISS
                '2'.  // status ISS
                str_pad($descricaoServicos, 2000, ' ', STR_PAD_RIGHT).  // descrição dos serviços
                str_pad($municEmitente->codigoModerna, 15, '0', STR_PAD_LEFT).  // codigo Município
                str_pad($qtdServicos, 15, '0', STR_PAD_LEFT).  // quantidade de serviços
                str_pad(number_format($notaFiscalItem->valorUnitario,2,',',''), 16, '0', STR_PAD_LEFT).  // valor unitário
                str_pad($autorizacao->cmc, 15, '0', STR_PAD_LEFT).  // cmc
                str_pad($emitente->nome, 115, ' ', STR_PAD_RIGHT).  // razão social
                str_pad($emitente->nomeFantasia, 60, ' ', STR_PAD_RIGHT).  // nome fantasia
                $emitente->documento.  // cnpj
                str_pad($emitente->logradouro, 125, ' ', STR_PAD_RIGHT).  
                str_pad($emitente->numero, 10, ' ', STR_PAD_RIGHT).  
                str_pad($emitente->complemento, 60, ' ', STR_PAD_RIGHT).  
                str_pad($emitente->bairro, 60, ' ', STR_PAD_RIGHT).  
                str_pad($municEmitente->codigoModerna, 15, '0', STR_PAD_LEFT).  
                $emitente->uf.  
                str_pad($emitente->cep, 8, ' ', STR_PAD_RIGHT).  
                str_pad($emitente->email, 80, ' ', STR_PAD_RIGHT).  
                str_pad($emitente->telefone, 11, ' ', STR_PAD_RIGHT).  
                str_pad($tomador->documento, 14, '0', STR_PAD_LEFT).  // cpf/cnpj tomador
                $tipoTomador.  // tipo pessoa tomador
                str_pad($tomador->nome, 115, ' ', STR_PAD_RIGHT).  // razão social
                str_pad($tomador->logradouro, 125, ' ', STR_PAD_RIGHT).  
                str_pad($tomador->numero, 10, ' ', STR_PAD_RIGHT).  
                str_pad($tomador->complemento, 60, ' ', STR_PAD_RIGHT).  
                str_pad($tomador->bairro, 60, ' ', STR_PAD_RIGHT).  
                str_pad($municTomador->codigoModerna, 15, '0', STR_PAD_LEFT).  
                $tomador->uf.  
                str_pad($tomador->cep, 8, ' ', STR_PAD_RIGHT).  
                str_pad($tomador->email, 80, ' ', STR_PAD_RIGHT).  
                str_pad($tomador->telefone, 11, ' ', STR_PAD_RIGHT).  
                '          '. // data cancelamento
                '1'. // sincronização
                '0000000000000,00'. // deduções
                '0000000000000,00'. // pis
                '0000000000000,00'. // cofins
                '0000000000000,00'. // inss
                '0000000000000,00'. // ir
                '0000000000000,00'. // csll
                '0000000000000,00'; // outras deduções

    $dtArq = date("Ymd", strtotime($data->dataEmissao));
    $arqRps = fopen("../arquivosNFSe/Moderna/rps/rps_".$dtArq.".txt","wt");
    fwrite($arqRps, $linhaRps);
    fclose($arqRps);

}

?>