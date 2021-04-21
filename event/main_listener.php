<?php
/**
 *
 * Autoimage. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Татьяна5
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tatiana5\autoimage\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
    protected $config;

	public $tagName;
	public $attrName;

    public function __construct()
    {
		$this->config = $config;
		$this->tagName = 'IMG';
		$this->attrName = 'src';
    }

	static public function getSubscribedEvents()
	{
		return array(
            'core.text_formatter_s9e_configure_after'	=> 'text_formatter_s9e_configure_after',
			'core.text_formatter_s9e_parse_before'	=> 'text_formatter_s9e_parser_setup',
		);
	}

    public function text_formatter_s9e_configure_after($event)
    {
		if (isset($event['configurator']->tags[$this->tagName]))
		{
			$tag = $event['configurator']->tags[$this->tagName];
		}
		else
		{
			// Create a tag
			$tag = $event['configurator']->tags->add($this->tagName);
		}

		// Add an attribute using the default url filter
		$filter = $event['configurator']->attributeFilters->get('#url');
		$tag->attributes->add($this->attrName)->filterChain->append($filter);

		// Set the default template
		$tag->template = '<img src="{@' . $this->attrName . '}"/>';

		$event['configurator']->tags[$this->tagName] = $tag;
    }

	public function text_formatter_s9e_parser_setup($event)
	{
		$parser   = $event['parser']->get_parser();
		//$regexp = '#\\bhttps?://[-.\\w]+/(?:[-+.:/\\w]|%[0-9a-f]{2}|\\(\\w+\\))+\\.(?:gif|jpe?g|png|svgz?|webp)(?!\\S)#i';
		$regexp = '#\b(?:(?:ftp|https?):|www\.)(?>[^\s()\[\]\x{FF01}-\x{FF0F}\x{FF1A}-\x{FF20}\x{FF3B}-\x{FF40}\x{FF5B}-\x{FF65}]|\([^\s()]*\)|\[\w*\])++#Siu';

		$parser->registerParser(
			'AutoimageExtended',
			function ($text, $matches) use ($parser)
			{
				// Here, $matches will contain the result of the following instruction:
				// preg_match_all($regexp, $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)
				foreach ($matches as $m)
				{
					$header = @get_headers($m[0][0], 1);
					if (isset($header['Content-Type']))
					{
						if (isset($header['Content-Type'][1]))
						{
							$header['Content-Type'] = $header['Content-Type'][1];
						}

						if (stripos($header['Content-Type'], 'image/') === 0)
						{
							// Let's create a self-closing tag around the match
							$parser->addTagPair($this->tagName, $m[0][1], 0, $m[0][1] + strlen($m[0][0]), 0, 2)
								 ->setAttribute($this->attrName, $m[0][0]);
						}
					}
				}
			},
			// Here we pass a regexp as the third argument to indicate that we only want to
			// run this parser if the text matches (<3)
			$regexp
		);
	}
}
