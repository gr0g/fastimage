<?php namespace FastImage\Transports;

/**
 * Class String
 *
 * This will fake the transport. Instead of inputting a URI, we import
 * a blob or a string. In this way, the transport doesn't happen.
 *
 * If we already have the blob in memory, this is useful
 *
 * @package FastImage\Transports
 * @author  Will Washburn <will@willwashburn.com>
 */
class InMemoryAdapter implements TransportInterface {

    /**
     * The curl handle
     * @var resource
     */
    protected $handle;

    /**
     * @var int
     */
    protected $strpos = 0;

    /**
     * The bits from the image
     *
     * @var string
     */
    protected $data;

    /**
     * @param $data
     */
    public function __construct($data) {
        $this->open($data);
    }

    /**
     * Opens the connection to the file
     *
     * @param $data
     *
     * @return $this;
     */
    public function open($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Doesn't do anything
     *
     * @return $this
     */
    public function close()
    {
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
                'There are no more characters left. Enough of the image '.
                'may not have been captured'
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
     * @throws Exception
     * @return $this
     */
    public function setTimeout($seconds)
    {
        throw new Exception('There is no timeout setting for this adapter');
    }

}