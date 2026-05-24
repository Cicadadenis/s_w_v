<?php

// ============= УЛУЧШЕННЫЕ ФУНКЦИИ ДЛЯ СТАРЫХ СКАНИРОВАНИЙ =============

// Расширенный парсинг WHOIS данных
function parseWhoisData($whois_text) {
  global $bold, $lblue, $fgreen, $cln, $yellow;
  
  echo $bold . $yellow . "\n[*] Parsed WHOIS Information:\n" . $cln;
  
  $patterns = array(
    'Registrar' => '/Registrar[^:]*:\s*(.+)/i',
    'Creation Date' => '/Created[^:]*:\s*(.+)/i',
    'Expiration Date' => '/Expir[^:]*:\s*(.+)/i',
    'Updated Date' => '/Updated[^:]*:\s*(.+)/i',
    'Registrant' => '/Registrant[^:]*Name:\s*(.+)/i',
    'Admin' => '/Admin[^:]*Name:\s*(.+)/i',
    'Tech' => '/Tech[^:]*Name:\s*(.+)/i',
    'Nameserver' => '/Name Server[^:]*:\s*(.+)/i',
  );
  
  foreach ($patterns as $field => $pattern) {
    if (preg_match($pattern, $whois_text, $matches)) {
      echo $bold . $lblue . "[" . $field . "]: " . $fgreen . trim($matches[1]) . $cln . "\n";
    }
  }
}

// Расширенный DNS Lookup с SPF/DKIM/DMARC
function advancedDNSLookup($domain) {
  global $bold, $lblue, $fgreen, $red, $cln, $yellow;
  
  echo $bold . $yellow . "\n[*] Advanced DNS Records:\n" . $cln;
  
  // SPF запись
  $dns_spf = @dns_get_record($domain, DNS_TXT);
  $spf_found = false;
  
  if ($dns_spf !== false) {
    foreach ($dns_spf as $record) {
      if (isset($record['txt']) && strpos($record['txt'], 'v=spf1') !== false) {
        echo $bold . $lblue . "[SPF]: " . $fgreen . $record['txt'] . $cln . "\n";
        $spf_found = true;
      }
      if (isset($record['txt']) && strpos($record['txt'], 'v=DMARC1') !== false) {
        echo $bold . $lblue . "[DMARC]: " . $fgreen . $record['txt'] . $cln . "\n";
      }
    }
  }
  
  if (!$spf_found) {
    echo $bold . $lblue . "[SPF]: " . $red . "NOT FOUND" . $cln . "\n";
  }
  
  // MX записи
  $dns_mx = @dns_get_record($domain, DNS_MX);
  if ($dns_mx !== false && count($dns_mx) > 0) {
    echo $bold . $lblue . "[MX Records Found]: " . $fgreen . count($dns_mx) . $cln . "\n";
    foreach ($dns_mx as $mx) {
      echo $bold . $lblue . "  - " . $fgreen . $mx['host'] . " (Priority: " . $mx['pri'] . ")" . $cln . "\n";
    }
  }
  
  // NS записи
  $dns_ns = @dns_get_record($domain, DNS_NS);
  if ($dns_ns !== false && count($dns_ns) > 0) {
    echo $bold . $lblue . "[Nameservers]: \n";
    foreach ($dns_ns as $ns) {
      echo $bold . $lblue . "  - " . $fgreen . $ns['target'] . $cln . "\n";
    }
  }
}

// Детекция уязвимостей по HTTP заголовкам и версиям
function detectVulnerabilitiesFromBanner($headers) {
  global $bold, $lblue, $fgreen, $red, $cln, $yellow;
  
  echo $bold . $yellow . "\n[*] Potential Vulnerabilities Detected:\n" . $cln;
  
  $vulnerability_patterns = array(
    'Apache/2.2' => 'Outdated Apache (Multiple known CVEs)',
    'Apache/2.0' => 'Severely Outdated Apache',
    'nginx/1.0' => 'Very Old Nginx',
    'IIS/6.0' => 'Legacy IIS (Security risks)',
    'PHP/5.' => 'Old PHP version (Security issues)',
    'OpenSSL/0.9' => 'Deprecated OpenSSL',
  );
  
  $full_header = implode(' ', $headers);
  $found_vuln = false;
  
  foreach ($vulnerability_patterns as $pattern => $vulnerability) {
    if (stripos($full_header, $pattern) !== false) {
      echo $bold . $red . "[WARN]: " . $vulnerability . $cln . "\n";
      $found_vuln = true;
    }
  }
  
  if (!$found_vuln) {
    echo $bold . $fgreen . "[OK]: No known vulnerable versions detected" . $cln . "\n";
  }
}

// Проверка доступности поддоменов
function checkSubdomainLiveness($subdomains_array, $protocol) {
  global $bold, $lblue, $fgreen, $red, $cln, $yellow;
  
  echo $bold . $yellow . "\n[*] Checking Subdomain Liveness:\n" . $cln;
  
  $alive_count = 0;
  $dead_count = 0;
  
  foreach ($subdomains_array as $subdomain) {
    $parts = explode(',', $subdomain);
    if (isset($parts[0])) {
      $domain = trim($parts[0]);
      $test_url = $protocol . $domain;
      
      $handle = curl_init($test_url);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($handle, CURLOPT_TIMEOUT, 3);
      curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_exec($handle);
      $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
      curl_close($handle);
      
      if ($httpCode != 0 && $httpCode != 503) {
        echo $bold . $lblue . "  [ALIVE] " . $fgreen . $domain . $yellow . " (HTTP " . $httpCode . ")" . $cln . "\n";
        $alive_count++;
      } else {
        echo $bold . $lblue . "  [DEAD] " . $red . $domain . $cln . "\n";
        $dead_count++;
      }
    }
  }
  
  echo $bold . $yellow . "\n[Summary] Alive: " . $fgreen . $alive_count . $yellow . " | Dead: " . $red . $dead_count . $cln . "\n";
}

// Расширенный SQLi Scanner с разными типами
function advancedSQLiScan($reallink, $links_array) {
  global $bold, $lblue, $fgreen, $red, $cln, $yellow;
  
  echo $bold . $yellow . "\n[*] Advanced SQL Injection Testing:\n" . $cln;
  
  $sql_error_patterns = array(
    "You have an error in your SQL syntax",
    "supplied invalid SQL",
    "sql syntax",
    "syntax error",
    "ORA-",
    "MySQL",
    "PostgreSQL",
    "database",
    "SQL",
    "Exception"
  );
  
  $payloads = array(
    "'" => "Single Quote",
    "' OR '1'='1" => "Basic OR",
    "\" OR 1=1--" => "Comment-based",
    "') OR ('1'='1" => "Parentheses",
  );
  
  $vulnerable_links = array();
  
  foreach ($links_array as $link) {
    foreach ($payloads as $payload => $type) {
      if (strpos($link, '?') !== false) {
        if (strpos($link, '://') !== false) {
          $test_url = $link . $payload;
        } else {
          $test_url = $reallink . "/" . $link . $payload;
        }
        
        $response = @file_get_contents($test_url);
        
        if ($response !== false) {
          foreach ($sql_error_patterns as $pattern) {
            if (stripos($response, $pattern) !== false) {
              $vulnerable_links[] = array(
                'url' => $link,
                'type' => $type,
                'pattern' => $pattern
              );
              break;
            }
          }
        }
      }
    }
  }
  
  if (count($vulnerable_links) > 0) {
    echo $bold . $red . "[VULNERABLE] Found " . count($vulnerable_links) . " SQLi points:\n" . $cln;
    foreach ($vulnerable_links as $vuln) {
      echo $bold . $lblue . "  - URL: " . $fgreen . $vuln['url'] . $cln . "\n";
      echo $bold . $lblue . "    Type: " . $yellow . $vuln['type'] . $cln . "\n";
    }
  } else {
    echo $bold . $fgreen . "[OK] No obvious SQL injection found\n" . $cln;
  }
}

// Сканирование WordPress плагинов и тем
function scanWordPressPluginsThemes($reallink) {
  global $bold, $lblue, $fgreen, $red, $cln, $yellow;
  
  echo $bold . $yellow . "\n[*] WordPress Plugins & Themes Scanner:\n" . $cln;
  
  $common_dirs = array(
    '/wp-content/plugins/',
    '/wp-content/themes/',
  );
  
  foreach ($common_dirs as $dir) {
    $url = $reallink . $dir;
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($handle, CURLOPT_TIMEOUT, 5);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
    $response = curl_exec($handle);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    
    if ($httpCode == 200) {
      if (strpos($response, 'Index of') !== false) {
        echo $bold . $red . "[!] Directory Listing Enabled: " . $url . $cln . "\n";
        
        // Парсим доступные плагины/темы
        preg_match_all('/<a href="([^"]+)\/">/i', $response, $matches);
        if (isset($matches[1])) {
          foreach ($matches[1] as $item) {
            echo $bold . $lblue . "  [FOUND] " . $fgreen . $item . $cln . "\n";
          }
        }
      }
    }
  }
}

// Расширенный анализ портов на основе ответа Nmap
function analyzePortScanResults($nmap_output) {
  global $bold, $lblue, $fgreen, $red, $yellow, $cln;

  echo $bold . $yellow . "\n[*] Port Exposure Analysis:\n" . $cln;

  $high_risk_ports = array(
    '21' => 'FTP (проверьте anonymous login и TLS)',
    '23' => 'Telnet (небезопасный протокол, лучше отключить)',
    '25' => 'SMTP (риск open relay / spoofing)',
    '53' => 'DNS (проверьте recursion и zone transfer)',
    '139' => 'NetBIOS (внутренний сервис не должен быть наружу)',
    '445' => 'SMB (частая цель эксплойтов)',
    '1433' => 'MSSQL (ограничьте доступ по IP)',
    '3306' => 'MySQL (должен быть закрыт снаружи)',
    '3389' => 'RDP (только через VPN/allowlist)',
    '5432' => 'PostgreSQL (ограничьте доступ по IP)',
    '6379' => 'Redis (нельзя оставлять без auth)',
    '9200' => 'Elasticsearch (часто утечки данных)',
    '27017' => 'MongoDB (запретить внешний доступ)',
  );

  $found = false;
  foreach ($high_risk_ports as $port => $advice) {
    if (preg_match('/^' . preg_quote($port, '/') . '\/tcp\s+open/im', $nmap_output)) {
      echo $bold . $red . "[WARN] Open " . $port . "/tcp: " . $advice . $cln . "\n";
      $found = true;
    }
  }

  if (!$found) {
    echo $bold . $fgreen . "[OK] Критичные из списка порты не обнаружены открытыми" . $cln . "\n";
  }
}

// Поиск распространенных web-уязвимостей через тестовые payload
function quickWebVulnFingerprint($reallink) {
  global $bold, $lblue, $fgreen, $red, $yellow, $cln;

  echo $bold . $yellow . "\n[*] Quick Web Vulnerability Fingerprint:\n" . $cln;

  $tests = array(
    array('name' => 'XSS Reflection', 'path' => '/?q=%3Cscript%3Ealert(1)%3C/script%3E', 'needle' => '<script>alert(1)</script>'),
    array('name' => 'LFI Traversal', 'path' => '/?file=../../../../etc/passwd', 'needle' => 'root:x:'),
    array('name' => 'Debug Leak', 'path' => '/?debug=true', 'needle' => 'stack trace'),
    array('name' => 'PHPInfo Leak', 'path' => '/phpinfo.php', 'needle' => 'phpinfo()'),
  );

  foreach ($tests as $test) {
    $url = rtrim($reallink, '/') . $test['path'];
    $resp = @readcontents($url);
    if ($resp !== false && $resp !== null && stripos($resp, $test['needle']) !== false) {
      echo $bold . $red . "[POTENTIAL] " . $test['name'] . ": " . $url . $cln . "\n";
    } else {
      echo $bold . $lblue . "[CHECKED] " . $test['name'] . ": " . $fgreen . "No obvious pattern" . $cln . "\n";
    }
  }
}

?>
