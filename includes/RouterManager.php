<?php

interface RouterInterface {
    public function connect($ip, $port, $user, $pass);
    public function getWifiSettings($interfaceMacOrId);
    public function setWifiSettings($interfaceMacOrId, $ssid, $password);
    public function getConnectedDevices($interfaceMacOrId);
    public function renameDevice($macAddress, $newName);
    public function blockDevice($macAddress, $block);
}

class RouterManager {
    public static function getRouter($osType) {
        switch (strtolower($osType)) {
            case 'bdcom':
                return new BdcomRouter();
            case 'mikrotik':
                return new MikrotikRouter();
            case 'zte':
            case 'zte_f6600p':
                return new ZteF6600pRouter();
            case 'local':
            case 'mock':
            default:
                return new LocalNetworkRouter();
        }
    }
}

/**
 * =============================================================
 * BDCOM GP1704/GP1705 Router - Integración directa via Web UI
 * =============================================================
 * Controla el ONU BDCOM directamente a través de su interfaz web
 * Servidor: Boa/0.93.15 (Realtek)
 * Auth: Form POST con checksum postSecurityFlag
 */
class BdcomRouter implements RouterInterface {
    private $ip = '';
    private $user = '';
    private $pass = '';
    private $baseUrl = '';
    private $cookieFile = '';
    private $loggedIn = false;

    public function connect($ip, $port, $user, $pass) {
        $this->ip = $ip ?: '192.168.123.1';
        $this->user = $user ?: 'user';
        $this->pass = $pass ?: '123456';
        $this->baseUrl = "http://{$this->ip}";
        $this->cookieFile = sys_get_temp_dir() . '/bdcom_session_' . md5($this->ip) . '.txt';
        
        return $this->login();
    }

    /**
     * Calcula el checksum postSecurityFlag requerido por el Boa server
     * Replica exacta del algoritmo en common.js
     */
    private function calcChecksum($fields) {
        $inputVal = '';
        foreach ($fields as $name => $value) {
            if ($name === 'postSecurityFlag' || $name === 'csrftoken') continue;
            $ev = rawurlencode($value);
            $ev = str_replace(['!', "'", '(', ')', '~', '%20'], ['%21', '%27', '%28', '%29', '%7E', '+'], $ev);
            $inputVal .= $name . '=' . $ev . '&';
        }
        $csum = 0;
        $len = strlen($inputVal);
        $i = 0;
        while ($i < $len) {
            if (($i + 4) > $len) {
                if ($i < $len) $csum += (ord($inputVal[$i]) << 24);
                if (($i+1) < $len) $csum += (ord($inputVal[$i+1]) << 16);
                if (($i+2) < $len) $csum += (ord($inputVal[$i+2]) << 8);
                break;
            } else {
                $csum += (ord($inputVal[$i]) << 24) + (ord($inputVal[$i+1]) << 16) + (ord($inputVal[$i+2]) << 8) + ord($inputVal[$i+3]);
                $i += 4;
            }
        }
        $csum = $csum & 0xFFFFFFFF;
        $csum = ($csum & 0xffff) + ($csum >> 16);
        $csum = $csum & 0xffff;
        $csum = (~$csum) & 0xffff;
        return (string)$csum;
    }

    /**
     * Login al router usando form POST con checksum
     */
    private function login() {
        @unlink($this->cookieFile);

        // GET login page para cookie de sesión
        $this->httpGet('/admin/login.asp');

        // Construir campos del formulario
        $fields = [
            'challenge' => '',
            'username' => $this->user,
            'password' => $this->pass,
            'save' => 'Login',
            'submit-url' => '/admin/login.asp',
        ];
        $checksum = $this->calcChecksum($fields);
        $fields['postSecurityFlag'] = $checksum;

        $resp = $this->httpPost('/boaform/admin/formLogin', $fields, false);
        
        // Login exitoso si hay redirect (301/302)
        $this->loggedIn = ($resp['code'] == 301 || $resp['code'] == 302);
        
        // Si falla, verificar si ya hay sesión activa probando una página
        if (!$this->loggedIn) {
            $test = $this->httpGet('/admin/status.asp');
            if ($test['code'] == 200 && stripos($test['body'], 'formLogin') === false && strlen($test['body']) > 200) {
                $this->loggedIn = true;
            }
        }
        
        return $this->loggedIn;
    }

    private function ensureLoggedIn() {
        if (!$this->loggedIn) {
            $this->login();
        }
    }

    /**
     * HTTP GET con cookies de sesión
     */
    private function httpGet($path) {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['body' => $body ?: '', 'code' => $code];
    }

    /**
     * HTTP POST con cookies de sesión y checksum automático
     */
    private function httpPost($path, $fields, $autoChecksum = true) {
        if ($autoChecksum && !isset($fields['postSecurityFlag'])) {
            $fields['postSecurityFlag'] = $this->calcChecksum($fields);
        }
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            CURLOPT_REFERER => $this->baseUrl . '/admin/login.asp',
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        return [
            'headers' => substr($response, 0, $headerSize),
            'body' => substr($response, $headerSize),
            'code' => $code,
        ];
    }

    // =====================================================
    //  WiFi Settings
    // =====================================================

    public function getWifiSettings($interfaceMacOrId) {
        $this->ensureLoggedIn();
        $result = [
            'ssid' => '',
            'ssid_5g' => '',
            'password' => '••••••••',
            'gateway_ip' => $this->ip,
            'local_ip' => '',
            'subnet' => '',
            'connection_type' => 'fiber',
            'device_name' => '',
            'mac_address' => '',
            'uptime' => '',
            'firmware' => '',
            'wan_ip' => '',
        ];

        // --- Obtener SSID de la página WiFi Basic ---
        $wlPage = $this->httpGet('/admin/wlbasic.asp');
        if ($wlPage['code'] == 200) {
            // SSID 2.4GHz - buscar: var regDomain_n, regDomain_s, ... o ssid = "..."
            if (preg_match('/var\s+ssid_5g\s*=\s*"([^"]+)"/i', $wlPage['body'], $m)) {
                $result['ssid_5g'] = $m[1];
            }
            // El SSID principal viene como el valor del input en la página
            // Buscar en el HTML del form
            if (preg_match('/name="ssid"[^>]*value="([^"]+)"/i', $wlPage['body'], $m)) {
                $result['ssid'] = $m[1];
            }
            // Fallback: buscar en variables JS
            if (empty($result['ssid'])) {
                // Buscar asignaciones de variables JS con ssid
                if (preg_match('/document\.wlanSetup\.ssid\.value\s*=\s*"([^"]+)"/i', $wlPage['body'], $m)) {
                    $result['ssid'] = $m[1];
                }
            }
            // Otro fallback: buscar la variable directa
            if (empty($result['ssid'])) {
                if (preg_match('/var\s+SSID\s*=\s*"([^"]+)"/i', $wlPage['body'], $m)) {
                    $result['ssid'] = $m[1];
                }
            }
            // Buscar campos de input con el SSID precargado
            if (empty($result['ssid'])) {
                if (preg_match('/<input[^>]*name=["\']?ssid["\']?[^>]*value=["\']([^"\']+)/i', $wlPage['body'], $m)) {
                    $result['ssid'] = $m[1];
                }
            }
        }

        // --- Obtener info del Status ---
        $statusPage = $this->httpGet('/admin/status.asp');
        if ($statusPage['code'] == 200) {
            $html = $statusPage['body'];
            // Device Name
            if (preg_match('/Device\s*Name.*?<td[^>]*>([^<]+)/is', $html, $m)) {
                $result['device_name'] = trim($m[1]);
            }
            // MAC Address
            if (preg_match('/MAC\s*Address.*?<td[^>]*>([A-F0-9]+)/is', $html, $m)) {
                $result['mac_address'] = trim($m[1]);
            }
            // Uptime
            if (preg_match('/Uptime.*?<td[^>]*>([^<]+)/is', $html, $m)) {
                $result['uptime'] = trim($m[1]);
            }
            // Firmware
            if (preg_match('/Firmware.*?<td[^>]*>([^<]+)/is', $html, $m)) {
                $result['firmware'] = trim($m[1]);
            }
            // WAN IP
            if (preg_match('/IP\s*Address.*?<td[^>]*>(192\.168\.\d+\.\d+)/is', $html, $m)) {
                // LAN IP first
            }
            // Look for WAN IP in WAN Configuration section
            if (preg_match('/WAN Configuration.*?IP\s*Address.*?<td[^>]*>(\d+\.\d+\.\d+\.\d+)/is', $html, $m)) {
                $result['wan_ip'] = trim($m[1]);
            }
        }

        // --- Obtener info LAN ---
        $lanPage = $this->httpGet('/admin/tcpiplan.asp');
        if ($lanPage['code'] == 200) {
            if (preg_match('/name=["\']?ip["\']?[^>]*value=["\']?(\d+\.\d+\.\d+\.\d+)/i', $lanPage['body'], $m)) {
                $result['local_ip'] = $m[1];
            }
            if (preg_match('/name=["\']?mask["\']?[^>]*value=["\']?(\d+\.\d+\.\d+\.\d+)/i', $lanPage['body'], $m)) {
                $result['subnet'] = $m[1];
            }
        }

        return $result;
    }

    public function setWifiSettings($interfaceMacOrId, $ssid, $password) {
        $this->ensureLoggedIn();
        // El cambio de SSID se hace via POST a /boaform/admin/formWlanSetup
        // Necesitamos enviar todos los campos del formulario wlbasic.asp
        // Por ahora retornamos false - se implementará cuando tengamos todos los campos
        return false;
    }

    // =====================================================
    //  Dispositivos conectados (usando ARP del servidor)
    // =====================================================

    public function getConnectedDevices($interfaceMacOrId) {
        // Usar detección local desde el servidor ya que las páginas 
        // de station list y DHCP del router no son accesibles con usuario "user"
        $localRouter = new LocalNetworkRouter();
        $localRouter->connect('', '', '', '');
        return $localRouter->getConnectedDevices('');
    }

    // =====================================================
    //  MAC Filter / Bloqueo de dispositivos
    // =====================================================

    public function blockDevice($macAddress, $block) {
        $this->ensureLoggedIn();
        
        if ($block) {
            // Agregar MAC al filtro de firewall
            $fields = [
                'mac' => $macAddress,
                'addFilterMac' => 'Add',
                'submit-url' => '/admin/fw-macfilter.asp',
            ];
            $resp = $this->httpPost('/boaform/admin/formFilter', $fields);
            return ($resp['code'] == 301 || $resp['code'] == 302 || $resp['code'] == 200);
        } else {
            // Eliminar MAC del filtro
            $fields = [
                'mac' => $macAddress,
                'deleteSelFilterMac' => 'Delete Selected',
                'submit-url' => '/admin/fw-macfilter.asp',
            ];
            $resp = $this->httpPost('/boaform/admin/formFilter', $fields);
            return ($resp['code'] == 301 || $resp['code'] == 302 || $resp['code'] == 200);
        }
    }

    public function renameDevice($macAddress, $newName) {
        // Los nombres se guardan localmente en la BD del sistema
        return true;
    }
}

/**
 * =============================================================
 * Detección de red local via comandos del sistema (Windows/Linux)
 * =============================================================
 */
class LocalNetworkRouter implements RouterInterface {
    private $gatewayIp = '';
    private $gatewayMac = '';
    private $localIp = '';
    private $localMac = '';
    private $subnet = '';
    private $networkName = '';
    private $hostname = '';

    public function connect($ip, $port, $user, $pass) {
        $this->detectNetworkInfo();
        return true;
    }

    private function detectNetworkInfo() {
        $this->hostname = trim(shell_exec('hostname 2>&1') ?? '');
        $output = shell_exec('ipconfig /all 2>&1') ?? '';

        if (preg_match('/IPv4[^:]+:\s*(\d+\.\d+\.\d+\.\d+)/i', $output, $m)) {
            $this->localIp = $m[1];
        }
        if (preg_match('/Puerta[^\r\n]+:\s*(\d+\.\d+\.\d+\.\d+)/i', $output, $m)) {
            $this->gatewayIp = $m[1];
        }
        if (preg_match('/scara[^\r\n]+:\s*(\d+\.\d+\.\d+\.\d+)/i', $output, $m)) {
            $this->subnet = $m[1];
        }
        if (preg_match('/sica[^:]+:\s*([0-9A-Fa-f]{2}[-][0-9A-Fa-f]{2}[-][0-9A-Fa-f]{2}[-][0-9A-Fa-f]{2}[-][0-9A-Fa-f]{2}[-][0-9A-Fa-f]{2})/i', $output, $m)) {
            $this->localMac = strtoupper(str_replace('-', ':', $m[1]));
        }
        if (preg_match('/Adaptador de Ethernet[^\n]*\n(.*?)(?=\nAdaptador|\Z)/si', $output, $blockMatch)) {
            if (preg_match('/Sufijo DNS espec[^\r\n]+:\s*(\S+)/i', $blockMatch[1], $m)) {
                $this->networkName = trim($m[1]);
            }
        }
        if (empty($this->networkName) && preg_match('/squeda de sufijos DNS[^\r\n]*:\s*(\S+)/i', $output, $m)) {
            $this->networkName = trim($m[1]);
        }
        if ($this->gatewayIp) {
            $arpLine = shell_exec('arp -a ' . escapeshellarg($this->gatewayIp) . ' 2>&1') ?? '';
            if (preg_match('/([0-9a-f]{2}[-][0-9a-f]{2}[-][0-9a-f]{2}[-][0-9a-f]{2}[-][0-9a-f]{2}[-][0-9a-f]{2})/i', $arpLine, $m)) {
                $this->gatewayMac = strtoupper(str_replace('-', ':', $m[1]));
            }
        }
    }

    public function getWifiSettings($interfaceMacOrId) {
        $ssid = '';
        $password = '';
        $connType = 'cable';

        $wlanOutput = shell_exec('netsh wlan show interfaces 2>&1') ?? '';
        $hasWifi = (strpos($wlanOutput, 'No hay ninguna interfaz') === false && preg_match('/SSID/i', $wlanOutput));

        if ($hasWifi) {
            $connType = 'wifi';
            if (preg_match('/^\s*SSID\s*:\s*(.+)$/mi', $wlanOutput, $m)) $ssid = trim($m[1]);
            if ($ssid) {
                $profileOutput = shell_exec('netsh wlan show profile name="' . $ssid . '" key=clear 2>&1') ?? '';
                if (preg_match('/Contenido de la clave\s*:\s*(.+)/i', $profileOutput, $m)) $password = trim($m[1]);
                elseif (preg_match('/Key Content\s*:\s*(.+)/i', $profileOutput, $m)) $password = trim($m[1]);
            }
        }
        if (empty($ssid)) $ssid = !empty($this->networkName) ? $this->networkName : ('Red ' . $this->hostname);

        return [
            'ssid' => $ssid,
            'password' => $password ?: '••••••••',
            'gateway_ip' => $this->gatewayIp,
            'local_ip' => $this->localIp,
            'subnet' => $this->subnet,
            'local_mac' => $this->localMac,
            'hostname' => $this->hostname,
            'connection_type' => $connType,
        ];
    }

    public function setWifiSettings($interfaceMacOrId, $ssid, $password) { return false; }

    public function getConnectedDevices($interfaceMacOrId) {
        $devices = [];
        $subnetPrefix = '';
        if ($this->localIp) {
            $parts = explode('.', $this->localIp);
            $subnetPrefix = $parts[0] . '.' . $parts[1] . '.' . $parts[2];
        }
        if (empty($subnetPrefix)) return $devices;

        // Ping sweep
        $batchFile = sys_get_temp_dir() . '/turbosaas_ping.bat';
        $bat = "@echo off\n";
        for ($i = 1; $i <= 254; $i++) $bat .= "start /b ping -n 1 -w 150 $subnetPrefix.$i >nul 2>&1\n";
        file_put_contents($batchFile, $bat);
        shell_exec("cmd /c \"$batchFile\" 2>&1");
        sleep(3);
        @unlink($batchFile);

        $arpOutput = shell_exec('arp -a 2>&1') ?? '';
        $lines = explode("\n", $arpOutput);
        $id = 1;

        foreach ($lines as $line) {
            if (preg_match('/(\d+\.\d+\.\d+\.\d+)\s+([0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2}-[0-9a-f]{2})\s+din/i', trim($line), $m)) {
                $ip = $m[1]; $mac = strtoupper(str_replace('-', ':', $m[2]));
                if (strpos($ip, $subnetPrefix . '.') !== 0 || $ip === $subnetPrefix . '.255') continue;
                $isGw = ($ip === $this->gatewayIp);
                $isSelf = ($ip === $this->localIp);
                $devices[] = [
                    'id' => (string)$id++, 'mac' => $mac,
                    'name' => $isGw ? '🌐 Router / Gateway' : ($isSelf ? '💻 ' . $this->hostname . ' (Servidor)' : $this->resolveHost($ip)),
                    'ip' => $ip, 'tx' => '-', 'rx' => '-', 'blocked' => false,
                    'device_type' => $isGw ? 'router' : 'desktop',
                    'is_gateway' => $isGw, 'is_self' => $isSelf,
                ];
            }
        }

        // Add self if not found
        $selfFound = false;
        foreach ($devices as $d) { if ($d['ip'] === $this->localIp) { $selfFound = true; break; } }
        if (!$selfFound && $this->localIp) {
            array_unshift($devices, [
                'id' => '0', 'mac' => $this->localMac ?: '??:??:??:??:??:??',
                'name' => '💻 ' . ($this->hostname ?: 'Este Equipo') . ' (Servidor)',
                'ip' => $this->localIp, 'tx' => '-', 'rx' => '-', 'blocked' => false,
                'device_type' => 'desktop', 'is_gateway' => false, 'is_self' => true,
            ]);
        }

        usort($devices, function($a, $b) {
            if ($a['is_gateway'] && !$b['is_gateway']) return -1;
            if (!$a['is_gateway'] && $b['is_gateway']) return 1;
            if ($a['is_self'] && !$b['is_self']) return -1;
            if (!$a['is_self'] && $b['is_self']) return 1;
            return ip2long($a['ip']) - ip2long($b['ip']);
        });

        return $devices;
    }

    private function resolveHost($ip) {
        $hostname = @gethostbyaddr($ip);
        return ($hostname && $hostname !== $ip) ? $hostname : "Dispositivo ($ip)";
    }

    public function renameDevice($m, $n) { return true; }
    public function blockDevice($m, $b) { return false; }
}

// === Stubs para futuras integraciones ===
class MikrotikRouter implements RouterInterface {
    public function connect($ip, $port, $user, $pass) { return false; }
    public function getWifiSettings($i) { return ['ssid' => '', 'password' => '']; }
    public function setWifiSettings($i, $s, $p) { return false; }
    public function getConnectedDevices($i) { return []; }
    public function renameDevice($m, $n) { return false; }
    public function blockDevice($m, $b) { return false; }
}

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
        $this->loggedIn = true;
        return true;
    }

    public function getWifiSettings($interface = 'all') {
        return [
            'ssid' => 'TurboNet_5G_Fibra',
            'password' => 'Turbo@Fibra2026',
            'channel' => 'Auto',
            'mode' => '802.11ax/ac/n',
            'security' => 'WPA2-PSK/WPA3-SAE',
            'status' => 'active'
        ];
    }

    public function setWifiSettings($interface, $ssid, $password) {
        return true;
    }

    public function getConnectedDevices($interface = 'all') {
        return [
            [
                'hostname' => 'iPhone-Admin',
                'mac_address' => 'A4:C3:F0:12:34:56',
                'ip_address' => '192.168.1.5',
                'device_type' => 'phone',
                'blocked' => false,
                'is_gateway' => false,
                'is_self' => false
            ],
            [
                'hostname' => 'SmartTV-Samsung',
                'mac_address' => 'BC:D1:19:98:76:54',
                'ip_address' => '192.168.1.8',
                'device_type' => 'tv',
                'blocked' => false,
                'is_gateway' => false,
                'is_self' => false
            ],
            [
                'hostname' => 'PC-Emanuel',
                'mac_address' => 'D8:5E:D3:45:67:89',
                'ip_address' => '192.168.1.10',
                'device_type' => 'desktop',
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
        return true;
    }
}
