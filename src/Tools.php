<?php

namespace NFHub\Common;

use Exception;

/**
 * Classe Tools
 *
 * Classe responsável pela comunicação com a API NFHub
 *
 * @category  NFHub
 * @package   NFHub\Common\Tools
 * @author    Jefferson Moreira <jeematheus at gmail dot com>
 * @copyright 2020 NFSERVICE
 * @license   https://opensource.org/licenses/MIT MIT
 */
class Tools
{
    /**
     * URL base para comunicação com a API
     *
     * @var string
     */
    public static $API_URL = [
        1 => 'https://api.fuganholi-fiscal.com.br/api',
        2 => 'http://api.nfhub.com/api',
        3 => 'https://api.sandbox.fuganholi-fiscal.com.br/api',
        4 => 'https://api.dusk.fuganholi-fiscal.com.br/api'
    ];

    /**
     * Variável responsável por armazenar os dados a serem utilizados para comunicação com a API
     * Dados como token, ambiente(produção ou homologação) e debug(true|false)
     *
     * @var array
     */
    private $config = [
        'token' => '',
        'environment' => '',
        'debug' => false,
        'upload' => false,
        'decode' => true
    ];

    /**
     * Define se a classe realizará um upload
     *
     * @param bool $isUpload Boleano para definir se é upload ou não
     *
     * @access public
     * @return void
     */
    public function setUpload(bool $isUpload) :void
    {
        $this->config['upload'] = $isUpload;
    }

    /**
     * Define se a classe realizará o decode do retorno
     *
     * @param bool $decode Boleano para definir se fa decode ou não
     *
     * @access public
     * @return void
     */
    public function setDecode(bool $decode) :void
    {
        $this->config['decode'] = $decode;
    }

    /**
     * Função responsável por definir se está em modo de debug ou não a comunicação com a API
     * Utilizado para pegar informações da requisição
     *
     * @param bool $isDebug Boleano para definir se é produção ou não
     *
     * @access public
     * @return void
     */
    public function setDebug(bool $isDebug) :void
    {
        $this->config['debug'] = $isDebug;
    }

    /**
     * Função responsável por definir o token a ser utilizado para comunicação com a API
     *
     * @param string $token Token para autenticação na API
     *
     * @access public
     * @return void
     */
    public function setToken(string $token) :void
    {
        $this->config['token'] = $token;
    }

    /**
     * Função responsável por setar o ambiente utilizado na API
     *
     * @param int $environment Ambiente API (1 - Produção | 2 - Local | 3 - Sandbox | 4 - Dusk)
     *
     * @access public
     * @return void
     */
    public function setEnvironment(int $environment) :void
    {
        if (in_array($environment, [1, 2, 3, 4])) {
            $this->config['environment'] = $environment;
        }
    }

    /**
     * Recupera se é upload ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getUpload() : bool
    {
        return $this->config['upload'];
    }

    /**
     * Recupera se faz decode ou não
     *
     *
     * @access public
     * @return bool
     */
    public function getDecode() : bool
    {
        return $this->config['decode'];
    }

    /**
     * Recupera o ambiente setado para comunicação com a API
     *
     * @access public
     * @return int
     */
    public function getEnvironment() :int
    {
        return $this->config['environment'];
    }

    /**
     * Retorna os cabeçalhos padrão para comunicação com a API
     *
     * @access private
     * @return array
     */
    private function getDefaultHeaders() :array
    {
        $headers = [
            'Authorization: Bearer '.$this->config['token'],
            'Accept: application/json',
        ];

        if (!$this->config['upload']) {
            $headers[] = 'Content-Type: application/json';
        } else {
            $headers[] = 'Content-Type: multipart/form-data';
        }
        return $headers;
    }

    /**
     * Consulta uma empresa no NFHub
     */
    public function consultaEmpresa(string $cnpj = '', array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'cnpj_company';
            }, ARRAY_FILTER_USE_BOTH);

            if (!empty($cnpj)) {
                $params[] = [
                    'name' => 'cnpj_company',
                    'value' => $cnpj
                ];
            }

            $dados = $this->get('/companies', $params);

            if (!isset($dados['body']->message)) {
                return $dados;
            }

            throw new Exception($dados['body']->message, 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra uma empresa nova no NFHub
     */
    public function cadastraEmpresa(array $dados, array $params = []): array
    {
        try {
            $dados = $this->post('companies', $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Atualiza uma empresa nova no NFHub
     */
    public function atualizaEmpresa(int $id, array $dados, array $params = []): array
    {
        try {
            $dados = $this->put('companies/'.$id, $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Deleta uma empresa
     */
    public function deletaEmpresa(int $id, array $params = [])
    {
        if (!isset($id) || empty($id)) {
            throw new Exception("O ID NFHub da empresa é obrigatório para exclusão", 1);
        }

        try {
            $dados = $this->delete('companies/'.$id, $params);

            if ($dados['httpCode'] == 200) {
                return 'Empresa deletada com sucesso';
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar os clientes de uma empresa
     *
     * @param int $company_id ID da empresa que será listado os clientes
     * @param array $params parametros adicionais aceitos pela rota
     *
     * @access public
     * @return array
     */
    public function listaClientes(int $company_id, array $params = []):array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'company_id',
                'value' => $company_id
            ];

            $dados = $this->get('customers', $params);

            if ($dados['httpCode'] === 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por cadastrar um cliente no NFHub
     *
     * @param int $company_id ID da empresa no NFHub
     * @param array $dados Dados do cliente a ser cadastrado
     * @param array $params Parametros adicionais aceitos pela requisição
     *
     * @access public
     * @return array
     */
    public function cadastraCliente(int $company_id, array $dados, array $params = []):array
    {
        try {
            $dados['company_id'] = $company_id;
            $dados = $this->post('customers', $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (\Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por buscar um cliente no NFHub
     *
     * @param int $customer_id ID do cliente no NFHub
     * @param int $company_id ID da empresa no NFHub
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaCliente(int $customer_id, int $company_id, array $params = []):array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'company_id',
                'value' => $company_id
            ];

            $dados = $this->get("customers/$customer_id", $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (\Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por atualizar um cliente no NFHub
     *
     * @param int $customer_id ID do cliente no NFHub
     * @param int $company_id ID da empresa no NFHub
     * @param array $dados Dados do cliente a ser atualizado
     * @param array $params Parametros adicionais aceitos pela requisição
     *
     * @access public
     * @return array
     */
    public function atualizaCliente(int $customer_id, int $company_id, array $dados, array $params = []):array
    {
        try {
            $dados['company_id'] = $company_id;
            $dados = $this->put("customers/$customer_id", $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (\Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Cadastra uma cobrança/conta a Pagar/Receber
     *
     * @param int $company_id ID da empresa ao qual pertence a cobrança
     * @param array $dados Dados da cobrança
     */
    public function cadastraCobranca(int $company_id, array $dados, array $params = []): array
    {
        try {
            $dados['company_id'] = $company_id;
            $dados = $this->post('installments', $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Busca as configurações de permissão do contador às notas fiscais
     *
     * @param int $company_id ID da empresa ao qual pertence a cobrança
     *
     * @access public
     * @return array
     */
    public function buscaConfigContador(int $company_id, string $cpfcnpj_contador_id, array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'cpfcnpj',
                'value' => $cpfcnpj_contador_id
            ];

            $params[] = [
                'name' => 'company_id',
                'value' => $company_id
            ];

            $dados = $this->get('contador/permissions', $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Atualiza as configurações de permissão contador
     *
     * @param int $company_id ID da empresa ao qual pertence a cobrança
     *
     * @access public
     * @return array
     */
    public function atualizaConfigContador(int $company_id, array $dados, array $params = []): array
    {
        try {
            $dados['company_id'] = $company_id;
            $dados = $this->post('contador/permissions', $dados, $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Busca as notas fiscais autorizadas para o contador
     *
     * @param string $cpfcnpj CNPJ do contador
     * @param string $company_cpfcnpj CNPJ da empresa ao qual pertence a cobrança
     *
     * @access public
     * @return array
     */
    public function buscaNotasContador(string $cpfcnpj, string $company_cpfcnpj, array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return !in_array($item['name'], ['company_cnpj', 'cpfcnpj']);
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'cpfcnpj',
                'value' => $cpfcnpj
            ];

            $params[] = [
                'name' => 'company_cpfcnpj',
                'value' => $company_cpfcnpj
            ];

            $dados = $this->get('contador/invoices', $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw $error;
        }
    }

    /**
     * Função responsável por buscar os bancos no NFHub
     *
     * @param array $params Parametros adicionais para a requisição
     *
     * @access public
     * @return array
     */
    public function buscaBancos(array $params = []): array
    {
        try {
            $dados = $this->get('banks', $params);

            if (!isset($dados['body']->message)) {
                return $dados;
            }

            throw new Exception($dados['body']->message, 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

     /**
     * Função responsável por listar os bancos cadastrados de cada empresa no Easy
     *
     * @param array $params Parametros adicionais para a requisição
     * @param int $company_id ID da empresa
     * @param string $cpfcnpj CNPJ da empresa
     *
     * @access public
     * @return array
     */
    public function listaBancos(int $company_id, string $cpfcnpj, string $filter, array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'cpfcnpj',
                'value' => $cpfcnpj
            ];

            $params[] = [
                'name' => 'company_id',
                'value' => $company_id
            ];

            $params[] = [
                'name' => 'filter',
                'value' => $filter
            ];

            $dados = $this->get('list-banks', $params);

            if (!isset($dados['body']->message)) {
                return $dados;
            }

            throw new Exception($dados['body']->message, 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Função responsável por listar as categorias de cada empresa no Easy
     *
     * @param array $params Parametros adicionais para a requisição
     * @param int $company_id ID da empresa
     * @param string $cpfcnpj CNPJ da empresa
     * @param string $filter Filtro
     * @param int $id ID da categoria
     *
     * @access public
     * @return array
     */
    public function listaCategorias(int $company_id, string $cpfcnpj, string $filter, int $id = null, array $params = []): array

    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'cpfcnpj',
                'value' => $cpfcnpj
            ];

            $params[] = [
                'name' => 'company_id',
                'value' => $company_id
            ];

            $params[] = [
                'name' => 'filter',
                'value' => $filter
            ];

            $params[] = [
                'name' => 'id',
                'value' => $id
            ];

            $dados = $this->get('categories', $params);

            if (!isset($dados['body']->message)) {
                return $dados;
            }

            throw new Exception($dados['body']->message, 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    public function buscaCertificadoPem(int $certificate_id, array $params = []): array
    {
        try {
            $params = array_filter($params, function($item) {
                return $item['name'] !== 'company_id';
            }, ARRAY_FILTER_USE_BOTH);

            $params[] = [
                'name' => 'certificate_id',
                'value' => $certificate_id
            ];

            $dados = $this->get("certificates/{$certificate_id}/pem", $params);

            if ($dados['httpCode'] == 200) {
                return $dados;
            }

            if (isset($dados['body']->errors)) {
                throw new \Exception(implode("\r\n", $dados['body']->errors), 1);
            }

            throw new Exception(json_encode($dados), 1);
        } catch (Exception $error) {
            throw new Exception($error, 1);
        }
    }

    /**
     * Execute a GET Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function get(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a POST Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function post(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => !$this->config['upload'] ? json_encode($body) : $this->convertToFormData($body),
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders()
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a PUT Request
     *
     * @param string $path
     * @param string $body
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function put(string $path, array $body = [], array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($body)
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a DELETE Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function delete(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_HTTPHEADER => $this->getDefaultHeaders(),
            CURLOPT_CUSTOMREQUEST => "DELETE"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Execute a OPTION Request
     *
     * @param string $path
     * @param array $params
     * @param array $headers Cabeçalhos adicionais para requisição
     *
     * @access protected
     * @return array
     */
    protected function options(string $path, array $params = [], array $headers = []) :array
    {
        $opts = [
            CURLOPT_CUSTOMREQUEST => "OPTIONS"
        ];

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $exec = $this->execute($path, $opts, $params);

        return $exec;
    }

    /**
     * Função responsável por realizar a requisição e devolver os dados
     *
     * @param string $path Rota a ser acessada
     * @param array $opts Opções do CURL
     * @param array $params Parametros query a serem passados para requisição
     *
     * @access protected
     * @return array
     */
    protected function execute(string $path, array $opts = [], array $params = []) :array
    {
        if (!preg_match("/^\//", $path)) {
            $path = '/' . $path;
        }

        $url = self::$API_URL[$this->config['environment']].$path;

        $curlC = curl_init();

        if (!empty($opts)) {
            curl_setopt_array($curlC, $opts);
        }

        if (!empty($params)) {
            $paramsJoined = [];

            foreach ($params as $param) {
                if (isset($param['name']) && !empty($param['name']) && isset($param['value']) && !empty($param['value'])) {
                    $paramsJoined[] = urlencode($param['name'])."=".urlencode($param['value']);
                }
            }

            if (!empty($paramsJoined)) {
                $params = '?'.implode('&', $paramsJoined);
                $url = $url.$params;
            }
        }

        curl_setopt($curlC, CURLOPT_URL, $url);
        curl_setopt($curlC, CURLOPT_RETURNTRANSFER, true);
        if (!empty($dados)) {
            curl_setopt($curlC, CURLOPT_POSTFIELDS, json_encode($dados));
        }
        $retorno = curl_exec($curlC);
        $info = curl_getinfo($curlC);
        $return["body"] = ($this->config['decode'] || !$this->config['decode'] && $info['http_code'] != '200') ? json_decode($retorno) : $retorno;
        $return["httpCode"] = curl_getinfo($curlC, CURLINFO_HTTP_CODE);
        if ($this->config['debug']) {
            $return['info'] = curl_getinfo($curlC);
        }
        curl_close($curlC);

        return $return;
    }

    /**
     * Função responsável por montar o corpo de uma requisição no formato aceito pelo FormData
     */
    private function convertToFormData($data)
    {
        $dados = [];

        $recursive = false;
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $dados[$key] = $value;
            } else {
                foreach ($value as $subkey => $subvalue) {
                    $dados[$key.'['.$subkey.']'] = $subvalue;

                    if (is_array($subvalue)) {
                        $recursive = true;
                    }
                }
            }
        }

        if ($recursive) {
            return $this->convertToFormData($dados);
        }

        return $dados;
    }
}
