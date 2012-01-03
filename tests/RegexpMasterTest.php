<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\RegexpMaster;

include_once __DIR__ . '/Test.php';
include_once __DIR__ . '/../src/RegexpMaster.php';

/**
* @covers s9e\TextFormatter\RegexpMaster
*/
class RegexpMasterTest extends Test
{
	protected $rm;

	public function setUp()
	{
		$this->rm = new RegexpMaster;
	}

	/**
	* @expectedException RuntimeException
	* @expectedExceptionMessage Invalid UTF-8 string
	* @testdox buildRegexpFromList() throws a RuntimeException if any word is not legal UTF-8
	*/
	public function testUTF8Exception()
	{
		$this->rm->buildRegexpFromList(array("\xff\xff"));
	}

	/**
	* @testdox buildRegexpFromList() merges heads
	*/
	public function testOptimizesRegexpByMergingHeads()
	{
		$this->assertSame(
			'ap(?:ple|ril)',
			$this->rm->buildRegexpFromList(array('apple', 'april'))
		);
	}

	/**
	* @depends testOptimizesRegexpByMergingHeads
	* @testdox buildRegexpFromList() merges tails in a character class
	*/
	public function testOptimizesRegexpByUsingCharacterClasses()
	{
		$this->assertSame(
			'ba[rz]',
			$this->rm->buildRegexpFromList(array('bar', 'baz'))
		);
	}

	/**
	* @depends testOptimizesRegexpByMergingHeads
	* @testdox buildRegexpFromList() uses a ? quantifier at the end of an expression if applicable
	*/
	public function testOptimizesRegexpByUsingQuantifier()
	{
		$this->assertSame(
			'fool?',
			$this->rm->buildRegexpFromList(array('foo', 'fool'))
		);
	}

	/**
	* @depends testOptimizesRegexpByMergingHeads
	* @testdox buildRegexpFromList() optimizes the tail of an expression if a subpattern ends with .*?
	*/
	public function testOptimizesRegexpThatUsesWildcards()
	{
		$this->assertSame(
			'apple.*?',
			$this->rm->buildRegexpFromList(
				array('apple*', 'applepie'),
				array('specialChars' => array('*' => '.*?'))
			)
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingCharacterClasses
	* @testdox buildRegexpFromList() correctly escapes parentheses in words
	*/
	public function testOptimizesRegexpThatUsesParentheses()
	{
		$this->assertSame(
			'\\:[\\(\\)]',
			$this->rm->buildRegexpFromList(array(':)', ':('))
		);
	}

	/**
	* @testdox buildRegexpFromList() generates a lookahead assertion at the start of the regexp
	*/
	public function testOptimizesRegexpByUsingLookaheadAssertion()
	{
		$this->assertSame(
			'(?=[bf])(?:bar|foo)',
			$this->rm->buildRegexpFromList(array('foo', 'bar'))
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingLookaheadAssertion
	* @testdox buildRegexpFromList() correctly escapes special characters in the lookahead assertion
	*/
	public function testOptimizesRegexpByUsingLookaheadAssertionEvenWithEscapedCharacters()
	{
		$this->assertSame(
			'(?=[\\*\\\\])(?:\\*foo|\\\\bar)',
			$this->rm->buildRegexpFromList(array('*foo', '\\bar'))
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingLookaheadAssertion
	* @testdox buildRegexpFromList() does not use a lookahead assertion if any word starts with a special sequence
	*/
	public function testDoesNotOptimizeRegexpByUsingLookaheadAssertionIfAnyWordStartsWithASpecialSequence()
	{
		$this->assertSame(
			'(?:.|bar)',

			// Here, we build a regexp that matches one single character or the word "bar"
			// The joker ? is replaced by the special character .
			$this->rm->buildRegexpFromList(
				array('?', 'bar'),
				array('specialChars' => array('?' => '.'))
			)
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingCharacterClasses
	* @depends testOptimizesRegexpByUsingLookaheadAssertion
	* @testdox buildRegexpFromList() does not use a lookahead assertion if no word is longer than one character
	*/
	public function testDoesNotOptimizeRegexpByUsingLookaheadAssertionIfAllWordsHaveOnly1Character()
	{
		$this->assertSame(
			'[ab]',
			$this->rm->buildRegexpFromList(array('a', 'b'))
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingCharacterClasses
	* @depends testOptimizesRegexpByUsingLookaheadAssertion
	* @testdox buildRegexpFromList() does not use a lookahead assertion if no word is longer than one Unicode character
	*/
	public function testDoesNotOptimizeRegexpByUsingLookaheadAssertionIfAllWordsHaveOnly1UnicodeCharacter()
	{
		$this->assertSame(
			'[♠♣♥♦]',
			$this->rm->buildRegexpFromList(array('♠', '♣', '♥', '♦'))
		);
	}

	/**
	* @testdox The lookahead assertion optimization can be disabled with the disableLookahead option
	* @testdox buildRegexpFromList() does not use a lookahead assertion if the "disableLookahead" option is true
	*/
	public function testDisableLookaheadAssertion()
	{
		$this->assertSame(
			'(?:bar|foo)',
			$this->rm->buildRegexpFromList(
				array('foo', 'bar'),
				array('disableLookahead' => true)
			)
		);
	}

	/**
	* @testdox parseRegexp() can parse plain regexps
	*/
	public function testCanParseRegexps1()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => 'foo',
				'tokens'    => array()
			),
			$this->rm->parseRegexp(
				'#foo#'
			)
		);
	}

	/**
	* @testdox parseRegexp() throws a RuntimeException if delimiters can't be parsed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not parse regexp delimiters
	*/
	public function testInvalidRegexpsException1()
	{
		$this->rm->parseRegexp('#foo/iD');
	}

	/**
	* @testdox parseRegexp() parses pattern modifiers
	*/
	public function testCanParseRegexps2()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => 'iD',
				'regexp'    => 'foo',
				'tokens'    => array()
			),
			$this->rm->parseRegexp(
				'#foo#iD'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses character classes
	*/
	public function testCanParseRegexps3()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 5,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#[a-z]#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses character classes with quantifiers
	*/
	public function testCanParseRegexps4()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]+',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 6,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => '+'
					)
				)
			),
			$this->rm->parseRegexp(
				'#[a-z]+#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses character classes that end with an escaped ]
	*/
	public function testCanParseRegexps5()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z\\]]',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'characterClass',
						'content' => 'a-z\\]',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#[a-z\\]]#'
			)
		);
	}

	/**
	* @testdox parseRegexp() throws a RuntimeException if a character class is not properly closed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching bracket from pos 0
	*/
	public function testInvalidRegexpsException2()
	{
		$this->rm->parseRegexp('#[a-z)#');
	}

	/**
	* @testdox parseRegexp() correctly parses escaped brackets
	*/
	public function testCanParseRegexps6()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\[x\\]',
				'tokens'    => array()
			),
			$this->rm->parseRegexp(
				'#\\[x\\]#'
			)
		);
	}

	/**
	* @testdox parseRegexp() correctly parses escaped parentheses
	*/
	public function testCanParseRegexps7()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\(x\\)',
				'tokens'    => array()
			),
			$this->rm->parseRegexp(
				'#\\(x\\)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses non-capturing subpatterns
	*/
	public function testCanParseRegexps8()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?:x+)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses non-capturing subpatterns with atomic grouping
	*/
	public function testCanParseRegexps8b()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'subtype' => 'atomic',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?>x+)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses non-capturing subpatterns with quantifiers
	*/
	public function testCanParseRegexps9()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)++',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 3,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => '++'
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?:x+)++#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses non-capturing subpatterns with options
	*/
	public function testCanParseRegexps10()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i:x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'nonCapturingSubpatternStart',
						'options' => 'i',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?i:x+)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses option settings
	*/
	public function testCanParseRegexps11()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i)abc',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'option',
						'options' => 'i'
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?i)abc#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses named subpatterns using the (?<name>) syntax
	*/
	public function testCanParseRegexps12()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<foo>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?<foo>x+)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses named subpatterns using the (?P<name>) syntax
	*/
	public function testCanParseRegexps13()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?P<foo>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 8,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 10,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?P<foo>x+)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses named subpatterns using the (?'name') syntax
	*/
	public function testCanParseRegexps14()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => "(?'foo'x+)",
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				"#(?'foo'x+)#"
			)
		);
	}

	/**
	* @testdox parseRegexp() parses capturing subpatterns
	*/
	public function testCanParseRegexps15()
	{
		$this->assertEquals(
			array(
				'delimiter' => '/',
				'modifiers' => '',
				'regexp'    => '(x+)(abc\\d+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'endToken' => 1
					),
					array(
						'pos' => 3,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					),
					array(
						'pos' => 4,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'endToken' => 3
					),
					array(
						'pos' => 11,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'/(x+)(abc\\d+)/'
			)
		);
	}

	/**
	* @testdox parseRegexp() throws a RuntimeException if an unmatched right parenthesis is found
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching pattern start for right parenthesis at pos 3
	*/
	public function testInvalidRegexpsException4()
	{
		$this->rm->parseRegexp('#a-z)#');
	}

	/**
	* @testdox parseRegexp() throws a RuntimeException if an unmatched left parenthesis is found
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching pattern end for left parenthesis at pos 0
	*/
	public function testInvalidRegexpsException5()
	{
		$this->rm->parseRegexp('#(a-z#');
	}

	/**
	* @testdox parseRegexp() throws a RuntimeException on unsupported subpatterns
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported subpattern type at pos 0
	*/
	public function testInvalidRegexpsUnsupportedSubpatternException()
	{
		$this->rm->parseRegexp('#(?(condition)yes-pattern|no-pattern)#');
	}

	/**
	* @testdox parseRegexp() parses lookahead assertions
	*/
	public function testCanParseRegexps16()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?=foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'lookaheadAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'lookaheadAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?=foo)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses negative lookahead assertions
	*/
	public function testCanParseRegexps17()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?!foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'negativeLookaheadAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'negativeLookaheadAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?!foo)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses lookbehind assertions
	*/
	public function testCanParseRegexps18()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<=foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'lookbehindAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 7,
						'len' => 1,
						'type' => 'lookbehindAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?<=foo)#'
			)
		);
	}

	/**
	* @testdox parseRegexp() parses negative lookbehind assertions
	*/
	public function testCanParseRegexps19()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<!foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'negativeLookbehindAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 7,
						'len' => 1,
						'type' => 'negativeLookbehindAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			$this->rm->parseRegexp(
				'#(?<!foo)#'
			)
		);
	}


	/**
	* @testdox pcreToJs() can convert plain regexps
	*/
	public function testConvertRegexp1()
	{
		$this->assertEquals(
			'/foo/',
			$this->rm->pcreToJs('#foo#')
		);
	}

	/**
	* @testdox pcreToJs() escapes forward slashes
	*/
	public function testConvertRegexpEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			$this->rm->pcreToJs('#fo/o#')
		);
	}

	/**
	* @testdox pcreToJs() does not double-escape forward slashes that are already escaped
	*/
	public function testConvertRegexpNoDoubleEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			$this->rm->pcreToJs('#fo\\/o#')
		);
	}

	/**
	* @testdox pcreToJs() does not "eat" backslashes while escaping forward slashes
	*/
	public function testConvertRegexpDoesNotEatEscapedBackslashes()
	{
		$this->assertEquals(
			'/fo\\\\\\/o/',
			$this->rm->pcreToJs('#fo\\\\/o#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with the "i" modifier
	*/
	public function testConvertRegexp2()
	{
		$this->assertEquals(
			'/foo/i',
			$this->rm->pcreToJs('#foo#i')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with capturing subpatterns
	*/
	public function testConvertRegexp3()
	{
		$this->assertEquals(
			'/f(o)o/',
			$this->rm->pcreToJs('#f(o)o#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with non-capturing subpatterns
	*/
	public function testConvertRegexp4()
	{
		$this->assertEquals(
			'/f(?:o)o/',
			$this->rm->pcreToJs('#f(?:o)o#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with non-capturing subpatterns with a quantifier
	*/
	public function testConvertRegexp5()
	{
		$this->assertEquals(
			'/f(?:oo)+/',
			$this->rm->pcreToJs('#f(?:oo)+#')
		);
	}

	/**
	* @testdox pcreToJs() converts greedy quantifiers to normal quantifiers in non-capturing subpatterns
	*/
	public function testConvertRegexp5b()
	{
		$this->assertEquals(
			'/f(?:o)+(?:o)*/',
			$this->rm->pcreToJs('#f(?:o)++(?:o)*+#')
		);
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on options (?i)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Regexp options are not supported
	*/
	public function testConvertRegexpException1()
	{
		$this->rm->pcreToJs('#(?i)x#');
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on subpattern options (?i:)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Subpattern options are not supported
	*/
	public function testConvertRegexpException2()
	{
		$this->rm->pcreToJs('#(?i:x)#');
	}

	/**
	* @testdox pcreToJs() can convert regexps with character classes with a quantifier
	*/
	public function testConvertRegexp6()
	{
		$this->assertEquals(
			'/[a-z]+/',
			$this->rm->pcreToJs('#[a-z]+#')
		);
	}

	/**
	* @testdox pcreToJs() converts greedy quantifiers to normal quantifiers in character classes
	*/
	public function testConvertRegexp6b()
	{
		$this->assertEquals(
			'/[a-z]+[a-z]*/',
			$this->rm->pcreToJs('/[a-z]++[a-z]*+/')
		);
	}

	/**
	* @testdox pcreToJs() replaces \pL with the full character range in character classes
	*/
	public function testConvertRegexp7()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			$this->rm->pcreToJs('#[0-9\\pL]#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{L} with the full character range in character classes
	*/
	public function testConvertRegexp7b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			$this->rm->pcreToJs('#[0-9\\p{L}]#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \pL outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			$this->rm->pcreToJs('#\\pL00\\pL#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{L} outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			$this->rm->pcreToJs('#\\p{L}00\\p{L}#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{^L} with a character class containing the full character range
	*/
	public function testConvertRegexp8c()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';

		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]/$#D',
			$this->rm->pcreToJs('#\\p{^L}#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{^L} with a character class equivalent to \PL
	*/
	public function testConvertRegexp8d()
	{
		$this->assertSame(
			$this->rm->pcreToJs('#\\PL#'),
			$this->rm->pcreToJs('#\\p{^L}#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \P{^L} with a character class equivalent to \pL
	*/
	public function testConvertRegexp8e()
	{
		$this->assertSame(
			$this->rm->pcreToJs('#\\pL#'),
			$this->rm->pcreToJs('#\\P{^L}#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with lookahead assertions
	*/
	public function testConvertRegexpLookahead()
	{
		$this->assertEquals(
			'/(?=foo)|(?=bar)/',
			$this->rm->pcreToJs('#(?=foo)|(?=bar)#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with negative lookahead assertions
	*/
	public function testConvertRegexpNegativeLookahead()
	{
		$this->assertEquals(
			'/(?!foo)|(?!bar)/',
			$this->rm->pcreToJs('#(?!foo)|(?!bar)#')
		);
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnLookbehind()
	{
		$this->rm->pcreToJs('#(?<=foo)x#');
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on negative lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Negative lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnNegativeLookbehind()
	{
		$this->rm->pcreToJs('#(?<!foo)x#');
	}

	/**
	* @testdox pcreToJs() converts . to [\s\S] outside of character classes is the "s" modifier is set
	*/
	public function testConvertRegexpDotAll()
	{
		$this->assertEquals(
			'/foo([\\s\\S]*)bar/',
			$this->rm->pcreToJs('#foo(.*)bar#s')
		);
	}

	/**
	* @testdox pcreToJs() does not convert . to [\s\S] if the "s" modifier is not set
	*/
	public function testConvertRegexpDotWithoutDotAll()
	{
		$this->assertEquals(
			'/foo(.*)bar/',
			$this->rm->pcreToJs('#foo(.*)bar#')
		);
	}

	/**
	* @testdox pcreToJs() does not convert . inside of character classes
	*/
	public function testConvertRegexpDotInCharacterClasses()
	{
		$this->assertEquals(
			'/foo[.]+bar/',
			$this->rm->pcreToJs('#foo[.]+bar#s')
		);
	}

	/**
	* @testdox pcreToJs() converts named captures into normal captures
	*/
	public function testConvertRegexpNamedCaptures()
	{
		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			$this->rm->pcreToJs('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#', $map)
		);
	}

	/**
	* @testdox pcreToJs() replaces its second parameter with an array that maps named captures to their index
	*/
	public function testConvertRegexpNamedCapturesMap()
	{
		$map = null;

		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			$this->rm->pcreToJs('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#', $map)
		);

		$this->assertEquals(
			array('foo' => 1, 'bar' => 2),
			$map
		);
	}
}