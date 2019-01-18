# Coupon Code Generator

This is a flexible coupon code generator.

A 'Coupon Code' is made up of letters and numbers grouped into 4 character
'parts'.  For example, a 3-part code might look like this:

  1K7Q-CTFM-LMTC
  
Coupon Codes are random codes which are easy for the recipient to type
accurately into a web form.  An example application might be to print a code on
a letter to a customer who would then enter the code as part of the
registration process for web access to their account.

## Installation

    composer require ryantxr/coupon-code

## Static Usage

```php
\Ryantxr\CouponCode\Generator::generate(); // generate a code
\Ryantxr\CouponCode\Generator::generate(true); // generate a lowercase code
$bytes = random_bytes(16);
\Ryantxr\CouponCode\Generator::generate(true, $bytes); // generate a lower case code and pass in the random bytes
\Ryantxr\CouponCode\Generator::init(['numberOfSegments' => 5, 'segmentLength' => 4])->generateCode(); // generate a code
```

## Use as an object

```php
$codeGenerator = new \Ryantxr\CouponCode\Generator();
$codeGenerator = new \Ryantxr\CouponCode\Generator(['numberOfSegments' => 5, 'segmentLength' => 4]);

$code = $codeGenerator->generateCode();
$code = $codeGenerator->generateCode(true); // generate a lowercase code
$code = $codeGenerator->generateCode(true, $randomBytes); // generate a lowercase code, passing in the random bytes
```

## See also

[perl](https://github.com/grantm/Algorithm-CouponCode) https://github.com/grantm/Algorithm-CouponCode

[NodeJS](https://www.npmjs.com/package/coupon-code) https://www.npmjs.com/package/coupon-code

[Ruby](https://rubygems.org/gems/coupon_code/versions/0.0.1) https://rubygems.org/gems/coupon_code/versions/0.0.1

[php](https://github.com/atelierdisko/coupon_code) https://github.com/atelierdisko/coupon_code
