<?php

namespace s9e\TextFormatter\Tests\Plugins\MediaEmbed\Configurator\Collections;

use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\CachedDefinitionCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\SiteDefinitionCollection
* @covers s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\CachedDefinitionCollection
*/
class CachedDefinitionCollectionTest extends Test
{
	/**
	* @testdox isset('youtube') returns TRUE
	*/
	public function testIsset()
	{
		$collection = new CachedDefinitionCollection;
		$this->assertTrue(isset($collection['youtube']));
	}

	/**
	* @testdox isset('unknown') returns FALSE
	*/
	public function testIssetFalse()
	{
		$collection = new CachedDefinitionCollection;
		$this->assertFalse(isset($collection['unknown']));
	}

	/**
	* @testdox get('*invalid*') throws an exception
	*/
	public function testIssetInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid site ID');

		$collection = new CachedDefinitionCollection;
		$collection->get('*invalid*');
	}

	/**
	* @testdox Is iterable
	*/
	public function testIsIterable()
	{
		$collection = new CachedDefinitionCollection;
		$sites      = iterator_to_array($collection);
		$this->assertIsArray($sites);
		$this->assertArrayHasKey('youtube', $sites);
	}

	/**
	* @testdox get('youtube') returns a configuration
	*/
	public function testGet()
	{
		$collection = new CachedDefinitionCollection;
		$siteConfig = $collection->get('youtube');
		$this->assertIsArray($siteConfig);
		$this->assertArrayHasKey('host', $siteConfig);
		$this->assertContains('youtube.com', $siteConfig['host']);
	}

	/**
	* @testdox get('unknown') returns FALSE
	*/
	public function testGetUnknown()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Media site 'unknown' does not exist");

		$collection = new CachedDefinitionCollection;
		$siteConfig = $collection->get('unknown');
	}

	/**
	* @testdox get('*invalid*') throws an exception
	*/
	public function testGetInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid site ID');

		$collection = new CachedDefinitionCollection;
		$siteConfig = $collection->get('*invalid*');
	}

	/**
	* @testdox Site definitions contain the site's name
	*/
	public function testMetadataName()
	{
		$collection = new CachedDefinitionCollection;
		$siteConfig = $collection->get('youtube');
		$this->assertArrayHasKey('name', $siteConfig);
		$this->assertSame('YouTube', $siteConfig['name']);
	}

	/**
	* @testdox Site definitions contain the site's tags
	*/
	public function testMetadataTags()
	{
		$collection = new CachedDefinitionCollection;
		$siteConfig = $collection->get('scribd');
		$this->assertArrayHasKey('tags', $siteConfig);
		$this->assertEquals(['documents', 'presentations'], $siteConfig['tags']);
	}
}