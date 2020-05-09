<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateNormalizations;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\FoldArithmeticConstants
* @covers s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractConstantFolding
*/
class FoldArithmeticConstantsTest extends AbstractTest
{
	public function tearDown(): void
	{
		setlocale(LC_NUMERIC, 'C');
	}

	/**
	* @testdox Ignores locale
	*/
	public function testLocale()
	{
		if (!setlocale(LC_NUMERIC, 'en_DK.utf8', 'fr_FR'))
		{
			$this->markTestSkipped('Cannot set locale');
		}

		$this->test(
			'<xsl:value-of select="1.5"/><xsl:value-of select="3 div 2"/><xsl:value-of select="1.1 * 1.1"/>',
			'<xsl:value-of select="1.5"/><xsl:value-of select="1.5"/><xsl:value-of select="1.21"/>'
		);
	}

	public function getData()
	{
		return [
			[
				'<iframe height="{300 + 20}"/>',
				'<iframe height="{320}"/>',
			],
			[
				'<iframe><xsl:attribute name="height"><xsl:value-of select="300 + 20"/></xsl:attribute></iframe>',
				'<iframe><xsl:attribute name="height"><xsl:value-of select="320"/></xsl:attribute></iframe>',
			],
			[
				'<iframe height="{300+20}"/>',
				'<iframe height="{320}"/>',
			],
			[
				'<iframe height="{100 * 2}"/>',
				'<iframe height="{200}"/>',
			],
			[
				'<iframe height="{100 * 2 * 7}"/>',
				'<iframe height="{1400}"/>',
			],
			[
				'<iframe height="{100 * 2 + 7}"/>',
				'<iframe height="{207}"/>',
			],
			[
				'<iframe height="{100 + 2 * 7}"/>',
				'<iframe height="{114}"/>',
			],
			[
				'<iframe height="{100*315div560}"/>',
				'<iframe height="{56.25}"/>',
			],
			[
				'<iframe height="{100 * (315 + 7) div 560}"/>',
				'<iframe height="{57.5}"/>',
			],
			[
				'<iframe height="{100 * (315 + 4 + 3) div 560}"/>',
				'<iframe height="{57.5}"/>',
			],
			[
				'<iframe height="{100*(315+4+3)div560}"/>',
				'<iframe height="{57.5}"/>',
			],
			[
				'<xsl:value-of select="(1 + 2) * (3 + 4)"/>',
				'<xsl:value-of select="21"/>'
			],
			[
				'<xsl:value-of select="((1 + 2) * 3) + 4"/>',
				'<xsl:value-of select="13"/>'
			],
			[
				'<xsl:value-of select="1 + (2 * 3) + 4"/>',
				'<xsl:value-of select="11"/>'
			],
			[
				'<xsl:value-of select="@foo + 0"/>',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<xsl:value-of select="0 + @foo"/>',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'<xsl:value-of select="@foo + 0 + @bar"/>',
				'<xsl:value-of select="@foo + @bar"/>'
			],
			[
				'<xsl:value-of select="@foo + 0 * @bar"/>',
				'<xsl:value-of select="@foo + 0 * @bar"/>'
			],
			[
				'<xsl:value-of select="(@foo + 0) * @bar"/>',
				'<xsl:value-of select="@foo * @bar"/>'
			],
			[
				'<xsl:value-of select="(@foo + 0) div @bar"/>',
				'<xsl:value-of select="@foo div @bar"/>'
			],
			[
				'<xsl:value-of select="(@foo + 0)div @bar"/>',
				'<xsl:value-of select="@foo div @bar"/>'
			],
			[
				'<xsl:value-of select="\'(123)\'"/>',
				'<xsl:value-of select="\'(123)\'"/>'
			],
			[
				'<xsl:value-of select="&quot;(123)&quot;"/>',
				'<xsl:value-of select="&quot;(123)&quot;"/>'
			],
			[
				'<xsl:value-of select="string(3)"/>',
				'<xsl:value-of select="string(3)"/>'
			],
			[
				'<xsl:value-of select="10 - 7"/>',
				'<xsl:value-of select="3"/>'
			],
			[
				'<xsl:value-of select="7 - 10"/>',
				'<xsl:value-of select="-3"/>'
			],
			[
				'<xsl:value-of select="2 * 1.5"/>',
				'<xsl:value-of select="3"/>'
			],
			[
				'<xsl:value-of select="2 * .5"/>',
				'<xsl:value-of select="1"/>'
			],
			[
				'<xsl:value-of select="3 div 1.5"/>',
				'<xsl:value-of select="2"/>'
			],
			[
				'<xsl:value-of select="2-1"/>',
				'<xsl:value-of select="1"/>'
			],
			[
				'<xsl:value-of select="2--1"/>',
				'<xsl:value-of select="3"/>'
			],
			[
				'<xsl:value-of select="@foo * 0 + 1"/>',
				'<xsl:value-of select="@foo * 0 + 1"/>'
			],
			[
				'<xsl:value-of select="@foo * (0 + 1)"/>',
				'<xsl:value-of select="@foo * 1"/>'
			],
		];
	}
}