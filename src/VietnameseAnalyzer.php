<?php
/**
 * @Created by : PhpStorm
 * @Author : Hiệp Nguyễn
 * @At : 28/04/2021, Wednesday
 * @Filename : VietnameseAnalyzer.php
 **/

namespace Nguyenhiep\VietnameseRelatedWords;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class VietnameseAnalyzer
{
    protected $mapping = [
        "N",
        "V",
        "Nb",
        "N-A",
        "N-A-N",
        "N-M",
        "N-M-N",
        "N-N-M",
        "N-N-N",
        "N-N",
        "N-E-N",
        "N-E-Np",
        "N-V-N",
        "N-V-N-N",
        "N-Cc-N",
        "N-Ny",
        "N-Np",
        "Nc-N",
        "Ny-Ny",
        "Np-Np",
        "Nb-Nb",
        "Nb-Nb-M",
        "Nb-Nb-M-Nu",
        "V-Np-Np",
        "Np-CH-Np",
        "Np-V",
        "V-N",
        "V-N-N",
        "V-N-M",
        "V-N-M-N",
        "V-N-V",
        "V-N-V-N",
        "V-N-V-V",
        "V-A",
        "V-V",
        "V-V-N",
        "P-V",
        "V-P-V",
        "V-Cc-V",
        "M-N",
        "N-N-V",
    ];
    protected $debug = false;

    public function __construct($debug = false, $mapping = [])
    {
        $this->mapping = array_unique(array_merge($this->mapping, $mapping, config("nguyenhiep.vietnamese-related-words.mapping", [])));
        $this->debug   = $debug;
    }

    /**
     * analyzing use elasticsearch with vi_analyer tokenizer api
     * @param $text
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function es_analyze($text)
    {
        $text     = $this->optimize($text);
        $client   = new Client(["base_uri" => config("nguyenhiep.vietnamese-related-words.es_host")]);
        $response = $client->request("GET", "_analyze", [
            'headers'            => [
                'Content-Type' => 'application/json'
            ],
            RequestOptions::JSON => [
                "analyzer" => "vi_analyzer",
                "text"     => $text,
            ],
        ]);

        try {
            $tokens  = json_decode($response->getBody()->getContents(), true)["tokens"];
            if ($this->debug){
                dump($tokens);
            }
            $phrases = [];
            foreach ($tokens as $token) {
                if (in_array($token["type"], ["<WORD>", "<PHRASE>"])) {
                    $phrases[] = $token["token"];
                }
            }

            return array_values(array_unique(array_filter($phrases, function ($v, $k) {
                return $v && substr_count($v, " ");
            }, ARRAY_FILTER_USE_BOTH)));
        } catch (\Exception $exception) {
        }

        return [];
    }

    /**
     * analyzing use vncorenlp
     * @param $text
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function vncorenlp($text)
    {
        $text   = $this->optimize($text);
        $tokens = $this->getTokens($text);
        if ($this->debug){
            dump($tokens);
        }
        $type_chains = "";
        foreach ($tokens as $token) {
            if (isset($token[2])) {
                $type_chains .= "{$token[2]}-";
            }
        }
        $pharses = [];
        foreach ($this->mapping as $valid_chain) {
            if ($possitions = $this->find_possition($valid_chain, $type_chains)) {
                foreach ($possitions as $possition) {
                    $pharse = "";
                    foreach ($possition as $p) {
                        if (isset($tokens[$p]) && isset($tokens[$p][1])) {
                            $pharse .= "{$tokens[$p][1]}_";
                        }
                    }
                    $pharse    = str_replace("_", " ", $pharse);
                    $pharses[] = trim($pharse) . ($this->debug ? ":$valid_chain" : "");
                }
            }
        }

        return array_values(array_unique(array_filter($pharses, function ($v, $k) {
            return $v && substr_count($v, " ");
        }, ARRAY_FILTER_USE_BOTH)));
    }

    protected function optimize($string)
    {
        if (!substr_count($string, ' ')){
            //string inclue "-" or "_"
            $string = str_replace("-"," ",$string);
            $string = str_replace("_"," ",$string);
            //string camelCase format
            if ($arr = preg_split('/(?=[A-Z])/',$string)){
                $string = implode(" ",$arr);
            }

        }

        //remove extention
        $string = preg_replace('/\\.[^.\\s]{3,4}$/', '', $string);
        //normalize string
        $string = trim($string);
        $string = mb_strtolower($string);
        $string = preg_replace("/[^\p{M}\w\s]+/ui", " ", $string);
        $string = preg_replace("/\s{2,}/", " ", $string);
        $string = trim($string);
        return $string;
    }

    /**
     * explose input to pharses with type
     * @param $text
     * @return false|string[]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getTokens($text)
    {
        $temp_in  = tmpfile();
        $path_in  = stream_get_meta_data($temp_in)['uri'];
        $temp_out = tmpfile();
        $path_out = stream_get_meta_data($temp_out)['uri'];
        fwrite($temp_in, $text);

        try {
            $process = new Process([
                "java",
                "-Xmx500m",
                "-jar",
                "VnCoreNLP-1.1.1.jar",
                "-fin",
                $path_in,
                "-fout",
                $path_out,
                "-annotators",
                "wseg,pos",
            ], __DIR__);
            $process->run();
        } catch (\Exception $exception) {
        }
        fclose($temp_in);
        if (!$process->isSuccessful()) {
            fclose($temp_out);

            throw new ProcessFailedException($process);
        }
        $tokens = explode("\n", fread($temp_out, 1024));
        fclose($temp_out);
        foreach ($tokens as $key => $value) {
            if (!$value) {
                unset($tokens[$key]);

                continue;
            }
            $tokens[$key] = array_filter(explode("\t", $value), function ($k) {
                return $k == 1 || $k == 2;
            }, ARRAY_FILTER_USE_KEY);
        }

        return $tokens;
    }

    /**
     * find possion of valid chain
     * @param $needle
     * @param $text
     * @return array
     */
    protected function find_possition($needle, $text)
    {
        $lastPos   = 0;
        $positions = [];
        while (($lastPos = strpos($text, "$needle-", $lastPos)) !== false) {
            $remain_text       = substr($text, 0, $lastPos);
            $position_diff_key = 0;
            foreach (explode("-", $remain_text) as $value) {
                if (($words_count = strlen($value)) > 1) {
                    $position_diff_key += $words_count - 1;
                }
            }
            $types    = explode("-", $needle);
            $position = [];
            foreach ($types as $key => $type) {
                $position[] = $lastPos + $key - substr_count($remain_text, "-") - $position_diff_key;
            }
            $positions[] = $position;
            $lastPos++;
        }

        return $positions;
    }

    public function merrge_pharses(...$pharses)
    {
        $new_pharse = $pharses[0];
        foreach ($pharses as $pharse) {
            if ($pharse === $new_pharse) {
                continue;
            }
            if (strpos($pharse, $new_pharse) === false) {
                $new_pharse = "$new_pharse $pharse";
            }
        }

        return $new_pharse;
    }
}
