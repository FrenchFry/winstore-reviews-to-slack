<?php

// This URL is provided by Slack. More info here: https://api.slack.com/incoming-webhooks
define("SLACK_HOOKURL", "https://hooks.slack.com/services/YOUR-SLACK-HOOK-URL");

// To know how to get all this data, go to the URL below :
// https://blogs.windows.com/buildingapps/2016/03/01/windows-store-analytics-api-now-available/
define("WINSTORE_APPID", "YOUR-APP-ID"); // Your Windows Store app ID
define("WINSTORE_APPNAME", "YOUR-APP-NAME"); // Your Windows Store app name
define("WINSTORE_TENANTID", "YOUR-TENANT-ID");
define("WINSTORE_CLIENTID", "YOUR-CLIENT-ID");
define("WINSTORE_CLIENTSECRET", "YOUR-CLIENT-SECRET");

// The reviews will be returned for this interval. By default: just the previous day because i start the task once a day at night
define("WINSTORE_REVIEWSINTERVAL", "P1D");


// Enable translations for foreign languages ? If false, you don't have to have a bing translate api client id
define("BINGTRANSLATE_ENABLED", true);
// Your client id for bing API. Go there to subscribe : https://msdn.microsoft.com/en-us/library/mt146806.aspx
// 2 million characters per month is free
define("BINGTRANSLATE_CLIENTID", "YOUR-CLIENT-ID");
// Language that you don't want to translate :
define("BINGTRANSLATE_TRANSLATE_ALL_BUT", "fr,en");

class MicrosoftHelper
{
	public static function translateIfNecessary(&$review)
	{
		if (!BINGTRANSLATE_ENABLED)
			return false;
			
		$translator = new BingTranslation(BINGTRANSLATE_CLIENTID);
		$lang = $translator->determineLang($review["reviewText"]);
		
		$dontTranslate = explode(",", BINGTRANSLATE_TRANSLATE_ALL_BUT);
		
		if (!in_array($lang, $dontTranslate))
		{
			$review["reviewText"] = $translator->translate($review["reviewText"], "en");
			$review["reviewTitle"] = $translator->translate($review["reviewTitle"], "en");
			return true;
		}
		return false;
	}
	
	public static function pushAppReviewsToSlack()
	{
		$appId      = WINSTORE_APPID;
		$accessToken = MicrosoftHelper::getToken();
		$dateNow    = new \DateTime();
		$interval   = new \DateInterval('P1D');
		$end_date   = $dateNow->format('m/d/Y');
		$start_date = $dateNow->sub($interval);
		$start_date = $start_date->format('m/d/Y');
		
		// Get data from devcenter
		$ch2 = curl_init();
		curl_setopt($ch2, CURLOPT_URL, "https://manage.devcenter.microsoft.com/v1.0/my/analytics/reviews?applicationId=".$appId."&startDate=".$start_date."&endDate=".$end_date."&orderby=date");
		curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch2, CURLOPT_HTTPHEADER,     array("Authorization: Bearer ".$accessToken));
		$result = curl_exec($ch2);
		curl_close($ch2);
		
		$json_result = json_decode($result, true);
		$slackReview = [];
		$reviewIndex = 0;
		
		$totalRankings = 0;
		$numberRankings = 0;
		$rankings = array(0,0,0,0,0,0);
		
		// Grab country names
		$countries =  json_decode(file_get_contents("./countries.json"), true);
		// var_dump($countries);
		
		//Rework data structure
		foreach ($json_result["Value"] as $review) {
			
			$hasTranslated = MicrosoftHelper::translateIfNecessary($review);
			
			$totalRankings += $review["rating"];
			$rankings[$review["rating"]]++;
			
			$slackReview[$reviewIndex]["fallback"] = "New reviews for " .WINSTORE_APPNAME." are available!";
			$slackReview[$reviewIndex]["mrkdwn_in"] = ["pretext", "text"];
			
			$color = $review["rating"] > 3 ? "good" : ($review["rating"] > 2 ? "warning" : "danger");
			$slackReview[$reviewIndex]["color"] = $color;

			$title = str_repeat("★", $review["rating"]) . str_repeat("☆", 5 - $review["rating"]) . " *" . $review["reviewTitle"] . "* - " . $review["reviewerName"] . ", " . 
				($review["market"] !== "" ? $countries[$review["market"]] : "") . ($hasTranslated === true ? " [Translated]" : "");
			
			$slackReview[$reviewIndex]["text"] = $title . "\n" . $review["reviewText"];
			if ($review["rating"] <= 3) {
				
				$technical = array(
					"Windows (" . $review["deviceType"] . ")",
					$review["packageVersion"] !== "" ? "Cover " . $review["packageVersion"] . ", " : "",
					$review["deviceModel"],
					$review["deviceScreenResolution"],
					$review["deviceRAM"] > 0 ? "RAM: " . $review["deviceRAM"] : "",
					$review["deviceStorageCapacity"] > 0 ? "Storage: " . $review["deviceStorageCapacity"] : "",
					$review["isTouchEnabled"] ? "Touch device" : "Non-touch device"
					);
				
				$slackReview[$reviewIndex]["text"] .= "\n" . "```  " . join(", ", array_filter($technical)) . "```";
			}
			
			$reviewIndex++;
		}
		
		if ($reviewIndex > 0)
		{
			// Summary item :
			$slackReview[$reviewIndex]["fallback"] = "New reviews for ".WINSTORE_APPNAME." are available!";
			$title = $review["date"]  . ": " . $reviewIndex . " reviews averaging  " . round(($totalRankings / $reviewIndex), 2) . "/5";
			$summaryText = "";
			for ($i = 0; $i < 6; $i++)
			{
				if ($rankings[$i] > 0)
				{
					$summaryText .= str_repeat("★", $i) . str_repeat("☆", 5 - $i) . " " . $rankings[$i] . " reviews\n";
				}
			}
			$summaryText .= "\n<https://developer.microsoft.com/dashboard/analytics/reports/reviews?productId=" . $appId . "|See or respond to reviews>";
			
			$slackReview[$reviewIndex]["fields"] = array(array("title" => $title), array("value" => $summaryText));
			
			
			// push to slack
			$ch = curl_init(SLACK_HOOKURL);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			
			$data = [];
			$data["username"] = "Review Police for " .WINSTORE_APPNAME;
			$data["attachments"] = $slackReview;
			
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// var_dump($data);
			$result = curl_exec($ch);
			curl_close($ch);
			
			echo WINSTORE_APPNAME.": " .($reviewIndex - 1). " reviews pushed to slack.";
		}
		else
			echo WINSTORE_APPNAME.": Nothing was pushed to slack";
	}
	
	private static function getToken()
	{
		$tenantId     = WINSTORE_TENANTID;
		$clientId     = WINSTORE_CLIENTID;
		$clientSecret = WINSTORE_CLIENTSECRET;
		$resource     = "https://manage.devcenter.microsoft.com";

		$headers = array( 
			"Content-type: application/x-www-form-urlencoded;charset=\"utf-8\""
		); 
			
		//Get access token
		$ch1 = curl_init();
		curl_setopt($ch1, CURLOPT_URL,            "https://login.microsoftonline.com/".$tenantId."/oauth2/token");
		curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch1, CURLOPT_POST,           1 );
		curl_setopt($ch1, CURLOPT_POSTFIELDS,     "grant_type=client_credentials&client_id=".$clientId."&client_secret=".$clientSecret."&resource=".$resource );
		curl_setopt($ch1, CURLOPT_HTTPHEADER,     array("Content-type: application/x-www-form-urlencoded;charset=\"utf-8\""));
		$response = curl_exec($ch1);
		curl_close($ch1);
			
		$arrayResult = json_decode($response, true);
		return $arrayResult["access_token"];
	}
}

?>
