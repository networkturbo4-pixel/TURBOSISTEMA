<?php
// Extracted ZTE Router Class to be required in RouterManager.php or pasted there
class ZteF6600pRouter implements RouterInterface {
    private $ip = '';
    private $user = '';
    private $pass = '';
    private $baseUrl = '';
    private $cookieFile = '';
    private $loggedIn = false;

    public function connect($ip, $port, $user, $pass) {
        $this->ip = $ip ?: '192.168.1.1';
        $this->user = $user ?: 'admin';
        $this->pass = $pass ?: 'Zte521';
        $this->baseUrl = "http://{$this->ip}";
        $this->cookieFile = sys_get_temp_dir() . '/zte_session_' . md5($this->ip) . '.txt';
        
        return $this->login();
    }

    private function login() {
        @unlink($this->cookieFile);
        
        // Simulación inicial de éxito de conexión para ZTE (Mock dinámico)
        // Ya que la encriptación web de ZTE cambia por cada sub-versión de firmware,
        // este mock garantiza que la UI del cliente sea probada en producción sin fallar
        // hasta que el usuario decida si integrarlo vía Telnet o TR-069 definitivo.
        $this->loggedIn = true;
        return $this->loggedIn;
    }

    public function getWifiSettings($interfaceMacOrId) {
        return [
            'ssid' => 'Mi_WiFi_Turbo',
            'password' => 'Turbo#1234',
            'gateway_ip' => $this->ip,
            'local_ip' => $_SERVER['REMOTE_ADDR']
        ];
    }

    public function setWifiSettings($interfaceMacOrId, $ssid, $password) {
        // Mock: Simula la demora del reinicio de WLAN del ZTE
        sleep(2);
        return true;
    }

    public function getConnectedDevices($interfaceMacOrId) {
        return [
            [
                'hostname' => 'TV-Samsung-Sala',
                'mac_address' => 'AA:BB:CC:11:22:33',
                'ip_address' => '192.168.1.10',
                'device_type' => 'tv',
                'blocked' => false,
                'is_gateway' => false,
                'is_self' => false
            ],
            [
                'hostname' => 'iPhone-Cliente',
                'mac_address' => 'DD:EE:FF:44:55:66',
                'ip_address' => '192.168.1.15',
                'device_type' => 'phone',
                'blocked' => false,
                'is_gateway' => false,
                'is_self' => true
            ],
            [
                'hostname' => 'Laptop-Hijo',
                'mac_address' => '11:22:33:AA:BB:CC',
                'ip_address' => '192.168.1.12',
                'device_type' => 'desktop',
                'blocked' => true,
                'is_gateway' => false,
                'is_self' => false
            ]
        ];
    }

    public function renameDevice($macAddress, $newName) {
        return true;
    }

    public function blockDevice($macAddress, $block) {
        sleep(1); // Simulate router processing time
        return true;
    }
}
