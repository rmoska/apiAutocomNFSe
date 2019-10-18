<?php

//phpinfo();

$respEnv = 
'<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
        <GerarNfseResponse xmlns="http://www.sistema.com.br/Sistema.Ws.Nfse">
            <GerarNfseResult>
                <NovaNfse xmlns="http://www.sistema.com.br/Nfse/arquivos/nfse_3.xsd">
                    <IdentificacaoNfse>
                        <IdentificacaoPrestador>
                            <Cnpj>80449374000128</Cnpj>
                            <InscricaoMunicipal>0625051</InscricaoMunicipal>
                        </IdentificacaoPrestador>
                        <Numero>3</Numero>
                        <Serie>E</Serie>
                        <CodigoVerificacao>B8DA791219</CodigoVerificacao>
                        <DataEmissao>2019-10-17T14:06:21.8527971-03:00</DataEmissao>
                        <Link/>
                    </IdentificacaoNfse>
                    <Signature xmlns="http://www.w3.org/2000/09/xmldsig#"/>
                </NovaNfse>
            </GerarNfseResult>
        </GerarNfseResponse>
    </s:Body>
</s:Envelope>';

$respEnv2 = 
'<Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><Header></Header>
    <Body xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
        <GerarNfseResponse xmlns="http://www.sistema.com.br/Sistema.Ws.Nfse">
            <GerarNfseResult>
                <NovaNfse xmlns="http://www.sistema.com.br/Nfse/arquivos/nfse_3.xsd">
                    <IdentificacaoNfse>
                        <IdentificacaoPrestador>
                            <Cnpj>80449374000128</Cnpj>
                            <InscricaoMunicipal>0625051</InscricaoMunicipal>
                        </IdentificacaoPrestador>
                        <Numero>3</Numero>
                        <Serie>E</Serie>
                        <CodigoVerificacao>B8DA791219</CodigoVerificacao>
                        <DataEmissao>2019-10-17T14:06:21.8527971-03:00</DataEmissao>
                        <Link/>
                    </IdentificacaoNfse>
                    <Signature xmlns="http://www.w3.org/2000/09/xmldsig#"/>
                </NovaNfse>
            </GerarNfseResult>
        </GerarNfseResponse>
    </Body>
</Envelope>';


$respEnv = str_replace("<s:", "<", $respEnv);
$respEnv = str_replace("</s:", "</", $respEnv);
//print_r($respEnv);

$msgResp = simplexml_load_string($respEnv);

/*
$DomXml=new DOMDocument('1.0', 'utf-8');
$DomXml->loadXML($respEnv2);
$xmlResp = (string) $DomXml->textContent;
$msgResp = simplexml_load_string($xmlResp);
*/
//echo "<pre>";
echo $xmlResp;
//print_r($nodeNova);
//echo "</pre>";

//$nuNF = (string) $nodeNova->NovaNfse->IdentificacaoNfse->Numero;

$nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
$cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
$linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;

echo $nuNF.' - '.$cdVerif.' - '.$linkNF;

?>