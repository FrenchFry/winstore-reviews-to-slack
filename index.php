<?php
require "BingTranslation.php";
require "MicrosoftHelper.php";

// curl module and ssl module must be enabled on the server.

// You have to call this URL when you want to push the reviews from the store to slack.
// Best is to cron it once a day (set the correct interval of reviews to grab accordingly in the MicrosoftHelper file)

MicrosoftHelper::pushAppReviewsToSlack();

?>
