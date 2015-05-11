<?php namespace FastImage\Transports;


/**
 * Interface TransportInterface
 *
 * Opens up a stream to a file and gets the bits
 *
 * @package FastImage\Transports
 */
interface TransportInterface {


    /**
     * Set the timeout for the request
     *
     * @param $seconds
     *
     * @return mixed
     */
    public function setTimeOut($seconds);

    /**
     * Opens the connection to the file
     *
     * @param $url
     * @return $this
     */
    public function open($url);

    /**
     * Closes the connection to the file
     *
     * @return $this
     */
    public function close();

    /**
     * Reads more characters of the file
     * @param $characters
     *
     * @return mixed
     */
    public function read($characters);

    /**
     * Resets the pointer where we are reading in the file
     *
     * @return $this
     */
    public function resetReadPointer();

}