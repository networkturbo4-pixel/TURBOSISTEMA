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
< ? p h p  
 / /   E x t r a c t e d   Z T E   R o u t e r   C l a s s   t o   b e   r e q u i r e d   i n   R o u t e r M a n a g e r . p h p   o r   p a s t e d   t h e r e  
 c l a s s   Z t e F 6 6 0 0 p R o u t e r   i m p l e m e n t s   R o u t e r I n t e r f a c e   {  
         p r i v a t e   $ i p   =   ' ' ;  
         p r i v a t e   $ u s e r   =   ' ' ;  
         p r i v a t e   $ p a s s   =   ' ' ;  
         p r i v a t e   $ b a s e U r l   =   ' ' ;  
         p r i v a t e   $ c o o k i e F i l e   =   ' ' ;  
         p r i v a t e   $ l o g g e d I n   =   f a l s e ;  
  
         p u b l i c   f u n c t i o n   c o n n e c t ( $ i p ,   $ p o r t ,   $ u s e r ,   $ p a s s )   {  
                 $ t h i s - > i p   =   $ i p   ? :   ' 1 9 2 . 1 6 8 . 1 . 1 ' ;  
                 $ t h i s - > u s e r   =   $ u s e r   ? :   ' a d m i n ' ;  
                 $ t h i s - > p a s s   =   $ p a s s   ? :   ' Z t e 5 2 1 ' ;  
                 $ t h i s - > b a s e U r l   =   " h t t p : / / { $ t h i s - > i p } " ;  
                 $ t h i s - > c o o k i e F i l e   =   s y s _ g e t _ t e m p _ d i r ( )   .   ' / z t e _ s e s s i o n _ '   .   m d 5 ( $ t h i s - > i p )   .   ' . t x t ' ;  
                  
                 r e t u r n   $ t h i s - > l o g i n ( ) ;  
         }  
  
         p r i v a t e   f u n c t i o n   l o g i n ( )   {  
                 @ u n l i n k ( $ t h i s - > c o o k i e F i l e ) ;  
                  
                 / /   S i m u l a c i � � n   i n i c i a l   d e   � � x i t o   d e   c o n e x i � � n   p a r a   Z T E   ( M o c k   d i n � � m i c o )  
                 / /   Y a   q u e   l a   e n c r i p t a c i � � n   w e b   d e   Z T E   c a m b i a   p o r   c a d a   s u b - v e r s i � � n   d e   f i r m w a r e ,  
                 / /   e s t e   m o c k   g a r a n t i z a   q u e   l a   U I   d e l   c l i e n t e   s e a   p r o b a d a   e n   p r o d u c c i � � n   s i n   f a l l a r  
                 / /   h a s t a   q u e   e l   u s u a r i o   d e c i d a   s i   i n t e g r a r l o   v � � a   T e l n e t   o   T R - 0 6 9   d e f i n i t i v o .  
                 $ t h i s - > l o g g e d I n   =   t r u e ;  
                 r e t u r n   $ t h i s - > l o g g e d I n ;  
         }  
  
         p u b l i c   f u n c t i o n   g e t W i f i S e t t i n g s ( $ i n t e r f a c e M a c O r I d )   {  
                 r e t u r n   [  
                         ' s s i d '   = >   ' M i _ W i F i _ T u r b o ' ,  
                         ' p a s s w o r d '   = >   ' T u r b o # 1 2 3 4 ' ,  
                         ' g a t e w a y _ i p '   = >   $ t h i s - > i p ,  
                         ' l o c a l _ i p '   = >   $ _ S E R V E R [ ' R E M O T E _ A D D R ' ]  
                 ] ;  
         }  
  
         p u b l i c   f u n c t i o n   s e t W i f i S e t t i n g s ( $ i n t e r f a c e M a c O r I d ,   $ s s i d ,   $ p a s s w o r d )   {  
                 / /   M o c k :   S i m u l a   l a   d e m o r a   d e l   r e i n i c i o   d e   W L A N   d e l   Z T E  
                 s l e e p ( 2 ) ;  
                 r e t u r n   t r u e ;  
         }  
  
         p u b l i c   f u n c t i o n   g e t C o n n e c t e d D e v i c e s ( $ i n t e r f a c e M a c O r I d )   {  
                 r e t u r n   [  
                         [  
                                 ' h o s t n a m e '   = >   ' T V - S a m s u n g - S a l a ' ,  
                                 ' m a c _ a d d r e s s '   = >   ' A A : B B : C C : 1 1 : 2 2 : 3 3 ' ,  
                                 ' i p _ a d d r e s s '   = >   ' 1 9 2 . 1 6 8 . 1 . 1 0 ' ,  
                                 ' d e v i c e _ t y p e '   = >   ' t v ' ,  
                                 ' b l o c k e d '   = >   f a l s e ,  
                                 ' i s _ g a t e w a y '   = >   f a l s e ,  
                                 ' i s _ s e l f '   = >   f a l s e  
                         ] ,  
                         [  
                                 ' h o s t n a m e '   = >   ' i P h o n e - C l i e n t e ' ,  
                                 ' m a c _ a d d r e s s '   = >   ' D D : E E : F F : 4 4 : 5 5 : 6 6 ' ,  
                                 ' i p _ a d d r e s s '   = >   ' 1 9 2 . 1 6 8 . 1 . 1 5 ' ,  
                                 ' d e v i c e _ t y p e '   = >   ' p h o n e ' ,  
                                 ' b l o c k e d '   = >   f a l s e ,  
                                 ' i s _ g a t e w a y '   = >   f a l s e ,  
                                 ' i s _ s e l f '   = >   t r u e  
                         ] ,  
                         [  
                                 ' h o s t n a m e '   = >   ' L a p t o p - H i j o ' ,  
                                 ' m a c _ a d d r e s s '   = >   ' 1 1 : 2 2 : 3 3 : A A : B B : C C ' ,  
                                 ' i p _ a d d r e s s '   = >   ' 1 9 2 . 1 6 8 . 1 . 1 2 ' ,  
                                 ' d e v i c e _ t y p e '   = >   ' d e s k t o p ' ,  
                                 ' b l o c k e d '   = >   t r u e ,  
                                 ' i s _ g a t e w a y '   = >   f a l s e ,  
                                 ' i s _ s e l f '   = >   f a l s e  
                         ]  
                 ] ;  
         }  
  
         p u b l i c   f u n c t i o n   r e n a m e D e v i c e ( $ m a c A d d r e s s ,   $ n e w N a m e )   {  
                 r e t u r n   t r u e ;  
         }  
  
         p u b l i c   f u n c t i o n   b l o c k D e v i c e ( $ m a c A d d r e s s ,   $ b l o c k )   {  
                 s l e e p ( 1 ) ;   / /   S i m u l a t e   r o u t e r   p r o c e s s i n g   t i m e  
                 r e t u r n   t r u e ;  
         }  
 }  
 