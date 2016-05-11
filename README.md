# winstore-reviews-to-slack
Grab the windows store apps reviews from the API, and push them to a slack hook
So you can get something like that :

![reviews in slack](http://i.imgur.com/1QM8Z5s.png)


curl module and ssl module must be enabled on the server.

You have to call index.php when you want to push the reviews from the store to slack.  
Best is to cron it once a day (set the correct interval of reviews to grab accordingly in the MicrosoftHelper file)


All this must be filled at the top of the MicrosoftHelper.php file :

This URL is provided by Slack. More info here: https://api.slack.com/incoming-webhooks  
define("SLACK_HOOKURL", "https://hooks.slack.com/services/YOUR-SLACK-HOOK-URL");  

To know how to get all this data, go to the URL below :  
https://blogs.windows.com/buildingapps/2016/03/01/windows-store-analytics-api-now-available/  
define("WINSTORE_APPID", "YOUR-APP-ID"); // Your Windows Store app ID  
define("WINSTORE_APPNAME", "YOUR-APP-NAME"); // Your Windows Store app name  
define("WINSTORE_TENANTID", "YOUR-TENANT-ID");  
define("WINSTORE_CLIENTID", "YOUR-CLIENT-ID");  
define("WINSTORE_CLIENTSECRET", "YOUR-CLIENT-SECRET");  

The reviews will be returned for this interval. By default: just the previous day because i start the task once a day at night  
define("WINSTORE_REVIEWSINTERVAL", "P1D");


Enable translations for foreign languages ? If false, you don't have to have a bing translate api client id  
define("BINGTRANSLATE_ENABLED", true);  
Your client id for bing API. Go there to subscribe : https://msdn.microsoft.com/en-us/library/mt146806.aspx  
2 million characters per month is free  
define("BINGTRANSLATE_CLIENTID", "YOUR-CLIENT-ID");  
Language that you don't want to translate :  
define("BINGTRANSLATE_TRANSLATE_ALL_BUT", "fr,en");  
