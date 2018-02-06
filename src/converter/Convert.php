<?php

namespace Crevasse;

class Convert extends Command
{
    const VERSION = '0.1.0';
    const VERSION_DATE = '2018-02-06 18:45:39';
    public $convert_content = null;
    public $convert_buffer = [];
    public $convert_result = [];

    public function __toString()
    {
        return (string) $this->convert_result;
    }

    public function __construct()
    {
        $this->detectCommandMode();
        $this->parseCommandArray();
        $this->matchCommand();
    }

    public function matchCommand()
    {
        if (self::$cli_mode && $this->command !== null) {
            switch (self::$cli_mode) {
                case array_key_exists('-v', $this->command):
                    $this->setConsoleVersion();
                    break;
                case array_key_exists('-help', $this->command):
                    $this->setConsoleHelp();
                    break;
                case isset($this->command['convert']) && isset($this->command['export']):
                    Report::setReport('convert start...');
                    Report::setReport("convert file path [{$this->command['convert']}] ".
                        "export file path [{$this->command['export']}]");
                    $this->getConvertContent($this->command['convert']);
                    $this->getConvertToJson($this->convert_content);
                    $this->setConvertBuild();
                    $this->outputConvert($this->convert_result, $this->command['export']);
                    Report::setReport('convert finish!');
                    break;
                case array_key_exists('convert', $this->command):
                    Report::setReport('convert start...');
                    Report::setReport('convert file path [default.conf] '.
                        'export file path [convert.json]');
                    $this->getConvertContent('default.conf');
                    $this->getConvertToJson($this->convert_content);
                    $this->setConvertBuild();
                    $this->outputConvert($this->convert_result, 'convert.json');
                    Report::setReport('convert finish!');
                    break;
                default:
                    Report::setReport('command not found.');
            }
        }
        elseif (self::$cli_mode === false) {
            switch (true) {
                case isset($this->command['convert']):
                    $this->getConvertContent($this->command['convert']);
                    $this->getConvertToJson($this->convert_content);
                    $this->setConvertBuild();
                    $this->outputConvert($this->convert_result);
                    break;
                default:
            }
        }
    }

    public function setConsoleHelp()
    {
        Report::setReport('Usage >>');
        Report::setReport('crevasse convert {default_import:default.conf default_export:convert.json}');
        Report::setReport('crevasse convert {import_conf_path/url_path} export {export_path}');
    }

    public function setConsoleVersion()
    {
        Report::setReport('version:'.self::VERSION.'|date:'.self::VERSION_DATE);
    }

    public function outputConvert(array $convert_content, $convert_output = null)
    {
        switch (true) {
            case self::$cli_mode === true:
                $output = fopen($convert_output, 'w');
                fwrite($output, json_encode($convert_content, JSON_PRETTY_PRINT));
                fclose($output);
                break;
            case self::$cli_mode === false:
                return $this->convert_result = json_encode($convert_content, JSON_PRETTY_PRINT);
                break;
            default:
        }

        return $this;
    }

    public function getConvertContent($convert_path)
    {
        switch ($convert_path) {
            case self::$cli_mode === true:
                if (@file_get_contents($convert_path)) {
                    $this->convert_content = file_get_contents($convert_path);
                }
                break;
            case self::$cli_mode === false || preg_match('(http|https):\/\/', $convert_path) === 1:
                $header = get_headers($convert_path);
                if ($header['Content-Length'] < '10240') {
                    $curl = new CurlX();
                    $curl->get($convert_path);
                    $curl->error ?: $this->convert_content = $curl->setDisableTransfer($curl->response);
                }
        }
    }

    public function getConvertToJson($convert_content)
    {
        if (is_null($convert_content)) {
            new ConvertException([
                'class'   => __CLASS__,
                'function'=> __FUNCTION__,
                'message' => 'convert_content is null!',
                'status'  => 500,
            ]);
        }
        $convert_content = explode("\n", $convert_content);
        for ($i = 0; $i < count($convert_content); $i++) {
            // rules (str_to_lower)
            if (preg_match('/^('.
            	'DOMAIN-SUFFIX|DOMAIN|DOMAIN-KEYWORD|IP-CIDR|IP-CIDR6'.
            	'),(.*?),('.
            	'[\x{4e00}-\x{9fa5}\x{1F000}-\x{1F6FF}\x{FE00}-\x{FEFF}\w+\d+\-]+'.
            	')([\d+\w+\,\-]+|)/u', $convert_content[$i])) {
                $explode = explode(',', preg_replace('/ \/\/.*/', '', $convert_content[$i]));
                $this->convert_buffer['rules'][sha1(strtolower($explode[1]))] = [
                    'type'  => $explode[0],
                    'value' => strtolower($explode[1]),
                    'policy'=> $explode[2],
                    'option'=> isset($explode[3]) ? strtolower($explode[3]) : null,
                ];
            }
            // rules (str_to_upper)
            elseif (preg_match('/^('.
            	'USER-AGENT|PROCESS-NAME|URL-REGEX'.
            	'),(.*?),('.
            	'[\x{4e00}-\x{9fa5}\x{1F000}-\x{1F6FF}\x{FE00}-\x{FEFF}\w+\d+\-]+'.
            	')([\d+\w+\,\-]+|)/u', $convert_content[$i])) {
                $explode = explode(',', preg_replace('/ \/\/.*/', '', $convert_content[$i]));
                $this->convert_buffer['rules'][sha1(strtolower($explode[1]))] = [
                    'type'  => $explode[0],
                    'value' => $explode[1],
                    'policy'=> $explode[2],
                    'option'=> isset($explode[3]) ? strtolower($explode[3]) : null,
                ];
            }
            //url_rewrite
            elseif (preg_match('/^.*? .*? (header|reject|302|307)$/', $convert_content[$i])) {
                $explode = explode(' ', $convert_content[$i]);
                $this->convert_buffer['url_rewrite'][sha1(strtolower($explode[0]))] = [
                    'type'   => 'url_rewrite',
                    'pattern'=> $explode[0],
                    'replace'=> $explode[1],
                    'policy' => strtolower($explode[2]),
                ];
            }
            //header_rewrite
            elseif (preg_match('/^.*? (header-add|header-del|header-replace) .*$/', $convert_content[$i])) {
                $explode = explode(' ', $convert_content[$i]);
                $this->convert_buffer['header_rewrite'][sha1(strtolower($convert_content[$i]))] = [
                    'type'        => 'header_rewrite',
                    'value'       => $explode[0],
                    'action'      => $explode[1],
                    'header_name' => $explode[2],
                    'header_value'=> isset($explode[3]) ? (string) $explode[3] : null,
                ];
            }
            //skip-proxy
            elseif (preg_match('/^skip-proxy = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $replace = preg_replace('/, /', ',', $explode[1]);
                $explode = explode(',', $replace);
                for ($c = 0; $c < count($explode); $c++) {
                    $this->convert_buffer['skip-proxy']['list'][sha1(strtolower($explode[$c]))] = $explode[$c];
                }
            }
            //bypass-tun
            elseif (preg_match('/^bypass-tun = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $replace = preg_replace('/, /', ',', $explode[1]);
                $explode = explode(',', $replace);
                for ($u = 0; $u < count($explode); $u++) {
                    $this->convert_buffer['bypass-tun']['list'][sha1(strtolower($explode[$u]))] = $explode[$u];
                }
            }
            //replica
            elseif (preg_match('/^('.
                'hide-apple-request|hide-crashlytics-request|use-keyword-filter'.
                ') = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $this->convert_buffer['replica']['option'][sha1(strtolower($explode[0]))] = [
                    'name' => $explode[0],
                    'value'=> $explode[1],
                ];
            } elseif (preg_match('/^keyword-filter = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $replace = preg_replace('/, /', ',', $explode[1]);
                $explode = explode(',', $replace);
                for ($j = 0; $j < count($explode); $j++) {
                    $this->convert_buffer['replica']['keyword-filter'][sha1($explode[1].
                        strtolower($explode[$j]))] = $explode[$j];
                }
            }
            //general
            elseif (preg_match('/^('.
                'loglevel|bypass-system|ipv6|interface|port|socks-interface|socks-port|'.
                'external-controller-access|use-default-policy-if-wifi-not-primary|'.
                'allow-wifi-access'.
                ') = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $this->convert_buffer['general'][sha1(strtolower($explode[0]))] = [
                    'name' => $explode[0],
                    'value'=> $explode[1],
                ];
            }
            //geo-ip
            elseif (preg_match('/^(GEOIP),(\w+),('.
            	'[\x{4e00}-\x{9fa5}\x{1F000}-\x{1F6FF}\x{FE00}-\x{FEFF}\w+\d+\-]+'.
            	')([\d+\w+\,\-]+|)/u', $convert_content[$i])) {
                $explode = explode(',', preg_replace('/ \/\/.*/', '', $convert_content[$i]));
                $this->convert_buffer['geoip'][sha1(strtolower($explode[1]))] = [
                    'type'  => $explode[0],
                    'region'=> $explode[1],
                    'policy'=> $explode[2],
                    'option'=> isset($explode[3]) ? strtolower($explode[3]) : null,
                ];
            }
            //keystore
            elseif (preg_match('/^.*? = password=.*?, base64=.*$/', $convert_content[$i])) {
                //...
            }
            //mitm
            elseif (preg_match('/^(enable|ca-passphrase|ca-p12) = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $this->convert_buffer['mitm'][$explode[0]] = $explode[1];
            } elseif (preg_match('/^hostname = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $replace = preg_replace('/, /', ',', $explode[1]);
                $explode = explode(',', $replace);
                for ($j = 0; $j < count($explode); $j++) {
                    $this->convert_buffer['mitm']['hostname'][sha1(strtolower($explode[$j]))] = $explode[$j];
                }
            } elseif (preg_match('/^(tcp-connection) = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $this->convert_buffer['mitm']['option'][sha1(strtolower($explode[0]))] = [
                    'name' => $explode[0],
                    'value'=> $explode[1],
                ];
            }
            //dns-server
            elseif (preg_match('/^dns-server = .*$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $replace = preg_replace('/, /', ',', $explode[1]);
                $explode = explode(',', $replace);
                for ($j = 0; $j < count($explode); $j++) {
                    $this->convert_buffer['dns-server']['list'][sha1(strtolower($explode[$j]))] = $explode[$j];
                }
            }
            //label
            elseif (preg_match('/^\[.*?\]$/', $convert_content[$i])) {
                $replace = preg_replace('/(\[|\])/', '', $convert_content[$i]);
                $replace = preg_replace('/ /', '_', $replace);
                $this->convert_buffer['label'][strtolower($replace)] = $convert_content[$i];
            }
            //host
            elseif (preg_match('/^(.*?\..*) = ([\w+\d+\:\*]+\.[\d+\w+\.]+)$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $this->convert_buffer['host'][sha1(strtolower($explode[0]))] = [
                    'type'    => 'host',
                    'host'    => $explode[0],
                    'redirect'=> $explode[1],
                ];
            } elseif (preg_match('/^(.*?) = ([\w+\d+]+\:\d+\.\d+\.\d+\.\d+|[\w+\d+]+\:\w+)$/', $convert_content[$i])) {
                $explode = explode(' = ', $convert_content[$i]);
                $this->convert_buffer['host'][sha1(strtolower($explode[0]))] = [
                    'type'    => 'host',
                    'host'    => $explode[0],
                    'redirect'=> $explode[1],
                ];
            }
            //ssid_setting
            elseif (preg_match('/^\".*?\" suspend\=(true|false)$/', $convert_content[$i])) {
                $explode = explode('" ', $convert_content[$i]);
                $suspend = explode('=', $explode[1]);
                $this->convert_buffer['ssid_setting'][sha1(strtolower($explode[0]))] = [
                    'type'   => 'ssid_setting',
                    'ssid'   => $explode[0].'"',
                    'suspend'=> $suspend[1],
                ];
            }
            //final
            elseif (preg_match('/^(FINAL),('.
            	'[\x{4e00}-\x{9fa5}\x{1F000}-\x{1F6FF}\x{FE00}-\x{FEFF}\w+\d+\-]+'.
            	')([\d+\w+\,\-]+|)/u', $convert_content[$i])) {
                $explode = explode(',', $convert_content[$i]);
                $this->convert_buffer['final'][sha1(strtolower($explode[1]))] = [
                    'type'  => $explode[0],
                    'policy'=> $explode[1],
                    'option'=> isset($explode[2]) ? strtolower($explode[2]) : null,
                ];
            }
        }

        return $this->convert_result = $this->convert_buffer;
    }

    public function setConvertBuild()
    {
        return $this->convert_result['build'] = [
            'convert_ver' => self::VERSION,
            'convert_time'=> time(),
        ];
    }
}
