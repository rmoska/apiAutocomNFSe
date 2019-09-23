<?php
/**
 * Está atualizada para :
 *      PHP 5.3
 *      Versão 3.10 dos webservices da SEFAZ com comunicação via SOAP 1.2
 *      e conforme Manual de Integração Versão 5
 *
 */

//define o caminho base da instalação do sistema
if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR);
}


class ComunicaAbrasf 
{
    /**
     * Tipo de ambiente produção
     */
    const AMBIENTE_PRODUCAO = 1;
    /**
     * Tipo de ambiente homologação
     */
    const AMBIENTE_HOMOLOGACAO = 2;
    /**
     * raizDir
     * Diretorio raiz da API
     * @var string
     */
    public $raizDir = PATH_ROOT;
    /**
     * arqDir
     * Diretorio raiz de armazenamento das notas
     * @var string
     */
    public $arqDir = '';
    /**
     * assDir
     * Diretorio onde são armazenados temporariamente as notas já assinadas
     * @var string
     */
    public $assDir = '';
    /**
     * certsDir
     * Diretorio onde estão os certificados
     * @var string
     */
    public $certsDir = '';
    /**
     * xsdDir
     * diretorio que contem os esquemas de validação
     * estes esquemas devem ser mantidos atualizados
     * @var string
     */
    public $xsdDir = '';
    /**
     * xmlURLfile
     * Arquivo XML com as URLs dos webservices das SEFAZ de todos dos Estados e
     * demais ambientes como AN, virtuais, etc.
     * @var string
     * @see /config/nfe_ws3_modXX.xml (arquivo novo, layout 3.10 da NF-e, onde
     * "XX" é o modelo da NF-e, "55" ou "65")
     */
    public $xmlURLfile = 'nfe_ws3_mod55.xml';
    /**
     * soapTimeout
     * Limite de tempo que o SOAP aguarda por uma conexão
     * @var integer 0-indefinidamente ou numero de segundos
     */
    public $soapTimeout = 10;
    /**
     * tpAmb
     * Tipo de ambiente 1-produção 2-homologação
     * @var string
     */
    protected $tpAmb = '';
    /**
     * schemeVer
     * String com o nome do subdiretorio onde se encontram os schemas
     * atenção é case sensitive
     * @var string
     */
    protected $schemeVer = '';
    /**
     * aProxy
     * Matriz com as informações sobre o proxy da rede para uso pelo SOAP
     * @var array IP PORT USER PASS
     */
    public $aProxy = '';
    /**
     * keyPass
     * Senha de acesso a chave privada
     * @var string
     */
    private $keyPass = '';
    /**
     * passPhrase
     * palavra passe para acessar o certificado (normalmente não usada)
     * @var string
     */
    private $passPhrase = '';
    /**
     * certName
     * Nome do certificado digital
     * @var string
     */
    private $certName = '';
    /**
     * certMonthsToExpire
     * Meses que faltam para o certificado expirar
     * @var integer
     */
    public $certMonthsToExpire = 0;
    /**
     * certDaysToExpire
     * Dias que faltam para o certificado expirar
     * @var integer
     */
    public $certDaysToExpire = 0;
    /**
     * pfxTimeStamp
     * Timestamp da validade do certificado A1 PKCS12 .pfx
     * @var timestamp
     */
    private $pfxTimestamp = 0;
    /**
     * priKEY
     * Path completo para a chave privada em formato pem
     * @var string
     */
    protected $priKEY = '';
    /**
     * pubKEY
     * Path completo para a chave public em formato pem
     * @var string
     */
    protected $pubKEY = '';
    /**
     * certKEY
     * Path completo para o certificado (chave privada e publica) em formato pem
     * @var string
     */
    protected $certKEY = '';
    /**
     * cnpj
     * CNPJ do emitente
     * @var string
     */
    protected $cnpj = '';
    /**
     * cUF
     * Código da unidade da Federação IBGE
     * @var string
     */
    protected $cUF = '';
    /**
     * UF
     * Sigla da Unidade da Federação
     * @var string
     */
    protected $siglaUF = '';
    /**
     * timeZone
     * Zona de tempo GMT
     */
    protected $timeZone = '-03:00';
    /**
     * anoMes
     * Variável que contem o ano com 4 digitos e o mes com 2 digitos
     * Ex. 201003
     * @var string
     */
    private $anoMes = '';
    /**
     * aURL
     * Array com as URLs dos webservices da SEFAZ
     * @var array
     */
    public $aURL = array();
    /**
     * aCabec
     * @var array
     */
    public $aCabec = array();
    /**
     * errMsg
     * Mensagens de erro do API
     * @var string
     */
    public $errMsg = '';
    /**
     * errStatus
     * Status de erro
     * @var boolean
     */
    public $errStatus = false;
    /**
     * soapDebug
     * Mensagens de debug da comunicação SOAP
     * @var string
     */
    public $soapDebug = '';
    /**
     * debugMode
     * Ativa ou desativa as mensagens de debug da classe
     * @var integer
     */
    protected $debugMode = 2;
     /**
     * URLxsi
     * Instância do WebService
     * @var string
     */
    private $URLxsi = 'http://www.w3.org/2001/XMLSchema-instance';
    /**
     * URLxsd
     * Instância do WebService
     * @var string
     */
    private $URLxsd = 'http://www.w3.org/2001/XMLSchema';
    /**
     * URLnfe
     * Instância do WebService
     * @var string
     */
    private $URLnfe = 'http://www.portalfiscal.inf.br/nfe';
    /**
     * URLdsig
     * Instância do WebService
     * @var string
     */
    private $URLdsig = 'http://www.w3.org/2000/09/xmldsig#';
    /**
     * URLCanonMeth
     * Instância do WebService
     * @var string
     */
    private $URLCanonMeth = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    /**
     * URLSigMeth
     * Instância do WebService
     * @var string
     */
    private $URLSigMeth = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    /**
     * URLTransfMeth_1
     * Instância do WebService
     * @var string
     */
    private $URLTransfMeth_1 = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    /**
     * URLTransfMeth_2
     * Instância do WebService
     * @var string
     */
    private $URLTransfMeth_2 = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    /**
     * URLDigestMeth
     * Instância do WebService
     * @var string
     */
    private $URLDigestMeth = 'http://www.w3.org/2000/09/xmldsig#sha1';
    /**
     * URLPortal
     * Instância do WebService
     * @var string
     */
    private $URLPortal = 'http://www.portalfiscal.inf.br/nfe';
    /**
     * cUFlist
     * Lista dos numeros identificadores dos estados
     * @var array
     */
    private $cUFlist = array('AC'=>'12',
        'AL'=>'27',
        'AM'=>'13',
        'AP'=>'16',
        'BA'=>'29',
        'CE'=>'23',
        'DF'=>'53',
        'ES'=>'32',
        'GO'=>'52',
        'MA'=>'21',
        'MG'=>'31',
        'MS'=>'50',
        'MT'=>'51',
        'PA'=>'15',
        'PB'=>'25',
        'PE'=>'26',
        'PI'=>'22',
        'PR'=>'41',
        'RJ'=>'33',
        'RN'=>'24',
        'RO'=>'11',
        'RR'=>'14',
        'RS'=>'43',
        'SC'=>'42',
        'SE'=>'28',
        'SP'=>'35',
        'TO'=>'17',
        'SVAN'=>'91'); // TODO fmertins 22/08: não existe código da SVRS?
    
    
    /**
     * exceptions
     * Ativa ou desativa o uso de exceções para transporte de erros
     * @var boolean
     */
    protected $exceptions = false;

    /**
     * construct
     * Método construtor da classe
     * Este método utiliza o arquivo de configuração localizado no diretorio config
     * para montar os diretórios e várias propriedades internas da classe, permitindo
     * automatizar melhor o processo de comunicação com o SEFAZ.
     *
     * Este metodo pode estabelecer as configurações a partir do arquivo config.php ou
     * através de um array passado na instanciação da classe.
     *
     * @param array   $aConfig   Opcional dados de configuração
     * @param integer $mododebug Opcional 2-Não altera nenhum parâmetro 1-SIM ou 0-NÃO (2 default)
     * @param boolean $exceptions Opcional Utilize true para gerar exceções
     * @return boolean true sucesso false Erro
     */
    public function __construct($idCliente = '', $aConfig = '', $mododebug = 2, $exceptions = false)
    {
        if (is_numeric($mododebug)) {
            $this->debugMode = $mododebug;
        }
        if ($mododebug == 1) {
            //ativar modo debug
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        }
        if ($mododebug == 0) {
            //desativar modo debug
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
        if ($exceptions) {
            $this->exceptions = true;
        }
        //verifica se foi passado uma matriz de configuração na inicialização da classe
        if (is_array($aConfig)) {
            $this->tpAmb=$aConfig['ambiente'];
            $this->empName=$aConfig['empresa'];
            $this->siglaUF=$aConfig['UF'];
            $this->cUF=$this->cUFlist[$aConfig['UF']];
            $this->cnpj=$aConfig['cnpj'];
            $this->certName=$aConfig['certName'];
            $this->keyPass=$aConfig['keyPass'];
            $this->passPhrase=$aConfig['passPhrase'];
            $this->arqDir = $aConfig['arquivosDir'];
            $this->xmlURLfile = $aConfig['arquivoURLxml'];
            $this->schemeVer = $aConfig['schemes'];
            if (isset($aConfig['certsDir'])) {
                $this->certsDir =  $aConfig['certsDir'];
            }
            if ($aConfig['proxyIP'] != '') {
                $this->aProxy = array(
                    'IP'=>$aConfig['proxyIP'],
                    'PORT'=>$aConfig['proxyPORT'],
                    'USER'=>$aConfig['proxyUSER'],
                    'PASS'=>$aConfig['proxyPASS']);
            }
        } else {
            //testa a existencia do arquivo de configuração
//						$dirCli = 'arq'.$GLOBALS['nuClienteATC'].'/nfe'; 
						$dirCli = 'arq'.$idCliente.'/nfe'; 
            if (is_file($this->raizDir.'public_html/aquarius_lite/'.$dirCli.'/config'.DIRECTORY_SEPARATOR.'configNFeV4.php')) {
                //carrega o arquivo de configuração
                include($this->raizDir.'public_html/aquarius_lite/'.$dirCli.'/config'.DIRECTORY_SEPARATOR.'configNFeV4.php');
                // carrega propriedades da classe com os dados de configuração
                // a sring $sAmb será utilizada para a construção dos diretorios
                // dos arquivos de operação do sistema
                $this->tpAmb = $ambiente;
                //carrega as propriedades da classe com as configurações
                $this->empName = $empresa;
                $this->siglaUF = $UF;
                $this->cUF = $this->cUFlist[$UF];
                $this->cnpj = $cnpj;
                $this->certName = $certName;
                $this->keyPass = $keyPass;
                $this->passPhrase = $passPhrase;
                $this->arqDir = $arquivosDir;
                $this->xmlURLfile = $arquivoURLxml;
                $this->schemeVer = $schemes;
                if (isset($certsDir)) {
                    $this->certsDir =  $certsDir;
                }
                if ($proxyIP != '') {
                    $this->aProxy = array(
                        'IP'=>$proxyIP,
                        'PORT'=>$proxyPORT,
                        'USER'=>$proxyUSER,
                        'PASS'=>$proxyPASS);
                }
            } else {
                // caso não exista arquivo de configuração retorna erro
                $msg = "Não foi localizado o arquivo de configuração.\n";
                $this->pSetError($msg);
                if ($this->exceptions) {
                    throw new nfephpException($msg);
                }
                return false;
            }
        }
        //estabelece o ambiente
        $sAmb = ($this->tpAmb == self::AMBIENTE_HOMOLOGACAO) ? 'homologacao' : 'producao';
        //carrega propriedade com ano e mes ex. 200911
        $this->anoMes = date('Ym');
        //carrega o caminho para os schemas
        $this->xsdDir = $this->raizDir.'public_html/aquarius_lite/arquivos/nfe/schemes'. DIRECTORY_SEPARATOR;
        //carrega o caminho para os certificados caso não tenha sido passado por config
        if (empty($this->certsDir)) {
            $this->certsDir =  $this->raizDir.'public_html/aquarius_lite/arquivos/nfe/certs'. DIRECTORY_SEPARATOR;
        }
        //carrega o caminho para as imegens
        $this->imgDir =  $this->raizDir.'public_html/aquarius_lite/arquivos/nfe/images'. DIRECTORY_SEPARATOR;
        //verifica o ultimo caracter da variável $arqDir
        // se não for um DIRECTORY_SEPARATOR então colocar um
        if (substr($this->arqDir, -1, 1) != DIRECTORY_SEPARATOR) {
            $this->arqDir .= DIRECTORY_SEPARATOR;
        }
        // monta a estrutura de diretorios utilizados na manipulação das NFe
        $this->assDir=$this->arqDir.DIRECTORY_SEPARATOR.'assinadas'.DIRECTORY_SEPARATOR;
        $this->canDir=$this->arqDir.DIRECTORY_SEPARATOR.'canceladas'.DIRECTORY_SEPARATOR;
        $this->temDir=$this->arqDir.DIRECTORY_SEPARATOR.'temporarias'.DIRECTORY_SEPARATOR;
        //monta a arvore de diretórios necessária e estabelece permissões de acesso
        if (!is_dir($this->arqDir)) {
            mkdir($this->arqDir, 0777);
        }
//        if (!is_dir($this->arqDir.DIRECTORY_SEPARATOR.$sAmb)) {
//            mkdir($this->arqDir.DIRECTORY_SEPARATOR.$sAmb, 0777);
//        }
        if (!is_dir($this->assDir)) {
            mkdir($this->assDir, 0777);
        }
        if (!is_dir($this->canDir)) {
            mkdir($this->canDir, 0777);
        }
        if (!is_dir($this->temDir)) {
            mkdir($this->temDir, 0777);
        }
        //carrega um array com os dados para acesso aos WebServices SEFAZ
        if (!$this->aURL = $this->pLoadSEFAZ($this->tpAmb, $this->siglaUF)) {
            $msg = "Erro no carregamento das informacoes da SEFAZ: $this->errMsg";
            $this->pSetError($msg);
            if ($this->exceptions) {
                throw new nfephpException($msg);
            }
            return false;
        }
        //se houver erro no carregamento dos certificados passe para erro
        if (!$this->pLoadCerts()) {
            $msg = "Erro no carregamento dos certificados.";
            $this->pSetError($msg);
            if ($this->exceptions) {
                throw new nfephpException($msg);
            }
            return false;
        }
        //definir o timezone default para o estado do emitente
        date_default_timezone_set($timezone);

        return true;
    } //fim construct





    public function gerarNFSe($sxml) 
    {
        try {
            //retorno do método em array (esta estrutura espelha a estrutura do XML retornado pelo webservice
            //IMPORTANTE: esta estrutura varia parcialmente conforme o $indSinc
            //dados do protocolo de recebimento da NF-e
            $aRetorno['protNFe'] = array(
                'versao'=>'',
                'infProt'=>array( //informações do protocolo de autorização da NF-e
                    'tpAmb'=>'',
                    'verAplic'=>'',
                    'chNFe'=>'',
                    'dhRecbto'=>'',
                    'nProt'=>'',
                    'digVal'=>'',
                    'cStat'=>'',
                    'xMotivo'=>''));


            //identificação do serviço: autorização de NF-e
            $servico = 'NfeAutorizacao';
            //recuperação da versão
            $versao = $aURL[$servico]['version'];
            //recuperação da url do serviço
            $urlservico = $aURL[$servico]['URL'];
            //recuperação do método
            $metodo = $aURL[$servico]['method'];
            //montagem do namespace do serviço
            $operation = $aURL[$servico]['operation'];
            $namespace = $this->URLPortal.'/wsdl/'.$operation;


            $metodo = 'GerarNfseEnvio';


            //valida o parâmetro da string do XML da NF-e
            if (empty($sxml) || ! simplexml_load_string($sxml)) {
                throw new nfephpException("XML de NF-e para autorizacao recebido no parametro parece invalido, verifique");
            }


            // limpa a variavel
            $sNFe = $xml;
            //remove <?xml version="1.0" encoding=... e demais caracteres indesejados
            $sNFe = preg_replace("/<\?xml.*\?>/", "", $sNFe);
            $sNFe = str_replace(array("\r","\n","\s"), "", $sNFe);



            //envia dados via SOAP
            $retorno = $this->pSendSOAP($urlservico, $namespace, $sNFSe, $metodo);
            //verifica o retorno
            if (! $retorno) {
							/*
//							$arqErro = fopen($this->arqDir."/nfe/msgerrnfe.txt","at");  # append
							$arqErro = fopen($this->raizDir.'public_html/aquarius_lite/'.$dirCli.'/nfe/msgErrNFe.txt',"at");  # append
							fwrite($arqErro, $this->soapDebug); 
							fwrite($arqErro, $retorno); 
							fwrite($arqErro, "\n--------------------------------------------------------------------------------\n"); 
							fclose($arqErro);

                            throw new nfephpException("Nao houve retorno Soap verifique a mensagem de erro e o debug!!");
                */
            }


            //tratar dados de retorno
//            $doc = new DomDocumentNFePHP();
//            $doc->loadXML($retorno, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
            //$doc = simplexml_load_string($retorno);

print_r($retorno);

/*
            $cStat = $this->pSimpleGetValue($doc, "cStat");
            $xMotivo = $this->pSimpleGetValue($doc, "xMotivo");
            //verifica o codigo do status da resposta, se vazio houve erro
            if ($cStat == '') {
                throw new nfephpException("O retorno nao contem cStat verifique o debug do soap !!");
            } elseif ($indSinc === 0 && $cStat == '103') { //103-Lote recebido com sucesso
                $aRetorno['bStat'] = true;
            } elseif ($indSinc === 1 && $cStat == '104') { //104-Lote processado, podendo ter ou não o protNFe (#AR11 no layout)
                $aRetorno['bStat'] = true;
            } else {
                throw new nfephpException(sprintf("%s - %s", $cStat, $xMotivo));
            }
            // status da resposta do webservice
            $aRetorno['cStat'] = $cStat;
            // motivo da resposta (opcional)
            $aRetorno['xMotivo'] = $this->pSimpleGetValue($doc, "xMotivo");
            // data e hora da mensagem (opcional)
            if ($dhRecbto = $this->pSimpleGetValue($doc, "dhRecbto")) {
                $aRetorno['dhRecbto'] = date("d/m/Y H:i:s", $this->pConvertTime($dhRecbto));
            }
            //tipo do ambiente, versão do aplicativo e código da UF
            $aRetorno['tpAmb'] = $this->pSimpleGetValue($doc, "tpAmb");
            $aRetorno['verAplic'] = $this->pSimpleGetValue($doc, "verAplic");
            $aRetorno['cUF'] = $this->pSimpleGetValue($doc, "cUF");
            if ($indSinc == 1) {
                //retorno síncrono do webservice: dados do protocolo da NF-e
                $nodeProtNFe = $doc->getElementsByTagName('protNFe')->item(0);
                $nodeInfProt = $doc->getElementsByTagName('infProt')->item(0);
                $aRetorno['protNFe']['versao'] = $nodeProtNFe->getAttribute('versao');
                $infProt = array();
                $infProt['tpAmb'] = $this->pSimpleGetValue($nodeInfProt, "tpAmb");
                $infProt['verAplic'] = $this->pSimpleGetValue($nodeInfProt, "verAplic");
                $infProt['chNFe'] = $this->pSimpleGetValue($nodeInfProt, "chNFe");
                $dhRecbto = $this->pSimpleGetValue($nodeInfProt, "dhRecbto");
                $infProt['dhRecbto'] = date("d/m/Y H:i:s", $this->pConvertTime($dhRecbto));
                $infProt['digVal'] = $this->pSimpleGetValue($nodeInfProt, "digVal");
                $infProt['cStat'] = $this->pSimpleGetValue($nodeInfProt, "cStat");
                $infProt['xMotivo'] = $this->pSimpleGetValue($nodeInfProt, "xMotivo");
                //número do protocolo de autorização (opcional)
                $infProt['nProt'] = $this->pSimpleGetValue($nodeInfProt, "nProt");
                $aRetorno['protNFe']['infProt'] = $infProt;
                //nome do arquivo de retorno: chave da NF-e com sufixo "-prot"
                $nome = $this->temDir.$infProt['chNFe'].'-prot.xml';
            } else {
                //retorno assíncrono do webservice: dados do recibo do lote
                $aRetorno['infRec'] = array();
                $aRetorno['infRec']['nRec'] = $this->pSimpleGetValue($doc, "nRec");
                $aRetorno['infRec']['tMed'] = $this->pSimpleGetValue($doc, "tMed");
                //nome do arquivo de retorno: ID do lote com sufixo "-prot"
                $nome = $this->temDir.$idLote.'-rec.xml';
            }
            //grava o retorno na pasta de temporários
            $nome = $doc->save($nome);
*/

        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }

        return $retorno;

    }






    /**
     * autoriza
     * Envia NFe para a SEFAZ autorizar.
     * ATENÇÃO! Este é o antigo método "sendLot()" que enviava lotes de NF-e versão "2.00"
     * consumindo o WS "NfeRecepcao2", agora este método está preparado apenas para a versão
     * "3.10" e por isso utiliza o WS "NfeAutorizacao" sempre em modo síncrono.
     *
     * @name autoriza
     * @package NFePHP
     * @author Roberto L. Machado <linux.rlm at gmail dot com>
     * @param string  $sxml   string com uma nota fiscal em xml
     * @param integer $idLote id do lote e um numero (numeração sequencial)
     * @param array   $aRetorno parametro passado por referencia contendo a resposta da consulta em um array
     * @param integer $indSinc Indicação webservice assíncrono (0) ou síncrono (1)
     * @return mixed string XML do retorno do webservice, ou false se ocorreu algum erro
     */
    public function autoriza($sxml, $idLote, &$aRetorno = array(), $indSinc = 0)
    {
        try {
            //retorno do método em array (esta estrutura espelha a estrutura do XML retornado pelo webservice
            //IMPORTANTE: esta estrutura varia parcialmente conforme o $indSinc
            $aRetorno = array(
                'bStat'=>false,
                'tpAmb'=>'',
                'verAplic'=>'',
                'cStat'=>'',
                'xMotivo'=>'',
                'cUF'=>'',
                'dhRecbto'=>'');
            if ($indSinc === 0) {
                //dados do recibo do lote (gerado apenas se o lote for aceito)
                $aRetorno['infRec'] = array('nRec'=>'','tMed'=>'');
            } elseif ($indSinc === 1) {
                //dados do protocolo de recebimento da NF-e
                $aRetorno['protNFe'] = array(
                    'versao'=>'',
                    'infProt'=>array( //informações do protocolo de autorização da NF-e
                        'tpAmb'=>'',
                        'verAplic'=>'',
                        'chNFe'=>'',
                        'dhRecbto'=>'',
                        'nProt'=>'',
                        'digVal'=>'',
                        'cStat'=>'',
                        'xMotivo'=>''));
            } else {
                throw new nfephpException("Parametro indSinc deve ser inteiro 0 ou 1, verifique!!");
            }
            //verifica se alguma SVC esta habilitada, neste caso precisa recarregar os webservices
            if ($this->enableSVCAN) {
                $aURL = $this->pLoadSEFAZ($this->tpAmb, self::CONTINGENCIA_SVCAN);
            } elseif ($this->enableSVCRS) {
                $aURL = $this->pLoadSEFAZ($this->tpAmb, self::CONTINGENCIA_SVCRS);
            } else {
                $aURL = $this->aURL;
            }
            //identificação do serviço: autorização de NF-e
            $servico = 'NfeAutorizacao';
            //recuperação da versão
            $versao = $aURL[$servico]['version'];
            //recuperação da url do serviço
            $urlservico = $aURL[$servico]['URL'];
            //recuperação do método
            $metodo = $aURL[$servico]['method'];
            //montagem do namespace do serviço
            $operation = $aURL[$servico]['operation'];
            $namespace = $this->URLPortal.'/wsdl/'.$operation;
            //valida o parâmetro da string do XML da NF-e
            if (empty($sxml) || ! simplexml_load_string($sxml)) {
                throw new nfephpException("XML de NF-e para autorizacao "
                        . "recebido no parametro parece invalido, verifique");
            }
            // limpa a variavel
            $sNFe = $sxml;
            //remove <?xml version="1.0" encoding=... e demais caracteres indesejados
            $sNFe = preg_replace("/<\?xml.*\?>/", "", $sNFe);
            $sNFe = str_replace(array("\r","\n","\s"), "", $sNFe);
            //montagem do cabeçalho da comunicação SOAP
            $cabec = "<nfeCabecMsg xmlns=\"$namespace\">"
                    . "<cUF>$this->cUF</cUF>"
                    . "<versaoDados>$versao</versaoDados>"
                    . "</nfeCabecMsg>";
            //montagem dos dados da mensagem SOAP
            $dados = "<nfeDadosMsg xmlns=\"$namespace\">"
                    . "<enviNFe xmlns=\"$this->URLPortal\" versao=\"$versao\">"
                    . "<idLote>$idLote</idLote>"
                    . "<indSinc>$indSinc</indSinc>$sNFe</enviNFe></nfeDadosMsg>";
            //envia dados via SOAP
            $retorno = $this->pSendSOAP($urlservico, $namespace, $cabec, $dados, $metodo, $this->tpAmb);
            //verifica o retorno
            if (! $retorno) {
							/*---*/
//							$arqErro = fopen($this->arqDir."/nfe/msgerrnfe.txt","at");  # append
							$arqErro = fopen($this->raizDir.'public_html/aquarius_lite/'.$dirCli.'/nfe/msgErrNFe.txt',"at");  # append
							fwrite($arqErro, $this->soapDebug); 
							fwrite($arqErro, $retorno); 
							fwrite($arqErro, "\n--------------------------------------------------------------------------------\n"); 
							fclose($arqErro);
							/*---*/
                throw new nfephpException("Nao houve retorno Soap verifique a mensagem de erro e o debug!!");
            }
            //tratar dados de retorno
            $doc = new DomDocumentNFePHP();
            $doc->loadXML($retorno, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
            $cStat = $this->pSimpleGetValue($doc, "cStat");
            $xMotivo = $this->pSimpleGetValue($doc, "xMotivo");
            //verifica o codigo do status da resposta, se vazio houve erro
            if ($cStat == '') {
                throw new nfephpException("O retorno nao contem cStat verifique o debug do soap !!");
            } elseif ($indSinc === 0 && $cStat == '103') { //103-Lote recebido com sucesso
                $aRetorno['bStat'] = true;
            } elseif ($indSinc === 1 && $cStat == '104') { //104-Lote processado, podendo ter ou não o protNFe (#AR11 no layout)
                $aRetorno['bStat'] = true;
            } else {
                throw new nfephpException(sprintf("%s - %s", $cStat, $xMotivo));
            }
            // status da resposta do webservice
            $aRetorno['cStat'] = $cStat;
            // motivo da resposta (opcional)
            $aRetorno['xMotivo'] = $this->pSimpleGetValue($doc, "xMotivo");
            // data e hora da mensagem (opcional)
            if ($dhRecbto = $this->pSimpleGetValue($doc, "dhRecbto")) {
                $aRetorno['dhRecbto'] = date("d/m/Y H:i:s", $this->pConvertTime($dhRecbto));
            }
            //tipo do ambiente, versão do aplicativo e código da UF
            $aRetorno['tpAmb'] = $this->pSimpleGetValue($doc, "tpAmb");
            $aRetorno['verAplic'] = $this->pSimpleGetValue($doc, "verAplic");
            $aRetorno['cUF'] = $this->pSimpleGetValue($doc, "cUF");
            if ($indSinc == 1) {
                //retorno síncrono do webservice: dados do protocolo da NF-e
                $nodeProtNFe = $doc->getElementsByTagName('protNFe')->item(0);
                $nodeInfProt = $doc->getElementsByTagName('infProt')->item(0);
                $aRetorno['protNFe']['versao'] = $nodeProtNFe->getAttribute('versao');
                $infProt = array();
                $infProt['tpAmb'] = $this->pSimpleGetValue($nodeInfProt, "tpAmb");
                $infProt['verAplic'] = $this->pSimpleGetValue($nodeInfProt, "verAplic");
                $infProt['chNFe'] = $this->pSimpleGetValue($nodeInfProt, "chNFe");
                $dhRecbto = $this->pSimpleGetValue($nodeInfProt, "dhRecbto");
                $infProt['dhRecbto'] = date("d/m/Y H:i:s", $this->pConvertTime($dhRecbto));
                $infProt['digVal'] = $this->pSimpleGetValue($nodeInfProt, "digVal");
                $infProt['cStat'] = $this->pSimpleGetValue($nodeInfProt, "cStat");
                $infProt['xMotivo'] = $this->pSimpleGetValue($nodeInfProt, "xMotivo");
                //número do protocolo de autorização (opcional)
                $infProt['nProt'] = $this->pSimpleGetValue($nodeInfProt, "nProt");
                $aRetorno['protNFe']['infProt'] = $infProt;
                //nome do arquivo de retorno: chave da NF-e com sufixo "-prot"
                $nome = $this->temDir.$infProt['chNFe'].'-prot.xml';
            } else {
                //retorno assíncrono do webservice: dados do recibo do lote
                $aRetorno['infRec'] = array();
                $aRetorno['infRec']['nRec'] = $this->pSimpleGetValue($doc, "nRec");
                $aRetorno['infRec']['tMed'] = $this->pSimpleGetValue($doc, "tMed");
                //nome do arquivo de retorno: ID do lote com sufixo "-prot"
                $nome = $this->temDir.$idLote.'-rec.xml';
            }
            //grava o retorno na pasta de temporários
            $nome = $doc->save($nome);
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return $retorno;
    }// fim autoriza



    /**
     * cancelEvent
     * Solicita o cancelamento de NFe autorizada
     * - O xml do evento de cancelamento será salvo na pasta Canceladas
     *
     * @name cancelEvent
     * @param string $chNFe
     * @param string $nProt
     * @param string $xJust
     * @param number $tpAmb
     * @param array  $aRetorno
     */
    public function montaCancelEvent($chNFe = '', $nProt = '', $xJust = '', $tpAmb = '', &$aRetorno = array())
    {
        try {
            //retorno da função
            $aRetorno = array(
                'bStat'=>false,
                'tpAmb'=>'',
                'verAplic'=>'',
                'cStat'=>'',
                'xMotivo'=>'',
                'nProt'=>'',
                'chNFe'=>'',
                'dhRecbto'=>'');
            //validação dos dados de entrada
            if ($chNFe == '' || $nProt == '' || $xJust == '') {
                $msg = "Não foi passado algum dos parâmetros necessários "
                        . "ID=$chNFe ou protocolo=$nProt ou justificativa=$xJust.";
                throw new nfephpException($msg);
            }
            if ($tpAmb == '') {
                $tpAmb = $this->tpAmb;
            }
            if (strlen($xJust) < 15) {
                $msg = "A justificativa deve ter pelo menos 15 digitos!!";
                throw new nfephpException($msg);
            }
            if (strlen($xJust) > 255) {
                $msg = "A justificativa deve ter no máximo 255 digitos!!";
                throw new nfephpException($msg);
            }
            if (strlen($chNFe) != 44) {
                $msg = "Uma chave de NFe válida não foi passada como parâmetro $chNFe.";
                throw new nfephpException($msg);
            }
            //estabelece o codigo do tipo de evento CANCELAMENTO
            $tpEvento = '110111';
            $descEvento = 'Cancelamento';
            //para cancelamento o numero sequencia do evento sempre será 1
            $nSeqEvento = '1';
            //remove qualquer caracter especial
            $xJust = $this->pCleanString($xJust);
            //verifica se alguma das contingências está habilitada
            if ($this->enableSVCAN) {
                $aURL = $this->pLoadSEFAZ($tpAmb, self::CONTINGENCIA_SVCAN);
            } elseif ($this->enableSVCRS) {
                $aURL = $this->pLoadSEFAZ($tpAmb, self::CONTINGENCIA_SVCRS);
            } else {
                $aURL = $this->aURL;
            }
            $numLote = $this->pGeraNumLote();
            //Data e hora do evento no formato AAAA-MM-DDTHH:MM:SSTZD (UTC)
            $dhEvento = date('Y-m-d\TH:i:s').$this->timeZone;
            //se o envio for para svan mudar o numero no orgão para 91
            if ($this->enableSVAN) {
                $cOrgao='90';
            } else {
                $cOrgao=$this->cUF;
            }
            //montagem do namespace do serviço
            $servico = 'RecepcaoEvento';
            //recuperação da versão
            $versao = $aURL[$servico]['version'];
            //recuperação da url do serviço
            $urlservico = $aURL[$servico]['URL'];
            //recuperação do método
            $metodo = $aURL[$servico]['method'];
            //montagem do namespace do serviço
            $operation = $aURL[$servico]['operation'];
            $namespace = $this->URLPortal.'/wsdl/'.$operation;
            //de acordo com o manual versão 5 de março de 2012
            // 2   +    6     +    44         +   2  = 54 digitos
            //“ID” + tpEvento + chave da NF-e + nSeqEvento
            //garantir que existam 2 digitos em nSeqEvento para montar o ID com 54 digitos
            if (strlen(trim($nSeqEvento))==1) {
                $zenSeqEvento = str_pad($nSeqEvento, 2, "0", STR_PAD_LEFT);
            } else {
                $zenSeqEvento = trim($nSeqEvento);
            }
            $eventId = "ID".$tpEvento.$chNFe.$zenSeqEvento;
            //monta mensagem
            $Ev = '';
            $Ev .= "<evento xmlns=\"$this->URLPortal\" versao=\"$versao\">";
            $Ev .= "<infEvento Id=\"$eventId\">";
            $Ev .= "<cOrgao>$cOrgao</cOrgao>";
            $Ev .= "<tpAmb>$tpAmb</tpAmb>";
            $Ev .= "<CNPJ>$this->cnpj</CNPJ>";
            $Ev .= "<chNFe>$chNFe</chNFe>";
            $Ev .= "<dhEvento>$dhEvento</dhEvento>";
            $Ev .= "<tpEvento>$tpEvento</tpEvento>";
            $Ev .= "<nSeqEvento>$nSeqEvento</nSeqEvento>";
            $Ev .= "<verEvento>$versao</verEvento>";
            $Ev .= "<detEvento versao=\"$versao\">";
            $Ev .= "<descEvento>$descEvento</descEvento>";
            $Ev .= "<nProt>$nProt</nProt>";
            $Ev .= "<xJust>$xJust</xJust>";
            $Ev .= "</detEvento></infEvento></evento>";
						
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
			return $Ev;
    } // fim montaCancelNF
						
    public function cancelEvent($chNFe, $cancXML, &$aRetorno = array()) {
        try {
            //verifica se alguma das contingências está habilitada
            if ($this->enableSVCAN) {
                $aURL = $this->pLoadSEFAZ($tpAmb, self::CONTINGENCIA_SVCAN);
            } elseif ($this->enableSVCRS) {
                $aURL = $this->pLoadSEFAZ($tpAmb, self::CONTINGENCIA_SVCRS);
            } else {
                $aURL = $this->aURL;
            }
            $numLote = $this->pGeraNumLote();
            //Data e hora do evento no formato AAAA-MM-DDTHH:MM:SSTZD (UTC)
            $dhEvento = date('Y-m-d\TH:i:s').$this->timeZone;
            //se o envio for para svan mudar o numero no orgão para 91
            if ($this->enableSVAN) {
                $cOrgao='90';
            } else {
                $cOrgao=$this->cUF;
            }
            //montagem do namespace do serviço
            $servico = 'RecepcaoEvento';
            //recuperação da versão
            $versao = $aURL[$servico]['version'];
            //recuperação da url do serviço
            $urlservico = $aURL[$servico]['URL'];
            //recuperação do método
            $metodo = $aURL[$servico]['method'];
            //montagem do namespace do serviço
            $operation = $aURL[$servico]['operation'];
            $namespace = $this->URLPortal.'/wsdl/'.$operation;
						
/*						
            //de acordo com o manual versão 5 de março de 2012
            // 2   +    6     +    44         +   2  = 54 digitos
            //“ID” + tpEvento + chave da NF-e + nSeqEvento
            //garantir que existam 2 digitos em nSeqEvento para montar o ID com 54 digitos
            if (strlen(trim($nSeqEvento))==1) {
                $zenSeqEvento = str_pad($nSeqEvento, 2, "0", STR_PAD_LEFT);
            } else {
                $zenSeqEvento = trim($nSeqEvento);
            }
*/

            $Ev = $this->pClearXml($cancXML, true);
            //carrega uma matriz temporária com os eventos assinados
            //montagem dos dados
            $dados = '';
            $dados .= "<envEvento xmlns=\"$this->URLPortal\" versao=\"$versao\">";
            $dados .= "<idLote>$numLote</idLote>";
            $dados .= $Ev;
            $dados .= "</envEvento>";
            //montagem da mensagem
            $cabec = "<nfeCabecMsg xmlns=\"$namespace\"><cUF>$this->cUF</cUF>"
                    . "<versaoDados>$versao</versaoDados></nfeCabecMsg>";
            $dados = "<nfeDadosMsg xmlns=\"$namespace\">$dados</nfeDadosMsg>";

/*
            //grava solicitação em temp
            $arqName = $this->temDir."$chNFe-$nSeqEvento-eventCanc.xml";
            if (!file_put_contents($arqName, $Ev)) {
                $msg = "Falha na gravacao do arquivo $arqName";
                $this->pSetError($msg);
            }
*/

            //envia dados via SOAP
            $retorno = $this->pSendSOAP($urlservico, $namespace, $cabec, $dados, $metodo, $tpAmb);
            //verifica o retorno
            if (!$retorno) {
                //não houve retorno
                $msg = "Nao houve retorno Soap verifique a mensagem de erro e o debug!!";
                throw new nfephpException($msg);
            }
            //tratar dados de retorno
            $xmlretEvent = new DOMDocument('1.0', 'utf-8'); //cria objeto DOM
            $xmlretEvent->formatOutput = false;
            $xmlretEvent->preserveWhiteSpace = false;
            $xmlretEvent->loadXML($retorno, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
            $retEnvEvento = $xmlretEvent->getElementsByTagName("retEnvEvento")->item(0);
            $cStat = !empty($retEnvEvento->getElementsByTagName('cStat')->item(0)->nodeValue) ?
                    $retEnvEvento->getElementsByTagName('cStat')->item(0)->nodeValue : '';
            $xMotivo = !empty($retEnvEvento->getElementsByTagName('xMotivo')->item(0)->nodeValue) ?
                    $retEnvEvento->getElementsByTagName('xMotivo')->item(0)->nodeValue : '';
            if ($cStat == '') {
                //houve erro
                $msg = "cStat está em branco, houve erro na comunicação Soap "
                        . "verifique a mensagem de erro e o debug!!";
                throw new nfephpException($msg);
            }
            //tratar erro de versão do XML
            if ($cStat == '238' || $cStat == '239') {
                $this->pTrata239($retorno, $this->siglaUF, $tpAmb, $servico, $versao);
                $msg = "Versão do arquivo XML não suportada no webservice!!";
                throw new nfephpException($msg);
            }
            //erro no processamento cStat <> 128
            if ($cStat != 128) {
                //se cStat <> 135 houve erro e o lote foi rejeitado
                $msg = "Retorno de ERRO: $cStat - $xMotivo";
                throw new nfephpException($msg);
            }
            //o lote foi processado cStat == 128
            $retEvento = $xmlretEvent->getElementsByTagName("retEvento")->item(0);
            $cStat = !empty($retEvento->getElementsByTagName('cStat')->item(0)->nodeValue) ?
                    $retEvento->getElementsByTagName('cStat')->item(0)->nodeValue : '';
            $xMotivo = !empty($retEvento->getElementsByTagName('xMotivo')->item(0)->nodeValue) ?
                    $retEvento->getElementsByTagName('xMotivo')->item(0)->nodeValue : '';
            if ($cStat != 135 && $cStat != 155) {
                //se cStat <> 135 houve erro e o lote foi rejeitado
                $msg = "Retorno de ERRO: $cStat - $xMotivo";
                throw new nfephpException($msg);
            }
            $aRetorno['bStat'] = true;
            // tipo de ambiente
            $aRetorno['tpAmb'] = $retEvento->getElementsByTagName('tpAmb')->item(0)->nodeValue;
            // verssão do aplicativo
            $aRetorno['verAplic'] = $retEvento->getElementsByTagName('verAplic')->item(0)->nodeValue;
            // status do serviço
            $aRetorno['cStat'] = $retEvento->getElementsByTagName('cStat')->item(0)->nodeValue;
            // motivo da resposta (opcional)
            $aRetorno['xMotivo'] = $retEvento->getElementsByTagName('xMotivo')->item(0)->nodeValue;
            // Numero de Protocolo
            $aRetorno['nProt'] = $retEvento->getElementsByTagName('nProt')->item(0)->nodeValue;
            // Chave
            $aRetorno['chNFe'] = $retEvento->getElementsByTagName('chNFe')->item(0)->nodeValue;
            // data e hora da mensagem (opcional)
            $aRetorno['dhRecbto'] = !empty($retEvento->getElementsByTagName('dhRegEvento')->item(0)->nodeValue) ?
                                    date("d/m/Y H:i:s", $this->pConvertTime($retEvento->getElementsByTagName('dhRegEvento')->item(0)->nodeValue)) : '';
            //o evento foi aceito cStat == 135 ou cStat == 155
            //carregar o evento
            $xmlenvEvento = new DOMDocument('1.0', 'utf-8'); //cria objeto DOM
            $xmlenvEvento->formatOutput = false;
            $xmlenvEvento->preserveWhiteSpace = false;
            $xmlenvEvento->loadXML($Ev, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
            $evento = $xmlenvEvento->getElementsByTagName("evento")->item(0);
            //Processo completo solicitação + protocolo
            $xmlprocEvento = new DOMDocument('1.0', 'utf-8');
            $xmlprocEvento->formatOutput = false;
            $xmlprocEvento->preserveWhiteSpace = false;
            //cria a tag procEventoNFe
            $procEventoNFe = $xmlprocEvento->createElement('procEventoNFe');
            $xmlprocEvento->appendChild($procEventoNFe);
            //estabele o atributo de versão
            $eventProc_att1 = $procEventoNFe->appendChild($xmlprocEvento->createAttribute('versao'));
            $eventProc_att1->appendChild($xmlprocEvento->createTextNode($versao));
            //estabelece o atributo xmlns
            $eventProc_att2 = $procEventoNFe->appendChild($xmlprocEvento->createAttribute('xmlns'));
            $eventProc_att2->appendChild($xmlprocEvento->createTextNode($this->URLPortal));
            //carrega o node evento
            $node1 = $xmlprocEvento->importNode($evento, true);
            $procEventoNFe->appendChild($node1);
            //carrega o node retEvento
            $node2 = $xmlprocEvento->importNode($retEvento, true);
            $procEventoNFe->appendChild($node2);
            //salva o xml como string em uma variável
            $procXML = $xmlprocEvento->saveXML();
            //remove as informações indesejadas
            $procXML = $this->pClearXml($procXML, false);
            //salva o arquivo xml
            $arqName = $this->canDir."$chNFe-procCanc.xml";
            if (!file_put_contents($arqName, $procXML)) {
                $msg = "Falha na gravacao do arquivo $arqName";
                $this->pSetError($msg);
						}
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }

        return $aRetorno;
    } //fim cancEvent


    /**
     * loadSEFAZ
     * Extrai o URL, nome do serviço e versão dos webservices das SEFAZ de
     * todos os Estados da Federação, a partir do arquivo XML de configurações,
     * onde este é estruturado para os modelos 55 (NF-e) e 65 (NFC-e) já que
     * os endereços dos webservices podem ser diferentes.
     *
     * @name loadSEFAZ
     * @param  string $tpAmb     Pode ser "2-homologacao" ou "1-producao"
     * @param  string $sUF       Sigla da Unidade da Federação (ex. SP, RS, etc..)
     * @return mixed             false se houve erro ou array com os dados dos URLs da SEFAZ
     * @see /config/nfe_ws3_modXX.xml
     */
    protected function pLoadSEFAZ($tpAmb = '', $sUF = '')
    {
        try {
            $spathXML = $this->raizDir.'public_html/aquarius_lite/arquivos/nfe/config'.DIRECTORY_SEPARATOR.$this->xmlURLfile;
            //verifica se o arquivo xml pode ser encontrado no caminho indicado
            if (!file_exists($spathXML)) {
                throw new nfephpException("O arquivo XML \"$spathXML\" nao foi encontrado");
            }
            //carrega o xml
            if (!$xmlWS = simplexml_load_file($spathXML)) {
                throw new nfephpException("O arquivo XML \"$spathXML\" parece ser invalido");
            }
            //variável de retorno do método
            $aUrl = array();
            //testa parametro tpAmb
            if ($tpAmb == '') {
                $tpAmb = $this->tpAmb;
            } elseif ($tpAmb == self::AMBIENTE_PRODUCAO) {
                $sAmbiente = 'producao';
            } else {
                //força homologação em qualquer outra situação
                $tpAmb = self::AMBIENTE_HOMOLOGACAO;
                $sAmbiente = 'homologacao';
            }

            //estabelece a expressão xpath de busca
            $xpathExpression = "/WS/UF[sigla='$alias']/$sAmbiente";
            //para cada "nó" no xml que atenda aos critérios estabelecidos
            foreach ($xmlWS->xpath($xpathExpression) as $gUF) {
                //para cada "nó filho" retonado
                foreach ($gUF->children() as $child) {
                    $u = (string) $child[0];
                    $aUrl[$child->getName()]['URL'] = $u;
                    // em cada um desses nós pode haver atributos como a identificação
                    // do nome do webservice e a sua versão
                    foreach ($child->attributes() as $a => $b) {
                        $aUrl[$child->getName()][$a] = (string) $b;
                    }
                }
            }
            //verifica se existem outros serviços exclusivos para esse estado
            if ($alias == self::SVAN || $alias == self::SVRS) {
                //para cada "nó" no xml que atenda aos critérios estabelecidos
                foreach ($xmlWS->xpath($xpathExpression) as $gUF) {
                    //para cada "nó filho" retonado
                    foreach ($gUF->children() as $child) {
                        $u = (string) $child[0];
                        $aUrl[$child->getName()]['URL'] = $u;
                        // em cada um desses nós pode haver atributos como a identificação
                        // do nome do webservice e a sua versão
                        foreach ($child->attributes() as $a => $b) {
                            $aUrl[$child->getName()][$a] = (string) $b;
                        }
                    }
                }
            }
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return $aUrl;
    } //fim loadSEFAZ

    /**
     * loadCerts
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
     * @name loadCerts
     * @param  boolean $testaVal True testa a validade do certificado ou false não testa
     * @return boolean true se o certificado foi carregado e false se não
     */
    public function pLoadCerts($testaVal = true)
    {
        try {
            if (!function_exists('openssl_pkcs12_read')) {
                $msg = "Função não existente: openssl_pkcs12_read!!";
                throw new nfephpException($msg);
            }
            //monta o path completo com o nome da chave privada
            $this->priKEY = $this->certsDir.$this->cnpj.'_priKEY.pem';
            //monta o path completo com o nome da chave prublica
            $this->pubKEY =  $this->certsDir.$this->cnpj.'_pubKEY.pem';
            //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
            $this->certKEY = $this->certsDir.$this->cnpj.'_certKEY.pem';
            //verificar se o nome do certificado e
            //o path foram carregados nas variaveis da classe
            if ($this->certsDir == '' || $this->certName == '') {
                $msg = "Um certificado deve ser passado para a classe pelo arquivo de configuração!! ";
                throw new nfephpException($msg);
            }
            //monta o caminho completo até o certificado pfx
            $pfxCert = $this->certsDir.$this->certName;
            //verifica se o arquivo existe
            if (!file_exists($pfxCert)) {
                $msg = "Certificado não encontrado!! $pfxCert";
                throw new nfephpException($msg);
            }
            //carrega o certificado em um string
            $pfxContent = file_get_contents($pfxCert);
            //carrega os certificados e chaves para um array denominado $x509certdata
            if (!openssl_pkcs12_read($pfxContent, $x509certdata, $this->keyPass)) {
                $msg = "O certificado não pode ser lido!! Provavelmente corrompido ou com formato inválido!!";
                throw new nfephpException($msg);
            }
            if ($testaVal) {
                //verifica sua validade
                if (!$aResp = $this->pValidCerts($x509certdata['cert'])) {
                     $msg = "Certificado invalido!! - ".$aResp['error'];
                    throw new nfephpException($msg);
                }
            }
            //aqui verifica se existem as chaves em formato PEM
            //se existirem pega a data da validade dos arquivos PEM
            //e compara com a data de validade do PFX
            //caso a data de validade do PFX for maior que a data do PEM
            //deleta dos arquivos PEM, recria e prossegue
            $flagNovo = false;
            if (file_exists($this->pubKEY)) {
                $cert = file_get_contents($this->pubKEY);
                if (!$data = openssl_x509_read($cert)) {
                    //arquivo não pode ser lido como um certificado
                    //então deletar
                    $flagNovo = true;
                } else {
                    //pegar a data de validade do mesmo
                    $cert_data = openssl_x509_parse($data);
                    // reformata a data de validade;
                    $ano = substr($cert_data['validTo'], 0, 2);
                    $mes = substr($cert_data['validTo'], 2, 2);
                    $dia = substr($cert_data['validTo'], 4, 2);
                    //obtem o timeestamp da data de validade do certificado
                    $dValPubKey = gmmktime(0, 0, 0, $mes, $dia, $ano);
                    //compara esse timestamp com o do pfx que foi carregado
                    if ($dValPubKey < $this->pfxTimestamp) {
                        //o arquivo PEM é de um certificado anterior
                        //então apagar os arquivos PEM
                        $flagNovo = true;
                    }//fim teste timestamp
                }//fim read pubkey
            } else {
                //arquivo não localizado
                $flagNovo = true;
            }//fim if file pubkey
            //verificar a chave privada em PEM
            if (!file_exists($this->priKEY)) {
                //arquivo não encontrado
                $flagNovo = true;
            }
            //verificar o certificado em PEM
            if (!file_exists($this->certKEY)) {
                //arquivo não encontrado
                $flagNovo = true;
            }
            //criar novos arquivos PEM
            if ($flagNovo) {
                if (file_exists($this->pubKEY)) {
                    unlink($this->pubKEY);
                }
                if (file_exists($this->priKEY)) {
                    unlink($this->priKEY);
                }
                if (file_exists($this->certKEY)) {
                    unlink($this->certKEY);
                }
                //recriar os arquivos pem com o arquivo pfx
                if (! file_put_contents($this->priKEY, $x509certdata['pkey'])) {
                    $msg = "Impossivel gravar no diretório!!! Permissão negada!!";
                    throw new nfephpException($msg);
                }
                //acrescenta a cadeia completa dos certificados se estiverem
                //inclusas no arquivo pfx, caso contrario deverão ser inclusas
                //manualmente para acessar os serviços em GO
                $aCer = $x509certdata['extracerts'];
                $chain = '';
                foreach ($aCer as $cert) {
                    $chain .= "$cert";
                }
                file_put_contents($this->pubKEY, $x509certdata['cert']);
                file_put_contents($this->certKEY, $x509certdata['cert'] . $chain);
            }
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return true;
    } //fim loadCerts

    /**
     * pValidCerts
     * Validaçao do cerificado digital, além de indicar
     * a validade, este metodo carrega a propriedade
     * mesesToexpire da classe que indica o numero de
     * meses que faltam para expirar a validade do mesmo
     * esta informacao pode ser utilizada para a gestao dos
     * certificados de forma a garantir que sempre estejam validos
     *
     * @name pValidCerts
     * @param    string  $cert Certificado digital no formato pem
     * @param    array   $aRetorno variavel passa por referência Array com os dados do certificado
     * @return  boolean true ou false
     */
    protected function pValidCerts($cert = '', &$aRetorno = '')
    {
        try {
            if ($cert == '') {
                $msg = "O certificado é um parâmetro obrigatorio.";
                throw new nfephpException($msg);
            }
            if (!$data = openssl_x509_read($cert)) {
                $msg = "O certificado não pode ser lido pelo SSL - $cert .";
                throw new nfephpException($msg);
            }
            $flagOK = true;
            $errorMsg = "";
            $cert_data = openssl_x509_parse($data);
            // reformata a data de validade;
            $ano = substr($cert_data['validTo'], 0, 2);
            $mes = substr($cert_data['validTo'], 2, 2);
            $dia = substr($cert_data['validTo'], 4, 2);
            //obtem o timestamp da data de validade do certificado
            $dValid = gmmktime(0, 0, 0, $mes, $dia, $ano);
            // obtem o timestamp da data de hoje
            $dHoje = gmmktime(0, 0, 0, date("m"), date("d"), date("Y"));
            // compara a data de validade com a data atual
            if ($dValid < $dHoje) {
                $flagOK = false;
                $errorMsg = "A Validade do certificado expirou em [" .$dia.'/'.$mes.'/'.$ano."]";
            } else {
                $flagOK = $flagOK && true;
            }
            //diferença em segundos entre os timestamp
            $diferenca = $dValid - $dHoje;
            // convertendo para dias
            $diferenca = round($diferenca /(60*60*24), 0);
            //carregando a propriedade
            $daysToExpire = $diferenca;
            // convertendo para meses e carregando a propriedade
            $numM = ($ano * 12 + $mes);
            $numN = (date("y") * 12 + date("m"));
            //numero de meses até o certificado expirar
            $monthsToExpire = ($numM-$numN);
            $this->certMonthsToExpire = $monthsToExpire;
            $this->certDaysToExpire = $daysToExpire;
            $this->pfxTimestamp = $dValid;
            $aRetorno = array('status'=>$flagOK,'error'=>$errorMsg,'meses'=>$monthsToExpire,'dias'=>$daysToExpire);
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return true;
    } //fim validCerts

    /**
     * cleanCerts
     * Retira as chaves de inicio e fim do certificado digital
     * para inclusão do mesmo na tag assinatura do xml
     *
     * @name cleanCerts
     * @param    $certFile
     * @return   mixed false ou string contendo a chave digital limpa
     */
    protected function pCleanCerts($certFile)
    {
        try {
            //inicializa variavel
            $data = '';
            //carregar a chave publica do arquivo pem
            if (!$pubKey = file_get_contents($certFile)) {
                $msg = "Arquivo não encontrado - $certFile .";
                throw new nfephpException($msg);
            }
            //carrega o certificado em um array usando o LF como referencia
            $arCert = explode("\n", $pubKey);
            foreach ($arCert as $curData) {
                //remove a tag de inicio e fim do certificado
                if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0
                    && strncmp($curData, '-----END CERTIFICATE', 20) != 0) {
                    //carrega o resultado numa string
                    $data .= trim($curData);
                }
            }
        } catch (nfephpException $e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
        return $data;
    }//fim cleanCerts


    /**
     * pSendSOAP
     * Função alternativa para estabelecer comunicaçao com servidor SOAP 1.2 da SEFAZ,
     * usando as chaves publica e privada parametrizadas na contrução da classe.
     * Conforme Manual de Integração Versão 4.0.1 Utilizando cURL e não o SOAP nativo
     *
     * @name pSendSOAP
     * @param string $urlsefaz
     * @param string $namespace
     * @param string $cabecalho
     * @param string $dados
     * @param string $metodo
     * @param numeric $ambiente
     * @param string $siglaUF sem uso mantido apenas para compatibilidade com sendSOAP
     * @return mixed false se houve falha ou o retorno em xml do SEFAZ
     */
    protected function pSendSOAP($urlsefaz, $namespace, $dados, $metodo)
    {
//        try {
            if ($urlsefaz == '') {
                $msg = "URL do webservice não disponível no arquivo xml das URLs da SEFAZ.";
//                throw new nfephpException($msg);
            }

            $data = '';
            $data .= '<?xml version="1.0" encoding="utf-8"?>';
            $data .= '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:e="http://www.betha.com.br/e-nota-contribuinte-test-ws">';
            $data .= '<soapenv:Header/>';
            $data .= '<e:'.$metodo.'>';
            $data .= '<nfseCabecMsg>';
            $data .= '<![CDATA[';
            $data .= '<cabecalho xmlns="http://www.betha.com.br/e-nota-contribuinte-test-ws" versao="2.02"><versaoDados>2.02</versaoDados></cabecalho>';
            $data .= ']]>';
            $data .= '</nfseCabecMsg>';
            $data .= '<nfseDadosMsg>';
            $data .= '<![CDATA[';
            $data .= $dados;
            $data .= ']]>';
            $data .= '</nfseDadosMsg>';
            $data .= '</e:'.$metodo.'>';
            $data .= '</soapenv:Body>';
            $data .= '</soapenv:Envelope>';

            
print_r($data);

            $tamanho = strlen($data);
            $parametros = array(
                'Content-Type: application/soap+xml;charset=utf-8;',
                'SOAPAction: "http://www.betha.com.br/e-nota-contribuinte-test-ws/'.$metodo.'"',
                "Content-length: $tamanho");
            
            $oCurl = curl_init();
            curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->soapTimeout);
            curl_setopt($oCurl, CURLOPT_URL, "http://www.betha.com.br/e-nota-contribuinte-test-ws");
//            curl_setopt($oCurl, CURLOPT_PORT, 80);
            curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
//            curl_setopt($oCurl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            curl_setopt($oCurl, CURLOPT_HEADER, 1); //retorna o cabeçalho de resposta
			curl_setopt($oCurl, CURLOPT_SSLVERSION, 'CURL_SSLVERSION_TLSv1_2'); // 6 
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2); // verifica o host evita MITM
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($oCurl, CURLOPT_SSLCERT, $this->certKEY);
            curl_setopt($oCurl, CURLOPT_SSLKEY, $this->priKEY);
            curl_setopt($oCurl, CURLOPT_POST, 1);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parametros);
            $xml = curl_exec($oCurl);
            $info = curl_getinfo($oCurl); //informações da conexão
            $lenN = strlen($xml);
            $posX = stripos($xml, "<");
            if ($posX !== false) {
                $xml = substr($xml, $posX, $lenN-$posX);
            } else {
                $xml = '';
            }
            $this->soapDebug = $data."\n\n".$xml;
            if ($xml === false || $posX === false) {
                //não houve retorno
                $msg = curl_error($oCurl);
                if (isset($info['http_code'])) {
                    $msg .= $info['http_code'];
                }
                throw new nfephpException($msg);
            } else {
                //houve retorno mas ainda pode ser uma mensagem de erro do webservice
                if ($info['http_code'] > 300) {
                    $msg = $info['http_code'];
                    $this->pSetError($msg);
                }
            }
            curl_close($oCurl);
            return $xml;
/*            
        } catch ($e) {
            $this->pSetError($e->getMessage());
            if ($this->exceptions) {
                throw $e;
            }
            return false;
        }
*/
    } //fim sendSOAP


    /**
     * convertTime
     * Converte o campo data/hora retornado pelo webservice em um timestamp unix
     *
     * @name convertTime
     * @param  string $DataHora Exemplo: "2014-03-28T14:39:54-03:00"
     * @return float
     */
    protected function pConvertTime($dataHora = '')
    {
        $timestampDH = 0;
        if ($dataHora) {
            $aDH = explode('T', $dataHora);
            $adDH = explode('-', $aDH[0]);
            $atDH = explode(':', substr($aDH[1], 0, 8));//substring para recuperar apenas a hora, sem o fuso horário
            $timestampDH = mktime($atDH[0], $atDH[1], $atDH[2], $adDH[1], $adDH[2], $adDH[0]);
        }
        return $timestampDH;
    } //fim convertTime


    /**
     * cleanString
     * Remove todos dos caracteres espceiais do texto e os acentos
     *
     * @name cleanString
     * @return  string Texto sem caractere especiais
     */
    private function pCleanString($texto)
    {
        $aFind = array('&','á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü',
            'ç','Á','À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ü','Ç');
        $aSubs = array('e','a','a','a','a','e','e','i','o','o','o','u','u',
            'c','A','A','A','A','E','E','I','O','O','O','U','U','C');
        $novoTexto = str_replace($aFind, $aSubs, $texto);
        $novoTexto = preg_replace("/[^a-zA-Z0-9 @,-.;:\/]/", "", $novoTexto);
        return $novoTexto;
    }//fim cleanString

    /**
     * pSetError
     * Adiciona descrição do erro ao contenedor dos erros
     *
     * @name pSetError
     * @param   string $msg Descrição do erro
     * @return  none
     */
    private function pSetError($msg)
    {
        $this->errMsg .= "$msg\n";
        $this->errStatus = true;
    }
    

    /**
     * pClearXml
     * Remove \r \n \s \t 
     * @param string $xml
     * @param boolean $remEnc remover encoding
     * @return string
     */
    private function pClearXml($xml = '', $remEnc = false)
    {
        $retXml = $xml;
        if ($remEnc) {
            $retXml = str_replace('<?xml version="1.0"?>', '', $retXml);
            $retXml = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $retXml);
            $retXml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $retXml);
        }
        $retXml = str_replace("xmlns:default=\"http://www.w3.org/2000/09/xmldsig#\"", '', $retXml);
        $retXml = str_replace('default:', '', $retXml);
        $retXml = str_replace(':default', '', $retXml);
        $retXml = str_replace("\n", '', $retXml);
        $retXml = str_replace("\r", '', $retXml);
        $retXml = str_replace("\s", '', $retXml);
        $retXml = str_replace("\t", '', $retXml);
        return $retXml;
    }
    
}

?>