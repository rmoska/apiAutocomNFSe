<?php
/*
$arrOK = array("http_code" => "201", 
"message" => "Nota Fiscal emitida", 
"idNotaFiscal" => 331,
"numeroNF" => "658",
"xml" => "http://www.autocominformatica.com.br/apiAutocomNFSe/arquivosNFSe/29983942000119/transmitidas/29983942000119_00000658-nfse.xml",
"pdf" => "http://www.autocominformatica.com.br/apiAutocomNFSe/arquivosNFSe/29983942000119/danfpse/29983942000119_00000658-nfse.pdf");
$retNFSe = json_encode($arrOK);

$headers = array( "Content-type: application/json" ); 
$curl = curl_init();
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 

curl_setopt($curl, CURLOPT_URL, "https://ws.fpay.me/crm/me/nfe/callback-status-nfe");

curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($curl, CURLOPT_POST, TRUE);
curl_setopt($curl, CURLOPT_POSTFIELDS, $retNFSe);
//
$result = curl_exec($curl);
$info = curl_getinfo( $curl );
print_r($result);
print_r($info);

exit;
*/
phpinfo(); exit;

$arq = "C:\Desenv\NFSe\SC\BalnearioCamboriu\xmlNFSeRetorno.xml";

$DomXml=new DOMDocument();
$DomXml->load($arq);

//print_r($DomXml);

$xmlResp = $DomXml->getElementById('NovaNfse');

print_r($xmlResp);
echo $xmlResp->IdentificacaoNfse->Numero;

//$msgResp = simplexml_load_string($xmlResp);

echo $msgResp;

$nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
$cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
$linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;

echo $nuNF.' - '.$cdVerif.' - '.$linkNF;


exit;

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
"<env:Envelope xmlns:env='http://schemas.xmlsoap.org/soap/envelope/'><env:Header></env:Header><env:Body><ns2:GerarNfseResponse xmlns:ns2='http://www.betha.com.br/e-nota-contribuinte-ws'><return>
<GerarNfseResposta xmlns='http://www.betha.com.br/e-nota-contribuinte-ws'>
    <ListaNfse>
        <CompNfse>
            <Nfse versao='2.02'>
                <InfNfse>
                    <Numero>619</Numero>
                    <CodigoVerificacao>ZJNEFAJVB</CodigoVerificacao>
                    <DataEmissao>2019-10-02T16: 43: 51</DataEmissao>
                    <OutrasInformacoes>http://e-gov.betha.com.br/e-nota/visualizarnotaeletronica?link=15700454316916191551624555555770644318806710487377433</OutrasInformacoes>
                    <ValoresNfse>
                        <BaseCalculo>10</BaseCalculo>
                        <Aliquota>0</Aliquota>
                        <ValorIss>0</ValorIss>
                        <ValorLiquidoNfse>10</ValorLiquidoNfse>
                    </ValoresNfse>
                    <PrestadorServico>
                        <IdentificacaoPrestador>
                            <CpfCnpj>
                                <Cnpj>06126514000174</Cnpj>
                            </CpfCnpj>
                            <InscricaoMunicipal>313076</InscricaoMunicipal>
                        </IdentificacaoPrestador>
                        <RazaoSocial>JULIO CESAR KUTNE</RazaoSocial>
                        <NomeFantasia>SEGHOUSE TECNOLOGIA EM SEGURANCA</NomeFantasia>
                        <Endereco>
                            <Endereco>Ambiente de testes não requer endereço</Endereco>
                            <CodigoMunicipio>0</CodigoMunicipio>
                            <Uf>SC</Uf>
                            <Cep>88800000</Cep>
                        </Endereco>
                        <Contato>
                            <Telefone>48999630276</Telefone>
                            <Email>CONTATO@SEGHOUSE.COM.BR</Email>
                        </Contato>
                    </PrestadorServico>
                    <OrgaoGerador>
                        <CodigoMunicipio>0</CodigoMunicipio>
                        <Uf>SC</Uf>
                    </OrgaoGerador>
                    <DeclaracaoPrestacaoServico>
                        <InfDeclaracaoPrestacaoServico>
                            <Competencia>2019-10-01</Competencia>
                            <Servico>
                                <Valores>
                                    <ValorServicos>10</ValorServicos>
                                    <ValorDeducoes>0</ValorDeducoes>
                                    <ValorIss>0</ValorIss>
                                    <Aliquota>0.00</Aliquota>
                                    <DescontoIncondicionado>0</DescontoIncondicionado>
                                    <DescontoCondicionado>0</DescontoCondicionado>
                                </Valores>
                                <IssRetido>2</IssRetido>
                                <ResponsavelRetencao>2</ResponsavelRetencao>
                                <ItemListaServico>1406</ItemListaServico>
                                <Discriminacao>{
    [
        [Descricao=Consulta clinica
        ][ItemServico=1406
        ][Quantidade=1
        ][ValorUnitario=10
        ][ValorServico=10
        ][ValorBaseCalculo=10
        ][Aliquota=0
        ]
    ]
}</Discriminacao>
                                <CodigoMunicipio>0</CodigoMunicipio>
                                <ExigibilidadeISS>3</ExigibilidadeISS>
                                <MunicipioIncidencia>0</MunicipioIncidencia>
                            </Servico>
                            <Prestador>
                                <CpfCnpj>
                                    <Cnpj>06126514000174</Cnpj>
                                </CpfCnpj>
                                <InscricaoMunicipal>313076</InscricaoMunicipal>
                            </Prestador>
                            <OptanteSimplesNacional>1</OptanteSimplesNacional>
                            <IncentivoFiscal>2</IncentivoFiscal>
                        </InfDeclaracaoPrestacaoServico>
                    </DeclaracaoPrestacaoServico>
                </InfNfse>
            </Nfse>
        </CompNfse>
    </ListaNfse>
</GerarNfseResposta>
</return></ns2:GerarNfseResponse></env:Body></env:Envelope>";



$respEnv = str_replace("<s:", "<", $respEnv);
$respEnv = str_replace("</s:", "</", $respEnv);
//print_r($respEnv);
echo $respEnv;

$msgResp = simplexml_load_string($respEnv);
$nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
$cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
$linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;

echo $nuNF.' - '.$cdVerif.' - '.$linkNF;


$DomXml=new DOMDocument('1.0', 'utf-8');
$DomXml->loadXML($respEnv);

$xmlResp = $DomXml->textContent;

echo 'resp='. $xmlResp;

$msgResp = simplexml_load_string($xmlResp);

echo $msgResp;

$nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
$cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
$linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;

echo $nuNF.' - '.$cdVerif.' - '.$linkNF;

?>