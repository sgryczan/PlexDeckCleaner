<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Plex Deck Cleaner (For Clearing Up "Continue Watching")</title>
</head>
<body>
<?php



/* ----------------START USER DEFINED VARIABLES SECTION---------------------------
----------------------------------------------------------------------------------
----------------------------------------------------------------------------------*/


/* NOTE - THIS MAY NOT WORK IF YOU DON'T ALLOW INSECURE CONNECTIONS TO YOUR PLEX SERVER, I ALSO HAVEN'T TESTED THIS RUNNING OUTSIDE THE LAN THE PLEX SERVER RESIDES ON */


//your plex token
$plexToken = 'TOKENHEREINSIDETHEQUOTES';


/*
List of any shows that will be removed from "On Deck" (otherwise known as "Continue Watching").
If a show in this list appears "On Deck" because it's started playing but not complete/fully watched it will be marked as completely watched.
When an episode of a show is marked as watched, Plex's normal behavior is to then add the next episode of that show to your "On Deck", this will remove that also (withoout marking it watched).
If a show in this list appears "On Deck" because it's the next unwatched episode coming up because you've recently watched the show, it won't mark that episode as watched, but it will remove it from appearing "On Deck"
IMPORTANT - These are NOT case sensitive, however the title name must match EXACTLY (not counting capitalization), and ANY special characters must be escaped (workin' moms is an example of this).
*/
$showsToMarkAsWatched = ['Seinfeld', 'ted lasso', 'curb your enthusiasm', 'The Marvelous Mrs. Maisel', 'workin\' moms'];


/*
This is how many days ago ANY MOVIE AT ALL that has been started, but not completed should be marked as fully watched.
So if it has a value of -10, any movie last played (but not completed) greater than 10 days ago, will be marked as fully watched.  This will obviously remove it from being "On Deck" / "Continue Watching"
THIS IS NOT going by the ORIGINAL time it was first played, but rather the last time it was played but not finished.  So if I started watching "Pulp Fiction" 15 days ago, only got 1/3'rd of the way through it,
then watched it for another ten minutes 9 days ago (so still "on deck" because it's not completed) it would NOT be removed because the last time it was played was within 10 days.  Tomorrow it would be removed after
it surpasses that 10 days of not having been played.  So it will be marked as fully watched, thus removing it from being "On Deck"
*/
$cutoffLimit = strtotime('-10 days');




/* SERVER ADDRESS - must be in this format IP:PORT - NO TRAILING SLASH, NO HTTP://
IP address and port listed are just for example, please make sure to correct it
*/

$serverAddress = '192.168.1.1:32400';



/* ---------------- END USER DEFINED VARIABLES SECTION -----------------------------
------------------------------------------------------------------------------------
------------------------------------------------------------------------------------
*/








/* --------------------  EVERYTHING BELOW IS THE WORKING CODE - SHOULD NOT BE MODIFIED UNLESS YOU WANT TO MODIFY FUNCTIONALITY ------------------- */




// Function to fetch XML data from a given URL and return it as a SimpleXMLElement
function fetchXmlAsObject($url, $plexToken) {

    // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Plex-Token: {$plexToken}",
    "Accept-Language: en-US,en;q=0.9",
    "Access-Control-Request-Method: PUT",
    "Connection: keep-alive",
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification for HTTPS 
    
    // Execute the cURL request
    $response = curl_exec($ch);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        echo 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }
    
    // Close the cURL session
    curl_close($ch);
    
    // Convert the response to a SimpleXMLElement
    try {
        $xmlObject = new SimpleXMLElement($response);
        return $xmlObject;
    } catch (Exception $e) {
        echo 'Error parsing XML: ' . $e->getMessage();
        return false;
    }
}

$url = "http://{$serverAddress}/library/onDeck";
$xmlObject = fetchXmlAsObject($url, $plexToken);

if ($xmlObject !== false) {
    
    $result = []; // Array to store matching ratingKeys
    
    // Convert allowed show names to lowercase for case-insensitive comparison
    $showsToMarkAsWatchedLower = array_map('strtolower', $showsToMarkAsWatched);
    
    foreach ($xmlObject->Video as $video) {
        // Check if "viewOffset" exists, "librarySectionTitle" is "TV Shows",
        // and "grandparentTitle" contains one of the allowed show names (case-insensitive)
        if (
        isset($video['viewOffset']) && !empty((string) $video['viewOffset']) &&
        isset($video['librarySectionTitle']) && (string) $video['librarySectionTitle'] === 'TV Shows' &&
        isset($video['grandparentTitle']) &&
        in_array(strtolower((string) $video['grandparentTitle']), $showsToMarkAsWatchedLower)
        ) {
            $result[] = (string) $video['ratingKey'];
        }
    }
    
    // First batch of GET requests - marking any shows that haven't been completely watched as complete/fully watched
    foreach ($result as $ratingKey) {
        $url = "{$serverAddress}/:/scrobble?identifier=com.plexapp.plugins.library&key={$ratingKey}";
        
        // Initialize cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout to prevent indefinite waiting
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Plex-Token: {$plexToken}",
        "Accept-Language: en-US,en;q=0.9",
        "Access-Control-Request-Method: PUT",
        "Connection: keep-alive",
        ]);
        // Execute the GET request and ignore the response
        curl_exec($ch);
        
        // Close the cURL session
        curl_close($ch);
        sleep(2);
    }
    
    $url = "http://{$serverAddress}/library/onDeck";
    $xmlObject = fetchXmlAsObject($url, $plexToken);
    $result = []; // Array to store matching ratingKeys
    
    foreach ($xmlObject->Video as $video) {
        // checking if the show returned matches any show in the allowed shows at all, since marking an incomplete episode as watched means it will place the next episode in your continue watching
        if (
        in_array(strtolower((string) $video['grandparentTitle']), $showsToMarkAsWatchedLower)
        ) {
            $result[] = (string) $video['ratingKey'];
        }
    }
    
    // second batch of GET requests - this is going to remove any show that appears in the allowed shows lists entirely from the "on deck" / "continue watching" section of plex
    foreach ($result as $ratingKey) {
        $url = "http://{$serverAddress}/actions/removeFromContinueWatching?ratingKey={$ratingKey}";
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set HTTP headers
        $headers = [
        "Host: {$serverAddress}",
        "Accept: */*",
        "Accept-Language: en-US,en;q=0.5",
        "Accept-Encoding: gzip, deflate, br, zstd",
        "Access-Control-Request-Method: PUT",
        "X-Plex-Token: $plexToken",
        "Connection: keep-alive",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Execute the cURL request
        $response = curl_exec($ch);
        
        // Check for errors
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        } else {
            
        }
        
        // Close the cURL session
        curl_close($ch);
    }
    
    foreach ($xmlObject->Video as $video) {
        // Check if "viewOffset" exists and is not empty
        if (isset($video['viewOffset']) && !empty((string) $video['viewOffset'])) {
            // Check if "lastViewedAt" exists and is older than 10 days ago
            if (isset($video['lastViewedAt']) && (string) $video['librarySectionTitle'] === 'Movies') {
                $lastViewedAt = (int) $video['lastViewedAt'];
                
                if ($lastViewedAt < $cutoffLimit) {
                    $ratingKey = (string) $video['ratingKey'];
                    
                    // Construct the URL
                    $url = "http://{$serverAddress}/:/scrobble?identifier=com.plexapp.plugins.library&key={$ratingKey}";
                    
                    // Initialize cURL
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout to prevent indefinite waiting
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "X-Plex-Token: {$plexToken}",
                    "Accept-Language: en-US,en;q=0.9",
                    "Access-Control-Request-Method: PUT",
                    "Connection: keep-alive",
                    ]);
                    // Execute the GET request and ignore the response
                    curl_exec($ch);
                    
                    // Close the cURL session
                    curl_close($ch);
                }
            }
        }
    }
    
} else {
    //whatever happens if it fails to parse the XML data
}

?>
</body>
</html>