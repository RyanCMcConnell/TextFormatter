<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Helpers\TemplateOptimizer;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateOptimizer
*/
class TemplateOptimizerTest extends Test
{
	// Start of content generated by ../../../scripts/patchTemplateOptimizerTest.php
	/**
	* @testdox Comments are removed
	*/
	public function test39C3D587()
	{
		$this->runCase(
			'Comments are removed',
			'<!-- This is a comment -->hi',
			'hi'
		);
	}

	/**
	* @testdox Superfluous whitespace between elements is removed
	*/
	public function testE770FE93()
	{
		$this->runCase(
			'Superfluous whitespace between elements is removed',
			'<div>
					<b>
						<xsl:apply-templates/>
					</b>
				</div>',
			'<div><b><xsl:apply-templates/></b></div>'
		);
	}

	/**
	* @testdox Single space characters are preserved
	*/
	public function test50A8C325()
	{
		$this->runCase(
			'Single space characters are preserved',
			'<b>foo:</b> <i><xsl:apply-templates/></i>',
			'<b>foo:</b> <i><xsl:apply-templates/></i>'
		);
	}

	/**
	* @testdox Superfluous whitespace inside tags is removed
	*/
	public function testAC8ED1D2()
	{
		$this->runCase(
			'Superfluous whitespace inside tags is removed',
			'<div id = "foo" ><xsl:apply-templates /></div >',
			'<div id="foo"><xsl:apply-templates/></div>'
		);
	}

	/**
	* @testdox Superfluous whitespace around XSL attributes is removed
	*/
	public function test4FD06380()
	{
		$this->runCase(
			'Superfluous whitespace around XSL attributes is removed',
			'<div><xsl:value-of select=" @foo "/></div>',
			'<div><xsl:value-of select="@foo"/></div>'
		);
	}

	/**
	* @testdox Superfluous whitespace in simple attribute expressions is removed
	*/
	public function test28322044()
	{
		$this->runCase(
			'Superfluous whitespace in simple attribute expressions is removed',
			'<div><xsl:value-of select="@ foo"/></div>',
			'<div><xsl:value-of select="@foo"/></div>'
		);
	}

	/**
	* @testdox Superfluous whitespace in @select expressions is removed
	*/
	public function testD47C4700()
	{
		$this->runCase(
			'Superfluous whitespace in @select expressions is removed',
			'<div><xsl:value-of select="concat(@foo, @bar, @baz)"/></div>',
			'<div><xsl:value-of select="concat(@foo,@bar,@baz)"/></div>'
		);
	}

	/**
	* @testdox Whitespace is literal strings used in @select expressions is preserved
	*/
	public function test2811C0C5()
	{
		$this->runCase(
			'Whitespace is literal strings used in @select expressions is preserved',
			'<div><xsl:value-of select="concat(@foo, \' @bar \', @baz)"/></div>',
			'<div><xsl:value-of select="concat(@foo,\' @bar \',@baz)"/></div>'
		);
	}

	/**
	* @testdox Superfluous whitespace in @test expressions is removed
	*/
	public function testA8B67871()
	{
		$this->runCase(
			'Superfluous whitespace in @test expressions is removed',
			'<div><xsl:if test="@foo = 2">!</xsl:if></div>',
			'<div><xsl:if test="@foo=2">!</xsl:if></div>'
		);
	}

	/**
	* @testdox Whitespace necessary to delimit names in @test expressions is preserved
	*/
	public function testD4F6B3A9()
	{
		$this->runCase(
			'Whitespace necessary to delimit names in @test expressions is preserved',
			'<div><xsl:if test="@foo - bar = 2">!</xsl:if></div>',
			'<div><xsl:if test="@foo -bar=2">!</xsl:if></div>'
		);
	}

	/**
	* @testdox Whitespace necessary to delimit names in @test expressions is preserved
	*/
	public function test85CD9459()
	{
		$this->runCase(
			'Whitespace necessary to delimit names in @test expressions is preserved',
			'<div><xsl:if test="foo or _bar">!</xsl:if></div>',
			'<div><xsl:if test="foo or _bar">!</xsl:if></div>'
		);
	}

	/**
	* @testdox <xsl:element/> is inlined where possible
	*/
	public function testBBC4349B()
	{
		$this->runCase(
			'<xsl:element/> is inlined where possible',
			'<xsl:element name="div"><xsl:apply-templates/></xsl:element>',
			'<div><xsl:apply-templates/></div>'
		);
	}

	/**
	* @testdox <xsl:attribute/> with one single <xsl:value-of/> child is inlined
	*/
	public function test19670F1C()
	{
		$this->runCase(
			'<xsl:attribute/> with one single <xsl:value-of/> child is inlined',
			'<div><xsl:attribute name="class"><xsl:value-of select="@foo"/></xsl:attribute><xsl:apply-templates/></div>',
			'<div class="{@foo}"><xsl:apply-templates/></div>'
		);
	}

	/**
	* @testdox <xsl:attribute/> with <xsl:value-of/>, <xsl:text/> and text nodes descendants is inlined
	*/
	public function test8613A729()
	{
		$this->runCase(
			'<xsl:attribute/> with <xsl:value-of/>, <xsl:text/> and text nodes descendants is inlined',
			'<div><xsl:attribute name="class">foo <xsl:value-of select="@bar"/><xsl:text> baz</xsl:text></xsl:attribute><xsl:apply-templates/></div>',
			'<div class="foo {@bar} baz"><xsl:apply-templates/></div>'
		);
	}

	/**
	* @testdox Curly brackets in text are escaped when attributes are inlined
	*/
	public function test82AE6D82()
	{
		$this->runCase(
			'Curly brackets in text are escaped when attributes are inlined',
			'<div><xsl:attribute name="title">{foo}</xsl:attribute><xsl:apply-templates/></div>',
			'<div title="{{foo}}"><xsl:apply-templates/></div>'
		);
	}

	/**
	* @testdox <xsl:attribute/> with <xsl:if/> child is not inlined
	*/
	public function test76FE1870()
	{
		$this->runCase(
			'<xsl:attribute/> with <xsl:if/> child is not inlined',
			'<div><xsl:attribute name="class">foo<xsl:if test="@bar">bar</xsl:if></xsl:attribute><xsl:apply-templates/></div>',
			'<div><xsl:attribute name="class">foo<xsl:if test="@bar">bar</xsl:if></xsl:attribute><xsl:apply-templates/></div>'
		);
	}

	/**
	* @testdox Conditional <xsl:attribute/> is replaced with <xsl:copy-of/> where possible
	*/
	public function test933268A2()
	{
		$this->runCase(
			'Conditional <xsl:attribute/> is replaced with <xsl:copy-of/> where possible',
			'<a><xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>',
			'<a><xsl:copy-of select="@title"/><xsl:apply-templates/></a>'
		);
	}

	/**
	* @testdox Conditional <xsl:attribute/> is not replaced with <xsl:copy-of/> if names do not match
	*/
	public function testADB20165()
	{
		$this->runCase(
			'Conditional <xsl:attribute/> is not replaced with <xsl:copy-of/> if names do not match',
			'<a><xsl:if test="@foo"><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>',
			'<a><xsl:if test="@foo"><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>'
		);
	}

	/**
	* @testdox <xsl:text/> is inlined
	*/
	public function testFD8BE5D1()
	{
		$this->runCase(
			'<xsl:text/> is inlined',
			'<b><xsl:text>Hello world</xsl:text></b>',
			'<b>Hello world</b>'
		);
	}
	// End of content generated by ../../../scripts/patchTemplateOptimizerTest.php

	/**
	* @testdox Throws an exception during XPath minification if a string isn't properly closed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot parse XPath expression 'foo="bar'
	*/
	public function testInvalidXPath()
	{
		TemplateOptimizer::optimize('<xsl:if test="foo=&quot;bar">!</xsl:if>');
	}

	public function runCase($title, $input, $expected)
	{
		$this->assertSame(
			$expected,
			TemplateOptimizer::optimize($input)
		);
	}

	public function getData()
	{
		return array(
			array(
				'Comments are removed',
				'<!-- This is a comment -->hi',
				'hi'
			),
			array(
				'Superfluous whitespace between elements is removed',
				'<div>
					<b>
						<xsl:apply-templates/>
					</b>
				</div>',
				'<div><b><xsl:apply-templates/></b></div>'
			),
			array(
				'Single space characters are preserved',
				'<b>foo:</b> <i><xsl:apply-templates/></i>',
				'<b>foo:</b> <i><xsl:apply-templates/></i>'
			),
			array(
				'Superfluous whitespace inside tags is removed',
				'<div id = "foo" ><xsl:apply-templates /></div >',
				'<div id="foo"><xsl:apply-templates/></div>'
			),
			array(
				'Superfluous whitespace around XSL attributes is removed',
				'<div><xsl:value-of select=" @foo "/></div>',
				'<div><xsl:value-of select="@foo"/></div>'
			),
			array(
				'Superfluous whitespace in simple attribute expressions is removed',
				'<div><xsl:value-of select="@ foo"/></div>',
				'<div><xsl:value-of select="@foo"/></div>'
			),
			array(
				'Superfluous whitespace in @select expressions is removed',
				'<div><xsl:value-of select="concat(@foo, @bar, @baz)"/></div>',
				'<div><xsl:value-of select="concat(@foo,@bar,@baz)"/></div>'
			),
			array(
				'Whitespace is literal strings used in @select expressions is preserved',
				'<div><xsl:value-of select="concat(@foo, \' @bar \', @baz)"/></div>',
				'<div><xsl:value-of select="concat(@foo,\' @bar \',@baz)"/></div>'
			),
			array(
				'Superfluous whitespace in @test expressions is removed',
				'<div><xsl:if test="@foo = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo=2">!</xsl:if></div>'
			),
			array(
				'Whitespace necessary to delimit names in @test expressions is preserved',
				'<div><xsl:if test="@foo - bar = 2">!</xsl:if></div>',
				'<div><xsl:if test="@foo -bar=2">!</xsl:if></div>'
			),
			array(
				'Whitespace necessary to delimit names in @test expressions is preserved',
				'<div><xsl:if test="foo or _bar">!</xsl:if></div>',
				'<div><xsl:if test="foo or _bar">!</xsl:if></div>'
			),
			array(
				'<xsl:element/> is inlined where possible',
				'<xsl:element name="div"><xsl:apply-templates/></xsl:element>',
				'<div><xsl:apply-templates/></div>'
			),
			array(
				'<xsl:attribute/> with one single <xsl:value-of/> child is inlined',
				'<div><xsl:attribute name="class"><xsl:value-of select="@foo"/></xsl:attribute><xsl:apply-templates/></div>',
				'<div class="{@foo}"><xsl:apply-templates/></div>'
			),
			array(
				'<xsl:attribute/> with <xsl:value-of/>, <xsl:text/> and text nodes descendants is inlined',
				'<div><xsl:attribute name="class">foo <xsl:value-of select="@bar"/><xsl:text> baz</xsl:text></xsl:attribute><xsl:apply-templates/></div>',
				'<div class="foo {@bar} baz"><xsl:apply-templates/></div>'
			),
			array(
				'Curly brackets in text are escaped when attributes are inlined',
				'<div><xsl:attribute name="title">{foo}</xsl:attribute><xsl:apply-templates/></div>',
				'<div title="{{foo}}"><xsl:apply-templates/></div>'
			),
			array(
				'<xsl:attribute/> with <xsl:if/> child is not inlined',
				'<div><xsl:attribute name="class">foo<xsl:if test="@bar">bar</xsl:if></xsl:attribute><xsl:apply-templates/></div>',
				'<div><xsl:attribute name="class">foo<xsl:if test="@bar">bar</xsl:if></xsl:attribute><xsl:apply-templates/></div>'
			),
			array(
				'Conditional <xsl:attribute/> is replaced with <xsl:copy-of/> where possible',
				'<a><xsl:if test="@title"><xsl:attribute name="title"><xsl:value-of select="@title"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>',
				'<a><xsl:copy-of select="@title"/><xsl:apply-templates/></a>'
			),
			array(
				'Conditional <xsl:attribute/> is not replaced with <xsl:copy-of/> if names do not match',
				'<a><xsl:if test="@foo"><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>',
				'<a><xsl:if test="@foo"><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></xsl:if><xsl:apply-templates/></a>'
			),
			array(
				'<xsl:text/> is inlined',
				'<b><xsl:text>Hello world</xsl:text></b>',
				'<b>Hello world</b>'
			)
		);
	}
}