import requests
import logging
import xml.etree.ElementTree as ET
from datetime import datetime, timedelta

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# User-defined variables
PLEX_TOKEN = 'YOURPLEXTOKENHERE'
SHOWS_TO_MARK_AS_WATCHED = ['Seinfeld', 'ted lasso', 'curb your enthusiasm', 'The Marvelous Mrs. Maisel', "workin' moms"]
CUTOFF_LIMIT = (datetime.now() - timedelta(days=10)).timestamp()
SERVER_ADDRESS = '192.168.1.1:32400'

# Function to fetch XML data from Plex API
def fetch_xml(url):
    headers = {
        "X-Plex-Token": PLEX_TOKEN,
        "Accept-Language": "en-US,en;q=0.9",
    }
    try:
        response = requests.get(url, headers=headers, timeout=30, verify=False)
        response.raise_for_status()
        return ET.fromstring(response.text)
    except requests.RequestException as e:
        logging.error(f"Error fetching XML: {e}")
        return None

# Function to mark media as watched
def mark_as_watched(rating_key):
    url = f"http://{SERVER_ADDRESS}/:/scrobble?identifier=com.plexapp.plugins.library&key={rating_key}"
    headers = {"X-Plex-Token": PLEX_TOKEN}
    try:
        response = requests.get(url, headers=headers, timeout=30, verify=False)
        response.raise_for_status()
        logging.info(f"Marked as watched: {rating_key}")
    except requests.RequestException as e:
        logging.error(f"Failed to mark as watched: {e}")

# Function to remove media from "Continue Watching"
def remove_from_continue_watching(rating_key):
    url = f"http://{SERVER_ADDRESS}/actions/removeFromContinueWatching?ratingKey={rating_key}"
    headers = {"X-Plex-Token": PLEX_TOKEN}
    try:
        response = requests.put(url, headers=headers, timeout=30, verify=False)
        response.raise_for_status()
        logging.info(f"Removed from Continue Watching: {rating_key}")
    except requests.RequestException as e:
        logging.error(f"Failed to remove from Continue Watching: {e}")

# Process "On Deck" items
on_deck_url = f"http://{SERVER_ADDRESS}/library/onDeck"
xml_data = fetch_xml(on_deck_url)

if xml_data is not None:
    for video in xml_data.findall(".//Video"):
        title = video.get("grandparentTitle", "")
        rating_key = video.get("ratingKey", "")
        last_viewed_at = int(video.get("lastViewedAt", 0))
        section_title = video.get("librarySectionTitle", "")
        view_offset = video.get("viewOffset", "")
        
        if title.lower() in map(str.lower, SHOWS_TO_MARK_AS_WATCHED) and view_offset:
            mark_as_watched(rating_key)
        
        if title.lower() in map(str.lower, SHOWS_TO_MARK_AS_WATCHED):
            remove_from_continue_watching(rating_key)
        
        if section_title == 'Movies' and last_viewed_at < CUTOFF_LIMIT:
            mark_as_watched(rating_key)
else:
    logging.error("Failed to retrieve On Deck items.")
