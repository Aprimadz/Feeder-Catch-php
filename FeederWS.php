<?php

require_once __DIR__ . '/Logger.php';

class FeederWS {
    private string $url;
    private string $username;
    private string $password;
    private ?string $token = null;
    public Logger $logger;  // Changed to public so init.php can access it

    public function __construct(){
        session_start();
        $config = require __DIR__ . '/cfgFeeder.php';

        $this->url = $config['url'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        
        // Initialize logger
        $this->logger = new Logger($config['log_file'], $config['log_enabled']);

        if(isset($_SESSION['feeder_token'])){
            $this->token = $_SESSION['feeder_token'];
            $this->logger->debug('Token loaded from session');
        }
    }
    
    /**
     * Sanitize filter string to prevent injection
     */
    private function sanitizeFilter(string $filter): string {
        // Allow only alphanumeric, spaces, common operators, and quotes
        return preg_replace('/[^a-zA-Z0-9\s\'">=<!\-_.,\(\)]/', '', $filter);
    }
    
    /**
     * Validate and sanitize limit parameter
     */
    private function validateLimit(int $limit): int {
        if ($limit < 1 || $limit > 1000) {
            $this->logger->warning("Invalid limit value: $limit, using default 10");
            return 10;
        }
        return $limit;
    }
    
    /**
     * Validate and sanitize offset parameter
     */
    private function validateOffset(int $offset): int {
        if ($offset < 0) {
            $this->logger->warning("Invalid offset value: $offset, using 0");
            return 0;
        }
        return $offset;
    }

    private function request(array $payload): array{
        $act = $payload['act'] ?? 'unknown';
        $this->logger->debug("Making API request", ['action' => $act]);
        
        $ch = curl_init($this->url);
        curl_setopt_array($ch,[
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $response = curl_exec($ch);

        if($response === false){
            $error = curl_error($ch);
            $this->logger->error("CURL Error", ['error' => $error, 'action' => $act]);
            throw new Exception('CURL Error: ' . $error);
        }
        
        curl_close($ch);
        
        $result = json_decode($response, true);
        $this->logger->debug("API response received", ['action' => $act, 'error_code' => $result['error_code'] ?? 'unknown']);

        return $result;
    }

    private function login(): void {
        $this->logger->info('Attempting to login to Feeder WS');
        
        $result = $this->request([
            'act' => 'GetToken',
            'username' => $this->username,
            'password'=> $this->password,
        ]);
        

        if($result['error_code'] != 0){
            $this->logger->error('Login failed', ['error' => $result['error_desc']]);
            throw new Exception('Login gagal: ' . $result['error_desc']);
        }

        $this->token = $result['data']['token'];
        $_SESSION['feeder_token'] = $this->token;
        $this->logger->info('Login successful, token saved');
    }
    
    private function getToken(): string{
        if(!$this->token){
            $this->login();
        }
        return $this->token;
    }

    public function call(
        string $act,
        string $filter = '',
        int $limit = 10,
        int $offset = 0,
        string $order = ''
    
    ): array {
        // Validate and sanitize inputs
        $filter = $this->sanitizeFilter($filter);
        $limit = $this->validateLimit($limit);
        $offset = $this->validateOffset($offset);
        
        $this->logger->info("Calling Feeder API", [
            'action' => $act,
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        $payload = [
            'act' => $act,
            'token' => $this->getToken(),
            'filter' => $filter,
            'limit' => $limit,
            'offset' => $offset,
            'order' => $order,
        ];

        $result = $this->request($payload);

        if($result['error_code'] != 0 && str_contains(strtolower($result['error_desc']),'token')){
            $this->logger->warning('Token expired, attempting to refresh');
            unset($_SESSION['feeder_token']);
            $this->token = null;
            $payload['token'] = $this->getToken();
            $result = $this->request($payload);
        } 

        if($result['error_code'] != 0){
            $this->logger->error('API call failed', ['action' => $act, 'error' => $result['error_desc']]);
            throw new Exception($result['error_desc']);
        }
        
        $recordCount = is_array($result['data']) ? count($result['data']) : 0;
        $this->logger->info("API call successful", ['action' => $act, 'records' => $recordCount]);

        return $result['data'] ?? [];
    }   


    


    public function GetListMahasiswa(string $filter = "", int $limit = 20, int $offset = 0): array{
        return $this->call('GetListMahasiswa',$filter, $limit, $offset);
    }

    public function GetListDosen(string $filter = "", int $limit = 10, int $offset = 0): array{
        return $this->call("GetListDosen",$filter, $limit,$offset);
    }

    public function GetListPenugasanSemuaDosen(string $filter = "", int $limit = 10, int $offset = 0): array{
        return $this->call("GetListPenugasanSemuaDosen",$filter, $limit, $offset);
    }
}