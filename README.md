# PlexDeckCleaner
This is a script for taking shows that are regularly watched and marking multiple episodes that aren't fully watched as completely watched, and making sure that the episodes for those shows don't appear on On Deck in the "Continue Watching" area.  It will also mark movies that are partially watched as fully watched (after user designated time).


**************** Here is the backstory of why this was created, with a detailed explanation further below (so you can skip this, but it'll help understand what the script does): ***********

We regularly use Plex for "going to bed" type shows or "working in the home office background noise" type shows, that we've created Plex Smart Playlists for.  They're only for shows that we've seen so many times they're good for just background noise and/or falling asleep to, because they require no engagement/attention.

The Smart Playlists will take a show, for example, like "The Office" and create an "on the fly" playlist of any episode that hasn't been played in the last 30 days.  It will then shuffles those episodes.  What we found was happening was that if we played it while going to bed, and the sleep timer on the TV turned the TV in the middle of an episode, that episode not only wouldn't be considered to have been "played" (since it didn't finish), but that tens/hundreds of these "partially played" videos were accumulating.  So what was happening, was Plex would create the "smart playlist" and when it would play an episode, the episodes would be starting in the middle/end/wherever it last played to.  This was obviously very frustrating.

We needed a way to mark any episode of a show/shows that I chose, that when appearing "On Deck" in the "Continue Watching" section of Plex, to be marked as fully watched.  Now when you mark an episode of a TV show as watched, Plex will then add the next episode of that show to the "On Deck" / "Continue Watching" section of Plex.  We would want that episode to be removed from there, as we don't need regularly watched shows to be constantly showing up as "continue watching".  So instead of marking the unwatched "next up" episode as watched (which would result in an endless loop) it simply takes that episode out of the "next up" by removing it.

This way none of the shows in the predefined list would appear in the "continue watching" section, and ALSO completely avoids any "partially watched" episodes from building up, as it marks them as completely watched.

Lastly, we noticed that our library had a ton of "partially played" movies in it, clogging up the "on deck" section.  Reducing the period of time that our "continue watching" area showed items of course reduced the clutter, but we wanted it so that when a movie "fell off" continue watching, it simply got marked as watched (I mean, if we hadn't watched the movie in two weeks, theres no chance we were going to start where we left off, we were going to restart it anyway).







****** SPECIFICALLY, HERE IS WHAT THE SCRIPT DOES *************




IMPORTANT NOTE - this script doesn't interact with the database at all, and makes no calls to modify your files in any way.  The only calls it interacts with, at all, are querying what's "On deck" (in your continue watching) and whether to make something as "watched" and to remove from "on deck".  It can't/doesn't/won't delete/modify files in any way.



In the "user defined variables" section, you're going to define the following:

  - Your server IP address and port number for your plex server - please note that I haven't tested this script to run outside of the LAN the plex server is on, and I also allow insecure connections to my server.  If you plan on having a different use case and experience issues, please let me know.
 
 
 
  - A list of shows that you don't want to appear "On Deck" / in the "Continue Watching" section
  
		List of any shows that will be removed from "On Deck" (otherwise known as "Continue Watching").
		If a show in this list appears "On Deck" because it's started playing but not complete/fully watched it will be marked as completely watched.
		When an episode of a show is marked as watched, Plex's normal behavior is to then add the next episode of that show to your "On Deck", this will remove that also (withoout marking it watched).
		If a show in this list appears "On Deck" because it's the next unwatched episode coming up because you've recently watched the show, it won't mark that episode as watched, but it will remove it from appearing "On Deck"
		IMPORTANT - These are NOT case sensitive, however the title name must match EXACTLY (not counting capitalization), and ANY special characters must be escaped (workin' moms is an example of this).
  
  
  
  - How many days ago a movie must have been last played, but not completed
  
		This is how many days ago ANY MOVIE AT ALL that has been started, but not completed should be marked as fully watched
		So if it has a value of -10, any movie last played (but not completed) greater than 10 days ago, will be marked as fully watched.  This will obviously remove it from being "On Deck" / "Continue Watching"
		THIS IS NOT going by the ORIGINAL time it was first played, but rather the last time it was played but not finished.  So if I started watching "Pulp Fiction" 15 days ago, only got 1/3'rd of the way through it,
		then watched it for another ten minutes 9 days ago (so still "on deck" because it's not completed) it would NOT be removed because the last time it was played was within 10 days.  Tomorrow it would be removed after
		it surpasses that 10 days of not having been played.  So it will be marked as fully watched, thus removing it from being "On Deck"
  
  


The first thing the script does, is query what's "on deck" in your "continue watching" section.  If it finds any episode of a show you've defined that is partially watched, it will mark that episode as fully watched.

Then the script will query your "on deck" one last time, because by marking those partially watched episodes as watched, Plex is going to automatically going to add the "next up episode" to your "continue watching" section.  This script will NOT mark THAT episode as watched, and will simply remove it from your "on deck" / "continue watching" section.

Lastly, the script will then mark any movie that was last played more than the period of time you defined, as fully watched.

This will leave your "continue watching" section only with partially watched episodes, or next up episodes of shows that you didn't define and any partially watched movies that were played within the period of time you defined (so if you last started playing a movie that you didn't finish 7 days ago, and you defined -10 days, it will remain.  On the 11th day it would be marked as watched.