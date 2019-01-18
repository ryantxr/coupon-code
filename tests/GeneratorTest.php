<?php 
use PHPUnit\Framework\TestCase;

/**
*  Corresponding Class to test YourClass class
*
*  For each class in your library, there should be a corresponding Unit-Test for it
*  Unit-Tests should be as much as possible independent from other test going on.
*
*  @author yourname
*/
class GeneratorTest extends TestCase
{
    
    /**
     * Just check if the YourClass has no syntax error 
     *
     * This is just a simple check to make sure your library has no syntax error. This helps you troubleshoot
     * any typo before you even use this library in a real project.
     * @test
     */
    public function testIsThereAnySyntaxError()
    {
        $obj = new Ryantxr\CouponCode\Generator;
        $this->assertTrue(is_object($obj));
        unset($obj);
    }
  
    /**
     * Test the string permuter
     * @test
     */
    public function testStringPermuter()
    {
        $obj = new Ryantxr\CouponCode\Generator;
        $strings = $obj->permute('abcd');
        // print_r($strings);
        // Each entry must exist once and only once in the array
        foreach($strings as $str) {
            $keys = array_keys($strings, $str);
            $this->assertEquals(1, count($keys));
        }
        // $this->assertTrue($obj->method1("hey") == 'Hello World');

        $strings = $obj->permute('abcdefa');
        // print_r($strings);
        // Each entry must exist once and only once in the array
        foreach($strings as $str) {
            $keys = array_keys($strings, $str);
            $this->assertEquals(1, count($keys));
        }
        // $this->assertTrue($obj->method1("hey") == 'Hello World');
        unset($obj);
    } 

    /**
     * @test
     */
    public function testGenerator()
    {
        $obj = new Ryantxr\CouponCode\Generator;
        $code = $obj->generateCode();
        echo "\n'";
        print_r($code);
        echo "'";

        $this->assertTrue(!empty($code));
    }
}
