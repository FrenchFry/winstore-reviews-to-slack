<?php
	class BingTranslation
	{
		public $clientID;
		
		public function __construct($cid)
		{
			$this->clientID = $cid;
		}
		
		public function determineLang($word)
		{
			// Prepare variables
			$text = urlencode($word);

			// Prepare cURL command
			$key = $this->clientID;
			$ch = curl_init('https://api.datamarket.azure.com/Bing/MicrosoftTranslator/v1/Detect?Text=%27'.$text. '%27');
			curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$key);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Parse the XML response
			$result = curl_exec($ch);
			$lng = simplexml_load_string($result)->entry->content->children('m', TRUE)->properties->children('d', TRUE)->Code;
			
			return $lng;
		}
		
		public function translate($word, $to)
		{
			// Prepare variables
			$text = urlencode($word);

			// Prepare cURL command
			$key = $this->clientID;
			$ch = curl_init('https://api.datamarket.azure.com/Bing/MicrosoftTranslator/v1/Translate?Text=%27'.$text.'%27&To=%27'.$to.'%27');
			curl_setopt($ch, CURLOPT_USERPWD, $key.':'.$key);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Parse the XML response
			$result = curl_exec($ch);
			$result = explode('<d:Text m:type="Edm.String">', $result);
			$result = explode('</d:Text>', $result[1]);
			$result = $result[0];

			return $result;
		}
		
	}
?>
