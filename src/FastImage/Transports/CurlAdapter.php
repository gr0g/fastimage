<?php namespace FastImage\Transports;

/**
 * Class CurlAdapter
 *
 * Not as fast since we just get the first 32768 bytes regardless
 * of image, but a little bit more stable
 *
 * @package FastImage\Transports
 * @author  Will Washburn <will@willwashburn.com>
 */
class CurlAdapter implements TransportInterface {

    /**
     * The curl handle
     *
     * @var array  curl resources
     */
    protected $handles = array();
    /**
     * @var int
     */
    protected $strpos = 0;

    /**
     * The string from the file
     *
     * @var string
     */
    protected $str = '';

    /**
     * The bits from the image
     *
     * @var string
     */
    protected $data;

    /**
     * @var int
     */
    protected $timeout = 10;

    /**
     * How much of the image to curl
     * @var int
     */
    protected $range = 32768;

    /**
     * @param int $range
     */
    public function __construct($range = null)
    {
        $this->range = is_null($range) ? $this->range : $range;
    }

    /**
     * Opens the connection to the file
     *
     * @param $url
     *
     * @throws Exception
     *
     * @return $this;
     */
    public function open($url)
    {

        $this->handles[$url] = $this->getCurlHandle($url);
        $data                = curl_exec($this->handles[$url]);

        if (curl_errno($this->handles[$url])) {
            throw new Exception(curl_error($this->handles[$url]), curl_errno($this->handles[$url]));
        }

        $this->data = $data;

        return $this;
    }

    /**
     * @param $uris
     *
     * @throws Exception
     * @return array
     */
    public function batch($uris)
    {

        $multi = curl_multi_init();

        foreach ($uris as $uri) {
            $this->handles[$uri] = $this->getCurlHandle($uri);
            $code = curl_multi_add_handle($multi,$this->handles[$uri]);

            if($code != CURLM_OK) {
                throw new Exception("Curl handle for $uri could not be added");
            }
        }

        do {
            while (($mrc = curl_multi_exec($multi, $active)) == CURLM_CALL_MULTI_PERFORM);
            if ($mrc != CURLM_OK && $mrc != CURLM_CALL_MULTI_PERFORM) {
                throw new Exception("Curl error code: $mrc");
            }

            if ($active && curl_multi_select($multi) === -1) {
                // Perform a usleep if a select returns -1.
                // See: https://bugs.php.net/bug.php?id=61141
                usleep(250);
            }
        } while ($active);

        $results = array();

        foreach ($uris as $uri) {
            $results[$uri] = curl_multi_getcontent($this->handles[$uri]);
        }

        return $results;
    }

    /**
     * Closes the connection to the file
     *
     * @return $this
     */
    public function close()
    {
        foreach ($this->handles as $handle) {
            curl_close($handle);
        }

        return $this;
    }

    /**
     * Reads more characters of the file
     *
     * @param $characters
     *
     * @throws Exception
     * @throws \InvalidArgumentException
     * @return mixed
     */
    public function read($characters)
    {
        if (!is_numeric($characters)) {
            throw new \InvalidArgumentException('"Read" expects a number');
        }

        $response = null;

        if ($this->strpos > strlen($this->data) ){

            throw new Exception(
                'Not enough of the file was curled.' .
                'Try increasing the range in the curl request'
            );

        }

        $result = substr($this->data, $this->strpos, $characters);
        $this->strpos += $characters;

        return $result;
    }

    /**
     * Resets the pointer where we are reading in the file
     *
     * @return mixed
     */
    public function resetReadPointer()
    {
        $this->strpos = 0;
    }

    /**
     * @param $seconds
     *
     * @return $this
     */
    public function setTimeout($seconds)
    {
        $this->timeout = floatval($seconds);

        return $this;
    }

    /**
     * Get the curl headers
     *
     * @param int $range_start What part of the file do we want to start with
     *
     * @return array
     */
    protected function getHeaders($range_start = 0)
    {

        $range_end = $range_start + $this->range;

        return array(
            "Range: bytes=$range_start-$range_end",
            "Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
            "Cache-Control: max-age=0",
            "Connection: keep-alive",
            "Keep-Alive: 300",
            "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "Accept-Language: en-us,en;q=0.5",
            "Pragma: ", // browsers keep this blank.
        );
    }

    /**
     * Create a curl resource
     *
     * @param $url
     *
     * @return resource
     */
    protected function getCurlHandle($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $this->getHeaders());
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        return $handle;
    }

}
