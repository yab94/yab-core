<?php

namespace Yab\Core;

class Config {

    use Factory;

	protected $params = [];

    static public function fromIniFile(string $iniFile) {

        if (!file_exists($iniFile)) {
            throw new \Exception('unable to find config file "' . $iniFile . '"');
        } 

        return new Config(parse_ini_file($iniFile, true));
        
    }
	
    static public function fromPHPFile($PHPFile) {
        
        
    }
    
	public function __construct(array $params = []) {
		
        $this->params = $this->digConfigDotParams($params);
		
	}
	
    protected function digConfigDotParams(array $config) {

        foreach ($config as $key => $value) {

            unset($config[$key]);

            $keyParts = explode('.', $key);

            $depth = &$config;

            foreach ($keyParts as $keyPart) {

                if (defined($keyPart)) {
                    $keyPart = constant($keyPart);
                }

                $depth[$keyPart] = isset($depth[$keyPart]) ? $depth[$keyPart] : [];

                $depth = &$depth[$keyPart];
            }

            if (is_array($value)) {

                $depth = $this->digConfigDotParams($value);
            } else {

                $depth = $value;
            }
        }

        return $config;
    }

    public function getParam($param, $default = null) {

        $parts = explode('.', $param);

        $part = array_shift($parts);

        if (!isset($this->params[$part])) {

            if ($default === null) {
                throw new \Exception('unable to find config param "' . $param . '" "'.$part.'"');
            }

            return $default;
        }

        $param = $this->params[$part];

        while (count($parts)) {

            $part = array_shift($parts);

            if (!isset($param[$part])) {

                if ($default === null) {
                    throw new \Exception('unable to find config param "'.$part.'"');
                }

                return $default;
            }

            $param = $param[$part];
        }

        return $param;
    }

    public function getParams() {
        
        return $this->params;
        
    }
    
	public function fusion(Config $config) {

        $this->params = $this->mergeArrays($this->params, $config->params);
        // $this->params = array_merge_recursive($this->params, $config->params);

		return $this;
		
    }
 
    protected function mergeArrays(array $arrayA, array $arrayB) {

        foreach($arrayB as $keyB => $valueB) {

            if(is_numeric($keyB)) {

                if(is_array($valueB)) {

                    $arrayA = $this->mergeArrays($arrayA, $valueB);

                } elseif(!in_array($valueB, $arrayA)) {

                    array_push($arrayA, $valueB);

                }

            } elseif(!isset($arrayA[$keyB])) {

                $arrayA[$keyB] = $valueB;

            } elseif(is_array($arrayA[$keyB]) && is_array($valueB)) {

                $arrayA[$keyB] = $this->mergeArrays($arrayA[$keyB], $valueB);

            } elseif(!is_array($valueB)) {

                $arrayA[$keyB] = $valueB;

            }

        }

        return $arrayA;

    }
    
}
