<?php

include_once '../shared/utilities.php';
$utilities = new Utilities();
//			
$xml = new XMLWriter;
//
// cria XML RPS
$xml->startElement("Rps");
    $xml->startElement("InfRps");
    $xml->writeAttribute("id", $notaFiscal->idNotaFiscal);
        $xml->startElement("IdentificacaoRps");
            $xml->writeElement("Numero", $notaFiscal->idNotaFiscal); // ????????????
            $xml->writeElement("Serie", 1);
            $xml->writeElement("Tipo", 1);
        $xml->endElement(); // IdentificacaoRps
        $xml->writeElement("DataEmissao", $dtEm);
        $xml->writeElement("NaturezaOperacao", $notaFiscalItem->cstIss);
        $xml->writeElement("RegimeEspecialTributacao", 6); // 6 = ME/EPP
        $xml->writeElement("OptanteSimplesNacional", $aAutoChave["optanteSN"]); // 1 = SIM
        $xml->writeElement("IncentivadorCultural", $idIncCultural); // 2 = NAO
        $xml->writeElement("Status", 1); // 1 = normal

        $xml->startElement("Servico");
            $xml->startElement("Valores");
                $xml->writeElement("ValorServicos", $vlTotServ);
    /*
            $xml->writeElement("ValorDeducoes", 0.00);
            $xml->writeElement("ValorPis", 0.00);
            $xml->writeElement("ValorCofins", 0.00);
            $xml->writeElement("ValorInss", 0.00);
            $xml->writeElement("ValorIr", 0.00);
            $xml->writeElement("ValorCsll", 0.00);
            $xml->writeElement("OutrasRetencoes", 0.00);
            $xml->writeElement("DescontoIncondicionado", 0.00);
            $xml->writeElement("DescontoCondicionado", 0.00);
    */
                $xml->writeElement("IssRetido", 2); // 1=Sim 2=Não
                $xml->writeElement("ValorIss", $notaFiscalItem->valorIss);
                $xml->writeElement("BaseCalculo", $vlTotBC);
                $xml->writeElement("Aliquota", $notaFiscalItem->taxaIss); 
                $xml->writeElement("ValorLiquidoNfse", $vlTotServ);
            $xml->endElement(); // Valores

            $xml->writeElement("ItemListaServico", $notaFiscalItem->codigoServico); 
            $xml->writeElement("CodigoCnae", "");
            $xml->writeElement("Discriminacao", $descServico);
            $xml->writeElement("CodigoMunicipio", $emitente->codigoMunicipio); // Município de prestação do serviço
        $xml->endElement(); // Serviço

        $xml->startElement("Prestador");
            $xml->writeElement("Cnpj", $emitente->documento);
            $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
        $xml->endElement(); // Prestador


        $xml->startElement("Tomador");
            $xml->startElement("IdentificacaoTomador");
                $xml->startElement("CpfCnpj");
                if (strlen($tomador->documento)==14) 
                    $xml->writeElement("Cnpj", $tomador->documento);
                else
                    $xml->writeElement("Cpf", $tomador->documento);
                $xml->endElement(); // CpfCnpj
            $xml->endElement(); // IdentificacaoTomador
            $xml->writeElement("RazaoSocial", $tomador->nome);
            $xml->startElement("Endereco");
                $xml->writeElement("Endereco", $tomador->logradouro);
                $xml->writeElement("Numero", $tomador->numero);
                $xml->writeElement("Complemento", $tomador->complemento);
                $xml->writeElement("Bairro", $tomador->bairro);
                $xml->writeElement("CodigoMunicipio", $tomador->codigoMunicipio);
                $xml->writeElement("Uf", $tomador->uf);
                $xml->writeElement("Cep", $tomador->cep);
            $xml->endElement(); // Endereco
        $xml->endElement(); // Tomador
    $xml->endElement(); // InfRps
$xml->endElement(); // Rps

$xmlRps = $xml->outputMemory(true);

$xmlAss = $objNFSe->signXML($xmlRps, 'InfRps', '');
if ($objNFSe->errStatus) {

    http_response_code(401);
    echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$objNFSe->errMsg));
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

//
// Inicia o cabeçalho do documento XML
$xml->startElement("EnviarLoteRpsEnvio");
$xml->writeAttribute("xmlns", "http://www.abrasf.org.br/ABRASF/arquivos/nfse.xsd");
    $xml->startElement("LoteRps");
    $xml->writeAttribute("id", "001");
        $xml->writeElement("NumeroLote", 1);
        $xml->writeElement("Cnpj", $emitente->documento);
        $xml->writeElement("InscricaoMunicipal", $autorizacao->cmc);
        $xml->writeElement("QuantidadeRps", 1);
        $xml->startElement("ListaRps");
            $xml->writeRaw($xmlAss);
        $xml->endElement(); // ListaRps
    $xml->endElement(); // LoteRps
$xml->endElement(); // EnviarLoteRpsEnvio
//
$xmlLote = $xml->outputMemory(true);
//
$xmlAss = $objNFSe->signXML($xmlLote, 'LoteRps', '');

$idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
$arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
fwrite($arqNFe, $xmlAss);
fclose($arqNFe);

?>