<?php

/**
 * FastImage - Because sometimes you just want the size!
 * Based on the Ruby Implementation by Steven Sykes (https://github.com/sdsykes/fastimage)
 *
 * Copyright (c) 2012 Tom Moor
 * Tom Moor, http://tommoor.com
 *
 * MIT Licensed
 * @version 0.2
 */
class FastImage
{
    /**
     * @var FastImage\Transports\TransportInterface
     */
    protected $transport;
    /**
     * @var string - The uri of the image we are loading
     */
    private $uri;
    /**
     * @var string - The type of image we've decided it is
     */
    private $type;

    /**
     * Construct
     *
     * @param null $uri
     * @param null $transport
     */
    public function __construct($uri = null, $transport = null)
    {
        $this->transport = is_null($transport) ? new FastImage\Transports\FileStreamAdapter : $transport;

        if ($uri) {
            $this->transport->open($uri);
        }
    }

    /**
     *
     * @param array $uris
     * @param null  $transport
     *
     * @return \FastImage
     */
    public static function batch(array $uris, $transport = null)
    {
        $transport = is_null($transport) ? new \FastImage\Transports\CurlAdapter : $transport;

        $responses = $transport->batch($uris);

        $instances = array();

        foreach ($responses as $uri => $payload) {
            $instances[$uri] = new self(null, new \FastImage\Transports\InMemoryAdapter($payload));
        }

        return $instances;
    }

    /**
     * Load a new image
     *
     * @param $uri
     */
    public function load($uri)
	{
        $this->transport->close();

        $this->uri = $uri;
		$this->transport->open($uri);
	}

    /**
     * Gets the size of the image in the uri
     *
     * @return array|bool
     */
    public function getSize()
	{
        $this->transport->resetReadPointer();

        if (!$this->type)
        {
            $this->type = 'jpeg';
        }

		if ($this->getType())
		{
			return array_values($this->parseSize());
		}
		
		return false;
	}

    /**
     * Reads and returns the type of the image
     *
     * @return bool|string
     */
    public function getType()
	{
        $this->transport->resetReadPointer();

		if (!$this->type)
		{
			switch ($this->getChars(2))
			{
				case "BM":
					return $this->type = 'bmp';
				case "GI":
					return $this->type = 'gif';
				case chr(0xFF).chr(0xd8):
					return $this->type = 'jpeg';
				case chr(0x89).'P':
					return $this->type = 'png';
				default:
					return false;
			}
		}

		return $this->type;
	}

    /**
     * @return array|bool|null
     */
    private function parseSize()
	{	
		$this->transport->resetReadPointer();

		switch ($this->type)
		{
			case 'png':
				return $this->parseSizeForPNG();
			case 'gif':
				return $this->parseSizeForGIF();
			case 'bmp':
				return $this->parseSizeForBMP();
			case 'jpeg':
				return $this->parseSizeForJPEG();	    
		}
		
		return null;
	}

    /**
     * @return array
     */
    private function parseSizeForPNG()
	{
		$chars = $this->getChars(25);

		return unpack("N*", substr($chars, 16, 8));
	}

    /**
     * @return array
     */
    private function parseSizeForGIF()
	{
		$chars = $this->getChars(11);

		return unpack("S*", substr($chars, 6, 4));
	}

    /**
     * @return array
     */
    private function parseSizeForBMP()
	{
		$chars = $this->getChars(29);
	 	$chars = substr($chars, 14, 14);
		$type = unpack('C', $chars);
		
		return (reset($type) == 40) ? unpack('L*', substr($chars, 4)) : unpack('L*', substr($chars, 4, 8));
	}

    /**
     * @return array|bool
     */
    private function parseSizeForJPEG()
	{
		$state = null;

		while (true)
		{
			switch ($state)
			{
				default:
					$this->getChars(2);
					$state = 'started';
					break;
					
				case 'started':
					$b = $this->getByte();
					if ($b === false) return false;
					
					$state = $b == 0xFF ? 'sof' : 'started';
					break;
					
				case 'sof':
					$b = $this->getByte();
					if (in_array($b, range(0xe0, 0xef)))
					{
						$state = 'skipframe';
					}
					elseif (in_array($b, array_merge(range(0xC0,0xC3), range(0xC5,0xC7), range(0xC9,0xCB), range(0xCD,0xCF))))
					{
						$state = 'readsize';
					}
					elseif ($b == 0xFF)
					{
						$state = 'sof';
					}
					else
					{
						$state = 'skipframe';
					}
					break;
					
				case 'skipframe':
					$skip = $this->readInt($this->getChars(2)) - 2;
					$state = 'doskip';
					break;
					
				case 'doskip':
					$this->getChars($skip);
					$state = 'started';
					break;
					
				case 'readsize':
					$c = $this->getChars(7);
			        
					return array($this->readInt(substr($c, 5, 2)), $this->readInt(substr($c, 3, 2)));
			}
		}

        return false;
	}

    /**
     * @param $n
     *
     * @return bool|string
     */
    private function getChars($n)
	{
        return $this->transport->read($n);
	}

    /**
     * @return mixed
     */
    private function getByte()
	{
		$c = $this->getChars(1);
		$b = unpack("C", $c);
		
		return reset($b);
	}

    /**
     * @param $str
     *
     * @return int
     */
    private function readInt($str)
	{
		$size = unpack("C*", $str);
		
	    	return ($size[1] << 8) + $size[2];
	}

    /**
     * Closes the connection
     */
    public function __destruct()
	{
		$this->transport->close();
	}
}
