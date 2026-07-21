<?php

use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * FUNÇÕES DE DATA E HORA
 */

if (!function_exists('datePtBr')) {
    function datePtBr($dateTime, $showTime = true, $dateType = 'FULL')
    {
        if (is_null($dateTime)) return null;

        $date = new \DateTime($dateTime);

        $dateFormat = $showTime ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE;

        $formatter = match ($dateType) {
            'FULL' => new \IntlDateFormatter('pt_BR', \IntlDateFormatter::FULL, $dateFormat, 'America/Sao_Paulo', \IntlDateFormatter::GREGORIAN),
            'MEDIUM' => new \IntlDateFormatter('pt_BR', \IntlDateFormatter::MEDIUM, $dateFormat, 'America/Sao_Paulo', \IntlDateFormatter::GREGORIAN),
            'LONG' => new \IntlDateFormatter('pt_BR', \IntlDateFormatter::LONG, $dateFormat, 'America/Sao_Paulo', \IntlDateFormatter::GREGORIAN),
            'SHORT' => new \IntlDateFormatter('pt_BR', \IntlDateFormatter::SHORT, $dateFormat, 'America/Sao_Paulo', \IntlDateFormatter::GREGORIAN),
            default => null
        };

        return $formatter->format($date);
    }
}

if (!function_exists('dateForHumans')) {
    function dateForHumans($dateTime)
    {
        if (is_null($dateTime)) return null;
        return Carbon::parse($dateTime)->diffForHumans();
    }
}

if (!function_exists('formatTestingDays')) {
    function formatTestingDays($testingDays)
    {
        if (empty($testingDays)) return null;

        $testingDays = (int) $testingDays;

        return $testingDays . ' ' . ($testingDays === 1 ? __('app.day') : __('app.days'));
    }
}

if (!function_exists('yearNumberRandom')) {
    function yearNumberRandom(): string
    {
        $year = now()->format('Y');

        return $year . str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }
}

/**
 * FUNÇÕES DE FORMATAÇÃO
 */

if (!function_exists('maskFormat')) {
    /**
     * Formata valores com máscaras específicas
     * 
     * @param string $type Tipo de máscara
     * @param string|float|int $value Valor a ser formatado
     * @param array $options Opções adicionais de formatação
     * @return string
     */
    function maskFormat($type, $value, $options = [])
    {
        switch ($type) {
            case 'at':
                return empty($value) ? '@usuário' : "@" . formatAt($value);
                break;
            case 'cpf':
                if (strlen($value) === 11) {
                    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
                }
                break;

            case 'cnpj':
                if (strlen($value) === 14) {
                    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $value);
                }
                break;

            case 'cpf_cnpj':
                if (strlen($value) === 11) {
                    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
                } elseif (strlen($value) === 14) {
                    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $value);
                }
                break;

            case 'zip_code':
                if (strlen($value) === 8) {
                    return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $value);
                }
                break;

            case 'phone':
                if (strlen($value) === 10) {
                    return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $value);
                } elseif (strlen($value) === 11) {
                    return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $value);
                } elseif (strlen($value) === 9) {
                    return preg_replace('/(\d{5})(\d{4})/', '$1-$2', $value);
                } elseif (strlen($value) === 8) {
                    return preg_replace('/(\d{4})(\d{4})/', '$1-$2', $value);
                }
                break;

            case 'currency':
                // Remove caracteres não numéricos
                $value = preg_replace('/[^0-9.]/', '', $value);
                // Converte para float
                $value = (float) str_replace(',', '.', $value);
                // Formata com 2 casas decimais

                if (isset($options['symbol'])) {
                    return $options['symbol'] . number_format($value, 2, ',', '.');
                }

                return 'R$ ' . number_format($value, 2, ',', '.');

            case 'percentage':
                // Remove caracteres não numéricos
                $value = preg_replace('/[^0-9.]/', '', $value);
                // Converte para float
                $value = (float) str_replace(',', '.', $value);
                // Define o número de casas decimais (padrão: 2)
                $decimals = isset($options['decimals']) ? $options['decimals'] : 2;

                // Formata inicialmente com todas as casas decimais solicitadas
                $formatted = number_format($value, $decimals, ',', '.');

                // Remove zeros à direita e a vírgula se não houver casas decimais
                $formatted = rtrim(rtrim($formatted, '0'), ',');

                return $formatted . '%';

            case 'decimal':
                // Remove caracteres não numéricos exceto ponto e vírgula
                $value = preg_replace('/[^0-9.,]/', '', $value);
                // Substitui vírgula por ponto
                $value = str_replace(',', '.', $value);
                // Remove pontos extras, mantendo apenas o último
                if (substr_count($value, '.') > 1) {
                    $parts = explode('.', $value);
                    $value = $parts[0] . '.' . implode('', array_slice($parts, 1));
                }
                // Formata com 2 casas decimais
                return number_format((float) $value, 2, ',', '.');

            case 'integer':
                // Remove todos os caracteres não numéricos
                return preg_replace('/[^0-9]/', '', $value);

            case 'cep':
                if (strlen($value) === 8) {
                    return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $value);
                }
                break;

            case 'rg':
                if (strlen($value) === 9) {
                    return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{1})/', '$1.$2.$3-$4', $value);
                }
                break;

            case 'cnh':
                if (strlen($value) === 11) {
                    return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $value);
                }
                break;

            case 'credit_card':
                if (strlen($value) === 16) {
                    return preg_replace('/(\d{4})(\d{4})(\d{4})(\d{4})/', '$1 $2 $3 $4', $value);
                }
                break;

            case 'expiry_date':
                if (strlen($value) === 4) {
                    return preg_replace('/(\d{2})(\d{2})/', '$1/$2', $value);
                }
                break;

            case 'cvv':
                if (strlen($value) === 3 || strlen($value) === 4) {
                    return $value;
                }
                break;

            case 'plate':
                if (empty($value)) return null;

                // Remove caracteres especiais e espaços
                $value = preg_replace('/[^A-Za-z0-9]/', '', $value);

                // Converte para maiúsculo
                $value = strtoupper($value);

                // Verifica se tem 7 caracteres
                if (strlen($value) !== 7) return $value;

                // Formato Mercosul: 3 letras + 1 número + 1 letra + 2 números (Ex: ABC4A47 -> ABC-4A47)
                if (preg_match('/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/', $value)) {
                    return substr($value, 0, 3) . '-' . substr($value, 3);
                }

                // Formato antigo: 3 letras + 4 números (Ex: BRA2017 -> BRA-2017)
                if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $value)) {
                    return substr($value, 0, 3) . '-' . substr($value, 3);
                }

                return $value;
                break;

            case 'boolean':
                return $value > 0 ? true : false;
                break;

            case 'file_size':
                $bytes = (float) $value;

                if ($bytes < 1024) {
                    return $bytes . ' B';
                }

                $units = ['KB', 'MB', 'GB', 'TB'];
                $size = $bytes / 1024;
                $unit = 0;

                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }

                $decimals = $options['decimals'] ?? 2;

                return number_format($size, $decimals, ',', '.') . ' ' . $units[$unit];

            case 'nbs_code':
                if (strlen($value) === 9) {
                    return preg_replace('/(\d{2})(\d{3})(\d{4})/', '$1.$2.$3', $value);
                }
                break;
        }

        return $value;
    }
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}

if (!function_exists('formatAt')) {
    function formatAt($string = '')
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', empty($string) ? yearNumberRandon() : $string));
    }
}

if (!function_exists('formatFileName')) {
    function formatFileName($originalName, $path)
    {
        // Normaliza os caracteres e substitui caracteres especiais por hífen
        $name = strtolower(preg_replace('/[\s%\&@=+]/', '-', normalizeChars($originalName)));
        // Verifica e incrementa o nome do arquivo se necessário
        return incrementIfExistFile($name, $path);
    }
}

if (!function_exists('toDecimal')) {
    /**
     * Converte um valor com máscara BR para decimal
     * 
     * @param string|float|int|null $value Valor a ser convertido
     * @param float $default Valor padrão caso o valor não seja válido
     * @return float
     */
    function toDecimal($value, $default = 0.0)
    {
        if (is_null($value) || (empty($value) && $value !== 0 && $value !== '0')) {
            return $default;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove tudo que não for número, ponto ou vírgula
        $value = preg_replace('/[^0-9.,]/', '', (string) $value);

        // Remove os pontos (separadores de milhar)
        $value = str_replace('.', '', $value);

        // Troca a vírgula decimal por ponto
        $value = str_replace(',', '.', $value);

        // Verifica se é numérico após tratamento
        return is_numeric($value) ? (float) $value : $default;
    }
}

if (!function_exists('autop')) {
    function autop(string $text): string
    {
        return '<p>' . implode('</p><p>', preg_split('/\R{2,}/', trim(e($text)))) . '</p>';
    }
}

if (! function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name))));

        if (empty($parts)) {
            return '';
        }

        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1));
        }

        return mb_strtoupper(
            mb_substr($parts[0], 0, 1) .
                mb_substr($parts[count($parts) - 1], 0, 1)
        );
    }
}

/** HELPERS DE SEGURANÇA */

if (!function_exists('encrypter')) {
    /**
     * Encripta um valor
     * 
     * @param string $value Valor a ser encriptado
     * @return string Retorna o valor encriptado
     */
    function encrypter($value)
    {
        if (is_null($value)) return;

        return Crypt::encryptString($value);
    }
}

if (!function_exists('decrypter')) {
    /**
     * Decripta um valor
     * 
     * @param string $value Valor a ser decriptado
     * @return string Retorna o valor decriptado
     */
    function decrypter($value)
    {
        if (is_null($value)) return;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            // Se não for possível decriptar, retorna o valor puro
            return $value;
        } catch (\Exception $e) {
            // Garante fallback para qualquer outro erro inesperado
            return $value;
        }
    }
}

if (!function_exists('hmac_hash')) {

    /**
     * Gera hash seguro para busca/indexacao
     *
     * @param string|null $value
     * @param bool $sanitizeSpecialCharacters
     * @param bool $removeSpaces
     * @return string|null
     */
    function hmac_hash(?string $value, bool $sanitizeSpecialCharacters = false, bool $removeSpaces = false): ?string
    {
        if (is_null($value)) return null;

        $value = mb_strtolower(trim($value));

        if ($sanitizeSpecialCharacters) {
            $value = sanitizeSpecialCharacters($value, $removeSpaces);
        }

        return hash_hmac('sha256', $value, config('app.key'));
    }
}

/**
 * FUNÇÕES DE VALIDAÇÃO
 */

if (!function_exists('isValidDocument')) {
    function isValidDocument($document)
    {
        // Remove caracteres não numéricos
        $document = preg_replace('/[^0-9]/', '', $document);

        // Verifica se é CPF (11 dígitos) ou CNPJ (14 dígitos)
        if (strlen($document) === 11) {
            return isValidCPF($document);
        } elseif (strlen($document) === 14) {
            return isValidCNPJ($document);
        }

        return false;
    }
}

if (!function_exists('getDocumentType')) {
    /**
     * Identifica se o documento é CPF ou CNPJ
     * 
     * @param string $document Documento a ser verificado
     * @return string|false Retorna 'CPF', 'CNPJ' ou false se inválido
     */
    function getDocumentType($document)
    {
        // Remove caracteres não numéricos
        $document = preg_replace('/[^0-9]/', '', $document);

        // Verifica o tamanho do documento
        if (strlen($document) === 11) {
            return isValidCPF($document) ? 'CPF' : false;
        } elseif (strlen($document) === 14) {
            return isValidCNPJ($document) ? 'CNPJ' : false;
        }

        return false;
    }
}

if (!function_exists('isValidCPF')) {
    function isValidCPF($cpf)
    {
        // Verifica se foi informado
        if (empty($cpf)) return false;

        // Remove caracteres especiais
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 caracteres
        if (strlen($cpf) != 11) return false;

        // Verifica se foi informada uma sequência de dígitos repetidos
        if (preg_match('/(\d)\1{10}/', $cpf)) return false;

        // Faz o calculo para validar o CPF
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }
}

if (!function_exists('isValidCNPJ')) {
    function isValidCNPJ($cnpj)
    {
        // Verifica se foi informado
        if (empty($cnpj)) return false;

        // Remove caracteres especiais
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 caracteres
        if (strlen($cnpj) != 14) return false;

        // Verifica se foi informada uma sequência de dígitos repetidos
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;

        // Valida primeiro dígito verificador
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;

        // Valida segundo dígito verificador
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
}

if (!function_exists('isValidCEP')) {
    function isValidCEP($cep)
    {
        // Verifica se foi informado
        if (empty($cep)) return false;

        // Remove caracteres especiais
        $cep = preg_replace('/[^0-9]/', '', $cep);

        // Verifica se tem 8 caracteres numéricos
        if (strlen($cep) != 8) return false;

        // Verifica se não são todos números iguais
        if (preg_match('/(\d)\1{7}/', $cep)) return false;

        // Verifica se o CEP está no formato válido (primeiro dígito de 0-9)
        if (!preg_match('/^[0-9]/', $cep)) return false;

        return true;
    }
}

/**
 * FUNÇÕES DE MANIPULAÇÃO DE DADOS
 */
if (!function_exists('sanitizeSpecialCharacters')) {
    /**
     * Remove caracteres especiais de uma string
     * 
     * @param string $value String a ser limpa
     * @param bool $removeSpaces Se true remove espaços, se false mantém espaços
     * @return string String limpa
     */
    function sanitizeSpecialCharacters($value, $removeSpaces = true)
    {
        if (empty($value)) return null;

        // Substitui caracteres acentuados
        $aFind = array('&', 'á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ü', 'ç', 'Á', 'À', 'Ã', 'Â', 'É', 'Ê', 'Í', 'Ó', 'Ô', 'Õ', 'Ú', 'Ü', 'Ç');
        $aSubs = array('e', 'a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'u', 'c', 'A', 'A', 'A', 'A', 'E', 'E', 'I', 'O', 'O', 'O', 'U', 'U', 'C');
        $value = str_replace($aFind, $aSubs, $value);

        // Define o padrão de regex baseado no parâmetro removeSpaces
        $pattern = $removeSpaces ? "/[^a-zA-Z0-9]/" : "/[^a-zA-Z0-9 ]/";

        return preg_replace($pattern, '', $value);
    }
}

if (!function_exists('limitWords')) {
    function limitWords($text, $limit, $ellipsis = '...')
    {
        $words = explode(" ", $text);

        if (count($words) > $limit) {
            $text = implode(" ", array_slice($words, 0, $limit)) . $ellipsis;
        }

        return $text;
    }
}

if (!function_exists('normalizeChars')) {
    function normalizeChars($string)
    {
        $chars = [
            'Š' => 'S',
            'š' => 's',
            'Ð' => 'D',
            'Ž' => 'Z',
            'ž' => 'z',
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'A',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'B',
            'ß' => 'Ss',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'a',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'o',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'b',
            'ÿ' => 'y',
            'R' => 'R',
            'r' => 'r',
        ];

        return strtr($string, $chars);
    }
}

if (! function_exists('toArrayRecursive')) {

    /**
     * Converte stdClass, arrays multidimensionais e valores mistos
     * em arrays puros recursivamente.
     *
     * @param mixed $value
     * @return mixed
     */
    function toArrayRecursive($value)
    {
        // Se for objeto, converte para array
        if (is_object($value)) {
            $value = (array) $value;
        }

        // Se não for array, retorna como está
        if (!is_array($value)) {
            return $value;
        }

        // Converte recursivamente
        foreach ($value as $key => $item) {
            $value[$key] = toArrayRecursive($item);
        }

        return $value;
    }
}

/**
 * FUNÇÕES DE CÁLCULOS
 */
if (!function_exists('applyPercentage')) {
    function applyPercentage(float $value, float $percentage, string $operation = 'add'): float
    {
        $adjustment = $value * ($percentage / 100);

        return match (strtolower($operation)) {
            'add', 'plus' => $value + $adjustment,
            'subtract', 'minus' => $value - $adjustment,
            default => throw new InvalidArgumentException("Invalid operation: use 'add' or 'subtract'")
        };
    }
}

/**
 * FUNÇÕES DE BANCO DE DADOS
 */

if (!function_exists('incrementIfExistDatabase')) {
    function incrementIfExistDatabase($value, $table, $column, $ignoreId = null, $idColumn = 'id')
    {
        $originalValue = $value;
        $increment = 0;

        while (DB::table($table)
            ->when($ignoreId, fn($q) => $q->where($idColumn, '!=', $ignoreId))
            ->where($column, $value)
            ->exists()
        ) {
            $increment++;
            $value = $originalValue . $increment;
        }

        return $value;
    }
}

/**
 * FUNÇÕES DE ARQUIVO
 */

if (!function_exists('incrementIfExistFile')) {
    function incrementIfExistFile($name, $path)
    {
        $fileInfo = pathinfo($path . '/' . $name);
        $filename = $fileInfo['filename'];
        $extension = isset($fileInfo['extension']) ? '.' . $fileInfo['extension'] : '';
        $fullPath = $fileInfo['dirname'] . '/' . $filename;
        $increment = 0;

        // Loop até encontrar um nome de arquivo que não exista
        while (file_exists($fullPath . ($increment ? "-$increment" : '') . $extension)) {
            $increment++;
        }

        return $filename . ($increment ? "-$increment" : '') . $extension;
    }
}

/**
 * FUNÇÕES DE URL
 */
if (!function_exists('buildSubdomainUrl')) {
    /**
     * Monta uma URL completa com subdomínio e protocolo adequado
     * 
     * @param string $subdomain Subdomínio desejado
     * @param string $domain Domínio principal
     * @param bool $forceHttps Forçar uso de HTTPS mesmo que não disponível
     * @return string URL completa
     */
    function buildSubdomainUrl($subdomain, $domain, $forceHttps = false)
    {
        // Limpa o subdomínio e domínio
        $subdomain = trim(strtolower($subdomain));
        $domain = trim(strtolower($domain));

        // Remove http:// ou https:// do domínio se existir
        $domain = preg_replace('#^https?://#', '', $domain);

        // Detecta se HTTPS está disponível
        $protocol = ($forceHttps || isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        // Monta a URL completa
        return $protocol . '://' . $subdomain . '.' . $domain;
    }
}
