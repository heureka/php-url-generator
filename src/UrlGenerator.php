<?php

namespace UrlGenerator;

class UrlGenerator
{
    /**
     * @const Array Keywords used in configuration
     */
    const CONFIG_KEYWORDS = [
        '@scheme',
        '@host',
        '@tld',
        '@path',
        '@query',
        '@fragment'
    ];

    const ALLOWED_URL_SCHEMES = [
        'http',
        'https'
    ];

    /**
     * @param array $params Global parameters
     * @param string $configFile Path to configuration file
     *
     * @throws UrlGeneratorException
     */
    public function __construct($configFile, $params=[])
    {
        if (! file_exists($configFile)) {
            throw new UrlGeneratorException("Configuration file not found '" . $configFile . '"');
        }

        $this->params = $params;
        $this->config = json_decode(file_get_contents($configFile), true);
    }

    /**
     * Evaluates condition like "{variable}=value" if variable equals value.
     *
     * @param string $condition
     * @param $params
     *
     * @return bool
     */
    private function evaluateTemplateCondition($condition, $params)
    {
        if (! preg_match('/^\{(.+)\}=(.+)$/', $condition, $matches)) {
            return false; // Not a template condition
        }
        $name = $matches[1];
        $value = $matches[2];

        if (! isset($params[$name])) {
            return false;
        }

        return (string)$params[$name] === $value;
    }

    /**
     * Shift path by one node ('heureka.category.index' -> 'category.index')
     *
     * @param array $path (like ['heureka', 'category', 'index'] means 'heureka.category.index')
     *
     * @return array
     */
    private function shiftPath($path)
    {
        array_shift($path);
        return $path;
    }

    /**
     * Get url parts configuration recursively for given path
     *
     * @param array $path (like ['heureka', 'category', 'index'] means 'heureka.category.index')
     * @param array $params like ['category' => 'auto-moto', 'page_index' => 10, 'lang' => 'sk']
     * @param array $config main configuration array
     *
     * @return array containing urlParts like ['@sheme' => 'https, '@host' => 'www.heureka.{tld}', '@path' => 'search' ...]
     */
    private function getUrlParts($path, $params, $config)
    {
        $urlParts = [];

        foreach ($config as $key => $value) {
            if (in_array($key, self::CONFIG_KEYWORDS)) {
                $urlParts[$key] = $value;
            }

            if (isset($path[0]) && $key === $path[0]) {
                $urlParts = array_merge($urlParts, $this->getUrlParts($this->shiftPath($path), $params, $config[$key]));
            }

            if ($this->evaluateTemplateCondition($key, $params)) {
                $urlParts = array_merge($urlParts, $this->getUrlParts($path, $params, $config[$key]));
            }
        }

        return $urlParts;
    }

    /**
     * Parse path into array from pathString
     *
     * @param string $pathString like 'heureka.index'
     *
     * @return array like ['heureka', 'index']
     */
    private function parsePath($pathString)
    {
        return explode('.', $pathString);
    }

    /**
     * Join URL defined by configuration
     *
     * @param array  $urlParts    like ['@sheme' => 'https, '@host' => 'www.heureka.{tld}', '@path' => 'search' ...]
     * @param string $queryString like 'q=automobily&offset=2&limit=10'
     *
     * @return string Compiled URL like 'https://www.heureka.cz/search/?q=automobily&offset=2&limit=1'
     * @throws UrlGeneratorException
     */
    private function urlJoin($urlParts, $queryString = "")
    {
        if (! isset($urlParts['@scheme'])){
            throw new UrlGeneratorException('Missing required property @scheme');
        }

        $scheme = $urlParts['@scheme'];
        if (! in_array($scheme, self::ALLOWED_URL_SCHEMES)) {
            throw new UrlGeneratorException('Unsupported URL scheme: "'. $scheme . '"');
        }

        if (! isset($urlParts['@host'])){
            throw new UrlGeneratorException('Missing required property @host');
        }
        $host = rtrim($urlParts['@host'], '/');

        $url = "$scheme://$host";

        if (isset($urlParts['@path'])) {
            $url .= '/' . ltrim($urlParts['@path'], '/');
        }

        if ($queryString !== "") {
            if (strpos($urlParts['@path'], '?') !== false) {
                $url .= '&' . $queryString;
            } else {
                $url .= '?' . $queryString;
            }
        }

        if (isset($urlParts['@fragment'])) {
            $url .= '#' . ltrim($urlParts['@fragment'], '#');
        }

        return $url;
    }

    /**
     * Get template params from URL template
     * @param string $urlTemplate like 'http://{category_name}.heureka.{lang}/'
     *
     * @return array|null like ['category_name', 'lang']
     */
    private function getTemplateParams($urlTemplate)
    {
        preg_match_all('/\{(.+)\}/U', $urlTemplate, $matches);
        return $matches[1];
    }

    /**
     * Compile and sanitize URL template
     * !Note that $params are deleted when used!
     *
     * @param string $url URL Template like 'http://{category_name}.heureka.{lang}/'
     * @param array $params like ['lang' => 'cz', ...]
     *
     * @return string Compiled template like 'http://auto-moto.heureka.cz/'
     *
     * @throws UrlGeneratorException for missing parameters
     */
    private function compileUrlTemplate($url, &$params)
    {
        $templateParams = $this->getTemplateParams($url);

        foreach ($templateParams as $paramName) {
            if (! isset($params[$paramName])) {
                throw new UrlGeneratorException('Missing mandatory parameter: "' . $paramName . '"');
            }

            $url = str_replace('{' . $paramName . '}', $params[$paramName], $url);
        }

        return $url;
    }

    /**
     * Compile query string from parameters using queryConfig
     * !Note that $params are deleted when used!
     *
     * @param array $queryConfig configuration like ['offset' => 'o', 'limit' => 'l']
     * @param array $params like ['offset' => 2, 'limit' => 10]
     *
     * @return string compiled query like 'o=2&l=10'
     */
    private function compileQueryString($queryConfig, &$params)
    {
        $queryParams = [];
        foreach ($queryConfig as $paramName => $paramsKey) {
            if (isset($params[$paramName])) {
                $queryParams[$paramsKey] = $params[$paramName];
            }
        }

        return http_build_query($queryParams);
    }

    /**
     * Return compiled URL from given $pathString using configuration
     *
     * See Readme.md for more information
     *
     * @param string $pathString like 'heureka.category.index'
     * @param array  $params     like ['category' => 'auto-moto', 'page_index' => 10, 'lang' => 'sk']
     *
     * @return string compiled URL like 'https://auto-moto.heureka.sk?page=10'
     * @throws UrlGeneratorException
     */
    public function getUrl($pathString, $params=[])
    {
        $path = $this->parsePath($pathString);

        $params = array_merge($this->params, $params);
        $config = $this->getUrlParts($path, $params, $this->config);

        if (isset($config['@query'])) {
            $queryString = $this->compileQueryString($config['@query'], $params);
        } else {
            $queryString = '';
        }

        $urlTemplate = $this->urlJoin($config, $queryString);
        return $this->compileUrlTemplate($urlTemplate, $params);
    }
}

class UrlGeneratorException extends \Exception {}
