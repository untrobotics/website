<?php
class rss_post
{
	/**
	 * @var DateTime|string $date The date of the post (not the date of retrieval)
	 */
	var $date;

	/**
	 * @var string $link A link to rpilocator (I know, it'd be better if they linked us to the website)
	 */
	var $link;

	/**
	 * @var string $guid The ID of the post	(Unique to each post; example is 'E17FC800-9F93-4FE4-920F0DF46AF1D661', the length does not variate?)
	 */
	var $guid;

	/**
	 * @var string $title The title of the post. rpilocator posts are always "Stock Alert..." etc.
	 */
	var $title;
}

class rss_feed
{
	/**
	 * @var rss_post[] $posts An array of the posts in the RSS feed
	 */
	public $posts = array();

	/**
	 * @var string $url The URL the feed is located at
	 */
	private $url;

	/**
	 * @var bool $use_DateTime Whether this feed uses DateTime object to represent the post dates
	 */
	private $use_DateTime;

	/**
	 *
	 */
	public $DateTime_format;

	/**
	 * Construct an object that retrieves posts from an RSS feed
	 * @param string $url The URL the feed resides at (generally these have "/feed" near the end e.g. 'www.website.com/feed')
	 * @param bool $use_DateTime If true, posts' dates in this object will use DateTime; if false, posts' dates will be represented as a string
	 * @param string $DateTime_format The format the publication dates are in the RSS feed. This is NOT the format in which they will be stored in this object.
	 */
	function __construct(string $url, bool $use_DateTime, string $DateTime_format = 'D, d M Y H:i:s e')
	{
		if (!($x = simplexml_load_file($url)))
			return;

		$this->use_DateTime = $use_DateTime;
		$this->DateTime_format = $DateTime_format;
		if($use_DateTime)
		{
			foreach ($x->channel->item as $item)
			{
				$post = new rss_post();
				$post->date = DateTime::createFromFormat($DateTime_format, (string)$item->pubDate);
				$post->link = (string)$item->link;
				$post->title = (string)$item->title;
				$post->guid = (string)$item->guid;
				$this->posts[] = $post;
			}
		}
		else
		{
			foreach ($x->channel->item as $item)
			{
				$post = new rss_post();
				$post->date = (string)$item->pubDate;
				$post->link = (string)$item->link;
				$post->title = (string)$item->title;
				$post->guid = (string)$item->guid;
				$this->posts[] = $post;
			}
		}
	}

	/**
	 * Check the feed for new posts. Update $this->>posts array
	 * @return array|false An array with new posts. If there are no new posts, the array will be empty. If it fails to load the feed, returns false.
	 */
	public function update()
	{
		if (!($x = simplexml_load_file($this->url)))
			return false;

		$updated_posts = array();
		$new_posts = array();

		if($this->use_DateTime)
		{
			foreach ($x->channel->item as $item) {
				$post = new rss_post();
				$post->date = DateTime::createFromFormat($this->DateTime_format, (string)$item->pubDate);
				$post->link = (string)$item->link;
				$post->title = (string)$item->title;
				$post->guid = (string)$item->guid;
				$updated_posts[] = $post;
				if (!$this->has_post($post->guid)) {
					$new_posts[] = $post;
				}
			}
		}
		else
		{
			foreach ($x->channel->item as $item) {
				$post = new rss_post();
				$post->date = (string)$item->pubDate;
				$post->link = (string)$item->link;
				$post->title = (string)$item->title;
				$post->guid = (string)$item->guid;
				$updated_posts[] = $post;
				if (!$this->has_post($post->guid)) {
					$new_posts[] = $post;
				}
			}
		}
		$this->posts = $updated_posts;
		return $new_posts;
	}

	/**
	 * Check the current post array for a post with given ID
	 * @param string $guid The ID of the post to find
	 * @return bool
	 */
	public function has_post($guid): bool
	{
		foreach($this->posts as $post)
		{
			if($post->guid===$guid)
				return true;
		}
		return false;
	}

	/**
	 * Getter for $use_DateTime property
	 * @return bool
	 */
	public function get_use_DateTime(): bool
	{
		return $this->use_DateTime;
	}

	/**
	 * Getter for $URL property
	 * @return string
	 */
	public function get_URL(): string
	{
		return $this->url;
	}


	/**
	 * Setter for $use_DateTime property
	 * @param bool $use_DateTime
	 * @param string $format The format the date of the post should be in. If changing from string to DateTime, this should be the format the string is in to convert to DateTime
	 */
	public function set_use_DateTime(bool $use_DateTime, string $format='D, d M Y H:i:s e')
	{
		if($use_DateTime!=$this->use_DateTime)
		{
			if($use_DateTime)
			{
				foreach($this->posts as &$post)
				{
					$post->date = DateTime::createFromFormat($format, $post->date);
				}
			}
			else
			{
				foreach($this->posts as &$post)
				{
					$post->date = $post->date->format($format);
				}
			}
		}
	}
}
