<?php

Class Modubot_Youtube extends Modubot_Module {

	public $regex = "#(?:https?://)?(?:(?:(?:www\\.)?youtube\\.com/watch\\?.*?v=([a-zA-Z0-9_\\-]+))|(?:(?:www\\.)?youtu\\.be/([a-zA-Z0-9_\\-]+)))#i";
	public $helpline = 'parses YouTube links and outputs information about them.';
	private $cache = array();
	public $noCommand = true;
	
	public function match(&$that, &$socket, $data, $matches){
		if($that->expect($data, 'topicchange', ['channel' => $that->channel($data)]))
			return;

		$sender = $that->sender($data);
		$id = $matches[1];
		if(isset($this->cache[$id])){
			if($this->cache[$id]['time'] < (time() - 120)){
				unset($this->cache[$id]);
			}
		}

		if(isset($this->cache[$id])){
			$title = $this->cache[$id]['title'];
			$channelTitle = $this->cache[$id]['channelTitle'];
			$viewCount = $this->cache[$id]['viewCount'];
			$this->privmsg($socket, $that->channel($data), "({$sender}) \x02{$title}\x02 {$channelTitle} - {$viewCount} views.");

			return true;
		}

		$serverAPIkey = ""; //v3 of the Youtube Data API requires an API key. Get one at http://console.developers.google.com
		if(empty($serverAPIkey))
		{
			$this->privmsg($socket, $that->channel($data), "({$sender}) No API key set, please have the bot's owner edit line 31 of the Youtube module.");
		}
		else
		{
		$ydata = file_get_contents("https://www.googleapis.com/youtube/v3/videos?part=id%2Csnippet%2Cstatistics&id={$id}&key=".$serverAPIkey);
		$ydata = json_decode($ydata,true);
		if(isset($ydata['items'], $ydata['items'][0]['snippet']['title'])){
			$ydata = $ydata['items'];
			$title = $ydata[0]['snippet']['title'];
			$channelTitle = $ydata[0]['snippet']['channelTitle'];
			$viewCount = $ydata[0]['statistics']['viewCount'];
			
			$this->cache[$id] = array(
				'title' => $title,
				'channelTitle' => $channelTitle,
				'viewCount' => $viewCount,
				'time' => time()
			);
			
			$this->privmsg($socket, $that->channel($data), "({$sender}) \x02{$title}\x02 {$channelTitle} - {$viewCount} views.");
		}
		}
		
		
	}

	public function process(&$that, &$socket, $data, $input, $command, $args){
		if($args == 'clearcache' && $that->getLevel($that->sender($data), '', $that->host($data)) > 6)
			$this->cache = array();
	}
}
