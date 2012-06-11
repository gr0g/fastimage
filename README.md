# FastImage

FastImage finds the dimensions or filetype of a remote image file given its uri by fetching as little as needed, based on the excellent [Ruby implementation by Stephen Sykes](https://github.com/sdsykes/fastimage).


## Usage

		$image = new Fastimage($uri);
		list($width, $height) = $image->getSize();
		echo "dimensions: " . $width . "x" . $height;

		$image = new Fastimage();
		$image->load($uri);
		$type $image->getType();
		echo "filetype: " . $type;

## References

* https://github.com/sdsykes/fastimage
* http://pennysmalls.com/find-jpeg-dimensions-fast-in-pure-ruby-no-ima
* http://snippets.dzone.com/posts/show/805
* http://www.anttikupila.com/flash/getting-jpg-dimensions-with-as3-without-loading-the-entire-file/
* http://imagesize.rubyforge.org/


## License

FastImage is released under the MIT license. It is simple and easy to understand and places almost no restrictions on what you can do with the software. [More Information](http://en.wikipedia.org/wiki/MIT_License)


## Download

Releases are available for download from
[GitHub](http://github.com/tommoor/fastimage/downloads).