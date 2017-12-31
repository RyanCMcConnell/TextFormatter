<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use s9e\TextFormatter\Parser as TagStack;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Utils\Http;

class Parser extends ParserBase
{
	/**
	* @var \s9e\TextFormatter\Utils\Http\Client Client used to perform HTTP request
	*/
	protected static $client;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$url = $m[0][0];
			$pos = $m[0][1];
			$len = strlen($url);

			// Give that tag priority over other tags such as Autolink's
			$tag = $this->parser->addSelfClosingTag($this->config['tagName'], $pos, $len, -10);
			$tag->setAttribute('url', $url);
		}
	}

	/**
	* Filter a MEDIA tag
	*
	* This will always invalidate the original tag, and possibly replace it with the tag that
	* corresponds to the media site
	*
	* @param  Tag      $tag      The original tag
	* @param  TagStack $tagStack Parser instance, so that we can add the new tag to the stack
	* @param  array    $sites    Map of [host => siteId]
	* @return void
	*/
	public static function filterTag(Tag $tag, TagStack $tagStack, array $sites)
	{
		$tag->invalidate();
		if ($tag->hasAttribute('site'))
		{
			self::addTagFromMediaId($tag, $tagStack, $sites);
		}
		elseif ($tag->hasAttribute('url'))
		{
			self::addTagFromMediaUrl($tag, $tagStack, $sites);
		}
	}

	/**
	* Invalidate given tag if it doesn't have at least one non-default attribute
	*
	* @param  Tag  $tag The original tag
	* @return void
	*/
	public static function hasNonDefaultAttribute(Tag $tag)
	{
		foreach ($tag->getAttributes() as $attrName => $void)
		{
			if ($attrName !== 'url')
			{
				return;
			}
		}

		$tag->invalidate();
	}

	/**
	* Scrape the content of an URL to extract some data
	*
	* @param  Tag    $tag          Source tag
	* @param  array  $scrapeConfig Array of scrape directives
	* @param  string $cacheDir     Path to the cache directory
	* @return bool                 Unconditionally TRUE
	*/
	public static function scrape(Tag $tag, array $scrapeConfig, $cacheDir = null)
	{
		if ($tag->hasAttribute('url'))
		{
			// Ensure that the URL actually looks like a URL if we want to use it to scrape
			$url = $tag->getAttribute('url');
			if (preg_match('#^https?://[^<>"\'\\s]+$#Di', $url))
			{
				$url = strtolower(substr($url, 0, 5)) . substr($url, 5);
				foreach ($scrapeConfig as $scrape)
				{
					self::scrapeEntry($url, $tag, $scrape, $cacheDir);
				}
			}
		}

		return true;
	}

	//==============================================================================================
	// Internals
	//==============================================================================================

	/**
	* Add a site tag
	*
	* @param  Tag      $tag      The original tag
	* @param  TagStack $tagStack Parser instance, so that we can add the new tag to the stack
	* @param  string   $siteId   Site ID
	* @return void
	*/
	protected static function addSiteTag(Tag $tag, TagStack $tagStack, $siteId)
	{
		$endTag = $tag->getEndTag();
		if ($endTag)
		{
			$startPos = $tag->getPos();
			$startLen = $tag->getLen();
			$endPos   = $endTag->getPos();
			$endLen   = $endTag->getLen();
		}
		else
		{
			$startPos = $tag->getPos();
			$startLen = 0;
			$endPos   = $tag->getPos() + $tag->getLen();
			$endLen   = 0;
		}

		// Create a new tag and copy this tag's attributes and priority
		$tagStack->addTagPair(strtoupper($siteId), $startPos, $startLen, $endPos, $endLen, $tag->getSortPriority())->setAttributes($tag->getAttributes());
	}

	/**
	* Add a media site tag based on the attributes of a MEDIA tag
	*
	* @param  Tag      $tag      The original tag
	* @param  TagStack $tagStack Parser instance
	* @param  array    $sites    Map of [host => siteId]
	* @return void
	*/
	protected static function addTagFromMediaId(Tag $tag, TagStack $tagStack, array $sites)
	{
		$siteId = strtolower($tag->getAttribute('site'));
		if (in_array($siteId, $sites, true))
		{
			self::addSiteTag($tag, $tagStack, $siteId);
		}
	}

	/**
	* Add a media site tag based on the url attribute of a MEDIA tag
	*
	* @param  Tag      $tag      The original tag
	* @param  TagStack $tagStack Parser instance
	* @param  array    $sites    Map of [host => siteId]
	* @return void
	*/
	protected static function addTagFromMediaUrl(Tag $tag, TagStack $tagStack, array $sites)
	{
		// Capture the host of the URL
		if (preg_match('(^\\w+://(?:[^@/]*@)?([^/]+))', $tag->getAttribute('url'), $m))
		{
			$siteId = self::findSiteIdByHost($m[1], $sites);
		}

		if (!empty($siteId))
		{
			self::addSiteTag($tag, $tagStack, $siteId);
		}
	}

	/**
	* Match a given host to a site ID
	*
	* @param  string      $host  Host
	* @param  array       $sites Map of [host => siteId]
	* @return string|bool        Site ID or FALSE
	*/
	protected static function findSiteIdByHost($host, array $sites)
	{
		// Start with the full host then pop domain labels off the start until we get a match
		do
		{
			if (isset($sites[$host]))
			{
				return $sites[$host];
			}

			$pos = strpos($host, '.');
			if ($pos === false)
			{
				break;
			}

			$host = substr($host, 1 + $pos);
		}
		while ($host > '');

		return false;
	}

	/**
	* Return a cached instance of the HTTP client
	*
	* @return \s9e\TextFormatter\Utils\Http\Client
	*/
	protected static function getHttpClient()
	{
		if (!isset(self::$client))
		{
			self::$client = Http::getClient();
		}
		self::$client->timeout = 10;

		return self::$client;
	}

	/**
	* Replace {@var} tokens in given URL
	*
	* @param  string   $url  Original URL
	* @param  string[] $vars Replacements
	* @return string         Modified URL
	*/
	protected static function replaceTokens($url, array $vars)
	{
		return preg_replace_callback(
			'#\\{@(\\w+)\\}#',
			function ($m) use ($vars)
			{
				return (isset($vars[$m[1]])) ? $vars[$m[1]] : '';
			},
			$url
		);
	}

	/**
	* Scrape the content of an URL to extract some data
	*
	* @param  string $url      Original URL
	* @param  Tag    $tag      Source tag
	* @param  array  $scrape   Array of scrape directives
	* @param  string $cacheDir Path to the cache directory
	* @return void
	*/
	protected static function scrapeEntry($url, Tag $tag, array $scrape, $cacheDir)
	{
		list($matchRegexps, $extractRegexps, $attrNames) = $scrape;
		if (!self::tagIsMissingAnyAttribute($tag, $attrNames))
		{
			return;
		}

		// Test whether this URL matches any regexp
		$vars    = [];
		$matched = false;
		foreach ((array) $matchRegexps as $matchRegexp)
		{
			if (preg_match($matchRegexp, $url, $m))
			{
				$vars   += $m;
				$matched = true;
			}
		}
		if (!$matched)
		{
			return;
		}

		// Add the tag's attributes to the named captures from the "match" regexp
		$vars += $tag->getAttributes();

		$scrapeUrl = (isset($scrape[3])) ? self::replaceTokens($scrape[3], $vars) : $url;
		self::scrapeUrl($scrapeUrl, $tag, (array) $extractRegexps, $cacheDir);
	}

	/**
	* Scrape a URL to help fill a tag's attributes
	*
	* @param  string      $url      URL to scrape
	* @param  Tag         $tag      Tag to fill
	* @param  string[]    $regexps  Regexps used to extract content from the page
	* @param  string|null $cacheDir Path to the cache directory
	* @return void
	*/
	protected static function scrapeUrl($url, Tag $tag, array $regexps, $cacheDir)
	{
		$content = self::wget($url, $cacheDir);

		// Execute the extract regexps and fill any missing attribute
		foreach ($regexps as $regexp)
		{
			if (preg_match($regexp, $content, $m))
			{
				foreach ($m as $k => $v)
				{
					if (!is_numeric($k) && !$tag->hasAttribute($k))
					{
						$tag->setAttribute($k, $v);
					}
				}
			}
		}
	}

	/**
	* Test whether a tag is missing any of given attributes
	*
	* @param  Tag      $tag
	* @param  string[] $attrNames
	* @return bool
	*/
	protected static function tagIsMissingAnyAttribute(Tag $tag, array $attrNames)
	{
		foreach ($attrNames as $attrName)
		{
			if (!$tag->hasAttribute($attrName))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Retrieve external content (possibly from the cache)
	*
	* If the cache directory exists, the external content will be saved into it. Cached content is
	* never pruned
	*
	* @param  string $url      URL
	* @param  string $cacheDir Path to the cache directory
	* @return string           External content
	*/
	protected static function wget($url, $cacheDir = null)
	{
		$prefix = '';
		$url    = preg_replace('(#.*)s', '', $url);

		// Return the content from the cache if applicable
		if (isset($cacheDir) && file_exists($cacheDir))
		{
			$cacheFile = $cacheDir . '/http.' . crc32($url);
			if (extension_loaded('zlib'))
			{
				$prefix     = 'compress.zlib://';
				$cacheFile .= '.gz';
			}
			if (file_exists($cacheFile))
			{
				return file_get_contents($prefix . $cacheFile);
			}
		}

		// Retrieve the external content from the source
		$content = @self::getHttpClient()->get($url, ['User-Agent: PHP (not Mozilla)']);

		// Save to the cache if applicable
		if (isset($cacheFile) && !empty($content))
		{
			file_put_contents($prefix . $cacheFile, $content);
		}

		return $content;
	}
}