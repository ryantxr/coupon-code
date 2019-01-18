<?php namespace Ryantxr\CouponCode;

/**
 * @author Ryan Teixeira
 * @see https://github.com/grantm/Algorithm-CouponCode
 * @see https://github.com/atelierdisko/coupon_code
 * @see https://www.npmjs.com/package/coupon-code
 * @see https://rubygems.org/gems/coupon_code/versions/0.0.1
 * Usage:
 * 	Static
 * 		\Ryantxr\CouponCode\Generator::generate(); // generate a code
 * 		\Ryantxr\CouponCode\Generator::generate(true); // generate a lowercase code
 * 		\Ryantxr\CouponCode\Generator::generate(true, $bytes); // generate a lower case code and pass in the random bytes
 * 		\Ryantxr\CouponCode\Generator::init(['numberOfSegments' => 5, 'segmentLength' => 4])->generateCode(); // generate a code
 * 
 * 
 *  As an object
 * 		$codeGenerator = new \Ryantxr\CouponCode\Generator();
 * 		$codeGenerator = new \Ryantxr\CouponCode\Generator(['numberOfSegments' => 5, 'segmentLength' => 4]);
 * 
 * 		$code = $codeGenerator->generate();
 * 		$code = $codeGenerator->generate(true); // generate a lowercase code
 * 		$code = $codeGenerator->generate(true, $randomBytes); // generate a lowercase code, passing in the random bytes
 */
class Generator
{
	/**
	 * Number of segments of the code.
	 *
	 * @var integer
	 */
	protected $numberOfSegments = 3;
	
	/**
	 * Length of each segment.
	 *
	 * @var integer
	 */
	protected $segmentLength = 4;
	
	/**
	 * Characters used to generate codes
	 * @var array
	 */
	protected $characters = [
		'0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
		'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K',
		'L', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'V', 'W',
		'X', 'Y'
	];
	
	/**
	 * ROT13 encoded list of bad words.
	 *
	 * @var array
	 */
	protected $badWordList = [
		'SHPX', 'PHAG', 'JNAX', 'JNAT', 'CVFF', 'PBPX', 'FUVG', 'GJNG', 'GVGF', 'SNEG', 'URYY', 'ZHSS', 'QVPX', 'XABO',
        'NEFR', 'FUNT', 'GBFF', 'FYHG', 'GHEQ', 'FYNT', 'PENC', 'CBBC', 'OHGG', 'SRPX', 'OBBO', 'WVFZ', 'WVMM', 'CUNG',
	];
	
	protected static $instance;

	/**
	 * Generate a code
	 */
	public static function generate($toLowerCase=false, $randomBytes=null)
	{
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance->generateCode($toLowerCase, $randomBytes);
	}

	/**
	 * validate a coupon code
	 */
	public static function validate($codeString)
	{
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance->validateCode($codeString);
	}

	/**
	 * initialize the singleton before using
	 */
	public static function init($config=[])
	{
		if ( ! self::$instance ) {
			self::$instance = new self($config);
		} else {
			if ( ! empty($config['numberOfSegments']) ) {
				self::$instance->numberOfSegments = $config['numberOfSegments'];
			}
			if ( ! empty($config['segmentLength']) ) {
				self::$instance->segmentLength = $config['segmentLength'];
			}
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @param array $config ['numberOfSegments' => X, 'segmentLength' => Y].
	 */
	public function __construct(array $config = []) 
	{
		$config = array_merge(['numberOfSegments' => 3, 'segmentLength' => 4], $config);
		$this->numberOfSegments = $config['numberOfSegments'] ?? null;
		$this->segmentLength = $config['segmentLength'] ?? null;
		// Keep this around so we only do it once.
		$this->indexedChars = array_flip($this->characters);
	}
	
	/**
	 * Generates a coupon code using the format `XXXX-XXXX-XXXX`.
	 *
	 * The 4th character of each part is a checkdigit.
	 *
	 * Not all letters and numbers are used, so if a person enters the letter 'O' we
	 * can automatically correct it to the digit '0' (similarly for I => 1, S => 5, Z
	 * => 2).
	 *
	 * The code generation algorithm avoids 'undesirable' codes. For example any code
	 * in which transposed characters happen to result in a valid checkdigit will be
	 * skipped.  Any generated part which happens to spell an 'inappropriate' 4-letter
	 * word (e.g.: 'P00P') will also be skipped.
	 * @param bool $toLowerCase flag to convert code to lowercase
	 * @param string $random Allows to directly support a plaintext i.e. for testing.
	 * @return string Dash separated and normalized code.
	 */
	public function generateCode($toLowerCase = false, $random = null)
	{
		$results = [];

		$randomBytes = $random ?? random_bytes(16);
		$plaintext = $this->compileBytesToCodeString($randomBytes);

		$segmentIndex = 0;
		$attempt = 0;
		while ( count($results) < $this->numberOfSegments ) {
			$segmentCandidate = substr($plaintext, $attempt * $this->segmentLength, $this->segmentLength - 1);

			if ( ! $segmentCandidate || strlen($segmentCandidate) !== $this->segmentLength - 1 ) {
				throw new \Exception('Ran out of plaintext.');
			}
			$segmentCandidate .= $this->checkdigitAlgorithm1($segmentCandidate, $segmentIndex + 1);

			// Reject if this is a bad word or 
			$attempt++;
			if ( $this->isBadWord($segmentCandidate) ) {
				// echo "Bad word Skipping segment {$segmentCandidate}\n";
				continue;
			} elseif ( $this->isSegmentValidWhenSwapped($segmentCandidate, $segmentIndex + 1) ) {
				// echo "Swappable Skipping segment {$segmentCandidate}\n";
				continue;
			}
			$segmentIndex++;

			$results[] = $segmentCandidate;
		}
		$result = implode('-', $results);
		if ( $toLowerCase === true ) {
			return strtolower($result);
		}
		return $result;
	}
	
	/**
	 * Validates given code. Codes are not case sensitive and
	 * certain letters i.e. `O` are converted to digit equivalents
	 * i.e. `0`.
	 *
	 * @param string $code string. (might not be properly formatted)
	 * @return boolean
	 */
	public function validateCode($code) 
	{
		$code = $this->normalize($code, ['clean' => true, 'case' => true]);

		// The entered code doesn't have dashes?
		if ( strlen($code) !== ($this->numberOfSegments * $this->segmentLength) ) {
			return false;
		}
		$segments = str_split($code, $this->segmentLength);

		foreach ($segments as $index => $segment) {
			$expectedCheckdigit = substr($segment, -1);
			$segmentData = substr($segment, 0, strlen($segment) - 1);
			$calculatedCheckdigit = $this->checkdigitAlgorithm1($segmentData, $index + 1);

			if ( $calculatedCheckdigit !== $expectedCheckdigit ) {
				return false;
			}
		}
		return true;
	}
	
	/*
	sub _checkdigit_alg_1 {
		my($data, $pos) = @_;
		my @char = split //, $data;
	
		my $check = $pos;
		foreach my $i (0..2) {
			my $k = index($sym_str, $char[$i]);
			$check = $check * 19 + $k;
		}
		return $sym[ $check % 31 ];
	}
	*/

	/**
	 * Implements the checkdigit algorithm #1 as used by the original library.
	 *
	 * @param string $value segment piece without the checkdigit.
	 * @param integer $segmentIndex which segment in the code.
	 * @return string checkdigit
	 */
	protected function checkdigitAlgorithm1($segmentData, $segmentIndex)
	{
		$result = $segmentIndex;

		foreach (str_split($segmentData) as $char) {
			$result = $result * 19 + $this->indexedChars[$char];
		}
		// map the calculated character into a valid character
		return $this->characters[$result % (count($this->characters))];
	}

	/**
	 * Verifies that a given value is a bad word.
	 *
	 * @param string $value
	 * @return boolean
	 */
	protected function isBadWord($word)
	{
		return in_array(str_rot13($word), $this->badWordList);
	}

	/*
	sub _valid_when_swapped {
		my($orig, $pos) = @_;

		my($a, $b, $c, $d) = split //, $orig;
		foreach my $code (
			"$b$a$c$d",
			"$a$c$b$d",
			"$a$b$d$c",
		) {
			next if $code eq $orig;
			if(_checkdigit_alg_1(substr($code, 0, 3), $pos) eq substr($code, 3, 1)) {
				return 1;
			}
		}
		return 0;
	}*/

	/**
	 * Is the segment valid if the characters are swapped around?
	 * If so, return true. (Not good)
	 *
	 * @param string $segmentData
	 * @param string $segmentIndex
	 * @return boolean
	 * Not using this because it causes too many segments to be discarded.	
	 */
	protected function isSegmentValidWhenSwapped($segment, $segmentIndex)
	{
		// $strings = $this->permuteString($segment, 0, strlen($segment));
		$strings = $this->swappableVariations($segment);
		$valid = false;
		foreach ( $strings as $string ) {
			if ( $string == $segment ) {
				continue;
			}
			$expectedCheckdigit = substr($string, -1);
			$segmentData = substr($string, 0, strlen($string) - 1);
			$checkdigit = $this->checkdigitAlgorithm1($segmentData, $segmentIndex);
			$valid = $expectedCheckdigit == $checkdigit;
			// printf("%s == %s\n", $expectedCheckdigit, $checkdigit);
			if ( $valid ) {
				return true;
			}
		}
		return false;
	}

	public function swappableVariations($string)
	{
		$swappable = [];
		for ( $i=0; $i<strlen($string)-1; $i++ ) {
			$temp = $string;
			$this->swapChars($temp, $i, $i+1);
			$swappable[] = $temp;
		}
		return $swappable;
	}

	public function permute($string)
	{
		return $this->permuteString($string, 0, strlen($string));
	}

	private function permuteString($str, $i, $n, $accumulator=null)
	{
		if ( empty($accumulator) ) {
			$accumulator = [];
		}
		if ( $i == $n ) {
			if ( ! in_array($str, $accumulator) ) {
				$accumulator[] = $str;
			}
		} else {
			for ($j = $i; $j < $n; $j++) {
				$prevStr = $str;
				$this->swapChars($str, $i, $j);
				$accumulator = $this->permuteString($str, $i+1, $n, $accumulator);
				// $str = $prevStr; // put it back
				$this->swapChars($str, $i, $j); // backtrack. (avoid the extra function call)
			}
		}
		return $accumulator;
	 }
	 
	// function to swap the char at pos $i and $j of $str.
	private function swapChars(&$str, $i, $j)
	{
		$temp = $str[$i];
		$str[$i] = $str[$j];
		$str[$j] = $temp;
	}

	/**
	 * Normalizes a given code using dash separators.
	 *
	 * @param string $string
	 * @return string
	 */
	public function normalize($string)
	{
		$string = $this->normalize($string, ['clean' => true, 'case' => true]);
		return implode('-', str_split($string, $this->segmentLength));
	}
	
	/**
	 * compileBytesToCodeString
	 *
	 * @param string $string
	 * @return string
	 */
	protected function compileBytesToCodeString($string)
	{
		$characters = $this->characters;

		$result = array_map(function($value) use ($characters) {
			return $characters[ord($value) & (count($characters) - 1)];
		}, str_split(hash('sha512', $string)));
		// echo "Number of result chars = " . count($result) . "\n";
		return implode('', $result);
	}

	/**
	 * Internal method to normalize code strings
	 * Does character replacements and removes unwanted characters.
	 * @param string $codeString
	 * @param array $options ['case' => true, 'clean' => true] convert to uppercase, remove invalid chars
	 * @return string
	 */
	protected function normalizeCode(string $codeString, array $options = [])
	{
		$options = array_merge(['clean' => false, 'case' => false], $options);
		
		if ( $options['case'] ) {
			$codeString = strtoupper($codeString);
		}
		// Always do this
		$codeString = str_replace(['I', 'O', 'S', 'Z'], ['1', '0', '5', '2'], $codeString);

		if ( $options['clean'] ) {
			$codeString = preg_replace('/[^0-9A-Z]+/', '', $codeString);
		}
		return $codeString;
	}
}
