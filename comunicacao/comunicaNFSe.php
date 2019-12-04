<? 

class comunicaNFSe {

    /**
     * Empresa do Sistema de Emissão
     * 0 = PM Florianopolis/SC
     * 1 = Betha
     * 2 = IPM
     * 3 = SimplISS
     */
    protected $sisEmit;
    /**
     * tpAmb
     * Tipo de ambiente P-produção H-homologação
     */
    protected $tpAmb = '';
    /**
     * Diretorio onde estão os certificados
     */
    public $certsDir;
    /**
     * diretorio que contem os esquemas de validação
     * estes esquemas devem ser mantidos atualizados
     */
    private $certName='';
    /**
     * priKEY
     * Path completo para a chave privada em formato pem
     */
    private $priKEY='';
    /**
     * pubKEY
     * Path completo para a chave public em formato pem
     */
    private $pubKEY='';
    /**
     * certKEY
     * Path completo para o certificado (chave privada e publica) em formato pem
     */
    private $certKEY='';
    private $cnpj;
    private $keyPass;
    private $arqDir;

    public $errMsg='';
    public $errStatus=false;

    private $URLdsig='http://www.w3.org/2000/09/xmldsig#';
    private $URLCanonMeth='http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $URLSigMeth='http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private $URLTransfMeth_1='http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private $URLTransfMeth_2='http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $URLDigestMeth='http://www.w3.org/2000/09/xmldsig#sha1';


    function __construct($arraySign) {
        //obtem o path da biblioteca
        $this->raizDir = dirname(dirname( __FILE__ )) . '/';

        $this->sisEmit = $arraySign["sisEmit"];
        $this->tpAmb = $arraySign["tpAmb"];
        $this->cnpj = $arraySign["cnpj"];
        $this->certName = "cert".$arraySign["cnpj"].".pfx";
        $this->keyPass = $arraySign["keyPass"];
        $this->arqDir = "../arquivosNFSe/".$arraySign["cnpj"];
        $this->certsDir =  $this->arqDir."/certificado/";

        //verifica o ultimo caracter da variável $arqDir
        // se não for um DIRECTORY_SEPARATOR então colocar um
        if (substr($this->arqDir, -1, 1) != '/'){
            $this->arqDir .= '/';
        }

        if ( !$retorno = $this->__loadCerts() ) {
            return false;
//            return $this->errMsg;
        }

        return true;
    } //fim __construct


    /**
     * signXML
     * Assinador TOTALMENTE baseado em PHP para arquivos XML
     * este assinador somente utiliza comandos nativos do PHP para assinar
     * os arquivos XML
     */
    public function signXML($docxml, $tagid='', $tagapp=''){
        if($tagid==''){
            $this->errMsg = "Uma tag deve ser indicada para que seja assinada!!\n";
            $this->errStatus = true;
            return false;
        }
        if($docxml==''){
            $this->errMsg = "Um xml deve ser passado para que seja assinado!!\n";
            $this->errStatus = true;
            return false;
        }
        // obter o chave privada para a ssinatura
        $fp = fopen($this->priKEY, "r");
        //var_dump('a',fread($fp));exit();
        $priv_key=fread($fp, 8192);
        fclose($fp);
        $pkeyid=openssl_get_privatekey($priv_key);
        // limpeza do xml com a retirada dos CR, LF, TAB, Tag de abertura e espacos desnecessarios
        $order=array("\r\n", "\n", "\r", "\t");
        $replace='';
	//mesa história dita antes, esse trecho realmente se repete muito, hoje vejo isso =/
        $docxml=str_replace($order, $replace, $docxml);
        $docxml=str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$docxml);
        $docxml=str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$docxml);
        $docxml=str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$docxml);
        $docxml=str_replace('<?xml version="1.0"?>','',$docxml);
        $docxml=str_replace("\n","",$docxml);
        $docxml=str_replace("  "," ",$docxml);
        $docxml=str_replace("> <","><",$docxml);
        // carrega o documento no DOM
        $xmldoc=new DOMDocument('1.0', 'utf-8');
        $xmldoc->preservWhiteSpace=false; //elimina espaços em branco
        $xmldoc->formatOutput=false;
        // MUITO IMPORTANTE: Deixar ativadas as opcoes para limpar os espacos em branco e as tags vazias
        $xmldoc->loadXML($docxml,LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        $root=$xmldoc->documentElement;
        //extrair a tag com os dados a serem assinados
        if ($tagapp > '') {
            //extrair a tag onde será anexada a assinatura
            $newroot = $xmldoc->getElementsByTagName($tagapp)->item(0);
             if (!isset($newroot)){
                $this->errMsg = "A tag < $tagapp > nao existe no XML!";
                $this->errStatus = true;
                return false;
            }
            $root = $newroot;
        }
        $node = $xmldoc->getElementsByTagName($tagid)->item(0);
        if (!isset($node)){
            $this->errMsg = "A tag < $tagid > nao existe no XML!";
            $this->errStatus = true;
            return false;
         }
         $id = trim($node->getAttribute("Id"));
         $idnome = preg_replace('/[^0-9]/','', $id);
         //extrai os dados da tag para uma string
         $dados = $node->C14N(false,false,NULL,NULL);
         $dados=str_replace(' >', '>', $dados);
         //echo htmlentities($dados);exit();
         //calcular o hash dos dados
         $hashValue = hash('sha1',$dados,true);
         //converte o valor para base64 para serem colocados no xml
         $digValue = base64_encode($hashValue);
         //monta a tag da assinatura digital
         $Signature = $xmldoc->createElementNS($this->URLdsig,'Signature');
         $root->appendChild($Signature);
         //$node->appendChild($Signature);
         $SignedInfo = $xmldoc->createElement('SignedInfo');
         $Signature->appendChild($SignedInfo);
         //Cannocalization
         $newNode = $xmldoc->createElement('CanonicalizationMethod');
         $SignedInfo->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLCanonMeth);
         //SignatureMethod
         $newNode = $xmldoc->createElement('SignatureMethod');
         $SignedInfo->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLSigMeth);
         //Reference
         $Reference = $xmldoc->createElement('Reference');
         $SignedInfo->appendChild($Reference);
         $Reference->setAttribute('URI', '#'.$id);
         //Transforms
         $Transforms = $xmldoc->createElement('Transforms');
         $Reference->appendChild($Transforms);
         //Transform
         $newNode = $xmldoc->createElement('Transform');
         $Transforms->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLTransfMeth_1);
         //Transform
         $newNode = $xmldoc->createElement('Transform');
         $Transforms->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLTransfMeth_2);
         //DigestMethod
         $newNode = $xmldoc->createElement('DigestMethod');
         $Reference->appendChild($newNode);
         $newNode->setAttribute('Algorithm', $this->URLDigestMeth);
         //DigestValue
         $newNode = $xmldoc->createElement('DigestValue',$digValue);
         $Reference->appendChild($newNode);
         // extrai os dados a serem assinados para uma string
         $dados = $SignedInfo->C14N(false,false,NULL,NULL);
         //inicializa a variavel que irÃ¡ receber a assinatura
         $signature = '';
         //executa a assinatura digital usando o resource da chave privada
         $resp = openssl_sign($dados,$signature,$pkeyid);
         //codifica assinatura para o padrao base64
         $signatureValue = base64_encode($signature);
         //SignatureValue
         $newNode = $xmldoc->createElement('SignatureValue',$signatureValue);
         $Signature->appendChild($newNode);
         //KeyInfo
         $KeyInfo = $xmldoc->createElement('KeyInfo');
         $Signature->appendChild($KeyInfo);
         //X509Data
         $X509Data = $xmldoc->createElement('X509Data');
         $KeyInfo->appendChild($X509Data);
         //carrega o certificado sem as tags de inicio e fim
         $cert = $this->__cleanCerts($this->pubKEY);
         //X509Certificate
         $newNode = $xmldoc->createElement('X509Certificate',$cert);
         $X509Data->appendChild($newNode);
         //grava na string o objeto DOM
         $xml = $xmldoc->saveXML();
         // libera a memoria
         openssl_free_key($pkeyid);
         //e olha essa m** aqui de novo, precisamos melhorar isso galera =/
         $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','<?xml version="1.0" encoding="UTF-8" standalone="no"?>',$xml);
         $xml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>','',$xml);
         $xml = str_replace('<?xml version="1.0" encoding="UTF-8"?>','',$xml);
         $xml = str_replace('<?xml version="1.0"?>','',$xml);
         $xml = str_replace("\n","",$xml);
         $xml = str_replace("  "," ",$xml);
         $xml = str_replace("> <","><",$xml);
         //retorna o documento assinado
         //echo htmlentities($xml);exit();
         return $xml;
    } //fim signXML


    /**
     * signatureExists
     * Check se o xml possi a tag Signature
     *
     * @param  DOMDocument $dom
     * @return boolean
     */
    private function zSignatureExists($dom)
    {
        $signature = $dom->getElementsByTagName('Signature')->item(0);
        if (! isset($signature)) {
            return false;
        }
        return true;
    }

    /**
     * zCleanPubKey
     * Remove a informação de inicio e fim do certificado
     * contido no formato PEM, deixando o certificado (chave publica) pronta para ser
     * anexada ao xml da NFe
     *
     * @return string contendo o certificado limpo
     */
    protected function zCleanPubKey()
    {
        //inicializa variavel
        $data = '';
        //carregar a chave publica
        $pubKey = $this->pubKEY;
        //carrega o certificado em um array usando o LF como referencia
        $arCert = explode("\n", $pubKey);
        foreach ($arCert as $curData) {
            //remove a tag de inicio e fim do certificado
            if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0
                && strncmp($curData, '-----END CERTIFICATE', 20) != 0
            ) {
                //carrega o resultado numa string
                $data .= trim($curData);
            }
        }
        return $data;
    }
    



    /**
     * __loadCerts
     * Carrega o certificado pfx e gera as chaves privada e publica no
     * formato pem para a assinatura e para uso do SOAP e registra as
     * variaveis de ambiente.
     * Esta função deve ser invocada antes das outras do sistema que
     * dependam do certificado.
     * Além disso esta função também avalia a validade do certificado.
     * Os certificados padrão A1 (que são usados pelo sistema) tem validade
     * limitada à 1 ano e caso esteja vencido a função retornará false.
     *
     * Resultado
     *  A função irá criar o certificado digital (chaves publicas e privadas)
     *  no formato pem e grava-los no diretorio indicado em $this->certsDir
     *  com os nomes :
     *     CNPJ_priKEY.pem
     *     CNPJ_pubKEY.pem
     *     CNPJ_certKEY.pem
     *  Estes arquivos tanbém serão carregados nas variáveis da classe
     *  $this->priKEY (com o caminho completo para o arquivo CNPJ_priKEY.pem)
     *  $this->pubKEY (com o caminho completo para o arquivo CNPJ_pubKEY.pem)
     *  $this->certKEY (com o caminho completo para o arquivo CNPJ_certKEY.pem)
     * Dependencias
     *   $this->pathCerts
     *   $this->nameCert
     *   $this->passKey
     *
     * @name __loadCerts
     * @version 2.10
     * @package NFePHP
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param	none
     * @return	boolean true se o certificado foi carregado e false se nao
     **/
    protected function __loadCerts(){
        //monta o path completo com o nome da chave privada
        $this->priKEY = $this->certsDir."cert".$this->cnpj.'_priKEY.pem';
        //monta o path completo com o nome da chave prublica
        $this->pubKEY =  $this->certsDir."cert".$this->cnpj.'_pubKEY.pem';
        //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
        $this->certKEY = $this->certsDir."cert".$this->cnpj.'_certKEY.pem';
        //verificar se o nome do certificado e
        //o path foram carregados nas variaveis da classe
        if ($this->certsDir == '' || $this->certName == '') {
                $this->errMsg = 'Um certificado deve ser passado para a classe!!';
                $this->errStatus = true;
                return false;
        }
        //monta o caminho completo até o certificado pfx
        $pCert = $this->certsDir.$this->certName;
        //verifica se o arquivo existe
        if(!file_exists($pCert)){
                $this->errMsg = 'Certificado não encontrado!!';
                $this->errStatus = true;
                return false;
        }
        //carrega o certificado em um string
        $key = file_get_contents($pCert);
        //carrega os certificados e chaves para um array denominado $x509certdata
        if (!openssl_pkcs12_read($key,$x509certdata,$this->keyPass) ){
                $this->errMsg = 'O certificado não pode ser lido!! Provavelmente com formato inválido on senha incorreta !!';
                $this->errStatus = true;
                return false;
        }
        //verifica sua validade
        $aResp = $this->__validCerts($x509certdata['cert']);
        if ( $aResp['error'] != '' ){
                $this->errMsg = 'Certificado invalido!! - ' . $aResp['error'];
                $this->errStatus = true;
                return false;
        }
        //verifica se arquivo já existe
        if(file_exists($this->priKEY)){
            //se existir verificar se é o mesmo
            $conteudo = file_get_contents($this->priKEY);
            //comparar os primeiros 30 digitos
            if ( !substr($conteudo,0,30) == substr($x509certdata['pkey'],0,30) ) {
                 //se diferentes gravar o novo
                if (!file_put_contents($this->priKEY,$x509certdata['pkey']) ){
                    $this->errMsg = 'Impossível gravar no diretório!!! Permissão negada!!';
                    $this->errStatus = true;
                    return false;
                }
            }
        } else {
            //salva a chave privada no formato pem para uso so SOAP
            if ( !file_put_contents($this->priKEY,$x509certdata['pkey']) ){
                   $this->errMsg = 'Impossível gravar no diretório!!! Permissão negada!!';
                   $this->errStatus = true;
                   return false;
            }
        }
        //verifica se arquivo com a chave publica já existe
        if(file_exists($this->pubKEY)){
            //se existir verificar se é o mesmo atualmente instalado
            $conteudo = file_get_contents($this->pubKEY);
            //comparar os primeiros 30 digitos
            if ( !substr($conteudo,0,30) == substr($x509certdata['cert'],0,30) ) {
                 //se diferentes gravar o novo
                $n = file_put_contents($this->pubKEY,$x509certdata['cert']);
                //salva o certificado completo no formato pem
                $n = file_put_contents($this->certKEY,$x509certdata['pkey']."\r\n".$x509certdata['cert']);
            }
        } else {
            //se não existir salva a chave publica no formato pem para uso do SOAP
            $n = file_put_contents($this->pubKEY,$x509certdata['cert']);
            //salva o certificado completo no formato pem
            $n = file_put_contents($this->certKEY,$x509certdata['pkey']."\r\n".$x509certdata['cert']);
        }
        return true;
    } //fim loadCerts


   /**
    * __validCerts
    * Validaçao do cerificado digital, além de indicar
    * a validade, este metodo carrega a propriedade
    * mesesToexpire da classe que indica o numero de
    * meses que faltam para expirar a validade do mesmo
    * esta informacao pode ser utilizada para a gestao dos
    * certificados de forma a garantir que sempre estejam validos
    *
    * @name __validCerts
    * @version  1.00
    * @package  NFePHP
    * @author Roberto L. Machado <linux.rlm at gmail dot com>
    * @param    string  $cert Certificado digital no formato pem
    * @return	array ['status'=>true,'meses'=>8,'dias'=>245]
    */
    protected function __validCerts($cert){
        $flagOK = true;
        $errorMsg = "";
        $data = openssl_x509_read($cert);
        $cert_data = openssl_x509_parse($data);
        // reformata a data de validade;
        $ano = substr($cert_data['validTo'],0,2);
        $mes = substr($cert_data['validTo'],2,2);
        $dia = substr($cert_data['validTo'],4,2);
        //obtem o timeestamp da data de validade do certificado
        $dValid = gmmktime(0,0,0,$mes,$dia,$ano);
        // obtem o timestamp da data de hoje
        $dHoje = gmmktime(0,0,0,date("m"),date("d"),date("Y"));
        // compara a data de validade com a data atual
        if ($dValid < $dHoje ){
            $flagOK = false;
            $errorMsg = "A Validade do certificado expirou em ["  . $dia.'/'.$mes.'/'.$ano . "]";
        } else {
            $flagOK = $flagOK && true;
        }
        //diferença em segundos entre os timestamp
        $diferenca = $dValid - $dHoje;
        // convertendo para dias
        $diferenca = round($diferenca /(60*60*24),0);
        //carregando a propriedade
        $daysToExpire = $diferenca;
        // convertendo para meses e carregando a propriedade
        $m = ($ano * 12 + $mes);
        $n = (date("y") * 12 + date("m"));
        //numero de meses até o certificado expirar
        $monthsToExpire = ($m-$n);
        $this->certMonthsToExpire = $monthsToExpire;
        $this->certDaysToExpire = $daysToExpire;
        return array('status'=>$flagOK,'error'=>$errorMsg,'meses'=>$monthsToExpire,'dias'=>$daysToExpire);
    } //fim validCerts


    /**
     * __cleanCerts
     * Retira as chaves de inicio e fim do certificado digital
     * para inclusão do mesmo na tag assinatura do xml
     *
     * @name __cleanCerts
     * @version 1.00
     * @package NFePHP
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param    $certFile
     * @return   string contendo a chave digital limpa
     * @access   private
     **/
    protected function __cleanCerts($certFile){
        //carregar a chave publica do arquivo pem
        $pubKey = file_get_contents($certFile);
        //inicializa variavel
        $data = '';
        //carrega o certificado em um array usando o LF como referencia
        $arCert = explode("\n", $pubKey);
        foreach ($arCert AS $curData) {
            //remove a tag de inicio e fim do certificado
            if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0 && strncmp($curData, '-----END CERTIFICATE', 20) != 0 ) {
                //carrega o resultado numa string
                $data .= trim($curData);
            }
        }
        return $data;
    }

    //
    // define namespace / url e chama soap
    public function transmitirNFSeBetha($servico, $sXml, $ambiente) {

        $this->namespace = 'http://www.betha.com.br/e-nota-contribuinte-ws';
        if ($this->ambiente=='P') // produção
            $this->url = 'http://e-gov.betha.com.br/e-nota-contribuinte-ws/nfseWS?wsdl';
        else // homologação
            $this->url = 'http://e-gov.betha.com.br/e-nota-contribuinte-test-ws/nfseWS?wsdl';

        //valida o parâmetro da string do XML da NF-e
        if (empty($sXml)) { // || ! simplexml_load_string($sXml)) {
            return array(false, 'XML de NF-e para autorizacao recebido no parametro parece invalido, verifique');
        }

        // limpa a variavel
        $sNFSe = $sXml;
        //remove <?xml version="1.0" encoding=... e demais caracteres indesejados
        $sNFSe = preg_replace("/<\?xml.*\?>/", "", $sNFSe);
        $sNFSe = str_replace(array("\r","\n","\s"), "", $sNFSe);

        $data = '';
        $data .= '<?xml version="1.0" encoding="utf-8"?>';
        $data .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:e="'.$this->namespace.'">';
        $data .= '<soapenv:Header/>';
        $data .= '<soapenv:Body>';
        $data .= '<e:'.$servico.'>';
        $data .= $sNFSe;
        $data .= '</e:'.$servico.'>';
        $data .= '</soapenv:Body>';
        $data .= '</soapenv:Envelope>';

        try {

            //envia dados via SOAP
            $retorno = $this->pSendSOAPCurl($data, '', 'S');
            //verifica o retorno
            if (! $retorno) {
                return array(false, 'URL de Comunicação inválida !');
            }
        } catch(Exception $e){

            $result = false;
        }        

        return $retorno;
    }

    //
    // define namespace / url e chama soap
    public function transmitirNFSeIpm( $params ) {

        try {

            $this->url = "http://sync.nfs-e.net/datacenter/include/nfw/importa_nfw/nfw_import_upload.php"; // resposta em html
//            $this->url = "http://sync.nfs-e.net/datacenter/include/nfw/importa_nfw/nfw_import_upload.php?eletron=1"; // resposta em xml

            //valida o parâmetro da string do XML da NF-e
            if (empty($params)) { // || ! simplexml_load_string($sXml)) {
                return array(false, 'Parâmetros de envio da NFSe inválidos, verifique');
            }

            //envia dados via SOAP
            $retorno = $this->pSendSOAPCurl($params, '', 'S');
            //verifica o retorno
            if (! $retorno) {

                return array(false, 'URL de Comunicação inválida !');
            }
        } catch(Exception $e){

            $result = false;
        }        
        return $retorno;
    }

    //
    // define namespace / url e chama soap
    public function transmitirNFSeSimplISS( $codMunic, $sXml, $servico) {

        try {

            if ($this->ambiente=='P') { // produção

                switch ($codMunic) {
                    case '4202008': // Balneário Camboriú
                        $this->url = 'http://wsbalneariocamboriu.simplissweb.com.br/nfseservice.svc?wsdl'; break;
                    default:
                        return array(false, 'O sistema ainda não está emitindo notas para o município escolhido'); break;
                }
            }
            else // homologação
                $this->url = 'http://wshomologacao.simplissweb.com.br/nfseservice.svc?wsdl';

            $action = "http://www.sistema.com.br/Sistema.Ws.Nfse/INfseService/".$servico;

            //valida o parâmetro da string do XML da NF-e
            if (empty($sXml)) { // || ! simplexml_load_string($sXml)) {
                return array(false, 'XML de NF-e para autorizacao recebido no parametro parece invalido, verifique');
            }

            // limpa a variavel
            $sNFSe = $sXml;
            //remove <?xml version="1.0" encoding=... e demais caracteres indesejados
            $sNFSe = preg_replace("/<\?xml.*\?>/", "", $sNFSe);
            $sNFSe = str_replace(array("\r","\n","\s"), "", $sNFSe);

            $data = '';
            $data .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xd="http://www.w3.org/2000/09/xmldsig#" ';
            $data .= 'xmlns:sis="http://www.sistema.com.br/Sistema.Ws.Nfse" ';
            $data .= 'xmlns:nfse="http://www.sistema.com.br/Nfse/arquivos/nfse_3.xsd" ';
            $data .= 'xmlns:sis1="http://www.sistema.com.br/Sistema.Ws.Nfse.Cn">';
            $data .= '<soapenv:Header/>';
            $data .= '<soapenv:Body>';
            $data .= $sNFSe;
            $data .= '</soapenv:Body>';
            $data .= '</soapenv:Envelope>';


            //envia dados via SOAP
            $retorno = $this->pSendSOAPCurl($data, $action, 'S');
            //verifica o retorno
            if (! $retorno) {
                return array(false, 'URL de Comunicação inválida !');
            }
        } catch(Exception $e){

            $result = false;
        }        

        return $retorno;
    }

    //
    // define namespace / url e chama soap
    public function transmitirNFSeCuritiba( $sXml, $servico) {

        try {

            if ($this->ambiente=='P') // produção
                $this->url = 'https://isscuritiba.curitiba.pr.gov.br/Iss.NfseWebService/nfsews.asmx?WSDL'; 
            else // homologação
                $this->url = 'https://piloto-iss.curitiba.pr.gov.br/nfse_ws/NfseWs.asmx?WSDL';

            $action = "http://www.e-governeapps2.com.br/WS_x0020_-_x0020_NFS-e_x0020_V1.0.0.1Soap/".$servico;

            //valida o parâmetro da string do XML da NF-e
            if (empty($sXml)) { // || ! simplexml_load_string($sXml)) {
                return array(false, 'XML de NF-e para autorizacao recebido no parametro parece invalido, verifique');
            }

            // limpa a variavel
            $sNFSe = $sXml;
            //remove <?xml version="1.0" encoding=... e demais caracteres indesejados
            $sNFSe = preg_replace("/<\?xml.*\?>/", "", $sNFSe);
            $sNFSe = str_replace(array("\r","\n","\s"), "", $sNFSe);

            $data = '';
            $data .= '<soap:Envelope xmlns:soap12="http://www.w3.org/2003/05/soap-envelope" ';
            $data .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
            $data .= 'xmlns:xsd="http://www.w3.org/2001/XMLSchema"> ';
            $data .= '<soap12:Header/>';
            $data .= '<soap12:Body>';
            $data .= $sNFSe;
            $data .= '</soap12:Body>';
            $data .= '</soap12:Envelope>';


            //envia dados via SOAP
            $retorno = $this->pSendSOAPCurl($data, $action, 'S');
            //verifica o retorno
            if (! $retorno) {
                return array(false, 'URL de Comunicação inválida !');
            }
        } catch(Exception $e){

            $result = false;
        }        

        return $retorno;
    }

    //
    // chamada soap + curl + envelope
    protected function pSendSOAPCurl($dados, $action, $assina) {

        $headers = array();
        $headers[] = "Content-type: text/xml; charset=utf-8";

        if (is_array($dados))
            $tamanho = strlen(implode($dados));
        else
            $tamanho = strlen($dados);
        $headers[] = "Content-Length: ".$tamanho;
        if ($action > '')
            $headers[] = "SOAPAction: ".$action;

        try {

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $dados);
            if ($assina=='S') {
                curl_setopt($curl, CURLOPT_SSLCERT, $this->pubKEY);
                curl_setopt($curl, CURLOPT_SSLKEY, $this->priKEY);
            }

            //
            $result = curl_exec( $curl );
            $info = curl_getinfo( $curl );
        } catch(Exception $e){

            $result = false;
        }        

        return array($result, $info);
    } //fim __sendSOAPCurl

/*
    //
    // chamada soap + curl + envelope
    protected function pSendSOAPCurlOK($dados) {

        $headers = array( "Content-type: application/xml;"); 

        try {
        
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); 
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

            //
            $result = curl_exec( $curl );
            $info = curl_getinfo( $curl );
        } catch(Exception $e){

            $result = false;
        }        

        return array($result, $info);
    } //fim __sendSOAPCurl
*/

} 

?>