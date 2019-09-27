<?php
 
// include database and object files
include_once '../objects/autorizacao.php';
 
// prepare emitente object
$autorizacao = new Autorizacao($db);
 
//    
// dados solicitados no cadastro da prefeitura
// AEDFe = login 
// senhaWeb = senha
if(
    !empty($data->idEmitente) &&
    !empty($data->aedf) && // login
    !empty($data->senhaWeb) &&
    !empty($data->certificado) &&
    !empty($data->senha)
){
    // set autorizacao property values
    $autorizacao->idEmitente = $data->idEmitente;
    $autorizacao->aedf = $data->aedf;
    $autorizacao->senhaWeb = $data->senhaWeb;
    $autorizacao->certificado = $data->certificado;
    $autorizacao->senha = $data->senha;

    if ($autorizacao->check() == 0)
        $retorno = $autorizacao->create($emitente->documento);
    else 
        $retorno = $autorizacao->update($emitente->documento);
 
    if($retorno[0]){

        include_once '../comunicacao/signNFSe.php';
        $arraySign = array("cnpj" => $emitente->documento, "keyPass" => $autorizacao->senha);
        $certificado = new SignNFSe($arraySign);
        if ($certificado->errStatus){
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Não foi possível incluir Certificado.", "erro" => $certificado->errMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Certificado. Erro=".$certificado->errMsg." Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }
        $validade = $certificado->certDaysToExpire;

            
        //
        // emite nota de teste

        //			
        $xml = new XMLWriter;
        $xml->openMemory();
        //
        // Inicia o cabeçalho do documento XML



        <?xml version="1.0" encoding="ISO-8859-1"?>
        <nfse>
          <nf>
            <valor_total>100,00</valor_total>
            <valor_desconto>0,00</valor_desconto>
            <valor_ir>0,00</valor_ir>
            <valor_inss>0,00</valor_inss>
            <valor_contribuicao_social>0,00</valor_contribuicao_social>
            <valor_rps>0,00</valor_rps>
            <valor_pis>0,00</valor_pis>
            <valor_cofins>0,00</valor_cofins>
            <observacao></observacao>
          </nf>
          <prestador>
            <cpfcnpj>21948242000181</cpfcnpj>
            <cidade>8003</cidade>
          </prestador>
          <tomador>
            <tipo>F</tipo>
            <cpfcnpj>99999999999</cpfcnpj>
            <ie></ie>
            <nome_razao_social>Empresa Teste</nome_razao_social>
            <sobrenome_nome_fantasia></sobrenome_nome_fantasia>
            <logradouro>Rua Jaco Finardi, 799</logradouro>
            <email>email@dominio.com.br</email>
            <complemento></complemento>
            <ponto_referencia></ponto_referencia>
            <bairro>Centro</bairro>
            <cidade>8291</cidade>
            <cep>89160000</cep>
            <ddd_fone_comercial></ddd_fone_comercial>
            <fone_comercial></fone_comercial>
            <ddd_fone_residencial></ddd_fone_residencial>
            <fone_residencial></fone_residencial>
            <ddd_fax></ddd_fax>
            <fone_fax></fone_fax>
          </tomador>
          <itens>
            <lista>
              <codigo_local_prestacao_servico>8003</codigo_local_prestacao_servico>
              <codigo_item_lista_servico>706</codigo_item_lista_servico>
              <descritivo>Teste para emissão de NFS-e</descritivo>
              <aliquota_item_lista_servico>3,00</aliquota_item_lista_servico>
              <situacao_tributaria>00</situacao_tributaria>
              <valor_tributavel>100</valor_tributavel>
              <valor_deducao>0,00</valor_deducao>
              <valor_issrf>0,00</valor_issrf>
              <tributa_municipio_prestador>S</tributa_municipio_prestador>
              <unidade_codigo/>
              <unidade_quantidade/>
              <unidade_valor_unitario/>                    
            </lista>
          </itens>
          <produtos>
          </produtos>
        </nfse>

        $dtEm = date('d/m/Y');
        $tipoTomador = 'F';
        if (strlen(trim(tomador->documento))==14)
            $tipoTomador = 'J';

        $xml->startElement("nfse");
            $xml->writeElement("nfse_teste", "1"); // ambiente homologação
            $xml->startElement("nf");
                $xml->writeElement("valor_total", "2,00");
                $xml->writeElement("observacao", "Teste de Homologacao");
                $xml->writeElement("data_fato_gerador", $dtEm);
            $xml->endElement(); // nf
            $xml->startElement("prestador");
                $xml->writeElement("cpfcnpj", $emitente->documento);
                $xml->writeElement("cidade", "8233"); // Palhoça
            $xml->endElement(); // prestador
            $xml->startElement("tomador");
                $xml->writeElement("tipo", $tipoTomador); 
                $xml->writeElement("cpfcnpj", "03118290072");
                $xml->writeElement("email", "rodrigo@autocominformatica.com.br");
            $xml->endElement(); // tomador
            // ITENS
            $xml->startElement("itens");
                $xml->startElement("lista");
                    $xml->writeElement("tributa_municipio_prestador", "S");
                    $xml->writeElement("codigo_local_prestacao_servico", "8233");
                    $xml->writeElement("unidade_codigo", "UN");
                    $xml->writeElement("unidade_quantidade", "1,00");
                    $xml->writeElement("unidade_valor_unitario", "2,00");
                    $xml->writeElement("codigo_item_lista_servico", "0402"); // LC116
                    $xml->writeElement("descritivo", "Servico para Teste de Homologacao");
                    $xml->writeElement("aliquota_item_lista_servico", "0,00");
                    $xml->writeElement("situacao_tributaria", "6"); // Isento
                    $xml->writeElement("valor_tributavel", "2,00");
                $xml->endElement(); // lista
            $xml->endElement(); // itens
        $xml->endElement(); // nfse
        //
        $xmlNFe = $xml->outputMemory(true);
        $xmlNFe = '<?xml version="1.0" encoding="utf-8"?>'.$xmlNFe;

        $idChaveNFSe = substr(str_pad($notaFiscal->idNotaFiscal,6,'0',STR_PAD_LEFT),0,6);
        $arqNFe = fopen("../arquivosNFSe/".$emitente->documento."/rps/".$idChaveNFSe."-nfse.xml","wt");
        fwrite($arqNFe, $xmlNFe);
        fclose($arqNFe);

        $arqNFSe = dirname($arqNFe);

        $xmlAss = $certificado->signXML($xmlNFe, 'nfse');
        if ($certificado->errStatus) {
    
            http_response_code(401);
            echo json_encode(array("http_code" => "401", "message" => "Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. ".$certificado->errMsg));
            error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível gerar Nota Fiscal Homologacao. Problemas na assinatura do XML. Emitente=".$autorizacao->idEmitente."\n"), 3, "../arquivosNFSe/apiErrors.log");
            exit;
        }

        //
        // transmite NFSe	
        $headers = array( "Content-type: application/xml" ); 

        $params = "login=".$emitente->documento."&senha=".$autorizacao->senhaWeb."&cidade=8233&fl=".$arqNFSe);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($curl, CURLOPT_URL, "http://sync.nfs-e.net/datacenter/include/nfe/importa_nfe/nfe_import_upload.php?eletron=1");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        //
        $result = curl_exec($curl);
        $info = curl_getinfo( $curl );

        $nuNF = 0;
        $cdVerif = '';

        if ($info['http_code'] == '200') {
            //
            $xmlNFRet = simplexml_load_string($result);
            $nuNF = (string) $xmlNFRet->numeroSerie;
            $cdVerif = (string) $xmlNFRet->codigoVerificacao;
        }
        else {

            if (substr($info['http_code'],0,1) == '5') {

                $cdVerif = "Erro no envio da NFSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido) !";
                error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe ! Problemas no servidor (Indisponivel ou Tempo de espera excedido).\n"), 3, "../arquivosNFSe/apiErrors.log");
            }
            else {
        
                $msg = $result;
                $dados = json_decode($result);
                if (isset($dados->error)) {
    
                    $cdVerif = "Erro no envio da NFSe ! (".$dados->error.") ".$dados->error_description;
                    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(1) (".$dados->error.") ".$dados->error_description ."\n"), 3, "../arquivosNFSe/apiErrors.log");
                }
                else {
    
                    $xmlNFRet = simplexml_load_string(trim($result));
                    $msgRet = (string) $xmlNFRet->message;
                    $cdVerif = "Erro no envio da NFSe ! ".$msgRet;
                    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Erro no envio da NFPSe !(2) (".$msgRet.")\n"), 3, "../arquivosNFSe/apiErrors.log");
                }
            }
        }

        http_response_code(201);
        echo json_encode(array("http_code" => 201, "message" => "Autorização atualizada", 
                               "token" => $autorizacao->token, 
                               "validade" => $validade." dias",
                               "nf-homolog" => $nuNF,
                               "verificacao-homolog" => $cdVerif));
    }
    else{
 
        http_response_code(500);
        echo json_encode(array("http_code" => "500", "message" => "Não foi possível incluir Autorização.", "erro" => $retorno[1]));
        $strData = json_encode($data);
        error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados = ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
        exit;
    }
}
else{
 
    http_response_code(400);
    echo json_encode(array("http_code" => "400", "message" => "Não foi possível incluir Autorização. Dados incompletos."));
    $strData = json_encode($data);
    error_log(utf8_decode("[".date("Y-m-d H:i:s")."] Não foi possível incluir Autorização. Dados incompletos. ".$strData."\n"), 3, "../arquivosNFSe/apiErrors.log");
    exit;
}

?>