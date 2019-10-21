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

$respEnv =
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

$msgResp = simplexml_load_string($respEnv);
$nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
$cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
$linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;

echo $nuNF.' - '.$cdVerif.' - '.$linkNF;


$DomXml=new DOMDocument('1.0', 'utf-8');
$DomXml->loadXML($respEnv);
$xmlResp = $DomXml->textContent;
$msgResp = simplexml_load_string($xmlResp);

echo $msgResp;

$nuNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Numero;
$cdVerif = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->CodigoVerificacao;
$linkNF = (string) $msgResp->Body->GerarNfseResponse->GerarNfseResult->NovaNfse->IdentificacaoNfse->Link;

echo $nuNF.' - '.$cdVerif.' - '.$linkNF;

?>